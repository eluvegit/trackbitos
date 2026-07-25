<?php

namespace App\Services;

/**
 * Envío saliente a la API de Telegram (sendMessage). Separado de Braintogram
 * (que solo atiende el webhook entrante) para poder reutilizarlo también
 * desde comandos spark que no tienen contexto de Controller/Request.
 */
class TelegramService
{
    /**
     * Envía un mensaje de texto al chat indicado. Sin braintogram.botToken
     * configurado no hace nada (permite seguir probando sin bot real).
     * Cualquier fallo de red/API queda solo logueado, nunca lanza excepción.
     */
    public function enviarMensaje(int $chatId, string $texto): bool
    {
        $token = env('braintogram.botToken');
        if (!$token) {
            return false;
        }

        try {
            $client = \Config\Services::curlrequest();
            $client->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'json' => [
                    'chat_id'    => $chatId,
                    'text'       => $texto,
                    'parse_mode' => 'Markdown',
                ],
                'timeout' => 5,
            ]);

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'TelegramService: fallo al enviar mensaje: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * chat_id de la whitelist braintogram.chatIdsAutorizados (lista separada
     * por comas), como destinatarios por defecto de avisos automáticos como
     * el resumen diario de recordatorios.
     *
     * @return list<int>
     */
    public function chatIdsAutorizados(): array
    {
        $lista = env('braintogram.chatIdsAutorizados');
        if ($lista === null || trim((string) $lista) === '') {
            return [];
        }

        return array_values(array_map(
            'intval',
            array_filter(array_map('trim', explode(',', (string) $lista)))
        ));
    }
}
