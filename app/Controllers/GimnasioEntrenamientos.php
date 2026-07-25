<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\GimnasioEntrenamientosModel;
use App\Models\GimnasioEntrenamientoEjerciciosModel;
use App\Models\GimnasioEjerciciosModel;
use App\Models\GimnasioSeriesModel;

class GimnasioEntrenamientos extends BaseController
{
    protected $entrenamientosModel;
    protected $ejerciciosModel;
    protected $entrenamientoEjerciciosModel;
    protected $seriesModel;

    public function __construct()
    {
        helper('gimnasio');
        $this->entrenamientosModel           = new GimnasioEntrenamientosModel();
        $this->ejerciciosModel               = new GimnasioEjerciciosModel();
        $this->entrenamientoEjerciciosModel  = new GimnasioEntrenamientoEjerciciosModel();
        $this->seriesModel                   = new GimnasioSeriesModel();
    }

    private function grupoNombre(?string $clave): string
    {
        return gim_grupo_nombre($clave);
    }

    public function index()
    {
        $model = new GimnasioEntrenamientosModel();
        $entrenamientos = $model->orderBy('fecha', 'DESC')->findAll();

        return view('gimnasio/entrenamientos/index', [
            'entrenamientos' => $entrenamientos
        ]);
    }

    public function crear()
    {
        $fecha = $this->request->getPost('fecha') ?? date('Y-m-d');

        // ❗ Evitar duplicados por fecha
        $existente = $this->entrenamientosModel
            ->where('fecha', $fecha)
            ->first();

        if ($existente) {
            return redirect()
                ->to(site_url("gimnasio/entrenamientos/registro/{$existente['id']}"))
                ->with('mensaje', "Ya existe un entrenamiento para {$fecha}. Te llevo a ese.");
        }

        // Crear solo si no existe
        $id = $this->entrenamientosModel->insert(['fecha' => $fecha]);

        return redirect()->to(site_url("gimnasio/entrenamientos/registro/$id"));
    }


    public function eliminar($id)
    {
        $entrenamiento = $this->entrenamientosModel->find($id);
        if (!$entrenamiento) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();

        // Iniciamos transacción por seguridad
        $db->transStart();

        // 1️⃣ Borrar series
        $db->table('gimnasio_series')
            ->whereIn('entrenamiento_ejercicio_id', function ($builder) use ($id) {
                $builder->select('id')
                    ->from('gimnasio_entrenamiento_ejercicios')
                    ->where('entrenamiento_id', $id);
            })
            ->delete();

        // 2️⃣ Borrar ejercicios asociados
        $db->table('gimnasio_entrenamiento_ejercicios')
            ->where('entrenamiento_id', $id)
            ->delete();

        // 3️⃣ Borrar el entrenamiento
        $this->entrenamientosModel->delete($id);

        $db->transComplete();

        return redirect()->to(site_url('gimnasio/entrenamientos'))
            ->with('mensaje', 'Entrenamiento eliminado correctamente');
    }

    public function actualizarDatos($id)
    {
        $ent = $this->entrenamientosModel->find($id);
        if (!$ent) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'tipo_sesion'    => $this->request->getPost('tipo_sesion'),
            'notas_generales' => $this->request->getPost('notas_generales') ?: null,
            'lesiones'        => $this->request->getPost('lesiones') ?: null,
            'sin_molestias'   => $this->request->getPost('sin_molestias') ? 1 : 0,
        ];

