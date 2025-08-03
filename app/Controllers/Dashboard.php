<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LentillasSustitucionesModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $model = new LentillasSustitucionesModel();
        $ultima = $model->whereIn('elemento', ['lentilla izquierda', 'lentilla derecha', 'lentillas'])
                        ->orderBy('fecha', 'DESC')
                        ->first();

        $dias = 0;
        if ($ultima) {
            $dias = (new \DateTime($ultima['fecha']))->diff(new \DateTime())->days;
        }

        $mostrarAlerta = $dias >= 45;

        return view('dashboard/index', [
            'dias' => $dias,
            'mostrarAlerta' => $mostrarAlerta,
        ]);
    }
}
