<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\CarActionModel;
use App\Models\CarFaultModel;
use App\Models\CarReminderModel;

class Coche extends BaseController
{
    public function index()
{
    $reminderModel = new CarReminderModel();
    $actionModel = new CarActionModel();

    $recordatorios = $reminderModel->findAll();
    $avisos = [];

    foreach ($recordatorios as $r) {
        $ultima = $actionModel
            ->where('reminder_id', $r['id'])
            ->orderBy('date', 'DESC')
            ->first();

        if ($ultima && $r['interval_days']) {
            $fechaUltima = \CodeIgniter\I18n\Time::parse($ultima['date']);
            $diasPasados = $fechaUltima->difference(\CodeIgniter\I18n\Time::now())->getDays();

            if ($diasPasados >= $r['interval_days']) {
                $avisos[] = [
                    'title' => $r['title'],
                    'dias' => $diasPasados,
                    'intervalo' => $r['interval_days']
                ];
            }
        }
    }

    // También puedes cargar últimas acciones si lo usas
    $ultimasAcciones = $actionModel
        ->orderBy('date', 'DESC')
        ->findAll(3); // Por ejemplo, 3 últimas

    return view('coche/index', [
        'ultimasAcciones' => $ultimasAcciones,
        'avisosVencidos' => $avisos
    ]);
}




    // ========== ACCIONES REALIZADAS ==========
    public function acciones()
    {
        $db = \Config\Database::connect();

        $builder = $db->table('car_actions');
        $builder->select('*');
        $builder->selectMax('date');
        $builder->orderBy('date', 'DESC');

        // Subconsulta: obtener solo la última acción por título
        $subquery = "
        SELECT *
        FROM car_actions ca1
        WHERE date = (
            SELECT MAX(date)
            FROM car_actions ca2
            WHERE ca2.title = ca1.title
        )
        ORDER BY date DESC
    ";

        $query = $db->query($subquery);
        $acciones = $query->getResultArray();

        return view('coche/acciones/index', ['acciones' => $acciones]);
    }

    public function accionRapida($tituloCodificado)
    {
        $title = ucwords(str_replace('-', ' ', urldecode($tituloCodificado)));

        // Busca si hay un recordatorio con ese título
        $reminderModel = new CarReminderModel();
        $reminder = $reminderModel->where('title', $title)->first();

        // Crear la acción
        $accionModel = new CarActionModel();
        $accionModel->save([
            'title'        => $title,
            'date'         => date('Y-m-d'),
            'reminder_id'  => $reminder['id'] ?? null,
            'kilometers'   => null,
            'notes'        => '',
        ]);

        return redirect()->to('/coche/acciones')->with('message', 'Acción "' . esc($title) . '" registrada para hoy');
    }


    public function nuevaAccion()
    {
        $reminderModel = new CarReminderModel();
        $data['reminders'] = $reminderModel->findAll();
        return view('coche/acciones/form', $data);
    }

    public function guardarAccion()
    {
        $model = new CarActionModel();
        $model->save($this->request->getPost());
        return redirect()->to('/coche/acciones');
    }

    public function editarAccion($id)
    {
        $model = new CarActionModel();
        $reminderModel = new CarReminderModel();
        $data['accion'] = $model->find($id);
        $data['reminders'] = $reminderModel->findAll();
        return view('coche/acciones/form', $data);
    }

    public function borrarAccion($id)
    {
        (new CarActionModel())->delete($id);
        return redirect()->to('/coche/acciones');
    }

    // ========== AVERÍAS ==========
    public function averias()
    {
        $model = new CarFaultModel();
        $averias = $model->orderBy('date', 'DESC')->findAll();

        return view('coche/averias/index', ['averias' => $averias]);
    }

    public function nuevaAveria()
    {
        return view('coche/averias/form');
    }

    public function guardarAveria()
    {
        $model = new CarFaultModel();
        $model->save($this->request->getPost());
        return redirect()->to('/coche/averias');
    }

    public function editarAveria($id)
    {
        $model = new CarFaultModel();
        $data['averia'] = $model->find($id);
        return view('coche/averias/form', $data);
    }

    public function borrarAveria($id)
    {
        (new CarFaultModel())->delete($id);
        return redirect()->to('/coche/averias');
    }

    // ========== RECORDATORIOS ==========
    public function recordatorios()
    {
        $model = new CarReminderModel();
        $recordatorios = $model->orderBy('title', 'ASC')->findAll();

        // Obtener última acción por cada reminder_id
        $actionModel = new CarActionModel();
        $ultimasAcciones = [];

        foreach ($recordatorios as $r) {
            $ultima = $actionModel
                ->where('reminder_id', $r['id'])
                ->orderBy('date', 'DESC')
                ->first();

            $diasPasados = null;
            $vencido = false;

            if ($ultima && $r['interval_days']) {
                $fechaUltima = \CodeIgniter\I18n\Time::parse($ultima['date']);
                $diasPasados = $fechaUltima->difference(\CodeIgniter\I18n\Time::now())->getDays();
                $vencido = $diasPasados >= $r['interval_days'];
            }

            $ultimasAcciones[$r['id']] = [
                'dias_pasados' => $diasPasados,
                'vencido' => $vencido
            ];
        }

        return view('coche/recordatorios/index', [
            'recordatorios' => $recordatorios,
            'estado' => $ultimasAcciones
        ]);
    }


    public function nuevoRecordatorio()
    {
        return view('coche/recordatorios/form');
    }

    public function guardarRecordatorio()
    {
        $model = new CarReminderModel();
        $model->save($this->request->getPost());
        return redirect()->to('/coche/recordatorios');
    }

    public function editarRecordatorio($id)
    {
        $model = new CarReminderModel();
        $data['recordatorio'] = $model->find($id);
        return view('coche/recordatorios/form', $data);
    }

    public function borrarRecordatorio($id)
    {
        (new CarReminderModel())->delete($id);
        return redirect()->to('/coche/recordatorios');
    }
}
