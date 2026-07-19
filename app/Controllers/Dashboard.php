<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LentillasSustitucionesModel;
use App\Models\RecordatorioModel;

class Dashboard extends BaseController
{
    public function index()
    {
        helper('recordatorio');

        $model = new LentillasSustitucionesModel();
        $ultima = $model->whereIn('elemento', ['lentilla izquierda', 'lentilla derecha', 'lentillas'])
                        ->orderBy('fecha', 'DESC')
                        ->first();

        $dias = 0;
        if ($ultima) {
            $dias = (new \DateTime($ultima['fecha']))->diff(new \DateTime())->days;
        }

        $mostrarAlerta = $dias >= 45;

        // Recordatorios que vencen dentro de 1 mes (o ya caducados)
        $recordatorioModel = new RecordatorioModel();
        $recordatoriosUrgentes = [];
        foreach ($recordatorioModel->findAll() as $r) {
            $periodo = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
            $fechaEfectiva = recordatorio_fecha_efectiva($r['fecha_evento'], $periodo);
            $estado = recordatorio_estado($fechaEfectiva);

            if (in_array($estado['nivel'], ['caducado', 'urgente'], true)) {
                $r['dias']  = $estado['dias'];
                $r['texto'] = $estado['texto'];
                $r['nivel'] = $estado['nivel'];
                $recordatoriosUrgentes[] = $r;
            }
        }
        usort($recordatoriosUrgentes, fn($a, $b) => $a['dias'] <=> $b['dias']);

        return view('dashboard/index', [
            'dias' => $dias,
            'mostrarAlerta' => $mostrarAlerta,
            'recordatoriosUrgentes' => $recordatoriosUrgentes,
        ]);
    }
}
