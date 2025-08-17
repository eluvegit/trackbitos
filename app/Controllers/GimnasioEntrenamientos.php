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
        $this->entrenamientosModel           = new GimnasioEntrenamientosModel();
        $this->ejerciciosModel               = new GimnasioEjerciciosModel();
        $this->entrenamientoEjerciciosModel  = new GimnasioEntrenamientoEjerciciosModel();
        $this->seriesModel                   = new GimnasioSeriesModel();
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

        $model = new GimnasioEntrenamientosModel();
        $id = $model->insert(['fecha' => $fecha]);

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
        $entrenamientoModel = new \App\Models\GimnasioEntrenamientosModel();
        $entrenamiento = $entrenamientoModel->find($id);
        if (!$entrenamiento) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();

        // ⚠️ Si tu tabla de ejercicios se llama distinto, cámbiala aquí:
        $tablaEjercicios = 'gimnasio_ejercicios'; // columnas esperadas: id, nombre, grupo

        // Query: series + orden de ejercicio y nombres
        $builder = $db->table('gimnasio_series s')
            ->select('
        s.id, s.series, s.repeticiones, s.peso, s.orden,
        s.rpe, s.nota,                   
        ee.orden AS orden_ejercicio,
        ee.ejercicio_id,
        ge.nombre AS ejercicio_nombre,
        ge.grupo_muscular AS grupo_key
    ')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join($tablaEjercicios . ' ge', 'ge.id = ee.ejercicio_id', 'left')
            ->where('ee.entrenamiento_id', $id)
            ->orderBy('ee.orden', 'ASC')
            ->orderBy('s.orden', 'ASC');


        $seriesGuardadas = $builder->get()->getResultArray();

        // Mapa rápido de nombres de grupo (por si no quieres otro join)
        $mapGrupos = [
            'biceps' => 'Bíceps',
            'triceps' => 'Tríceps',
            'hombros' => 'Hombros',
            'espalda' => 'Espalda',
            'pecho' => 'Pecho',
            'abdominales' => 'Abdominales',
            'piernas' => 'Piernas',
            'maquinas' => 'Máquinas',
            'calentamientos' => 'Calentamientos',
            'movilidad' => 'Movilidad',
            'cardio' => 'Cardio',
            'especificos' => 'Específicos',
            'recuperacion' => 'Recuperación'
        ];

        // Dar forma a los campos esperados por la vista
        foreach ($seriesGuardadas as &$s) {
            $s['grupo_nombre']     = $mapGrupos[$s['grupo_key'] ?? ''] ?? ($s['grupo_key'] ?? '');
            $s['ejercicio_nombre'] = $s['ejercicio_nombre'] ?: ('Ejercicio #' . $s['ejercicio_id']);
            // opcional: si quieres mostrar orden de serie:
            $s['orden'] = (int)($s['orden'] ?? 0);
        }

        return view('gimnasio/entrenamientos/registro', [
            'entrenamiento'    => $entrenamiento,
            'fecha'            => $entrenamiento['fecha'],
            'entrenamiento_id' => $id,
            'seriesGuardadas'  => $seriesGuardadas,
        ]);
    }


    public function guardarSerie()
    {
        $req = service('request');

        $entrenamientoId = $req->getPost('entrenamiento_id');
        $ejercicioId     = $req->getPost('ejercicio_id');

        $ejercicioModel = new \App\Models\GimnasioEntrenamientoEjerciciosModel();

        // Buscar o crear ejercicio dentro del entrenamiento (sin rpe/nota aquí)
        $ejercicio = $ejercicioModel
            ->where('entrenamiento_id', $entrenamientoId)
            ->where('ejercicio_id', $ejercicioId)
            ->first();

        if (!$ejercicio) {
            // orden de ejercicio
            $maxOrden = $ejercicioModel->selectMax('orden')
                ->where('entrenamiento_id', $entrenamientoId)->first();
            $ordenEj = (int)($maxOrden['orden'] ?? 0) + 1;

            $entrenamientoEjercicioId = $ejercicioModel->insert([
                'entrenamiento_id' => $entrenamientoId,
                'ejercicio_id'     => $ejercicioId,
                'orden'            => $ordenEj,
                // opcional: guarda última rpe/nota resumen del ejercicio si quieres
                // 'rpe' => $req->getPost('rpe'),
                // 'nota_ejercicio' => $req->getPost('nota'),
            ]);
        } else {
            $entrenamientoEjercicioId = $ejercicio['id'];
        }

        // orden de serie
        $serieModel = new \App\Models\GimnasioSeriesModel();
        $maxSerie = $serieModel->selectMax('orden')
            ->where('entrenamiento_ejercicio_id', $entrenamientoEjercicioId)->first();
        $ordenSerie = (int)($maxSerie['orden'] ?? 0) + 1;

        // insertar serie con rpe/nota por serie
        $serieModel->insert([
            'entrenamiento_ejercicio_id' => $entrenamientoEjercicioId,
            'series'        => $req->getPost('series'),
            'repeticiones'  => $req->getPost('repeticiones'),
            'peso'          => $req->getPost('peso'),
            'rpe'           => $req->getPost('rpe') ?: null,
            'nota'          => $req->getPost('nota') ?: null,
            'orden'         => $ordenSerie,
        ]);

        return redirect()->back()->with('mensaje', 'Serie registrada correctamente');
    }

    public function eliminarSerie($id)
    {
        $serie = $this->seriesModel->find($id);
        if (!$serie) return redirect()->back()->with('error', 'Serie no encontrada');

        $db = \Config\Database::connect();
        $db->transStart();

        $eeId = (int)$serie['entrenamiento_ejercicio_id'];

        // 1) Borrar
        $this->seriesModel->delete($id);

        // 2) Reordenar series restantes 1..n
        $resto = $this->seriesModel
            ->where('entrenamiento_ejercicio_id', $eeId)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $i = 1;
        foreach ($resto as $row) {
            if ((int)$row['orden'] !== $i) {
                $this->seriesModel->update($row['id'], ['orden' => $i]);
            }
            $i++;
        }

        // 3) (Opcional) si ya no quedan series, borrar el ejercicio y reordenar ejercicios
        if (empty($resto)) {
            $eeModel = $this->entrenamientoEjerciciosModel;
            $ee     = $eeModel->find($eeId);
            if ($ee) {
                $entrenamientoId = (int)$ee['entrenamiento_id'];
                $eeModel->delete($eeId);

                // Reordenar ejercicios 1..n
                $ejercicios = $eeModel->where('entrenamiento_id', $entrenamientoId)
                    ->orderBy('orden', 'ASC')->findAll();
                $j = 1;
                foreach ($ejercicios as $e) {
                    if ((int)$e['orden'] !== $j) {
                        $eeModel->update($e['id'], ['orden' => $j]);
                    }
                    $j++;
                }
            }
        }

        $db->transComplete();

        return redirect()->back()->with('mensaje', 'Serie eliminada y orden actualizado');
    }


    public function actualizarSerie($id)
    {
        $serie = $this->seriesModel->find($id);
        if (!$serie) return redirect()->back()->with('error', 'Serie no encontrada');

        $data = [
            'series'       => (int)$this->request->getPost('series'),
            'repeticiones' => (int)$this->request->getPost('repeticiones'),
            'peso'         => (float)$this->request->getPost('peso'),
            'rpe'          => $this->request->getPost('rpe') !== '' ? (int)$this->request->getPost('rpe') : null,
            'nota'         => $this->request->getPost('nota') ?: null,
        ];

        $this->seriesModel->update($id, $data);
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

        // Render HTML para el modal
        ob_start();
?>
        <div class="p-2">
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
