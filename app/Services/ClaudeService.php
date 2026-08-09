<?php

namespace App\Services;

/**
 * Interpretación de mensajes de texto con Claude. Usa "tool use" (function
 * calling) en vez de pedir JSON en texto libre: Claude solo devuelve datos
 * estructurados cuando decide invocar alguna herramienta, lo que evita tener
 * que parsear/validar un formato de texto propio. Para añadir una capacidad
 * nueva (consultar recordatorios, tareas de Hogar...) solo hay que añadir su
 * definición a self::herramientas() — el resto (llamada a la API, extracción
 * del tool_use) es genérico.
 */
class ClaudeService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-haiku-4-5-20251001';

    /**
     * Interpreta el mensaje contra todas las herramientas disponibles. Si
     * Claude decide usar una, devuelve su nombre y los datos ya
     * estructurados; si el mensaje no encaja con ninguna, devuelve null.
     *
     * @return array{tool: string, input: array}|null
     */
    public function interpretar(string $texto): ?array
    {
        $apiKey = env('braintogram.anthropicApiKey');
        if (!$apiKey) {
            log_message('error', 'ClaudeService: braintogram.anthropicApiKey no configurado.');

            return null;
        }

        $hoy = date('Y-m-d (l)');

        $payload = [
            'model'      => self::MODEL,
            'max_tokens' => 1024,
            'system'     => "Eres un asistente que interpreta mensajes en español enviados por Telegram para "
                . 'la app personal Trackbitos. Hoy es ' . $hoy . ", zona horaria Europe/Madrid. Distingue bien dos "
                . "cosas que NO son lo mismo: los 'eventos' viven en Google Calendar (citas, reuniones, tareas con "
                . "hora); los 'recordatorios' son avisos propios de Trackbitos sobre trámites/revisiones "
                . '(ITV, DNI, vacunas, revisiones médicas...) sin relación con el calendario — si preguntan por '
                . "'recordatorios' o cosas de ese estilo, usa consultar_recordatorios, NUNCA consultar_eventos. "
                . "Resuelve fechas relativas ('mañana', 'el viernes', 'esta semana') a partir de la fecha de hoy. "
                . 'Si piden cambiar/mover/aplazar un evento existente, usa modificar_evento con una palabra clave '
                . "de su título en 'busqueda' (no hace falta la fecha antigua, solo la nueva). Si piden borrar/"
                . 'cancelar un evento, usa borrar_evento igual con una palabra clave. Usa la herramienta que '
                . 'corresponda a la petición; si el mensaje no tiene relación con ninguna de estas cosas, no uses '
                . 'ninguna herramienta.',
            'tools'    => $this->herramientas(),
            'messages' => [
                ['role' => 'user', 'content' => $texto],
            ],
        ];

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->post(self::API_URL, [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json'    => $payload,
                'timeout' => 15,
            ]);

            $data = json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            log_message('error', 'ClaudeService: fallo al llamar a la API: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }

        foreach ($data['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? null) === 'tool_use') {
                return ['tool' => $bloque['name'], 'input' => $bloque['input']];
            }
        }

        return null;
    }

    /**
     * Desglosa una tarea en subtareas pequeñas y accionables (pensado para
     * ayudar a arrancar/progresar cuando la tarea se percibe como demasiado
     * grande o difusa). Fuerza el uso de la herramienta con tool_choice para
     * no tener que parsear texto libre.
     *
     * @param list<string> $existentes Títulos de subtareas que ya existen, para no repetirlas
     * @return list<string>|null
     */
    public function sugerirSubtareas(string $titulo, ?string $categoria = null, ?string $notas = null, array $existentes = []): ?array
    {
        $apiKey = env('braintogram.anthropicApiKey');
        if (!$apiKey) {
            log_message('error', 'ClaudeService: braintogram.anthropicApiKey no configurado.');

            return null;
        }

        $contexto = 'Tarea: ' . $titulo;
        if ($categoria) {
            $contexto .= "\nCategoría: " . $categoria;
        }
        if ($notas) {
            $contexto .= "\nNotas: " . $notas;
        }
        if (!empty($existentes)) {
            $contexto .= "\nSubtareas que ya existen (no las repitas): " . implode('; ', $existentes);
        }

        $payload = [
            'model'      => self::MODEL,
            'max_tokens' => 512,
            'system'     => 'Ayudas a una persona con TDAH a desglosar una tarea en subtareas pequeñas y '
                . 'concretas para que le resulte más fácil arrancar y progresar. Da entre 3 y 7 subtareas, '
                . 'cada una una acción de un solo paso, breve, en español, sin numerarlas ni añadir '
                . 'explicaciones. Responde siempre usando la herramienta listar_subtareas.',
            'tools'       => [[
                'name'        => 'listar_subtareas',
                'description' => 'Devuelve la lista de subtareas sugeridas para la tarea.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'subtareas' => [
                            'type'        => 'array',
                            'items'       => ['type' => 'string'],
                            'description' => 'Lista de subtareas concretas y accionables',
                        ],
                    ],
                    'required' => ['subtareas'],
                ],
            ]],
            'tool_choice' => ['type' => 'tool', 'name' => 'listar_subtareas'],
            'messages'    => [
                ['role' => 'user', 'content' => $contexto],
            ],
        ];

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->post(self::API_URL, [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json'    => $payload,
                'timeout' => 20,
            ]);

            $data = json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            log_message('error', 'ClaudeService: fallo al llamar a la API: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }

        foreach ($data['content'] ?? [] as $bloque) {
            if (($bloque['type'] ?? null) === 'tool_use' && $bloque['name'] === 'listar_subtareas') {
                $subtareas = $bloque['input']['subtareas'] ?? [];

                return array_values(array_filter(array_map('trim', $subtareas), fn($s) => $s !== ''));
            }
        }

        return null;
    }

    /**
     * @return list<array{name: string, description: string, input_schema: array}>
     */
    private function herramientas(): array
    {
        return [
            [
                'name'        => 'crear_evento',
                'description' => 'Crea un evento en Google Calendar a partir de la petición del usuario.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'titulo'           => ['type' => 'string', 'description' => 'Título breve del evento'],
                        'fecha'            => ['type' => 'string', 'description' => 'Fecha en formato YYYY-MM-DD'],
                        'hora_inicio'      => ['type' => 'string', 'description' => 'Hora de inicio en formato HH:MM (24h). Si no se especifica, usa 09:00'],
                        'duracion_minutos' => ['type' => 'integer', 'description' => 'Duración estimada en minutos. Si no se especifica, usa 60'],
                        'descripcion'      => ['type' => 'string', 'description' => 'Notas adicionales, opcional'],
                    ],
                    'required' => ['titulo', 'fecha', 'hora_inicio', 'duracion_minutos'],
                ],
            ],
            [
                'name'        => 'consultar_eventos',
                'description' => 'Consulta qué eventos hay en el calendario dentro de un rango de fechas '
                    . "(ej. 'qué tengo mañana', 'qué tengo esta semana').",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'fecha_desde' => ['type' => 'string', 'description' => 'Primer día del rango (inclusive), formato YYYY-MM-DD'],
                        'fecha_hasta' => ['type' => 'string', 'description' => 'Último día del rango (inclusive), formato YYYY-MM-DD. Si solo preguntan por un día, igual a fecha_desde'],
                    ],
                    'required' => ['fecha_desde', 'fecha_hasta'],
                ],
            ],
            [
                'name'        => 'modificar_evento',
                'description' => "Cambia la fecha y/u hora de un evento ya existente (ej. 'cambia la cita del "
                    . "dentista al jueves', 'mueve la reunión de mañana a las 6').",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda'               => ['type' => 'string', 'description' => 'Palabra o frase clave para encontrar el evento (parte de su título)'],
                        'nueva_fecha'            => ['type' => 'string', 'description' => 'Nueva fecha en formato YYYY-MM-DD'],
                        'nueva_hora_inicio'      => ['type' => 'string', 'description' => 'Nueva hora de inicio en formato HH:MM. Si no se especifica, se mantiene la hora original del evento'],
                        'nueva_duracion_minutos' => ['type' => 'integer', 'description' => 'Nueva duración en minutos. Si no se especifica, se mantiene la duración original del evento'],
                    ],
                    'required' => ['busqueda', 'nueva_fecha'],
                ],
            ],
            [
                'name'        => 'borrar_evento',
                'description' => "Borra/cancela un evento existente (ej. 'borra la cita del dentista', "
                    . "'cancela la reunión de mañana').",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => ['type' => 'string', 'description' => 'Palabra o frase clave para encontrar el evento (parte de su título)'],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'        => 'consultar_recordatorios',
                'description' => "Consulta los recordatorios propios de Trackbitos (ITV, DNI, vacunas, revisiones "
                    . "médicas...), NO eventos de calendario. Se usa con preguntas como 'qué recordatorios "
                    . "tengo', 'cuándo caduca el DNI', 'qué tengo pendiente de revisar'.",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'filtro' => ['type' => 'string', 'description' => 'Palabra clave opcional para filtrar (título o categoría, ej. "vehículo", "DNI"). Vacío para ver todos'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }
}
