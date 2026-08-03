<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

/**
 * Envío de push de alta prioridad vía FCM HTTP v1, usando la misma cuenta de
 * servicio que GoogleCalendarService pero con el scope de Firebase Messaging.
 * El JSON de credenciales vive en writable/buscapp/, fuera de git (se genera
 * en Firebase Console → Configuración del proyecto → Cuentas de servicio →
 * Generar nueva clave privada).
 */
class BuscappFcmService
{
    private const SCOPE           = 'https://www.googleapis.com/auth/firebase.messaging';
    private const CREDENTIALS_PATH = WRITEPATH . 'buscapp/firebase-service-account.json';

    /**
     * Envía un mensaje de datos (no de "notification") al token indicado;
     * la app receptora construye la notificación localmente para controlar
     * CallStyle/sonido/botones (ver BuscappMessagingService en el proyecto
     * Android). Devuelve false si falla (queda logueado), nunca lanza.
     *
     * @param array<string, string> $datos
     */
    public function enviarDatos(string $fcmToken, array $datos, string $urgencia = 'normal'): bool
    {
        $projectId = env('buscapp.firebaseProjectId');
        if (!$projectId) {
            log_message('error', 'BuscappFcmService: buscapp.firebaseProjectId no configurado.');

            return false;
        }

        try {
            $token = $this->obtenerAccessToken();
        } catch (\Throwable $e) {
            log_message('error', 'BuscappFcmService: fallo al obtener token: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }

        $mensaje = [
            'message' => [
                'token'   => $fcmToken,
                'android' => [
                    'priority' => 'high',
                    'ttl'      => '3600s',
                ],
                'data' => $datos,
            ],
        ];

        try {
            $client = \Config\Services::curlrequest();
            // http_errors=false: por defecto CI4 usa CURLOPT_FAILONERROR, que
            // en un 4xx/5xx descarta el cuerpo de la respuesta (el error que
            // queda logueado es solo "error: 400" de curl, sin explicar el
            // motivo). Con esto se recibe la respuesta completa siempre y se
            // decide aquí, pudiendo loguear el JSON de error real de FCM.
            $response = $client->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'headers'     => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'        => $mensaje,
                    'timeout'     => 10,
                    'http_errors' => false,
                ]
            );

            if ($response->getStatusCode() >= 300) {
                log_message('error', 'BuscappFcmService: FCM respondió {code}: {body}', [
                    'code' => $response->getStatusCode(),
                    'body' => $response->getBody(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'BuscappFcmService: fallo al enviar push: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    private function obtenerAccessToken(): string
    {
        $credenciales = new ServiceAccountCredentials(self::SCOPE, self::CREDENTIALS_PATH);
        $token        = $credenciales->fetchAuthToken();

        return $token['access_token'];
    }
}
