<?php

namespace App\Controllers\Buscapp;

use App\Controllers\BaseController;
use App\Models\BuscappTelegramaDestinoModel;
use App\Models\BuscappTelegramaModel;
use App\Models\BuscappUsuarioModel;

/**
 * Panel de gestión de Buscapp: protegido con tu login habitual (filtro
 * 'auth' de Myth\Auth), a diferencia de Api.php que usa el token propio de
 * la app. Aquí ves usuarios registrados y telegramas enviados, nada más.
 */
class Admin extends BaseController
{
    public function index()
    {
        $usuarios   = new BuscappUsuarioModel();
        $telegramas = new BuscappTelegramaModel();

        return view('buscapp/index', [
            'usuarios'   => $usuarios->orderBy('creado_en', 'DESC')->findAll(),
            'telegramas' => $telegramas->orderBy('enviado_en', 'DESC')->paginate(30),
            'pager'      => $telegramas->pager,
        ]);
    }

    public function ver(int $id)
    {
        $telegrama = (new BuscappTelegramaModel())->find($id);
        if ($telegrama === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Telegrama no encontrado');
        }

        $emisor   = (new BuscappUsuarioModel())->find($telegrama['emisor_id']);
        $destinos = (new BuscappTelegramaDestinoModel())->where('telegrama_id', $id)->findAll();

        $usuariosPorId = [];
        foreach ($destinos as $d) {
            $usuariosPorId[$d['receptor_id']] = (new BuscappUsuarioModel())->find($d['receptor_id']);
        }

        return view('buscapp/ver', [
            'telegrama'      => $telegrama,
            'emisor'         => $emisor,
            'destinos'       => $destinos,
            'usuariosPorId'  => $usuariosPorId,
        ]);
    }
}
