<?php

namespace App\Controllers\Buscapp;

use App\Controllers\BaseController;
use App\Filters\BuscappApiAuth;
use App\Models\BuscappTelegramaDestinoModel;
use App\Models\BuscappTelegramaModel;
use App\Models\BuscappUsuarioModel;
use App\Services\BuscappFcmService;

/**
 * Endpoints que consume la app Android (y en el futuro el bot de Telegram
 * como canal alternativo, ver §5.7 del informe). No hay vistas aquí: todo
 * responde JSON. La autenticación es un Bearer token propio (filtro
 * 'buscappApi'), no Myth\Auth — ver app/Filters/BuscappApiAuth.php.
 */
class Api extends BaseController
{
    private BuscappUsuarioModel $usuarios;
    private BuscappTelegramaModel $telegramas;
    private BuscappTelegramaDestinoModel $destinos;

    public function __construct()
    {
        $this->usuarios   = new BuscappUsuarioModel();
        $this->telegramas = new BuscappTelegramaModel();
        $this->destinos   = new BuscappTelegramaDestinoModel();
    }

    /**
     * Alta de usuario sin verificación de teléfono (MVP de círculo cerrado,
     * ver §5.5.3 del informe): cualquiera con la app puede registrarse con
     * un nombre. Devuelve el api_token que la app debe guardar y mandar como
     * "Authorization: Bearer <token>" en el resto de peticiones.
     */
    public function registro()
    {
        $nombre = trim((string) $this->request->getJsonVar('nombre'));
        if ($nombre === '') {
            return $this->response->setJSON(['error' => 'nombre es obligatorio'])->setStatusCode(422);
        }

        $telefono = $this->request->getJsonVar('telefono_e164');
        $token    = bin2hex(random_bytes(32));

        $id = $this->usuarios->insert([
            'nombre'        => $nombre,
            'telefono_e164' => $telefono !== null && trim((string) $telefono) !== '' ? trim((string) $telefono) : null,
            'api_token'     => $token,
            'creado_en'     => date('Y-m-d H:i:s'),
            'ultimo_acceso' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'id'        => $id,
            'nombre'    => $nombre,
            'api_token' => $token,
        ])->setStatusCode(201);
    }

