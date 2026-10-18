<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** Sección Inventario: grupo 'inventario' y también 'admin' (para revisarla). */
class SoloInventario implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        if (! logged_in()) {
            session()->set('redirect_url', current_url());

            return redirect()->route('login');
        }

        if (! in_groups(['admin', 'inventario'])) {
            service('authentication')->logout();

            return redirect()->route('login')->with('error', 'Tu usuario no tiene acceso.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
