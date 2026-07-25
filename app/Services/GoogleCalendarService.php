<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

/**
 * Creación de eventos en Google Calendar vía cuenta de servicio (el JSON de
 * credenciales vive en writable/braintogram/, fuera del document root y de
 * git). El calendario en cuestión debe estar compartido con el client_email
 * de esa cuenta de servicio, con permiso de "Hacer cambios en los eventos".
 */
class GoogleCalendarService
{
    private const SCOPE = 'https://www.googleapis.com/auth/calendar.events';
    private const CREDENTIALS_PATH = WRITEPATH . 'braintogram/google-calendar-credentials.json';

    /**
     * @return array{id: ?string, link: ?string}|null null si falla (queda logueado)
     */
    public function crearEvento(
        string $titulo,
        \DateTimeImmutable $inicio,
        \DateTimeImmutable $fin,
        ?string $descripcion = null
    ): ?array {
        $calendarId = env('braintogram.googleCalendarId');
        if (!$calendarId) {
            log_message('error', 'GoogleCalendarService: braintogram.googleCalendarId no configurado.');

            return null;
        }

        try {
            $token = $this->obtenerAccessToken();
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al obtener token: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }

        $body = [
            'summary' => $titulo,
            'start'   => ['dateTime' => $inicio->format(DATE_RFC3339), 'timeZone' => 'Europe/Madrid'],
            'end'     => ['dateTime' => $fin->format(DATE_RFC3339), 'timeZone' => 'Europe/Madrid'],
        ];
        if ($descripcion !== null && $descripcion !== '') {
            $body['description'] = $descripcion;
        }

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->post(
                'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json'    => $body,
                    'timeout' => 10,
                ]
            );

            $data = json_decode($response->getBody(), true);

            return [
                'id'   => $data['id'] ?? null,
                'link' => $data['htmlLink'] ?? null,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al crear evento: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Eventos del calendario dentro de [$desde, $hasta].
     *
     * @return list<array{id: string, titulo: string, inicio: string, fin: string, todo_el_dia: bool}>|null null si falla (queda logueado)
     */
    public function listarEventos(\DateTimeImmutable $desde, \DateTimeImmutable $hasta): ?array
    {
        return $this->buscarEventosEnRango($desde, $hasta, null);
    }

    /**
     * Eventos que coinciden con un texto libre (búsqueda de Google sobre
     * título/descripción/ubicación) dentro de una ventana de fechas.
     *
     * @return list<array{id: string, titulo: string, inicio: string, fin: string, todo_el_dia: bool}>|null null si falla (queda logueado)
     */
    public function buscarEvento(string $texto, \DateTimeImmutable $desde, \DateTimeImmutable $hasta): ?array
    {
        return $this->buscarEventosEnRango($desde, $hasta, $texto);
    }

    /**
     * Actualiza la fecha/hora de un evento existente por su id.
     *
     * @return array{id: ?string, link: ?string}|null null si falla (queda logueado)
     */
    public function actualizarEvento(string $eventId, \DateTimeImmutable $inicio, \DateTimeImmutable $fin): ?array
    {
        $calendarId = env('braintogram.googleCalendarId');
        if (!$calendarId) {
            log_message('error', 'GoogleCalendarService: braintogram.googleCalendarId no configurado.');

            return null;
        }

        try {
            $token = $this->obtenerAccessToken();
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al obtener token: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->patch(
                'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId)
                    . '/events/' . rawurlencode($eventId),
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'start' => ['dateTime' => $inicio->format(DATE_RFC3339), 'timeZone' => 'Europe/Madrid'],
                        'end'   => ['dateTime' => $fin->format(DATE_RFC3339), 'timeZone' => 'Europe/Madrid'],
                    ],
                    'timeout' => 10,
                ]
            );

            $data = json_decode($response->getBody(), true);

            return [
                'id'   => $data['id'] ?? null,
                'link' => $data['htmlLink'] ?? null,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al actualizar evento: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Borra un evento por su id. Devuelve false tanto si falla la llamada
     * como si Google responde que el evento no existe (queda logueado).
     */
    public function borrarEvento(string $eventId): bool
    {
        $calendarId = env('braintogram.googleCalendarId');
        if (!$calendarId) {
            log_message('error', 'GoogleCalendarService: braintogram.googleCalendarId no configurado.');

            return false;
        }

        try {
            $token = $this->obtenerAccessToken();
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al obtener token: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }

        try {
            $client = \Config\Services::curlrequest();
            $client->delete(
                'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId)
                    . '/events/' . rawurlencode($eventId),
                [
                    'headers' => ['Authorization' => 'Bearer ' . $token],
                    'timeout' => 10,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al borrar evento: {msg}', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @return list<array{id: string, titulo: string, inicio: string, fin: string, todo_el_dia: bool}>|null
     */
    private function buscarEventosEnRango(\DateTimeImmutable $desde, \DateTimeImmutable $hasta, ?string $texto): ?array
    {
        $calendarId = env('braintogram.googleCalendarId');
        if (!$calendarId) {
            log_message('error', 'GoogleCalendarService: braintogram.googleCalendarId no configurado.');

            return null;
        }

        try {
            $token = $this->obtenerAccessToken();
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al obtener token: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }

        $query = [
            'timeMin'      => $desde->format(DATE_RFC3339),
            'timeMax'      => $hasta->format(DATE_RFC3339),
            'singleEvents' => 'true',
            'orderBy'      => 'startTime',
        ];
        if ($texto !== null && $texto !== '') {
            $query['q'] = $texto;
        }

        try {
            $client   = \Config\Services::curlrequest();
            $response = $client->get(
                'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
                [
                    'headers' => ['Authorization' => 'Bearer ' . $token],
                    'query'   => $query,
                    'timeout' => 10,
                ]
            );

            $data = json_decode($response->getBody(), true);

            return array_map(static fn (array $e) => [
                'id'          => $e['id'],
                'titulo'      => $e['summary'] ?? '(sin título)',
                'inicio'      => $e['start']['dateTime'] ?? $e['start']['date'] ?? null,
                'fin'         => $e['end']['dateTime'] ?? $e['end']['date'] ?? null,
                'todo_el_dia' => isset($e['start']['date']),
            ], $data['items'] ?? []);
        } catch (\Throwable $e) {
            log_message('error', 'GoogleCalendarService: fallo al consultar eventos: {msg}', ['msg' => $e->getMessage()]);

            return null;
        }
    }

    private function obtenerAccessToken(): string
    {
        $credenciales = new ServiceAccountCredentials(self::SCOPE, self::CREDENTIALS_PATH);
        $token        = $credenciales->fetchAuthToken();

        return $token['access_token'];
    }
}
