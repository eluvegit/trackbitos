<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\GimnasioPlanesModel;
use App\Models\GimnasioPlanBloquesModel;
use App\Models\GimnasioPlanAsignacionesModel;

class GimnasioMesociclos extends BaseController
{
    protected GimnasioPlanesModel $planes;
    protected GimnasioPlanBloquesModel $bloques;
    protected GimnasioPlanAsignacionesModel $asign;

    public function __construct()
    {
        $this->planes = new GimnasioPlanesModel();
        $this->bloques = new GimnasioPlanBloquesModel();
        $this->asign = new GimnasioPlanAsignacionesModel();
        helper(['form', 'url']);
    }

    /** Helpers de cálculo **/
    protected function roundToStep(float $kg, float $step): float
    {
        return round($kg / $step) * $step;
    }

    protected function topKg(float $e1rm, float $pct, float $step): float
    {
        return $this->roundToStep($e1rm * $pct, $step);
    }

    protected function boKg(float $topKg, float $boPct, float $step): float
    {
        return $this->roundToStep($topKg * (1 + $boPct), $step);
    }

    /** Lista de planes */
    public function index()
    {
        $planes = $this->planes->orderBy('id', 'DESC')->findAll();

        foreach ($planes as &$p) {
            $bloques = $this->bloques
                ->where('plan_id', $p['id'])
                ->orderBy('orden', 'ASC')
                ->findAll();

            $hechos = [];
            $pend = [];
            foreach ($bloques as $b) {
                if (($b['estado'] ?? 'pendiente') === 'hecho') $hechos[] = $b;
                else $pend[] = $b;
            }

            usort($pend, fn($a, $b) => $a['orden'] <=> $b['orden']);
            $siguienteOrden = !empty($pend) ? (int)$pend[0]['orden'] : null;

            // Calcular progreso usando último lote
            if (empty($bloques)) {
                $pct = 0;
            } else {
                $maxLote = max(array_column($bloques, 'lote_id'));
                $bloquesLote = array_values(array_filter($bloques, fn($b) => $b['lote_id'] == $maxLote));
                usort($bloquesLote, fn($a, $b) => $a['orden'] <=> $b['orden']);
                $totalMeso = count($bloquesLote);
                $hechosEnMeso = count(array_filter($bloquesLote, fn($b) => ($b['estado'] ?? 'pendiente') === 'hecho'));
                $pct = $totalMeso > 0 ? round(($hechosEnMeso / $totalMeso) * 100) : 0;
            }

            $p['_pendientes'] = count($pend);
            $p['_hechos'] = count($hechos);
            $p['_progreso'] = $pct;
            $p['_siguiente'] = $siguienteOrden;
        }
        unset($p);

        return view('gimnasio/mesociclos/index', ['planes' => $planes]);
    }


    /** Crear plan nuevo */
    public function nuevo()
    {
        if ($this->request->getMethod() === 'POST') {
            $data = [
                'nombre'      => $this->request->getPost('nombre') ?? 'Mesociclo',
                'ejercicio'   => $this->request->getPost('ejercicio') ?? 'Sentadillas',
                'e1rm_base'   => (float)$this->request->getPost('e1rm_base'),
                'redondeo_kg' => (float)($this->request->getPost('redondeo_kg') ?? 2.5),
            ];
            $id = $this->planes->insert($data);
            return redirect()->to(site_url("gimnasio/mesociclos/{$id}"));
        }
        return view('gimnasio/mesociclos/nuevo');
    }

