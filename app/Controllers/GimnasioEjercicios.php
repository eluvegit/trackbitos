<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\GimnasioEjerciciosModel;
use App\Services\GimnasioStats;

class GimnasioEjercicios extends BaseController
{
    protected $model;

    public function __construct()
    {
        helper('gimnasio');
        $this->model = new GimnasioEjerciciosModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        $data['ejercicios'] = $this->model
            ->select('gimnasio_ejercicios.*, gc.nombre AS catalogo_nombre, gc.imagen AS catalogo_imagen')
            ->join('gimnasio_catalogo gc', 'gc.id = gimnasio_ejercicios.catalogo_id', 'left')
            ->orderBy('grupo_muscular')->orderBy('gimnasio_ejercicios.nombre')
            ->findAll();
        $data['grupoNombres'] = gim_grupos();

        // Uso de cada ejercicio (veces entrenado y última fecha), para la vista nueva
        $uso = $db->table('gimnasio_entrenamiento_ejercicios ee')
            ->select('ee.ejercicio_id, COUNT(*) AS veces, MAX(en.fecha) AS ultima')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->groupBy('ee.ejercicio_id')
            ->get()->getResultArray();
        $data['uso'] = array_column($uso, null, 'ejercicio_id');

        return view(gim_vista('gimnasio/ejercicios/index'), $data);
    }

    public function create()
    {
        return view(gim_vista('gimnasio/ejercicios/create'), ['grupo' => (string) $this->request->getGet('grupo')]);
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
        $ejercicio = $this->model->find($id);
        if (!$ejercicio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view(gim_vista('gimnasio/ejercicios/edit'), ['ejercicio' => $ejercicio]);
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

    /** Marca/desmarca un ejercicio como principal (estrella). AJAX o formulario. */
    public function principal($id)
    {
        $ej = $this->model->find($id);
        if (!$ej) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $nuevo = empty($ej['principal']) ? 1 : 0;
        $this->model->update($id, ['principal' => $nuevo]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'principal' => $nuevo]);
        }
        return redirect()->back();
    }

    public function estadisticas($id)
    {
        $db = \Config\Database::connect();

        $ejercicio = $db->table('gimnasio_ejercicios')->where('id', $id)->get()->getRowArray();
        if (!$ejercicio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Ejercicio no encontrado');
        }

        $prog = (new GimnasioStats())->progresion((int) $id);

        // Detalle de todas las series individuales (alimenta el buscador por peso)
        $seriesDetalle = $db->table('gimnasio_series s')
            ->select('en.id AS entrenamiento_id, en.fecha AS fecha, s.series, s.repeticiones, s.peso, s.rpe, s.nota')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id', 'inner')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id', 'inner')
            ->where('ee.ejercicio_id', $id)
            ->orderBy('en.fecha', 'DESC')
            ->orderBy('s.orden', 'ASC')
            ->orderBy('s.id', 'ASC')
            ->get()->getResultArray();

        $hayCatalogo = $db->tableExists('gimnasio_catalogo');

        return view(gim_vista('gimnasio/ejercicios/estadisticas'), [
            'ejercicio'          => $ejercicio,
            'progresion'         => $prog['progresion'],
            'pr'                 => $prog['pr'],
            'ultimo'             => $prog['ultimo'],
            'volumen'            => $prog['volumen'],
            'sesiones'           => $prog['sesiones'],
            'seriesDetalle'      => $seriesDetalle,
            'catalogo'           => !empty($ejercicio['catalogo_id'])
                ? $db->table('gimnasio_catalogo')->where('id', $ejercicio['catalogo_id'])->get()->getRowArray()
                : null,
            // Sugerencias para vincular o cambiar el vínculo (vacío si no hay catálogo importado)
            'hayCatalogo'        => $hayCatalogo,
            'candidatosCatalogo' => $hayCatalogo
                ? (new \App\Services\GimnasioCatalogoService())->candidatosPara($ejercicio)
                : [],
        ]);
    }

    public function principales()
    {
        $stats = new GimnasioStats();

        if (gim_vista('x') === 'x_nueva') {
            return view('gimnasio/ejercicios/estadisticas_principales_nueva', [
                'principales' => $stats->principales(),
                'semana'      => $stats->semanaPorGrupo(),
                'constancia'  => $stats->constancia(),
                'dias'        => $stats->dias(date('Y-m-d', strtotime('-26 weeks'))),
            ]);
        }

        // Vista clásica: los 3 básicos buscados por nombre, como siempre
        $db = \Config\Database::connect();
        $nombresBuscados = [
            'press banca'    => ['press banca', 'press de banca'],
            'peso muerto'    => ['peso muerto', 'deadlift'],
            'sentadillas'    => ['sentadilla', 'sentadillas', 'back squat', 'squat'],
        ];

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

        $data = ['ejercicios' => $ejercicios, 'bloques' => []];
        foreach ($ejercicios as $clave => $ej) {
            if (!$ej) {
                $data['bloques'][$clave] = null;
                continue;
            }
            $prog = $stats->progresion((int) $ej['id']);
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
