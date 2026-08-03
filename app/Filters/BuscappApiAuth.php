<?php

namespace App\Filters;

use App\Models\BuscappUsuarioModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Autenticación de la API de Buscapp: no usa Myth\Auth (eso es para tu login
 * web) sino un token Bearer propio por usuario, emitido en el registro.
 * Solo valida que el token exista; cada controlador vuelve a resolver el
 * usuario con BuscappApiAuth::usuarioDesde($request) (ver BuscappTrait), para
 * no depender de propiedades dinámicas sobre el objeto Request.
 */
class BuscappApiAuth implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (self::usuarioDesde($request) === null) {
            return service('response')
                ->setJSON(['error' => 'Falta o es inválida la cabecera Authorization: Bearer <token>'])
                ->setStatusCode(401);
        }
    }

    public static function usuarioDesde(RequestInterface $request): ?array
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            return null;
        }

        return (new BuscappUsuarioModel())->porToken(trim($m[1]));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
