<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\GimnasioEjerciciosModel;

class GimnasioEjercicios extends BaseController
{
    protected $model;

    public function __construct()
    {
        helper('gimnasio');
        $this->model = new GimnasioEjerciciosModel();
    }

    /**
     * Progresión de un ejercicio: la mejor serie (por 1RM estimado, Epley) de
     * cada fecha, más su PR y su última sesión. Se usa tanto en el resumen de
     * los 3 básicos como en el historial completo de un ejercicio cualquiera.
     */
    private function calcularProgresion(int $ejercicioId): array
    {
        $db = \Config\Database::connect();

        $series = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha, s.repeticiones, s.peso')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->where('ee.ejercicio_id', $ejercicioId)
            ->where('s.peso >', 0)
            ->where('s.repeticiones >', 0)
            ->orderBy('en.fecha', 'ASC')
            ->get()->getResultArray();

        $porFecha = [];
        foreach ($series as $s) {
            $peso = (float) $s['peso'];
            $reps = (int) $s['repeticiones'];
            $e1rm = $peso * (1 + $reps / 30);

            $fecha = $s['fecha'];
            if (!isset($porFecha[$fecha]) || $e1rm > $porFecha[$fecha]['e1rm']) {
                $porFecha[$fecha] = [
                    'fecha' => $fecha,
                    'peso'  => $peso,
                    'reps'  => $reps,
                    'e1rm'  => round($e1rm, 1),
                ];
            }
        }
        $progresion = array_values($porFecha);

        $pr = null;
        foreach ($progresion as $p) {
            if (!$pr || $p['e1rm'] > $pr['e1rm']) {
                $pr = $p;
            }
        }

        return [
            'progresion' => $progresion,
            'pr'         => $pr,
            'ultimo'     => $progresion ? end($progresion) : null,
        ];
    }

    public function index()
    {
        $data['ejercicios'] = $this->model->orderBy('grupo_muscular')->orderBy('nombre')->findAll();
        $data['grupoNombres'] = gim_grupos();
        return view('gimnasio/ejercicios/index', $data);
    }

    public function create()
    {
        return view('gimnasio/ejercicios/create');
    }

    /**
     * Ejercicios de un grupo muscular, ordenados por frecuencia de uso
     * (los más usados primero) para no tener que buscar entre decenas de
     * ejercicios poco usados a la hora de registrar una serie.
     */
    public function porGrupo($grupo)
    {
        $db = \Config\Database::connect();

        $ejercicios = $db->table('gimnasio_ejercicios ge')
            ->select('ge.id, ge.nombre, ge.grupo_muscular, COUNT(ee.id) AS usos')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.ejercicio_id = ge.id', 'left')
            ->where('ge.grupo_muscular', $grupo)
            ->groupBy('ge.id, ge.nombre, ge.grupo_muscular')
            ->orderBy('usos', 'DESC')
            ->orderBy('ge.nombre', 'ASC')
            ->get()->getResultArray();

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

        $ejercicio = $db->table('gimnasio_ejercicios')->where('id', $id)->get()->getRowArray();
        if (!$ejercicio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Ejercicio no encontrado');
        }

        $prog = $this->calcularProgresion((int) $id);

        // Detalle de todas las series individuales (alimenta el buscador por peso)
        $seriesDetalle = $db->table('gimnasio_series s')
            ->select('en.fecha AS fecha, s.series, s.repeticiones, s.peso, s.rpe, s.nota')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id', 'inner')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id', 'inner')
            ->where('ee.ejercicio_id', $id)
            ->orderBy('en.fecha', 'DESC')
            ->orderBy('s.id', 'ASC')
            ->get()->getResultArray();

        return view('gimnasio/ejercicios/estadisticas', [
            'ejercicio'      => $ejercicio,
            'progresion'     => $prog['progresion'],
            'pr'             => $prog['pr'],
            'ultimo'         => $prog['ultimo'],
            'seriesDetalle'  => $seriesDetalle,
        ]);
    }

    public function principales()
    {
        $db = \Config\Database::connect();

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

        // 2) Para cada ejercicio: progresión (mejor 1RM estimado por sesión), PR y últimas sesiones
        $data = [
            'ejercicios' => $ejercicios,
            'bloques'    => [],
        ];

        foreach ($ejercicios as $clave => $ej) {
            if (!$ej) {
                $data['bloques'][$clave] = null;
                continue;
            }

            $prog = $this->calcularProgresion((int) $ej['id']);

            $data['bloques'][$clave] = [
                'progresion' => $prog['progresion'],
                'pr'         => $prog['pr'],
                'ultimo'     => $prog['ultimo'],
                'reciente'   => array_slice(array_reverse($prog['progresion']), 0, 12),
            ];
        }

        return view('gimnasio/ejercicios/estadisticas_principales', $data);
    }


}
