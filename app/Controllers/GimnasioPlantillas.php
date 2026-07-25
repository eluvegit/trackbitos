<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\GimnasioPlantillasModel;
use App\Models\GimnasioPlantillaEjerciciosModel;
use App\Models\GimnasioPlantillaSeriesModel;
use App\Models\GimnasioEjerciciosModel;
use App\Models\GimnasioEntrenamientosModel;
use App\Models\GimnasioEntrenamientoEjerciciosModel;
use App\Models\GimnasioSeriesModel;

class GimnasioPlantillas extends BaseController
{
    protected $plantillasModel;
    protected $plantillaEjerciciosModel;
    protected $plantillaSeriesModel;
    protected $ejerciciosModel;

    public function __construct()
    {
        helper('gimnasio');
        $this->plantillasModel         = new GimnasioPlantillasModel();
        $this->plantillaEjerciciosModel = new GimnasioPlantillaEjerciciosModel();
        $this->plantillaSeriesModel    = new GimnasioPlantillaSeriesModel();
        $this->ejerciciosModel         = new GimnasioEjerciciosModel();
    }

    public function index()
    {
        $db = \Config\Database::connect();

        $plantillas = $this->plantillasModel->orderBy('nombre', 'ASC')->findAll();

        foreach ($plantillas as &$p) {
            $p['num_ejercicios'] = $this->plantillaEjerciciosModel
                ->where('plantilla_id', $p['id'])->countAllResults();
        }
        unset($p);

        return view('gimnasio/plantillas/index', ['plantillas' => $plantillas]);
    }

