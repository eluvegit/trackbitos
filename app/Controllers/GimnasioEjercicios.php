<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\GimnasioEjerciciosModel;

class GimnasioEjercicios extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new GimnasioEjerciciosModel();
    }

    public function index()
    {
        $data['ejercicios'] = $this->model->orderBy('grupo_muscular')->orderBy('nombre')->findAll();
        return view('gimnasio/ejercicios/index', $data);
    }

    public function create()
    {
        return view('gimnasio/ejercicios/create');
    }

    public function porGrupo($grupo)
    {
        $ejercicios = $this->model
            ->where('grupo_muscular', $grupo)
            ->orderBy('nombre')
            ->findAll();

        return $this->response->setJSON($ejercicios);
    }


    public function store()
    {
        $this->model->save([
            'nombre'         => $this->request->getPost('nombre'),
            'grupo_muscular' => $this->request->getPost('grupo_muscular')
        ]);

        return redirect()->to(site_url('gimnasio/ejercicios'))->with('success', 'Ejercicio creado correctamente.');
    }

    public function edit($id)
    {
        $data['ejercicio'] = $this->model->find($id);
        return view('gimnasio/ejercicios/edit', $data);
    }

    public function update($id)
    {
        $this->model->update($id, [
            'nombre'         => $this->request->getPost('nombre'),
            'grupo_muscular' => $this->request->getPost('grupo_muscular')
        ]);

        return redirect()->to(site_url('gimnasio/ejercicios'))->with('success', 'Ejercicio actualizado.');
    }

    public function delete($id)
    {
        $this->model->delete($id);
        return redirect()->to(site_url('gimnasio/ejercicios'))->with('success', 'Ejercicio eliminado.');
    }

    public function estadisticas($id)
    {
        $db = \Config\Database::connect();

        // Ejercicio
        $ejercicio = $db->table('gimnasio_ejercicios')->where('id', $id)->get()->getRowArray();
        if (!$ejercicio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Ejercicio no encontrado');
        }

        // Resumen por fecha (fecha viene de entrenamientos)
        $seriesAgrupadas = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha, SUM(s.series) AS total_series, SUM(s.series * s.repeticiones) AS total_reps, COUNT(*) AS registros')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->where('ee.ejercicio_id', $id)
            ->groupBy('en.fecha')
            ->orderBy('en.fecha', 'DESC')
            ->get()->getResultArray();

        // Detalle de series
        $seriesDetalle = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha, s.series, s.repeticiones, s.peso, s.rpe, s.nota')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id', 'inner')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id', 'inner')
            ->where('ee.ejercicio_id', $id)
            ->orderBy('en.fecha', 'DESC')
            ->orderBy('s.id', 'ASC')
            ->get()->getResultArray();

        // Resumen por fecha (añadimos total_volumen)
        $seriesAgrupadas = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha,
              SUM(s.series) AS total_series,
              SUM(s.series * s.repeticiones) AS total_reps,
              SUM(s.series * s.repeticiones * s.peso) AS total_volumen,
              COUNT(*) AS registros')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->where('ee.ejercicio_id', $id)
            ->groupBy('en.fecha')
            ->orderBy('en.fecha', 'DESC')
            ->get()->getResultArray();


        return view('gimnasio/ejercicios/estadisticas', [
            'ejercicio'      => $ejercicio,
            'seriesAgrupadas' => $seriesAgrupadas,
            'seriesDetalle'  => $seriesDetalle,
        ]);
    }

    public function principales()
{
    $db = \Config\Database::connect();

    // Límite de registros (puedes cambiarlo desde la URL: ?limite=30)
    $limite = (int)($this->request->getGet('limite') ?? 20);

    // 1) Localizar IDs de los 3 ejercicios (tolerante con nombres comunes)
    $nombresBuscados = [
        'press banca'    => ['press banca', 'press de banca'],
        'peso muerto'    => ['peso muerto', 'deadlift'],
        'sentadillas'    => ['sentadilla', 'sentadillas', 'back squat', 'squat'],
    ];

    // Buscar por LIKE (case-insensitive)
    $ejercicios = [];
    foreach ($nombresBuscados as $clave => $patrones) {
        $builder = $db->table('gimnasio_ejercicios');
        $builder->groupStart();
        foreach ($patrones as $p) {
            $builder->orLike('LOWER(nombre)', mb_strtolower($p, 'UTF-8'));
        }
        $builder->groupEnd();
        $row = $builder->orderBy('id', 'ASC')->get()->getRowArray();
        $ejercicios[$clave] = $row ?: null;
    }

    // 2) Para cada ejercicio encontrado, obtener resumen por fecha y detalle
    $data = [
        'ejercicios' => $ejercicios,
        'bloques'    => [],
        'limite'     => $limite
    ];

    foreach ($ejercicios as $clave => $ej) {
        if (!$ej) {
            $data['bloques'][$clave] = ['resumen' => [], 'detalle' => []];
            continue;
        }
        $id = (int)$ej['id'];

        // Resumen limitado
        $resumen = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha,
                      SUM(s.series) AS total_series,
                      SUM(s.series * s.repeticiones) AS total_reps,
                      SUM(s.series * s.repeticiones * s.peso) AS total_volumen,
                      COUNT(*) AS registros')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->where('ee.ejercicio_id', $id)
            ->groupBy('en.fecha')
            ->orderBy('en.fecha', 'DESC')
            ->limit($limite)
            ->get()->getResultArray();

        // Detalle limitado
        $detalle = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha, s.series, s.repeticiones, s.peso, s.rpe, s.nota')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id', 'inner')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id', 'inner')
            ->where('ee.ejercicio_id', $id)
            ->orderBy('en.fecha', 'DESC')
            ->orderBy('s.id', 'ASC')
            ->limit($limite)
            ->get()->getResultArray();

        $data['bloques'][$clave] = [
            'resumen' => $resumen,
            'detalle' => $detalle,
        ];
    }

    return view('gimnasio/ejercicios/estadisticas_principales', $data);
}


}
