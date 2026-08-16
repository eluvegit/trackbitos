<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autenticación de /piezas/api: mono-usuario, un único token Bearer
 * compartido (piezas.apiToken en .env), no Myth\Auth ni tokens por
 * máquina — la identidad de máquina va aparte, en el body (uuid), no aquí.
 * Ver App\Filters\BuscappApiAuth para el mismo patrón con tokens por usuario.
 */
class PiezasApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tokenEsperado = env('piezas.apiToken');
        if (!$tokenEsperado) {
            return service('response')
                ->setJSON(['error' => 'API de Piezas sin configurar (falta piezas.apiToken en .env).'])
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