        $this->entrenamientosModel->update($id, $data);
        return redirect()->back()->with('mensaje', 'Datos del entrenamiento actualizados');
    }



    public function registro($id)
    {
        $entrenamiento = $this->entrenamientosModel->find($id);
        if (!$entrenamiento) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();

        // Ejercicios del entrenamiento (aunque todavía no tengan series), en orden
        $ejerciciosEntreno = $db->table('gimnasio_entrenamiento_ejercicios ee')
            ->select('ee.id AS ee_id, ee.ejercicio_id, ee.orden, ge.nombre AS ejercicio_nombre, ge.grupo_muscular AS grupo_key')
            ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id', 'left')
            ->where('ee.entrenamiento_id', $id)
            ->orderBy('ee.orden', 'ASC')
            ->get()->getResultArray();

        // Todas las series de este entrenamiento
        $todasLasSeries = $db->table('gimnasio_series s')
            ->select('s.id, s.entrenamiento_ejercicio_id AS ee_id, s.series, s.repeticiones, s.peso, s.rpe, s.nota, s.orden')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->where('ee.entrenamiento_id', $id)
            ->orderBy('s.orden', 'ASC')
            ->get()->getResultArray();

        $seriesPorEe = [];
        foreach ($todasLasSeries as $s) {
            $seriesPorEe[$s['ee_id']][] = $s;
        }

        $ejerciciosAgrupados = [];
        foreach ($ejerciciosEntreno as $e) {
            $ejerciciosAgrupados[] = [
                'ee_id'            => (int) $e['ee_id'],
                'ejercicio_id'     => (int) $e['ejercicio_id'],
                'ejercicio_nombre' => $e['ejercicio_nombre'] ?: ('Ejercicio #' . $e['ejercicio_id']),
                'grupo_nombre'     => $this->grupoNombre($e['grupo_key']),
                'series'           => $seriesPorEe[$e['ee_id']] ?? [],
            ];
        }

        // Sugerencias del picker rápido: mezcla de los más usados y los más
        // recientes (en cualquier entrenamiento), hasta 20 en total. Así no
        // solo salen los "clásicos" de siempre, también lo último que hayas
        // empezado a hacer aunque todavía no acumule muchos usos.
        $baseRecientes = $db->table('gimnasio_entrenamiento_ejercicios ee')
            ->select('ge.id, ge.nombre, ge.grupo_muscular, COUNT(ee.id) AS usos, MAX(ee.created_at) AS ultima_vez')
            ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
            ->groupBy('ge.id, ge.nombre, ge.grupo_muscular');

        $masUsados = (clone $baseRecientes)
            ->orderBy('usos', 'DESC')->orderBy('ultima_vez', 'DESC')
            ->limit(20)->get()->getResultArray();

        $masRecientes = (clone $baseRecientes)
            ->orderBy('ultima_vez', 'DESC')
            ->limit(20)->get()->getResultArray();

        $recientes = [];
        foreach (array_merge($masUsados, $masRecientes) as $r) {
            if (isset($recientes[$r['id']])) {
                continue;
            }
            $recientes[$r['id']] = $r;
            if (count($recientes) >= 20) {
                break;
            }
        }
        $recientes = array_values($recientes);

        // Entrenamientos anteriores con ejercicios, para "reutilizar rutina"
        $candidatos = $db->table('gimnasio_entrenamientos')
            ->select('id, fecha, tipo_sesion')
            ->where('id !=', $id)
            ->orderBy('fecha', 'DESC')
            ->limit(20)
            ->get()->getResultArray();

        $anteriores = [];
        foreach ($candidatos as $c) {
            $ejs = $db->table('gimnasio_entrenamiento_ejercicios ee')
                ->select('ge.nombre')
                ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
                ->where('ee.entrenamiento_id', $c['id'])
                ->orderBy('ee.orden', 'ASC')
                ->get()->getResultArray();

            if (empty($ejs)) {
                continue;
            }

            $c['ejercicios_resumen'] = implode(', ', array_column($ejs, 'nombre'));
            $c['num_ejercicios']     = count($ejs);
            $anteriores[]            = $c;

            if (count($anteriores) >= 8) {
                break;
            }
        }

        // Plantillas guardadas, para aplicarlas a este entrenamiento
        $plantillas = $db->table('gimnasio_plantillas p')
            ->select('p.id, p.nombre, COUNT(pe.id) AS num_ejercicios')
            ->join('gimnasio_plantilla_ejercicios pe', 'pe.plantilla_id = p.id', 'left')
            ->groupBy('p.id, p.nombre')
            ->orderBy('p.nombre', 'ASC')
            ->get()->getResultArray();

        return view('gimnasio/entrenamientos/registro', [
            'entrenamiento'       => $entrenamiento,
            'fecha'               => $entrenamiento['fecha'],
            'entrenamiento_id'    => $id,
            'ejerciciosAgrupados' => $ejerciciosAgrupados,
            'recientes'           => $recientes,
            'anteriores'          => $anteriores,
            'plantillas'          => $plantillas,
            'grupos'              => gim_grupos(),
        ]);
    }


    public function guardarSerie()
    {
        $req = service('request');

        $entrenamientoId = (int) $req->getPost('entrenamiento_id');
        $ejercicioId     = (int) $req->getPost('ejercicio_id');

        $ejercicioModel = $this->entrenamientoEjerciciosModel;

        // Buscar o crear ejercicio dentro del entrenamiento
        $ejercicio = $ejercicioModel
            ->where('entrenamiento_id', $entrenamientoId)
            ->where('ejercicio_id', $ejercicioId)
            ->first();

        if (!$ejercicio) {
            $maxOrden = $ejercicioModel->selectMax('orden')
                ->where('entrenamiento_id', $entrenamientoId)->first();
            $ordenEj = (int) ($maxOrden['orden'] ?? 0) + 1;

            $entrenamientoEjercicioId = $ejercicioModel->insert([
                'entrenamiento_id' => $entrenamientoId,
                'ejercicio_id'     => $ejercicioId,
                'orden'            => $ordenEj,
            ]);
        } else {
            $entrenamientoEjercicioId = $ejercicio['id'];
        }

        $eeRow = $ejercicioModel->find($entrenamientoEjercicioId);

        // orden de serie
        $serieModel = $this->seriesModel;
        $maxSerie = $serieModel->selectMax('orden')
            ->where('entrenamiento_ejercicio_id', $entrenamientoEjercicioId)->first();
        $ordenSerie = (int) ($maxSerie['orden'] ?? 0) + 1;

        $rpe  = $req->getPost('rpe');
        $nota = $req->getPost('nota');
        $rpeLimpio = ($rpe !== '' && $rpe !== null) ? (int) $rpe : null;

        $serieId = $serieModel->insert([
            'entrenamiento_ejercicio_id' => $entrenamientoEjercicioId,
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
                'ee_id'        => (int) $entrenamientoEjercicioId,
                'id'           => $ejercicioId,
                'nombre'       => $ejercicioInfo['nombre'] ?? ('Ejercicio #' . $ejercicioId),
                'grupo_nombre' => $this->grupoNombre($ejercicioInfo['grupo_muscular'] ?? null),
                'orden'        => (int) $eeRow['orden'],
            ],
        ]);
    }

    /**
     * Último peso/reps/series registrados para un ejercicio (de cualquier
     * entrenamiento), usado para pre-rellenar el registro rápido.
     */
    public function ultimoValor($ejercicioId)
    {
        $db = \Config\Database::connect();

        $row = $db->table('gimnasio_series s')
            ->select('s.series, s.repeticiones, s.peso, s.rpe')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->where('ee.ejercicio_id', $ejercicioId)
            ->orderBy('s.id', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        return $this->response->setJSON($row ?: new \stdClass());
    }

    /**
     * Intercambia el orden de un ejercicio dentro del entrenamiento con su
     * vecino inmediato (arriba o abajo), para poder corregir el orden en
     * el que se anotaron los ejercicios.
     */
    public function reordenarEjercicio()
    {
        $req = service('request');
        $eeId      = (int) $req->getPost('entrenamiento_ejercicio_id');
        $direction = $req->getPost('direction');

        $eeModel = $this->entrenamientoEjerciciosModel;
        $actual  = $eeModel->find($eeId);
        if (!$actual) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $lista = $eeModel->where('entrenamiento_id', $actual['entrenamiento_id'])
            ->orderBy('orden', 'ASC')->findAll();

        $index = null;
        foreach ($lista as $i => $row) {
            if ((int) $row['id'] === $eeId) {
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
        $eeModel->update($a['id'], ['orden' => $b['orden']]);
        $eeModel->update($b['id'], ['orden' => $a['orden']]);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Copia la lista de ejercicios de un entrenamiento anterior al actual,
     * junto con sus series (peso, repeticiones, rpe, nota), respetando el
     * orden. Así, como gran parte de la rutina se repite igual, el usuario
     * solo tiene que ajustar o borrar lo que cambie en vez de anotarlo todo
     * desde cero.
     */
    public function reutilizar($origenId)
    {
        $destinoId = (int) $this->request->getPost('entrenamiento_id');
        $origenId  = (int) $origenId;

        $destino = $this->entrenamientosModel->find($destinoId);
        $origen  = $this->entrenamientosModel->find($origenId);
        if (!$destino || !$origen) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $eeModel    = $this->entrenamientoEjerciciosModel;
        $serieModel = $this->seriesModel;

        $existentes = array_column(
            $eeModel->select('ejercicio_id')->where('entrenamiento_id', $destinoId)->findAll(),
            'ejercicio_id'
        );

        $origenEjercicios = $eeModel->where('entrenamiento_id', $origenId)
            ->orderBy('orden', 'ASC')->findAll();

        $maxOrden = $eeModel->selectMax('orden')->where('entrenamiento_id', $destinoId)->first();
        $siguienteOrden = (int) ($maxOrden['orden'] ?? 0) + 1;

        $añadidos = 0;
        $seriesCopiadas = 0;
        foreach ($origenEjercicios as $oe) {
            if (in_array($oe['ejercicio_id'], $existentes, true)) {
                continue;
            }

            $nuevoEeId = $eeModel->insert([
                'entrenamiento_id' => $destinoId,
                'ejercicio_id'     => $oe['ejercicio_id'],
                'orden'            => $siguienteOrden++,
            ]);
            $añadidos++;

            $seriesOrigen = $serieModel->where('entrenamiento_ejercicio_id', $oe['id'])
                ->orderBy('orden', 'ASC')->findAll();

            foreach ($seriesOrigen as $so) {
                $serieModel->insert([
                    'entrenamiento_ejercicio_id' => $nuevoEeId,
                    'series'        => $so['series'],
                    'repeticiones'  => $so['repeticiones'],
                    'peso'          => $so['peso'],
                    'rpe'           => $so['rpe'],
                    'nota'          => $so['nota'],
                    'orden'         => $so['orden'],
                ]);
                $seriesCopiadas++;
            }
        }

        $mensaje = $añadidos > 0
            ? "Se han añadido {$añadidos} ejercicios con {$seriesCopiadas} series de la rutina anterior. Ajusta o borra lo que cambie."
            : 'Esos ejercicios ya estaban en el entrenamiento de hoy.';

        return redirect()->to(site_url("gimnasio/entrenamientos/registro/{$destinoId}"))
            ->with('mensaje', $mensaje);
    }

    public function eliminarSerie($id)
    {
        $serie = $this->seriesModel->find($id);
        if (!$serie) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            }
            return redirect()->back()->with('error', 'Serie no encontrada');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $eeId = (int) $serie['entrenamiento_ejercicio_id'];

        // 1) Borrar
        $this->seriesModel->delete($id);

        // 2) Reordenar series restantes 1..n
        $resto = $this->seriesModel
            ->where('entrenamiento_ejercicio_id', $eeId)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $i = 1;
        foreach ($resto as $row) {
            if ((int) $row['orden'] !== $i) {
                $this->seriesModel->update($row['id'], ['orden' => $i]);
            }
            $i++;
        }

        // 3) Si ya no quedan series, borrar el ejercicio y reordenar ejercicios
        $ejercicioEliminado = false;
        if (empty($resto)) {
            $eeModel = $this->entrenamientoEjerciciosModel;
            $ee     = $eeModel->find($eeId);
            if ($ee) {
                $entrenamientoId = (int) $ee['entrenamiento_id'];
                $eeModel->delete($eeId);
                $ejercicioEliminado = true;

                // Reordenar ejercicios 1..n
                $ejercicios = $eeModel->where('entrenamiento_id', $entrenamientoId)
                    ->orderBy('orden', 'ASC')->findAll();
                $j = 1;
                foreach ($ejercicios as $e) {
                    if ((int) $e['orden'] !== $j) {
                        $eeModel->update($e['id'], ['orden' => $j]);
                    }
                    $j++;
                }
            }
        }

        $db->transComplete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'ejercicio_eliminado' => $ejercicioEliminado]);
        }

        return redirect()->back()->with('mensaje', 'Serie eliminada y orden actualizado');
    }


    public function actualizarSerie($id)
    {
        $serie = $this->seriesModel->find($id);
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

        $this->seriesModel->update($id, $data);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'serie' => array_merge(['id' => (int) $id], $data)]);
        }

        return redirect()->back()->with('mensaje', 'Serie actualizada');
    }

    public function resumen($id)
    {
        $db = \Config\Database::connect();

        // 1) Datos generales del entrenamiento
        $entrenamiento = $db->table('gimnasio_entrenamientos')
            ->select('id, fecha, notas_generales, lesiones, sin_molestias')
            ->where('id', $id)
            ->get()->getRowArray();

        if (!$entrenamiento) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Entrenamiento no encontrado');
        }

        // Entrenamiento
        $ent = $db->table('gimnasio_entrenamientos')->where('id', $id)->get()->getRowArray();
        if (!$ent) {
            return $this->response->setStatusCode(404)->setBody('<div class="p-3">No existe el entrenamiento.</div>');
        }

        // Totales globales
        $global = $db->table('gimnasio_series s')
            ->select('COUNT(*) AS registros,
                  SUM(s.series) AS total_series,
                  SUM(s.series * s.repeticiones) AS total_reps,
                  SUM(s.series * s.repeticiones * s.peso) AS total_volumen')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->where('ee.entrenamiento_id', $id)
            ->get()->getRowArray();

        // Resumen por ejercicio
        $porEjercicio = $db->table('gimnasio_series s')
            ->select('
        ge.id AS ejercicio_id,
        ge.nombre AS ejercicio,
        ee.orden AS orden_ejercicio,
        SUM(s.series) AS sets,
        SUM(s.series * s.repeticiones) AS reps,
        SUM(s.series * s.repeticiones * s.peso) AS volumen_total
    ')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
            ->where('ee.entrenamiento_id', $id)
            ->groupBy('ge.id, ge.nombre, ee.orden')
            ->orderBy('ee.orden', 'ASC')  // 👈 respetar orden del entreno
            ->get()->getResultArray();


        // Detalle de series por ejercicio (todas las series, ordenadas)
        $series = $db->table('gimnasio_series s')
            ->select('
        ge.id AS ejercicio_id,
        ge.nombre AS ejercicio,
        ee.orden AS orden_ejercicio,
        s.series, s.repeticiones, s.peso, s.rpe, s.nota, s.orden
    ')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
            ->where('ee.entrenamiento_id', $id)
            ->orderBy('ee.orden', 'ASC')  // 👈 primero orden de ejercicio
            ->orderBy('s.orden', 'ASC')   // luego orden de serie
            ->orderBy('s.id', 'ASC')
            ->get()->getResultArray();


        // Agrupar detalle por ejercicio_id
        $detallePorEj = [];
        foreach ($series as $row) {
            $detallePorEj[$row['ejercicio_id']][] = $row;
        }

        // Los 3 básicos hechos (o no) en esta sesión, para destacarlos arriba del todo
        $nombresBuscados = [
            'press banca' => ['press banca', 'press de banca'],
            'peso muerto' => ['peso muerto', 'deadlift'],
            'sentadillas' => ['sentadilla', 'sentadillas', 'back squat', 'squat'],
        ];
        $titulosBonitos = ['press banca' => 'Press banca', 'peso muerto' => 'Peso muerto', 'sentadillas' => 'Sentadillas'];

        $grandes = [];
        foreach ($nombresBuscados as $clave => $patrones) {
            $builder = $db->table('gimnasio_ejercicios');
            $builder->groupStart();
            foreach ($patrones as $p) {
                $builder->orLike('LOWER(nombre)', mb_strtolower($p, 'UTF-8'));
            }
            $builder->groupEnd();
            $ejRow = $builder->orderBy('id', 'ASC')->get()->getRowArray();

            $detalleSesion = $ejRow ? ($detallePorEj[$ejRow['id']] ?? []) : [];

            $mejor = null;
            foreach ($detalleSesion as $d) {
                $peso = (float) $d['peso'];
                $reps = (int) $d['repeticiones'];
                if ($peso <= 0 || $reps <= 0) {
                    continue;
                }
                $e1rm = $peso * (1 + $reps / 30);
                if (!$mejor || $e1rm > $mejor['e1rm']) {
                    $mejor = ['peso' => $peso, 'reps' => $reps, 'e1rm' => round($e1rm, 1)];
                }
            }

            $grandes[$clave] = [
                'titulo'   => $titulosBonitos[$clave],
                'hecho'    => $mejor !== null,
                'mejor'    => $mejor,
                'series'   => count($detalleSesion),
            ];
        }

        // Render HTML para el modal
        ob_start();
?>
        <div class="p-2">
            <div class="rs-grandes mb-3">
                <?php foreach ($grandes as $g): ?>
                    <div class="rs-grande <?= $g['hecho'] ? 'is-hecho' : '' ?>">
                        <span class="rs-grande-titulo"><?= esc($g['titulo']) ?></span>
                        <?php if ($g['hecho']): ?>
                            <span class="rs-grande-valor"><?= $g['mejor']['peso'] ?>kg × <?= $g['mejor']['reps'] ?></span>
                            <span class="rs-grande-sub">1RM est. <?= $g['mejor']['e1rm'] ?>kg<?= $g['series'] > 1 ? ' · ' . $g['series'] . ' series' : '' ?></span>
                        <?php else: ?>
                            <span class="rs-grande-valor rs-grande-vacio">—</span>
                            <span class="rs-grande-sub">No entrenado</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-2">
                <span class="badge bg-primary"><?= date('d/m/Y', strtotime($ent['fecha'])) ?></span>
                <?php if (!empty($ent['tipo_sesion'])): ?>
                    <span class="badge bg-warning text-dark">
                        <?= esc($ent['tipo_sesion']) ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Totales -->
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="small text-muted">Registros</div>
                        <div class="fw-bold"><?= (int)($global['registros'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="small text-muted">Series</div>
                        <div class="fw-bold"><?= (int)($global['total_series'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="small text-muted">Reps</div>
                        <div class="fw-bold"><?= (int)($global['total_reps'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-2 text-center">
                        <div class="small text-muted">Volumen</div>
                        <div class="fw-bold"><?= number_format((float)($global['total_volumen'] ?? 0), 0, ',', '.') ?> kg</div>
                    </div>
                </div>
            </div>

            <div class="mb-4 mt-3">
                <?php if (!empty($entrenamiento['notas_generales'])): ?>
                    <p><strong>📝 Notas:</strong> <?= esc($entrenamiento['notas_generales']) ?></p>
                <?php endif; ?>

                <?php if (!empty($entrenamiento['lesiones'])): ?>
                    <p><strong>⚠️ Lesiones:</strong> <?= esc($entrenamiento['lesiones']) ?></p>
                <?php endif; ?>

                <p>
                    <strong>✔ Sin molestias:</strong>
                    <?= $entrenamiento['sin_molestias'] ? 'Sí' : 'No' ?>
                </p>
            </div>

            <!-- Desglose de series por ejercicio (simple) -->
            <div class="mt-3">
                <?php foreach ($porEjercicio as $r): ?>
                    <?php
                    $ejId    = $r['ejercicio_id'];
                    $detalle = $detallePorEj[$ejId] ?? [];
                    ?>
                    <!-- Título del ejercicio -->
                    <h6 class="mb-2">
                        <?= esc($r['ejercicio']) ?>
                        <span class="text-muted small">— <?= (int)$r['sets'] ?> sets</span>
                    </h6>

                    <?php if ($detalle): ?>
                        <ul class="list-unstyled ms-2 mb-3">
                            <?php foreach ($detalle as $d): ?>
                                <?php
                                // Construcción de "3x10x80 kg"
                                $peso = (float)$d['peso'];
                                $pesoMostrar = '';
                                if ($peso > 0) {
                                    $pesoMostrar = (floor($peso) == $peso)
                                        ? (string)(int)$peso
                                        : rtrim(rtrim(number_format($peso, 3, '.', ''), '0'), '.');
                                    $pesoMostrar .= 'kg';
                                }
                                $linea = (int)$d['series'] . 'x' . (int)$d['repeticiones'] . ($pesoMostrar ? 'x' . $pesoMostrar : '');
                                ?>
                                <li class="mb-1">
                                    <span class="badge bg-light text-dark me-1"><?= $linea ?></span>
                                    <?php if ($d['rpe'] !== null && $d['rpe'] !== ''): ?>
                                        <span class="badge bg-secondary me-1">RPE <?= esc($d['rpe']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($d['nota'])): ?>
                                        <span class="text-muted">“<?= esc($d['nota']) ?>”</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-muted ms-2 mb-3">Sin series registradas.</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

        </div>
<?php
        $html = ob_get_clean();

        return $this->response->setContentType('text/html')->setBody($html);
    }
}