    /** Ver plan */
    public function ver(int $planId)
    {
        $plan = $this->planes->find($planId);
        if (!$plan) return redirect()->to(site_url('gimnasio/mesociclos'));

        $bloques = $this->bloques->byPlanOrdered($planId);
        $pendientes = [];
        $hechos = [];
        $step = (float)$plan['redondeo_kg'];

        foreach ($bloques as &$b) {
            $e1rmRef = isset($b['e1rm_snapshot']) && $b['e1rm_snapshot'] > 0
                ? (float)$b['e1rm_snapshot']
                : (float)$plan['e1rm_base'];

            $topMin = $this->topKg($e1rmRef, (float)$b['top_pct_min'], $step);
            $topMax = $b['top_pct_max'] ? $this->topKg($e1rmRef, (float)$b['top_pct_max'], $step) : null;
            $boMin  = $this->boKg($topMin, (float)$b['bo_pct_rel_top'], $step);
            $boMax  = $topMax ? $this->boKg($topMax, (float)$b['bo_pct_rel_top'], $step) : null;

            $b['_top_min'] = $topMin;
            $b['_top_max'] = $topMax;
            $b['_bo_min']  = $boMin;
            $b['_bo_max']  = $boMax;

            if (($b['estado'] ?? 'pendiente') !== 'hecho') $pendientes[] = $b;
            else $hechos[] = $b;
        }

        usort($pendientes, fn($a, $b) => $a['orden'] <=> $b['orden']);
        usort($hechos, fn($a, $b) => $b['orden'] <=> $a['orden']);

        $bloquesPendientes = count($pendientes);
        $siguienteOrden = $bloquesPendientes ? $pendientes[0]['orden'] : null;

        return view('gimnasio/mesociclos/ver', [
            'plan' => $plan,
            'pendientes' => $pendientes,
            'hechos' => $hechos,
            'bloquesPendientes' => $bloquesPendientes,
            'siguienteOrden' => $siguienteOrden,
        ]);
    }




    /** Crear/editar/borrar bloques (solo el “nuevo” como ejemplo) */
    public function bloqueNuevo(int $planId)
    {
        $plan = $this->planes->find($planId);

        if ($this->request->getMethod() === 'POST') {
            $data = [
                'plan_id'        => $planId,
                'orden'          => (int)$this->request->getPost('orden'),
                'bloque_tipo'    => $this->request->getPost('bloque_tipo'),
                'top_pct_min'    => (float)$this->request->getPost('top_pct_min'),
                'top_pct_max'    => $this->request->getPost('top_pct_max') !== '' ? (float)$this->request->getPost('top_pct_max') : null,
                'top_reps_min'   => (int)$this->request->getPost('top_reps_min'),
                'top_reps_max'   => $this->request->getPost('top_reps_max') !== '' ? (int)$this->request->getPost('top_reps_max') : null,
                'bo_pct_rel_top' => (float)$this->request->getPost('bo_pct_rel_top'),
                'bo_sets'        => (int)$this->request->getPost('bo_sets'),
                'bo_reps'        => (int)$this->request->getPost('bo_reps'),
                'notas'          => $this->request->getPost('notas') ?: null,
            ];
            $this->bloques->insert($data);
            return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"));
        }

        return view('gimnasio/mesociclos/bloque_nuevo', compact('plan'));
    }

    /** Vista simplificada “lo que toca” */
    public function simplificado(int $planId)
    {
        $plan = $this->planes->find($planId);

        $bloques = $this->bloques->byPlanOrdered($planId);
        $rows = [];
        $step = (float)$plan['redondeo_kg'];

        foreach ($bloques as $b) {
            $topPct = (float)$b['top_pct_max'] ?: (float)$b['top_pct_min']; // usar tope alto si existe
            $topKg  = $this->topKg((float)$plan['e1rm_base'], $topPct, $step);
            $boKg   = $this->boKg($topKg, (float)$b['bo_pct_rel_top'], $step);

            $reps   = $b['top_reps_max'] ?: $b['top_reps_min'];
            $rows[] = [
                'orden' => (int)$b['orden'],
                'top'   => "1 x {$reps}",
                'topKg' => $topKg,
                'bo'    => "{$b['bo_sets']} x {$b['bo_reps']}",
                'boKg'  => $boKg,
                'bloque_tipo' => $b['bloque_tipo'],
                'bloque_id'   => $b['id'],
            ];
        }

        return view('gimnasio/mesociclos/simplificado', compact('plan', 'rows'));
    }

