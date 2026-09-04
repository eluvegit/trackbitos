<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autenticación de /silo/agente: mono-usuario, un único token Bearer
 * compartido (silo.apiToken en .env) — mismo patrón que
 * App\Filters\PiezasApiAuth. El agente `.py` no usa Myth\Auth (no hay
 * sesión web), y la identidad de la unidad va en el body de cada petición,
 * no aquí.
 */
class SiloApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tokenEsperado = env('silo.apiToken');
        if (!$tokenEsperado) {
            return service('response')
                ->setJSON(['error' => 'API de Silo sin configurar (falta silo.apiToken en .env).'])
                ->setStatusCode(500);
        }

        $header = trim($request->getHeaderLine('Authorization'));
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m) || !hash_equals($tokenEsperado, trim($m[1]))) {
            return service('response')
                ->setJSON(['error' => 'Falta o es inválida la cabecera Authorization: Bearer <token>.'])
                ->setStatusCode(401);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
