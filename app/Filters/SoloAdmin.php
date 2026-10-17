<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Todo lo de la web salvo la sección de inventario es solo del grupo
 * 'admin'. Un usuario de 'inventario' que llegue aquí (p. ej. por el
 * landingRoute 'dashboard' al loguearse) se manda a su sección; uno sin
 * grupo se desconecta.
 */
class SoloAdmin implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('auth');

        if (! logged_in()) {
            session()->set('redirect_url', current_url());

            return redirect()->route('login');
        }

        if (in_groups('admin')) {
            return;
        }

        if (in_groups('inventario')) {
            return redirect()->to(site_url('inventario'));
        }

        service('authentication')->logout();

        return redirect()->route('login')->with('error', 'Tu usuario no tiene acceso.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