    /** Asignar bloque a entrenamiento (stub simple) */
    public function asignar(int $planId, int $bloqueId)
    {
        $plan = $this->planes->find($planId);

        if ($this->request->getMethod() === 'POST') {
            $data = [
                'plan_id'   => $planId,
                'bloque_id' => $bloqueId,
                'entrenamiento_id' => $this->request->getPost('entrenamiento_id') ?: null,
                'fecha_prevista'   => $this->request->getPost('fecha_prevista') ?: null,
                'estado'           => $this->request->getPost('estado') ?: 'pendiente',
            ];
            $this->asign->insert($data);
            return redirect()->to(site_url("gimnasio/mesociclos/{$planId}/simplificado"));
        }

        return view('gimnasio/mesociclos/asignar', compact('planId', 'bloqueId'));
    }

    public function marcarHecho(int $bloqueId)
    {
        $bloque = $this->bloques->find($bloqueId);
        if (!$bloque) return redirect()->back()->with('error', 'Bloque no encontrado.');

        $planId = (int)$bloque['plan_id'];

        // calcular el siguiente que toca (mínimo orden pendiente)
        $siguiente = $this->bloques
            ->where('plan_id', $planId)
            ->where('estado !=', 'hecho')
            ->orderBy('orden', 'ASC')
            ->first();

        if (!$siguiente) {
            return redirect()->back()->with('error', 'No hay bloques pendientes que marcar.');
        }

        if ((int)$siguiente['id'] !== (int)$bloqueId) {
            return redirect()->back()->with('error', 'Solo puedes marcar como hecho el siguiente bloque en orden.');
        }

        // actualizar
        if ($this->bloques->update($bloqueId, ['estado' => 'hecho']) === false) {
            return redirect()->back()->with('error', 'No se pudo actualizar el bloque.');
        }
        return redirect()->back()->with('message', 'Bloque marcado como hecho.');
    }



    /** Generar nuevo lote */
    public function generar(int $planId)
    {
        $plan = $this->planes->find($planId);
        if (!$plan) return redirect()->to(site_url('gimnasio/mesociclos'))->with('error', 'Plan no encontrado.');

        $pendientes = $this->bloques
            ->where('plan_id', $planId)
            ->where('estado !=', 'hecho')
            ->countAllResults();

        if ($pendientes > 0) {
            return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"))
                ->with('error', 'Aún quedan bloques pendientes.');
        }

        $e1rmSnap = (float)$plan['e1rm_base'];
        $loteId = $this->nextLoteIdForPlan($planId);

        // Plantilla estándar
        $tpl = [
            ['push', 0.65, null, 4, 5,  -0.10, 3, 5,  'Reentrada'],
            ['push', 0.70, null, 3, 4,  -0.08, 4, 4,  'Control'],
            ['push', 0.75, null, 3, null, -0.08, 4, 3, 'Empuje'],
            ['stabilization', 0.70, null, 4, null, -0.10, 3, 4, 'Consolidar'],
            ['push', 0.77, null, 3, 4,  -0.08, 4, 4,  'Empuje 2'],
            ['push', 0.82, null, 2, 3,  -0.06, 3, 3,  'Carga alta'],
            ['deload', 0.60, null, 4, 5,  -0.15, 3, 5,  'Descarga'],
            ['pico', 0.93, 0.95, 1, 2,  -0.05, 3, 8,  'Pico'],
        ];

        $maxOrden = $this->bloques->where('plan_id', $planId)->selectMax('orden')->first()['orden'] ?? 0;
        $ordenBase = (int)$maxOrden;

        $rows = [];
        $i = 1;
        foreach ($tpl as [$tipo, $pctMin, $pctMax, $repsMin, $repsMax, $boPct, $boSets, $boReps, $notas]) {
            $rows[] = [
                'plan_id'        => $planId,
                'lote_id'        => $loteId,
                'e1rm_snapshot'  => $e1rmSnap,
                'orden'          => $ordenBase + $i,
                'bloque_tipo'    => $tipo,
                'top_pct_min'    => $pctMin,
                'top_pct_max'    => $pctMax,
                'top_reps_min'   => $repsMin,
                'top_reps_max'   => $repsMax,
                'bo_pct_rel_top' => $boPct,
                'bo_sets'        => $boSets,
                'bo_reps'        => $boReps,
                'notas'          => $notas,
                'estado'         => 'pendiente',
            ];
            $i++;
        }

        $this->bloques->insertBatch($rows);

        return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"))
            ->with('message', "Bloques generados (lote #{$loteId}, e1RM snapshot {$e1rmSnap} kg).");
    }


