<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autenticación de /piezas/sterclicks-api: token Bearer único compartido
 * (sterclicks.apiToken en .env), dedicado a la web de sterclicks — no se
 * reutiliza el token del CLI (piezas.apiToken) para no mezclar clientes.
 * Mismo patrón que App\Filters\PiezasApiAuth.
 */
class SterclicksApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tokenEsperado = env('sterclicks.apiToken');
        if (!$tokenEsperado) {
            return service('response')
                ->setJSON(['error' => 'API de Sterclicks sin configurar (falta sterclicks.apiToken en .env).'])
                ->setStatusCode(500);
        }

        $header = trim($request->getHeaderLine('Authorization'));
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m) && hash_equals($tokenEsperado, trim($m[1]))) {
            return;
        }

        // Las etiquetas <img> no pueden mandar cabecera Authorization: para esas
        // rutas (imágenes del catálogo) se acepta también el token por query string.
        $tokenQuery = (string) $request->getGet('token');
        if ($tokenQuery !== '' && hash_equals($tokenEsperado, $tokenQuery)) {
            return;
        }

        return service('response')
            ->setJSON(['error' => 'Falta o es inválida la cabecera Authorization: Bearer <token>.'])
            ->setStatusCode(401);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