    public function crear()
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->back()->with('error', 'Ponle un nombre a la plantilla');
        }

        $id = $this->plantillasModel->insert(['nombre' => $nombre]);

        return redirect()->to(site_url("gimnasio/plantillas/editar/{$id}"));
    }

    public function eliminar($id)
    {
        $plantilla = $this->plantillasModel->find($id);
        if (!$plantilla) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $db->table('gimnasio_plantilla_series')
            ->whereIn('plantilla_ejercicio_id', function ($builder) use ($id) {
                $builder->select('id')->from('gimnasio_plantilla_ejercicios')->where('plantilla_id', $id);
            })
            ->delete();

        $db->table('gimnasio_plantilla_ejercicios')->where('plantilla_id', $id)->delete();
        $this->plantillasModel->delete($id);

        $db->transComplete();

        return redirect()->to(site_url('gimnasio/plantillas'))->with('mensaje', 'Plantilla eliminada');
    }

    public function renombrar($id)
    {
        $plantilla = $this->plantillasModel->find($id);
        if (!$plantilla) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->back()->with('error', 'El nombre no puede estar vacío');
        }

        $this->plantillasModel->update($id, [
            'nombre' => $nombre,
            'notas'  => $this->request->getPost('notas') ?: null,
        ]);

        return redirect()->back()->with('mensaje', 'Plantilla actualizada');
    }

    public function editar($id)
    {
        $plantilla = $this->plantillasModel->find($id);
        if (!$plantilla) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();

        $ejerciciosPlantilla = $db->table('gimnasio_plantilla_ejercicios pe')
            ->select('pe.id AS pe_id, pe.ejercicio_id, pe.orden, ge.nombre AS ejercicio_nombre, ge.grupo_muscular AS grupo_key')
            ->join('gimnasio_ejercicios ge', 'ge.id = pe.ejercicio_id', 'left')
            ->where('pe.plantilla_id', $id)
            ->orderBy('pe.orden', 'ASC')
            ->get()->getResultArray();

        $todasLasSeries = $db->table('gimnasio_plantilla_series ps')
            ->select('ps.id, ps.plantilla_ejercicio_id AS pe_id, ps.series, ps.repeticiones, ps.peso, ps.rpe, ps.nota, ps.orden')
            ->join('gimnasio_plantilla_ejercicios pe', 'pe.id = ps.plantilla_ejercicio_id')
            ->where('pe.plantilla_id', $id)
            ->orderBy('ps.orden', 'ASC')
            ->get()->getResultArray();

        $seriesPorPe = [];
        foreach ($todasLasSeries as $s) {
            $seriesPorPe[$s['pe_id']][] = $s;
        }

        $ejerciciosAgrupados = [];
        foreach ($ejerciciosPlantilla as $e) {
            $ejerciciosAgrupados[] = [
                'pe_id'            => (int) $e['pe_id'],
                'ejercicio_id'     => (int) $e['ejercicio_id'],
                'ejercicio_nombre' => $e['ejercicio_nombre'] ?: ('Ejercicio #' . $e['ejercicio_id']),
                'grupo_nombre'     => gim_grupo_nombre($e['grupo_key']),
                'series'           => $seriesPorPe[$e['pe_id']] ?? [],
            ];
        }

        $recientes = $db->table('gimnasio_entrenamiento_ejercicios ee')
            ->select('ge.id, ge.nombre, ge.grupo_muscular, COUNT(ee.id) AS usos, MAX(ee.created_at) AS ultima_vez')
            ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
            ->groupBy('ge.id, ge.nombre, ge.grupo_muscular')
            ->orderBy('usos', 'DESC')
            ->orderBy('ultima_vez', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        return view('gimnasio/plantillas/editar', [
            'plantilla'           => $plantilla,
            'plantilla_id'        => $id,
            'ejerciciosAgrupados' => $ejerciciosAgrupados,
            'recientes'           => $recientes,
            'grupos'              => gim_grupos(),
        ]);
    }

    public function guardarSerie()
    {
        $req = service('request');

        $plantillaId = (int) $req->getPost('plantilla_id');
        $ejercicioId = (int) $req->getPost('ejercicio_id');

        $peModel = $this->plantillaEjerciciosModel;

        $pe = $peModel->where('plantilla_id', $plantillaId)->where('ejercicio_id', $ejercicioId)->first();

        if (!$pe) {
            $maxOrden = $peModel->selectMax('orden')->where('plantilla_id', $plantillaId)->first();
            $ordenPe = (int) ($maxOrden['orden'] ?? 0) + 1;

            $plantillaEjercicioId = $peModel->insert([
                'plantilla_id' => $plantillaId,
                'ejercicio_id' => $ejercicioId,
                'orden'        => $ordenPe,
            ]);
        } else {
            $plantillaEjercicioId = $pe['id'];
        }

        $peRow = $peModel->find($plantillaEjercicioId);

        $psModel = $this->plantillaSeriesModel;
        $maxSerie = $psModel->selectMax('orden')->where('plantilla_ejercicio_id', $plantillaEjercicioId)->first();
        $ordenSerie = (int) ($maxSerie['orden'] ?? 0) + 1;

        $rpe  = $req->getPost('rpe');
        $nota = $req->getPost('nota');
        $rpeLimpio = ($rpe !== '' && $rpe !== null) ? (int) $rpe : null;

        $serieId = $psModel->insert([
            'plantilla_ejercicio_id' => $plantillaEjercicioId,
            'series'        => (int) $req->getPost('series'),
            'repeticiones'  => (int) $req->getPost('repeticiones'),
            'peso'          => (float) $req->getPost('peso'),
            'rpe'           => $rpeLimpio,
            'nota'          => $nota ?: null,
            'orden'         => $ordenSerie,
        ]);

        $ejercicioInfo = $this->ejerciciosModel->find($ejercicioId);

        return $this->response->setJSON([
            'ok'    => true,
            'serie' => [
                'id'           => $serieId,
                'series'       => (int) $req->getPost('series'),
                'repeticiones' => (int) $req->getPost('repeticiones'),
                'peso'         => (float) $req->getPost('peso'),
                'rpe'          => $rpeLimpio,
                'nota'         => $nota ?: null,
            ],
            'ejercicio' => [
                'pe_id'        => (int) $plantillaEjercicioId,
                'id'           => $ejercicioId,
                'nombre'       => $ejercicioInfo['nombre'] ?? ('Ejercicio #' . $ejercicioId),
                'grupo_nombre' => gim_grupo_nombre($ejercicioInfo['grupo_muscular'] ?? null),
                'orden'        => (int) $peRow['orden'],
            ],
        ]);
    }

    public function eliminarSerie($id)
    {
        $serie = $this->plantillaSeriesModel->find($id);
        if (!$serie) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            }
            return redirect()->back()->with('error', 'Serie no encontrada');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $peId = (int) $serie['plantilla_ejercicio_id'];

        $this->plantillaSeriesModel->delete($id);

        $resto = $this->plantillaSeriesModel
            ->where('plantilla_ejercicio_id', $peId)
            ->orderBy('orden', 'ASC')->findAll();

        $i = 1;
        foreach ($resto as $row) {
            if ((int) $row['orden'] !== $i) {
                $this->plantillaSeriesModel->update($row['id'], ['orden' => $i]);
            }
            $i++;
        }

        $ejercicioEliminado = false;
        if (empty($resto)) {
            $peModel = $this->plantillaEjerciciosModel;
            $pe = $peModel->find($peId);
            if ($pe) {
                $plantillaId = (int) $pe['plantilla_id'];
                $peModel->delete($peId);
                $ejercicioEliminado = true;

                $ejercicios = $peModel->where('plantilla_id', $plantillaId)->orderBy('orden', 'ASC')->findAll();
                $j = 1;
                foreach ($ejercicios as $e) {
                    if ((int) $e['orden'] !== $j) {
                        $peModel->update($e['id'], ['orden' => $j]);
                    }
                    $j++;
                }
            }
        }

        $db->transComplete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'ejercicio_eliminado' => $ejercicioEliminado]);
        }

        return redirect()->back()->with('mensaje', 'Serie eliminada');
    }

    public function actualizarSerie($id)
    {
        $serie = $this->plantillaSeriesModel->find($id);
        if (!$serie) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            }
            return redirect()->back()->with('error', 'Serie no encontrada');
        }

        $rpe = $this->request->getPost('rpe');

        $data = [
            'series'       => (int) $this->request->getPost('series'),
            'repeticiones' => (int) $this->request->getPost('repeticiones'),
            'peso'         => (float) $this->request->getPost('peso'),
            'rpe'          => $rpe !== '' && $rpe !== null ? (int) $rpe : null,
            'nota'         => $this->request->getPost('nota') ?: null,
        ];

        $this->plantillaSeriesModel->update($id, $data);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'serie' => array_merge(['id' => (int) $id], $data)]);
        }

        return redirect()->back()->with('mensaje', 'Serie actualizada');
    }

    public function reordenarEjercicio()
    {
        $req  = service('request');
        $peId = (int) $req->getPost('plantilla_ejercicio_id');
        $direction = $req->getPost('direction');

        $peModel = $this->plantillaEjerciciosModel;
        $actual  = $peModel->find($peId);
        if (!$actual) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $lista = $peModel->where('plantilla_id', $actual['plantilla_id'])->orderBy('orden', 'ASC')->findAll();

        $index = null;
        foreach ($lista as $i => $row) {
            if ((int) $row['id'] === $peId) {
                $index = $i;
                break;
            }
        }

        $targetIndex = $index === null ? null : $index + ($direction === 'up' ? -1 : 1);
        if ($index === null || $targetIndex < 0 || $targetIndex >= count($lista)) {
            return $this->response->setJSON(['ok' => false]);
        }

        $a = $lista[$index];
        $b = $lista[$targetIndex];
        $peModel->update($a['id'], ['orden' => $b['orden']]);
        $peModel->update($b['id'], ['orden' => $a['orden']]);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Aplica una plantilla a un entrenamiento: copia sus ejercicios y series
     * (peso, reps, rpe, nota) al entrenamiento de destino, igual que
     * "reutilizar rutina anterior" pero desde una plantilla guardada.
     */
    public function aplicar($plantillaId)
    {
        $destinoId = (int) $this->request->getPost('entrenamiento_id');
        $plantillaId = (int) $plantillaId;

        $entrenamientosModel = new GimnasioEntrenamientosModel();
        $destino   = $entrenamientosModel->find($destinoId);
        $plantilla = $this->plantillasModel->find($plantillaId);
        if (!$destino || !$plantilla) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $eeModel = new GimnasioEntrenamientoEjerciciosModel();
        $serieModel = new GimnasioSeriesModel();

        $existentes = array_column(
            $eeModel->select('ejercicio_id')->where('entrenamiento_id', $destinoId)->findAll(),
            'ejercicio_id'
        );

        $ejerciciosPlantilla = $this->plantillaEjerciciosModel
            ->where('plantilla_id', $plantillaId)->orderBy('orden', 'ASC')->findAll();

        $maxOrden = $eeModel->selectMax('orden')->where('entrenamiento_id', $destinoId)->first();
        $siguienteOrden = (int) ($maxOrden['orden'] ?? 0) + 1;

        $añadidos = 0;
        $seriesCopiadas = 0;
        foreach ($ejerciciosPlantilla as $pe) {
            if (in_array($pe['ejercicio_id'], $existentes, true)) {
                continue;
            }

            $nuevoEeId = $eeModel->insert([
                'entrenamiento_id' => $destinoId,
                'ejercicio_id'     => $pe['ejercicio_id'],
                'orden'            => $siguienteOrden++,
            ]);
            $añadidos++;

            $seriesPlantilla = $this->plantillaSeriesModel
                ->where('plantilla_ejercicio_id', $pe['id'])->orderBy('orden', 'ASC')->findAll();

            foreach ($seriesPlantilla as $ps) {
                $serieModel->insert([
                    'entrenamiento_ejercicio_id' => $nuevoEeId,
                    'series'        => $ps['series'],
                    'repeticiones'  => $ps['repeticiones'],
                    'peso'          => $ps['peso'],
                    'rpe'           => $ps['rpe'],
                    'nota'          => $ps['nota'],
                    'orden'         => $ps['orden'],
                ]);
                $seriesCopiadas++;
            }
        }

        $mensaje = $añadidos > 0
            ? "Plantilla \"{$plantilla['nombre']}\" aplicada: {$añadidos} ejercicios con {$seriesCopiadas} series."
            : 'Esos ejercicios ya estaban en el entrenamiento de hoy.';

        return redirect()->to(site_url("gimnasio/entrenamientos/registro/{$destinoId}"))->with('mensaje', $mensaje);
    }

    /**
     * Crea una plantilla nueva a partir de los ejercicios y series ya
     * registrados en un entrenamiento (de hoy o pasado), para reutilizarlo
     * en el futuro sin tener que reconstruirlo desde cero.
     */
    public function guardarDesdeEntrenamiento($entrenamientoId)
    {
        $entrenamientoId = (int) $entrenamientoId;
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->back()->with('error', 'Ponle un nombre a la plantilla');
        }

        $entrenamientosModel = new GimnasioEntrenamientosModel();
        $entrenamiento = $entrenamientosModel->find($entrenamientoId);
        if (!$entrenamiento) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $eeModel = new GimnasioEntrenamientoEjerciciosModel();
        $serieModel = new GimnasioSeriesModel();

        $plantillaId = $this->plantillasModel->insert(['nombre' => $nombre]);

        $ejercicios = $eeModel->where('entrenamiento_id', $entrenamientoId)->orderBy('orden', 'ASC')->findAll();

        $ejerciciosCopiados = 0;
        $seriesCopiadas = 0;
        foreach ($ejercicios as $ee) {
            $nuevoPeId = $this->plantillaEjerciciosModel->insert([
                'plantilla_id' => $plantillaId,
                'ejercicio_id' => $ee['ejercicio_id'],
                'orden'        => $ee['orden'],
            ]);
            $ejerciciosCopiados++;

            $series = $serieModel->where('entrenamiento_ejercicio_id', $ee['id'])->orderBy('orden', 'ASC')->findAll();
            foreach ($series as $s) {
                $this->plantillaSeriesModel->insert([
                    'plantilla_ejercicio_id' => $nuevoPeId,
                    'series'        => $s['series'],
                    'repeticiones'  => $s['repeticiones'],
                    'peso'          => $s['peso'],
                    'rpe'           => $s['rpe'],
                    'nota'          => $s['nota'],
                    'orden'         => $s['orden'],
                ]);
                $seriesCopiadas++;
            }
        }

        $mensaje = $ejerciciosCopiados > 0
            ? "Plantilla \"{$nombre}\" creada con {$ejerciciosCopiados} ejercicios y {$seriesCopiadas} series."
            : "Plantilla \"{$nombre}\" creada (el entrenamiento no tenía ejercicios que copiar).";

        return redirect()->to(site_url("gimnasio/entrenamientos/registro/{$entrenamientoId}"))->with('mensaje', $mensaje);
    }
}