    // Helper opcional: estima e1RM con Epley y ajuste por RPE (muy simple)
    protected function estimateE1RM(float $weight, int $reps, ?float $rpe = null): float
    {
        // Epley base
        $e1rm = $weight * (1 + $reps / 30);
        // Ajuste lineal por RPE (si lo pasas). 10 = 100%, 9 ~ 96%, 8.5 ~ 92%, 8 ~ 89% aprox.
        if ($rpe !== null) {
            // mapa sencillo (puedes refinarlo)
            $map = [
                10.0 => 1.00,
                9.5 => 0.98,
                9.0 => 0.96,
                8.5  => 0.92,
                8.0 => 0.89,
                7.5 => 0.86,
                7.0 => 0.82
            ];
            // interpola groseramente
            $keys = array_keys($map);
            sort($keys);
            $factor = 0.96;
            foreach ($keys as $k) if ($rpe <= $k) {
                $factor = $map[$k];
                break;
            }
            $e1rm = $weight / $factor;
        }
        return round($e1rm, 1);
    }

    // GET: muestra formulario para ajustar e1RM antes de generar
    public function ajustar(int $planId)
    {
        $plan = $this->planes->find($planId);
        if (! $plan) return redirect()->to(site_url('gimnasio/mesociclos'))->with('error', 'Plan no encontrado.');

        // ¿Quedan bloques pendientes?
        $pendientes = $this->bloques
            ->where('plan_id', $planId)
            ->where('estado !=', 'hecho')
            ->countAllResults();

        if ($pendientes > 0) {
            return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"))
                ->with('error', 'Aún quedan bloques pendientes. Márcalos como "hecho" antes de generar más.');
        }

        // Sugerencia “por estado”: fatigado / justo / sobrado
        $e1rmActual = (float)$plan['e1rm_base'];
        $sugFatigado = $e1rmActual - 2.5;         // o -2% si prefieres
        $sugJusto    = $e1rmActual;               // igual
        $sugSobrado  = $e1rmActual + 2.5;         // o +3%

        return view('gimnasio/mesociclos/ajustar', [
            'plan'        => $plan,
            'sugFatigado' => $sugFatigado,
            'sugJusto'    => $sugJusto,
            'sugSobrado'  => $sugSobrado,
        ]);
    }

    // POST: recibe la decisión, actualiza e1RM y lanza generación
    public function ajustarPost(int $planId)
    {
        $plan = $this->planes->find($planId);
        if (! $plan) return redirect()->to(site_url('gimnasio/mesociclos'))->with('error', 'Plan no encontrado.');

        // seguridad: no permitir si hay pendientes
        $pendientes = $this->bloques
            ->where('plan_id', $planId)
            ->where('estado !=', 'hecho')
            ->countAllResults();
        if ($pendientes > 0) {
            return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"))->with('error', 'Quedan bloques pendientes.');
        }

        $modo = $this->request->getPost('modo'); // 'estado' | 'medicion' | 'manual'
        $e1rmNuevo = (float)$plan['e1rm_base'];

        if ($modo === 'estado') {
            $estado = $this->request->getPost('estado'); // 'fatigado'|'justo'|'sobrado'
            if ($estado === 'fatigado') $e1rmNuevo -= 2.5;          // o *0.98
            if ($estado === 'justo')    $e1rmNuevo += 0.0;
            if ($estado === 'sobrado')  $e1rmNuevo += 2.5;          // o *1.02..1.03
        } elseif ($modo === 'medicion') {
            $peso = (float)$this->request->getPost('peso');
            $reps = (int)$this->request->getPost('reps');
            $rpe  = $this->request->getPost('rpe') !== '' ? (float)$this->request->getPost('rpe') : null;
            if ($peso > 0 && $reps > 0) {
                $e1rmNuevo = $this->estimateE1RM($peso, $reps, $rpe);
            }
        } elseif ($modo === 'manual') {
            $val = (float)$this->request->getPost('e1rm_manual');
            if ($val > 0) $e1rmNuevo = $val;
        }

        // Redondea a múltiplos si quieres (2.5)
        $step = (float)($plan['redondeo_kg'] ?? 2.5);
        $e1rmNuevo = round($e1rmNuevo / $step) * $step;

        // Actualiza e1RM del plan
        $this->planes->update($planId, ['e1rm_base' => $e1rmNuevo]);

        // Redirige a generar
        return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"))->with('message', "e1RM actualizado a {$e1rmNuevo} kg. Ahora puedes generar el nuevo lote.");
    }

