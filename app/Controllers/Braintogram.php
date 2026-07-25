<?php

namespace App\Controllers;

use App\Models\BraintogramMensajeModel;

class Braintogram extends BaseController
{
    protected BraintogramMensajeModel $model;

    /** Rate limit simple por chat_id: máximo de mensajes... */
    private const RATE_LIMIT_MAX = 20;
    /** ...dentro de esta ventana en segundos. */
    private const RATE_LIMIT_WINDOW = 60;

    public function __construct()
    {
        $this->model = new BraintogramMensajeModel();
    }

    /**
     * Endpoint público que Telegram llama por POST con cada update (setWebhook).
     * No lleva filtro 'auth' -> Telegram no puede loguearse. En su lugar hay tres
     * verificaciones en cadena, en este orden, antes de que pueda llegar a
     * dispararse cualquier llamada a la IA:
     *   1) secret_token del webhook (cabecera X-Telegram-Bot-Api-Secret-Token)
     *   2) chat_id autorizado (whitelist en braintogram.chatIdsAutorizados)
     *   3) rate limit simple por chat_id
     * Todo intento se guarda igual, pase o no las verificaciones, para poder
     * ver en el log quién está llamando a la URL y por qué se ha cortado.
     */
    public function webhook()
    {
        $raw  = $this->request->getBody() ?: '';
        $data = json_decode($raw, true);

        $secretEsperado = env('braintogram.webhookSecret');
        $secretRecibido = $this->request->getHeaderLine('X-Telegram-Bot-Api-Secret-Token');
        $secretValido   = $secretEsperado ? hash_equals((string) $secretEsperado, (string) $secretRecibido) : null;

        $parsed = $this->parseUpdate(is_array($data) ? $data : null);
        $chatId = $parsed['chat_id'];

        // 2) chat_id autorizado: se calcula siempre (es barato) aunque el
        // secret ya haya fallado, para poder ver en el log si habría pasado.
        $chatAutorizado = $this->chatAutorizado($chatId);

        // 1) secret_token: si está configurado y no coincide, se corta aquí.
        $bloqueadoPorSecret = $secretEsperado && $secretValido === false;

        // 3) rate limit: solo se evalúa (y solo consume cupo) si ya se superaron
        // las dos verificaciones anteriores, respetando el orden pedido.
        $rateLimited = null;
        if (!$bloqueadoPorSecret && $chatAutorizado !== false && $chatId !== null) {
            $rateLimited = $this->dentroDeLimite((int) $chatId) ? 0 : 1;
        }

        $this->model->insert([
            'update_id'       => $parsed['update_id'],
            'tipo'            => $parsed['tipo'],
            'chat_id'         => $chatId,
            'chat_type'       => $parsed['chat_type'],
            'from_id'         => $parsed['from_id'],
            'from_username'   => $parsed['from_username'],
            'from_nombre'     => $parsed['from_nombre'],
            'texto'           => $parsed['texto'],
            'fecha_telegram'  => $parsed['fecha_telegram'],
            'ip_origen'       => $this->request->getIPAddress(),
            'secret_valido'   => $secretValido,
            'chat_autorizado' => $chatAutorizado,
            'rate_limited'    => $rateLimited,
            'raw_json'        => $raw,
        ]);

        if ($bloqueadoPorSecret) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        if ($chatAutorizado === false) {
            // Chat no autorizado: se responde 200 para que Telegram no reintente,
            // pero no se sigue procesando (no hay llamada a IA para este chat).
            return $this->response->setStatusCode(200)->setBody('OK');
        }

        if ($rateLimited === 1) {
            // Mismo criterio: 200 para cortar reintentos, sin seguir procesando.
            return $this->response->setStatusCode(200)->setBody('OK');
        }

        // A partir de aquí: secret válido + chat autorizado + dentro del rate
        // limit. Es el punto donde iría la llamada a la IA (todavía no existe).

        return $this->response->setStatusCode(200)->setBody('OK');
    }

    /**
     * Whitelist de chat_id permitidos, en braintogram.chatIdsAutorizados
     * (lista separada por comas). Sin configurar -> no se filtra nada (null,
     * igual que secret_valido cuando no hay secret) porque el bot todavía no
     * existe y conviene poder probar sin haber decidido aún el chat_id real.
     */
    private function chatAutorizado(?int $chatId): ?bool
    {
        $lista = env('braintogram.chatIdsAutorizados');
        if ($lista === null || trim((string) $lista) === '') {
            return null;
        }

        if ($chatId === null) {
            return false;
        }

        $autorizados = array_map('trim', explode(',', (string) $lista));
        return in_array((string) $chatId, $autorizados, true);
    }