    /**
     * Actualiza el fcm_token del dispositivo del usuario autenticado (se
     * llama cada vez que Firebase entrega uno nuevo, ver onNewToken en la
     * app Android).
     */
    public function token()
    {
        $usuario = $this->usuarioActual();

        $fcmToken = trim((string) $this->request->getJsonVar('fcm_token'));
        if ($fcmToken === '') {
            return $this->response->setJSON(['error' => 'fcm_token es obligatorio'])->setStatusCode(422);
        }

        $this->usuarios->update($usuario['id'], ['fcm_token' => $fcmToken]);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Crea un telegrama y dispara el push. Aplica la regla de escasez §3.1
     * bis: no se puede crear uno nuevo si ya hay uno pendiente del mismo
     * emisor hacia el mismo receptor.
     */
    public function crear()
    {
        $emisor = $this->usuarioActual();

        $receptorId = (int) $this->request->getJsonVar('receptor_id');
        $tipo       = (string) $this->request->getJsonVar('tipo');
        $mensaje    = $this->request->getJsonVar('mensaje');
        $urgencia   = (string) ($this->request->getJsonVar('urgencia') ?? 'normal');
        $caducaEn   = $this->request->getJsonVar('caduca_en');
        $uuid       = (string) $this->request->getJsonVar('uuid_cliente');

        if ($receptorId <= 0 || $uuid === '' || !in_array($tipo, ['LLAMAME', 'CONFIRMA', 'INFO'], true)) {
            return $this->response->setJSON([
                'error' => 'receptor_id, uuid_cliente y tipo (LLAMAME|CONFIRMA|INFO) son obligatorios',
            ])->setStatusCode(422);
        }

        $receptor = $this->usuarios->find($receptorId);
        if ($receptor === null) {
            return $this->response->setJSON(['error' => 'receptor no encontrado'])->setStatusCode(404);
        }

        // Sin canal alternativo (no hay bot de Telegram): sin fcm_token no hay
        // forma de entregar el aviso, así que se corta aquí en vez de crear un
        // telegrama "enviado" que en realidad nunca llegaría a nadie.
        if (!$receptor['fcm_token']) {
            return $this->response->setJSON([
                'error' => 'El receptor todavía no tiene token FCM (no ha abierto la app o no tiene notificaciones activas).',
            ])->setStatusCode(422);
        }

        if ($this->destinos->tienePendiente($emisor['id'], $receptorId)) {
            return $this->response->setJSON([
                'error' => 'Ya hay una solicitud pendiente para este contacto. Cancélala antes de enviar otra.',
            ])->setStatusCode(409);
        }

        // Idempotencia: un reintento de red con el mismo uuid_cliente no crea un segundo telegrama.
        $existente = $this->telegramas->where('uuid_cliente', $uuid)->first();
        $telegramaId = $existente['id'] ?? $this->telegramas->insert([
            'uuid_cliente' => $uuid,
            'emisor_id'    => $emisor['id'],
            'modo'         => 'individual',
            'tipo'         => $tipo,
            'mensaje'      => $mensaje !== null ? mb_substr((string) $mensaje, 0, 200) : null,
            'urgencia'     => in_array($urgencia, ['normal', 'urgente'], true) ? $urgencia : 'normal',
            'caduca_en'    => $caducaEn ?: null,
            'enviado_en'   => date('Y-m-d H:i:s'),
        ]);

        if ($existente === null) {
            $this->destinos->insert([
                'telegrama_id' => $telegramaId,
                'receptor_id'  => $receptorId,
                'canal'        => 'fcm',
                'estado'       => 'enviado',
            ]);

            (new BuscappFcmService())->enviarDatos($receptor['fcm_token'], [
                'telegrama_id'    => (string) $telegramaId,
                'tipo'            => $tipo,
                'emisor_nombre'   => $emisor['nombre'],
                'emisor_telefono' => (string) ($emisor['telefono_e164'] ?? ''),
                'mensaje'         => (string) ($mensaje ?? ''),
                'urgencia'        => $urgencia,
                'caduca_en'       => (string) ($caducaEn ?? ''),
            ], $urgencia);
        }

        return $this->response->setJSON(['id' => $telegramaId])->setStatusCode(201);
    }

    /**
     * Respuesta de un toque desde la notificación (§3.1: botones-estado, sin
     * texto libre en el MVP).
     */
    public function responder(int $telegramaId)
    {
        $usuario = $this->usuarioActual();

        $respuesta = trim((string) $this->request->getJsonVar('respuesta'));
        if ($respuesta === '') {
            return $this->response->setJSON(['error' => 'respuesta es obligatoria'])->setStatusCode(422);
        }

        $destino = $this->destinos
            ->where('telegrama_id', $telegramaId)
            ->where('receptor_id', $usuario['id'])
            ->first();

        if ($destino === null) {
            return $this->response->setJSON(['error' => 'telegrama no encontrado para este usuario'])->setStatusCode(404);
        }

        $this->destinos->update($destino['id'], [
            'estado'        => 'respondido',
            'respuesta'     => mb_substr($respuesta, 0, 50),
            'respondido_en' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Historial breve (enviados + recibidos) de los últimos 30 días.
     */
    public function historial()
    {
        $usuario = $this->usuarioActual();
        $desde   = date('Y-m-d H:i:s', strtotime('-30 days'));

        $enviados = $this->telegramas
            ->where('emisor_id', $usuario['id'])
            ->where('enviado_en >=', $desde)
            ->orderBy('enviado_en', 'DESC')
            ->findAll();

        $recibidos = $this->destinos
            ->select('buscapp_telegrama_destinos.*, buscapp_telegramas.tipo, buscapp_telegramas.mensaje, buscapp_telegramas.urgencia, buscapp_telegramas.enviado_en, buscapp_telegramas.emisor_id')
            ->join('buscapp_telegramas', 'buscapp_telegramas.id = buscapp_telegrama_destinos.telegrama_id')
            ->where('buscapp_telegrama_destinos.receptor_id', $usuario['id'])
            ->where('buscapp_telegramas.enviado_en >=', $desde)
            ->orderBy('buscapp_telegramas.enviado_en', 'DESC')
            ->findAll();

        return $this->response->setJSON(['enviados' => $enviados, 'recibidos' => $recibidos]);
    }

    /**
     * El filtro 'buscappApi' ya garantiza que el token es válido antes de
     * llegar aquí; esto solo lo vuelve a resolver para tener los datos del
     * usuario en el controlador (ver nota en BuscappApiAuth).
     */
    private function usuarioActual(): array
    {
        return BuscappApiAuth::usuarioDesde($this->request);
    }
}