    // app/Controllers/GimnasioMesociclos.php

    public function generarBilbo(int $planId)
    {
        $plan = $this->planes->find($planId);
        if (!$plan) return redirect()->to(site_url('gimnasio/mesociclos'));

        // Bloques pendientes: no generamos si aún hay
        $pendientes = $this->bloques
            ->where('plan_id', $planId)
            ->where('estado !=', 'hecho')
            ->countAllResults();

        if ($this->request->getMethod() === 'get') {
            // Vista simple con botón “Generar” y knobs opcionales
            return view('gimnasio/mesociclos/generar_bilbo', [
                'plan' => $plan,
                'bloquesPendientes' => $pendientes,
                'defaults' => [
                    'bo_sets' => 3,
                    'bo_reps' => 8,
                    'pico_min' => 0.93,
                    'pico_max' => 0.95,
                    'pico_bo_rel' => -0.50,
                ]
            ]);
        }

        // POST: generar
        if ($pendientes > 0) {
            return redirect()->back()->with('error', 'Aún hay bloques pendientes. Márcalos como hechos antes de generar un nuevo mesociclo.');
        }

        $boSets = (int)($this->request->getPost('bo_sets') ?? 3);
        $boReps = (int)($this->request->getPost('bo_reps') ?? 8);
        $picoMin = (float)($this->request->getPost('pico_min') ?? 0.93);
        $picoMax = (float)($this->request->getPost('pico_max') ?? 0.95);
        $picoBo = (float)($this->request->getPost('pico_bo_rel') ?? -0.50);

        // Orden de arranque = siguiente al último existente
        $row = $this->bloques
            ->where('plan_id', $planId)
            ->selectMax('orden')
            ->get()
            ->getRowArray();
        $lastOrden = $row && isset($row['orden']) ? (int)$row['orden'] : 0;


        $spec = $this->specBilboBase($boSets, $boReps, $picoMin, $picoMax, $picoBo);

        // Inserción transaccional
        $db = \Config\Database::connect();
        $db->transStart();
        $orden = $lastOrden;


        $e1rmSnap = (float)$plan['e1rm_base'];
        $loteId   = $this->nextLoteIdForPlan($planId);


        foreach ($spec as $b) {
            $orden++;
            $row = [
                'plan_id'        => $planId,
                'orden'          => $orden,
                'plan_id'        => $planId,
                'lote_id'        => $loteId,      // << nuevo
                'e1rm_snapshot'  => $e1rmSnap,    // << nuevo
                'bloque_tipo'    => $b['tipo'],
                'top_pct_min'    => $b['min'],
                'top_pct_max'    => $b['max'],
                'top_reps_min'   => $b['rmin'],
                'top_reps_max'   => $b['rmax'],
                'bo_pct_rel_top' => $b['bo_rel'],
                'bo_sets'        => $b['bo_sets'],
                'bo_reps'        => $b['bo_reps'],
                'notas'          => $b['notas'],
                'estado'         => 'pendiente',
            ];
            $this->bloques->insert($row);
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'No se pudo generar el mesociclo Bilbo.');
        }