    /**
     * Rate limit simple por chat_id vía cache de archivos: contador con TTL
     * de RATE_LIMIT_WINDOW segundos, se corta al superar RATE_LIMIT_MAX.
     */
    private function dentroDeLimite(int $chatId): bool
    {
        $cache = \Config\Services::cache();
        $key   = 'braintogram_rate_' . $chatId;

        $actual = (int) ($cache->get($key) ?? 0);
        $cache->save($key, $actual + 1, self::RATE_LIMIT_WINDOW);

        return $actual < self::RATE_LIMIT_MAX;
    }

    /**
     * Extrae los campos relevantes de un update de Telegram, sea cual sea su
     * tipo. Devuelve todo a null si el JSON no es válido o no reconocemos la
     * forma del update, pero el raw_json siempre queda guardado.
     */
    private function parseUpdate(?array $data): array
    {
        $vacio = [
            'update_id'      => null,
            'tipo'           => 'invalido',
            'chat_id'        => null,
            'chat_type'      => null,
            'from_id'        => null,
            'from_username'  => null,
            'from_nombre'    => null,
            'texto'          => null,
            'fecha_telegram' => null,
        ];

        if ($data === null) {
            return $vacio;
        }

        $vacio['update_id'] = isset($data['update_id']) ? (int) $data['update_id'] : null;

        $mensaje = null;
        $tipo    = 'otro';
        foreach (['message', 'edited_message', 'channel_post', 'edited_channel_post'] as $clave) {
            if (isset($data[$clave]) && is_array($data[$clave])) {
                $mensaje = $data[$clave];
                $tipo    = $clave;
                break;
            }
        }

        if ($mensaje !== null) {
            $from = $mensaje['from'] ?? [];
            $chat = $mensaje['chat'] ?? [];

            $vacio['tipo']           = $tipo;
            $vacio['chat_id']        = isset($chat['id']) ? (int) $chat['id'] : null;
            $vacio['chat_type']      = $chat['type'] ?? null;
            $vacio['from_id']        = isset($from['id']) ? (int) $from['id'] : null;
            $vacio['from_username']  = $from['username'] ?? null;
            $vacio['from_nombre']    = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: null;
            $vacio['texto']          = $mensaje['text'] ?? $mensaje['caption'] ?? null;
            $vacio['fecha_telegram'] = isset($mensaje['date']) ? date('Y-m-d H:i:s', (int) $mensaje['date']) : null;

            return $vacio;
        }

        if (isset($data['callback_query']) && is_array($data['callback_query'])) {
            $cq   = $data['callback_query'];
            $from = $cq['from'] ?? [];
            $chat = $cq['message']['chat'] ?? [];

            $vacio['tipo']           = 'callback_query';
            $vacio['chat_id']        = isset($chat['id']) ? (int) $chat['id'] : null;
            $vacio['chat_type']      = $chat['type'] ?? null;
            $vacio['from_id']        = isset($from['id']) ? (int) $from['id'] : null;
            $vacio['from_username']  = $from['username'] ?? null;
            $vacio['from_nombre']    = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: null;
            $vacio['texto']          = $cq['data'] ?? null;
            $vacio['fecha_telegram'] = isset($cq['message']['date']) ? date('Y-m-d H:i:s', (int) $cq['message']['date']) : null;

            return $vacio;
        }

        $vacio['tipo'] = 'otro';
        return $vacio;
    }

    /**
     * Log de ingesta: pantalla protegida (auth) para verificar visualmente
     * que los mensajes llegan y con qué datos exactos.
     */
    public function index()
    {
        $mensajes = $this->model->orderBy('id', 'DESC')->paginate(30);

        return view('braintogram/index', [
            'mensajes'    => $mensajes,
            'pager'       => $this->model->pager,
            'webhookUrl'  => site_url('braintogram/webhook'),
        ]);
    }

    public function ver(int $id)
    {
        $mensaje = $this->model->find($id);
        if (!$mensaje) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Mensaje no encontrado');
        }

        return view('braintogram/ver', ['mensaje' => $mensaje]);
    }
}
