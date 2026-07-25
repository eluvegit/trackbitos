<?php

namespace App\Controllers;

use App\Models\BraintogramMensajeModel;
use App\Models\CompraCompradoModel;
use App\Models\CompraFaltanteModel;
use App\Models\RecordatorioModel;

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
        // limit. Aquí es donde más adelante se llamará a la IA para generar
        // la respuesta real; de momento solo hay una regla fija ("lista de
        // la compra" -> faltantes de Mercadona) y un "Recibido" por defecto,
        // para validar que el ida-y-vuelta con Telegram funciona.
        if ($chatId !== null) {
            $this->enviarMensajeTelegram((int) $chatId, $this->generarRespuesta($parsed['texto']));
        }

        return $this->response->setStatusCode(200)->setBody('OK');
    }

    /**
     * Genera la respuesta a devolver por Telegram según el texto recibido.
     * Orden: primero el atajo fijo "lista de la compra" (no necesita IA);
     * si no, se manda a Claude para ver qué herramienta pide (crear o
     * consultar eventos); si no encaja con ninguna, el "Recibido" por
     * defecto. Añadir una capacidad nueva es: definirla en
     * ClaudeService::herramientas() y añadir su caso aquí.
     */
    private function generarRespuesta(?string $texto): string
    {
        if ($texto === null || trim($texto) === '') {
            return 'Recibido ✅';
        }

        if (preg_match('/lista\s+de\s+la\s+compra/i', $texto) === 1) {
            return $this->textoListaCompra('Mercadona');
        }

        $accion = (new \App\Services\ClaudeService())->interpretar($texto);
        if ($accion === null) {
            return 'Recibido ✅';
        }

        return match ($accion['tool']) {
            'crear_evento'            => $this->crearEventoYResponder($accion['input']),
            'consultar_eventos'       => $this->consultarEventosYResponder($accion['input']),
            'modificar_evento'        => $this->modificarEventoYResponder($accion['input']),
            'borrar_evento'           => $this->borrarEventoYResponder($accion['input']),
            'consultar_recordatorios' => $this->consultarRecordatoriosYResponder($accion['input']),
            default                   => 'Recibido ✅',
        };
    }

    /**
     * Crea el evento en Google Calendar a partir de los datos que extrajo
     * Claude y devuelve el texto de confirmación (o de error) para Telegram.
     * Antes de crear, avisa (sin bloquear) si ya había algo a esa hora.
     *
     * @param array{titulo: string, fecha: string, hora_inicio: string, duracion_minutos: int, descripcion: ?string} $evento
     */
    private function crearEventoYResponder(array $evento): string
    {
        try {
            $inicio = new \DateTimeImmutable(
                $evento['fecha'] . ' ' . $evento['hora_inicio'],
                new \DateTimeZone('Europe/Madrid')
            );
        } catch (\Throwable $e) {
            return '⚠️ No entendí bien la fecha/hora del evento, ¿puedes reformularlo?';
        }

        $duracion = (int) ($evento['duracion_minutos'] ?? 60);
        $fin      = $inicio->modify("+{$duracion} minutes");

        $servicio = new \App\Services\GoogleCalendarService();

        $solapados = $servicio->listarEventos($inicio, $fin);
        $aviso     = '';
        if (!empty($solapados)) {
            $nombres = implode(', ', array_map(static fn (array $e) => $e['titulo'], $solapados));
            $aviso   = "\n⚠️ Ya tenías: {$nombres} a esa hora";
        }

        $resultado = $servicio->crearEvento(
            $evento['titulo'],
            $inicio,
            $fin,
            $evento['descripcion'] ?? null
        );

        if ($resultado === null) {
            return '⚠️ No pude crear el evento en el calendario. Inténtalo de nuevo en un momento.';
        }

        return sprintf(
            "✅ Evento creado: %s\n📅 %s a las %s (%d min)%s",
            $evento['titulo'],
            $inicio->format('d/m/Y'),
            $evento['hora_inicio'],
            $duracion,
            $aviso
        );
    }

    /**
     * Consulta los eventos del rango que extrajo Claude y redacta la
     * respuesta a partir de los datos reales del calendario (no se inventa
     * nada: si Calendar no devuelve eventos, se dice explícitamente).
     *
     * @param array{fecha_desde: string, fecha_hasta: string} $params
     */
    private function consultarEventosYResponder(array $params): string
    {
        try {
            $desde = new \DateTimeImmutable($params['fecha_desde'] . ' 00:00:00', new \DateTimeZone('Europe/Madrid'));
            $hasta = new \DateTimeImmutable($params['fecha_hasta'] . ' 23:59:59', new \DateTimeZone('Europe/Madrid'));
        } catch (\Throwable $e) {
            return '⚠️ No entendí bien el rango de fechas, ¿puedes reformularlo?';
        }

        $eventos = (new \App\Services\GoogleCalendarService())->listarEventos($desde, $hasta);

        if ($eventos === null) {
            return '⚠️ No pude consultar el calendario. Inténtalo de nuevo en un momento.';
        }

        if (empty($eventos)) {
            return '📅 No tienes nada en esas fechas.';
        }

        $lineas = array_map(static function (array $e) {
            $hora = $e['todo_el_dia'] ? 'todo el día' : (new \DateTimeImmutable($e['inicio']))->format('d/m H:i');

            return "- {$e['titulo']} ({$hora})";
        }, $eventos);

        return "📅 Tienes:\n" . implode("\n", $lineas);
    }

    /**
     * Busca el evento existente por texto y le cambia la fecha/hora,
     * conservando la duración original si no se especifica una nueva.
     *
     * @param array{busqueda: string, nueva_fecha: string, nueva_hora_inicio: ?string, nueva_duracion_minutos: ?int} $params
     */
    private function modificarEventoYResponder(array $params): string
    {
        ['evento' => $evento, 'error' => $error] = $this->buscarEventoUnico($params['busqueda']);
        if ($error !== null) {
            return $error;
        }

        try {
            $inicioOriginal = new \DateTimeImmutable($evento['inicio']);
            $finOriginal    = new \DateTimeImmutable($evento['fin']);
        } catch (\Throwable $e) {
            return '⚠️ No pude leer las fechas del evento encontrado.';
        }

        $duracionOriginal = $inicioOriginal->diff($finOriginal);

        try {
            $horaNueva   = $params['nueva_hora_inicio'] ?? $inicioOriginal->format('H:i');
            $nuevoInicio = new \DateTimeImmutable(
                $params['nueva_fecha'] . ' ' . $horaNueva,
                new \DateTimeZone('Europe/Madrid')
            );
        } catch (\Throwable $e) {
            return '⚠️ No entendí bien la nueva fecha/hora, ¿puedes reformularlo?';
        }

        $nuevoFin = isset($params['nueva_duracion_minutos'])
            ? $nuevoInicio->modify('+' . (int) $params['nueva_duracion_minutos'] . ' minutes')
            : $nuevoInicio->add($duracionOriginal);

        $resultado = (new \App\Services\GoogleCalendarService())->actualizarEvento($evento['id'], $nuevoInicio, $nuevoFin);

        if ($resultado === null) {
            return '⚠️ No pude actualizar el evento. Inténtalo de nuevo en un momento.';
        }

        return sprintf(
            "✅ Evento actualizado: %s\n📅 %s a las %s",
            $evento['titulo'],
            $nuevoInicio->format('d/m/Y'),
            $nuevoInicio->format('H:i')
        );
    }

    /**
     * Busca el evento existente por texto y lo borra.
     *
     * @param array{busqueda: string} $params
     */
    private function borrarEventoYResponder(array $params): string
    {
        ['evento' => $evento, 'error' => $error] = $this->buscarEventoUnico($params['busqueda']);
        if ($error !== null) {
            return $error;
        }

        $ok = (new \App\Services\GoogleCalendarService())->borrarEvento($evento['id']);

        if (!$ok) {
            return '⚠️ No pude borrar el evento. Inténtalo de nuevo en un momento.';
        }

        return "🗑️ Evento borrado: {$evento['titulo']}";
    }

    /**
     * Consulta los recordatorios propios de Trackbitos (ITV, DNI, vacunas...)
     * — no tiene nada que ver con Google Calendar. Reutiliza la misma lógica
     * de fecha efectiva/urgencia que ya usa Recordatorios::index() y el cron
     * de resumen diario, para no duplicar el cálculo en un tercer sitio.
     *
     * @param array{filtro: ?string} $params
     */
    private function consultarRecordatoriosYResponder(array $params): string
    {
        helper('recordatorio');

        $recordatorios = (new RecordatorioModel())->findAll();

        $filtro = trim((string) ($params['filtro'] ?? ''));
        if ($filtro !== '') {
            $filtroNormalizado = $this->normalizarTexto($filtro);
            $recordatorios      = array_values(array_filter(
                $recordatorios,
                fn (array $r) => str_contains($this->normalizarTexto($r['titulo']), $filtroNormalizado)
                    || str_contains($this->normalizarTexto(Recordatorios::CATEGORIAS[$r['categoria']][0] ?? ''), $filtroNormalizado)
            ));
        }

        if (empty($recordatorios)) {
            return $filtro !== ''
                ? sprintf('🔍 No tienes recordatorios que coincidan con "%s".', $filtro)
                : '📋 No tienes recordatorios guardados.';
        }

        foreach ($recordatorios as &$r) {
            $periodo            = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
            $fechaEfectiva       = recordatorio_fecha_efectiva($r['fecha_evento'], $periodo);
            $estado              = recordatorio_estado($fechaEfectiva);
            $r['dias']           = $estado['dias'];
            $r['texto']          = $estado['texto'];
            $r['categoria_label'] = Recordatorios::CATEGORIAS[$r['categoria']][0] ?? 'Otro';
        }
        unset($r);

        usort($recordatorios, static fn (array $a, array $b) => $a['dias'] <=> $b['dias']);

        $lineas = array_map(
            static fn (array $r) => "- {$r['titulo']} ({$r['categoria_label']}) — {$r['texto']}",
            $recordatorios
        );

        return "📋 Recordatorios:\n" . implode("\n", $lineas);
    }

    /**
     * Minúsculas y sin tildes, para comparar texto libre sin que "vehiculo"
     * (sin tilde, como suele escribir Claude o el usuario) falle contra
     * "Vehículo" en base de datos.
     */
    private function normalizarTexto(string $texto): string
    {
        $normalizado = \Normalizer::normalize($texto, \Normalizer::FORM_D) ?: $texto;

        return mb_strtolower(preg_replace('/\p{Mn}/u', '', $normalizado));
    }

    /**
     * Busca un evento por texto libre en una ventana razonable (-1 día a
     * +180 días) y exige exactamente una coincidencia: si hay 0 o varias, no
     * adivina — devuelve un mensaje de error/aclaración listo para Telegram
     * en vez de un evento, para no arriesgarse a tocar el equivocado.
     *
     * @return array{evento: ?array, error: ?string}
     */
    private function buscarEventoUnico(string $busqueda): array
    {
        $ventanaDesde = new \DateTimeImmutable('-1 day', new \DateTimeZone('Europe/Madrid'));
        $ventanaHasta = new \DateTimeImmutable('+180 days', new \DateTimeZone('Europe/Madrid'));

        $coincidencias = (new \App\Services\GoogleCalendarService())->buscarEvento($busqueda, $ventanaDesde, $ventanaHasta);

        if ($coincidencias === null) {
            return ['evento' => null, 'error' => '⚠️ No pude consultar el calendario. Inténtalo de nuevo en un momento.'];
        }

        if (empty($coincidencias)) {
            return ['evento' => null, 'error' => sprintf('🔍 No encontré ningún evento que coincida con "%s".', $busqueda)];
        }

        if (count($coincidencias) > 1) {
            $lineas = array_map(static function (array $e) {
                $hora = $e['todo_el_dia'] ? 'todo el día' : (new \DateTimeImmutable($e['inicio']))->format('d/m H:i');

                return "- {$e['titulo']} ({$hora})";
            }, $coincidencias);

            return ['evento' => null, 'error' => "🔍 Encontré varios que coinciden, sé más concreto:\n" . implode("\n", $lineas)];
        }

        return ['evento' => $coincidencias[0], 'error' => null];
    }

    /**
     * Productos marcados como faltantes de un supermercado (por nombre) que
     * todavía no están marcados como comprados, formateados como texto listo
     * para enviar por Telegram.
     */
    private function textoListaCompra(string $nombreSupermercado): string
    {
        $productos = $this->faltantesPorSupermercado($nombreSupermercado);

        if (empty($productos)) {
            return "No falta nada de {$nombreSupermercado} 🛒";
        }

        $lineas = array_map(static fn (array $p) => '- ' . $p['nombre'], $productos);

        return "Lista de la compra ({$nombreSupermercado}):\n" . implode("\n", $lineas);
    }

    /**
     * @return array<int, array{producto_id: int, nombre: string}>
     */
    private function faltantesPorSupermercado(string $nombreSupermercado): array
    {
        $faltanteModel = new CompraFaltanteModel();

        $faltantes = $faltanteModel
            ->select('compra_faltantes.producto_id, compra_productos.nombre')
            ->join('compra_productos', 'compra_productos.id = compra_faltantes.producto_id')
            ->join('compra_supermercados', 'compra_supermercados.id = compra_productos.supermercado_id')
            ->where('compra_supermercados.nombre', $nombreSupermercado)
            ->orderBy('compra_productos.nombre', 'ASC')
            ->findAll();

        if (empty($faltantes)) {
            return [];
        }

        $ids = array_column($faltantes, 'producto_id');

        $compradoModel = new CompraCompradoModel();
        $idsComprados  = array_column(
            $compradoModel->select('producto_id')->whereIn('producto_id', $ids)->findAll(),
            'producto_id'
        );

        return array_values(array_filter(
            $faltantes,
            static fn (array $p) => !in_array($p['producto_id'], $idsComprados, true)
        ));
    }

    /**
     * Envía la respuesta por Telegram. Delega en TelegramService (compartido
     * con el comando spark de recordatorios) para no duplicar la llamada a
     * sendMessage; cualquier fallo queda solo logueado ahí, nunca debe romper
     * la respuesta 200 que se le da a Telegram.
     */
    private function enviarMensajeTelegram(int $chatId, string $texto): void
    {
        (new \App\Services\TelegramService())->enviarMensaje($chatId, $texto);
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