        return redirect()->to(site_url("gimnasio/mesociclos/{$planId}"))
            ->with('message', "Mesociclo Bilbo generado (lote #{$loteId}, e1RM snapshot {$e1rmSnap} kg).");
    }

    /**
     * Especificación base “Bilbo” según tu patrón:
     * 8 bloques push (0.45–0.68), deload (0.50–0.55), pico (0.93–0.95)
     * Back-off = 3x8 mismo peso (bo_rel=0.00), excepto pico (bo_rel=-0.50).
     */
    protected function specBilboBase(int $boSets, int $boReps, float $picoMin, float $picoMax, float $picoBo): array
    {
        $m = function (string $tipo, float $min, ?float $max, int $rmin, int $rmax, float $boRel, string $notas) use ($boSets, $boReps) {
            return [
                'tipo'    => $tipo,
                'min'     => $min,
                'max'     => $max,
                'rmin'    => $rmin,
                'rmax'    => $rmax,
                'bo_rel'  => $boRel,
                'bo_sets' => $boSets,
                'bo_reps' => $boReps,
                'notas'   => $notas,
            ];
        };

        $spec = [
            $m('push',   0.45, 0.50, 35, 50, 0.00, 'Reentrada'),
            $m('push',   0.51, 0.53, 32, 34, 0.00, 'Empuje'),
            $m('push',   0.54, 0.55, 28, 31, 0.00, 'Empuje'),
            $m('push',   0.56, 0.57, 25, 27, 0.00, 'Empuje'),
            $m('push',   0.58, 0.60, 22, 24, 0.00, 'Empuje'),
            $m('push',   0.61, 0.63, 19, 21, 0.00, 'Empuje'),
            $m('push',   0.64, 0.65, 16, 18, 0.00, 'Empuje'),
            $m('push',   0.66, 0.68, 14, 16, 0.00, 'Carga alta'),
            $m('deload', 0.50, 0.55, 25, 30, 0.00, 'Descarga'),
            $m('pico',   $picoMin, $picoMax, 3, 5, $picoBo, 'Pico'),
        ];

        return $spec;
    }
    protected function topKgWith(float $e1rm, float $pct, float $step): float
    {
        return $this->roundToStep($e1rm * $pct, $step);
    }
    protected function boKgWith(float $topKg, float $boPct, float $step): float
    {
        return $this->roundToStep($topKg * (1 + $boPct), $step);
    }

    /** Devuelve el próximo lote_id para un plan (max + 1; empieza en 1 si no hay) */
    protected function nextLoteIdForPlan(int $planId): int
    {
        $row = $this->bloques
            ->selectMax('lote_id')
            ->where('plan_id', $planId)
            ->get()->getRowArray();
        $max = isset($row['lote_id']) ? (int)$row['lote_id'] : 0;
        return $max > 0 ? $max + 1 : 1;
    }

    /** Calcula top/bo usando snapshot si viene (para hechos) o el e1RM actual si no */
    protected function computeKgForBlock(array $b, float $e1rmActual, float $step): array
    {
        // Para HECHOS: si hay snapshot úsalo; si no, usa actual (retrocompatible)
        // Para PENDIENTES: el llamante decidirá si pasa snapshot o actual.
        $e1rm = isset($b['e1rm_snapshot']) && $b['e1rm_snapshot'] > 0 ? (float)$b['e1rm_snapshot'] : $e1rmActual;

        $topMin = $this->topKg($e1rm, (float)$b['top_pct_min'], $step);
        $topMax = $b['top_pct_max'] ? $this->topKg($e1rm, (float)$b['top_pct_max'], $step) : null;
        $boMin  = $this->boKg($topMin, (float)$b['bo_pct_rel_top'], $step);
        $boMax  = $topMax ? $this->boKg($topMax, (float)$b['bo_pct_rel_top'], $step) : null;

        $b['_top_min'] = $topMin;
        $b['_top_max'] = $topMax;
        $b['_bo_min']  = $boMin;
        $b['_bo_max']  = $boMax;
        return $b;
    }
}
