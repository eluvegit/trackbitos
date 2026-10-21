<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Sección Inventario: pública sin sesión; con sesión, solo 'admin' o 'inventario'. */
class SoloInventario implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        if (logged_in() && ! in_groups(['admin', 'inventario'])) {
            service('authentication')->logout();

            return redirect()->route('login')->with('error', 'Tu usuario no tiene acceso.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
