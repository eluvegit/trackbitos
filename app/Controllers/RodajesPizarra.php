<?php

namespace App\Controllers;

use App\Models\{RodajesProyectoModel, RodajesEscenaModel, RodajesEscenaImagenModel, RodajesPizarraOrdenModel};

/**
 * Pizarra de escenas: columnas por un campo de la escena y tarjetas que se
 * arrastran. Mover una tarjeta de columna cambia ese campo; el orden de las
 * tarjetas es propio de cada vista (la narrativa usa rodajes_escenas.orden,
 * el resto rodajes_pizarra_orden) para que reorganizar por ubicación no
 * descoloque la historia.
 */
class RodajesPizarra extends BaseController
{
    protected $helpers = ['rodajes'];

    /** vista => [título, icono, campo, nombre de la bandeja (valor vacío), columnas fijas] */
    public const VISTAS = [
        'narrativa'  => ['Narrativa', 'bi-book', 'escena_bloque', 'Sin bloque', []],
        'ubicacion'  => ['Ubicación', 'bi-geo-alt', 'escena_ubicacion', 'Sin ubicación', []],
        'montaje'    => ['Montaje de cámara', 'bi-camera-reels', 'camara_soporte', 'Sin soporte', ['Trípode', 'Mano', 'Gimbal', 'Otro']],
        'produccion' => ['Producción', 'bi-clipboard-check', 'toma_estado', null, []],
    ];

    public function index($proyectoId, $vista = 'narrativa')
    {
        $pid      = (int) $proyectoId;
        $proyecto = (new RodajesProyectoModel())->find($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        if (!isset(self::VISTAS[$vista])) return redirect()->to(site_url("rodajes/$pid/pizarra"));

        $escenas = (new RodajesEscenaModel())->where('proyecto_id', $pid)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();

        // Orden propio de la vista (las escenas sin posición van detrás, en orden narrativo).
        if ($vista !== 'narrativa' && $escenas) {
            $pos = array_column(
                (new RodajesPizarraOrdenModel())->where('vista', $vista)->whereIn('escena_id', array_column($escenas, 'id'))->findAll(),
                'posicion', 'escena_id'
            );
            $clave = static fn ($e) => isset($pos[$e['id']]) ? (int) $pos[$e['id']] : 1_000_000 + (int) $e['orden'];
            usort($escenas, static fn ($a, $b) => ($clave($a) <=> $clave($b)) ?: ($a['id'] <=> $b['id']));
        }

        return view('rodajes/pizarra/index', [
            'proyecto'  => $proyecto,
            'vista'     => $vista,
            'vistas'    => self::VISTAS,
            'columnas'  => $this->columnas($vista, $escenas),
            'miniaturas' => $this->miniaturas(array_column($escenas, 'id')),
            'total'     => count($escenas),
        ]);
    }

    /**
     * Guarda el tablero entero: {vista, columnas: [{valor, ids: [...]}, ...]}.
     * Asigna el campo de la vista a cada escena y renumera las posiciones.
     */
    public function guardar($proyectoId)
    {
        $pid   = (int) $proyectoId;
        $in    = $this->request->getJSON(true) ?? [];
        $vista = (string) ($in['vista'] ?? '');
        if (!isset(self::VISTAS[$vista]) || !is_array($in['columnas'] ?? null)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        $campo = self::VISTAS[$vista][2];

        $model   = new RodajesEscenaModel();
        $actual  = [];
        foreach ($model->select("id, orden, $campo")->where('proyecto_id', $pid)->findAll() as $e) {
            $actual[(int) $e['id']] = $e;
        }

        // Columnas normalizadas; la bandeja (valor vacío) va al final de la secuencia narrativa.
        $columnas = [];
        $vistos   = [];
        foreach ($in['columnas'] as $c) {
            $valor = trim((string) ($c['valor'] ?? ''));
            if ($vista === 'produccion' && $valor !== '' && !in_array($valor, RodajesEscenaModel::TOMA_ESTADOS, true)) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            }
            $ids = [];
            foreach ((array) ($c['ids'] ?? []) as $id) {
                $id = (int) $id;
                if (isset($actual[$id]) && !isset($vistos[$id])) { $ids[] = $id; $vistos[$id] = true; }
            }
            $columnas[] = ['valor' => mb_substr($valor, 0, 255), 'ids' => $ids];
        }
        if ($vista === 'narrativa') {
            usort($columnas, static fn ($a, $b) => ($a['valor'] === '') <=> ($b['valor'] === ''));
        }

        $db = db_connect();
        $db->transStart();
        $model->skipValidation(true);
        $posiciones = [];
        $n = 0;
        foreach ($columnas as $c) {
            foreach ($c['ids'] as $id) {
                $n++;
                $datos = [];
                $previo = trim((string) ($actual[$id][$campo] ?? ''));
                if ($previo !== $c['valor']) {
                    $datos[$campo] = $c['valor'] === '' ? ($vista === 'produccion' ? null : '') : $c['valor'];
                    if ($vista === 'produccion') $datos['toma_estado_at'] = $c['valor'] === '' ? null : date('Y-m-d H:i:s');
                }
                if ($vista === 'narrativa') {
                    if ((int) $actual[$id]['orden'] !== $n) $datos['orden'] = $n;
                } else {
                    $posiciones[] = ['escena_id' => $id, 'vista' => $vista, 'posicion' => $n];
                }
                if ($datos) $model->update($id, $datos);
            }
        }
        if ($posiciones) {
            $orden = new RodajesPizarraOrdenModel();
            $orden->where('vista', $vista)->whereIn('escena_id', array_column($posiciones, 'escena_id'))->delete();
            $orden->insertBatch($posiciones);
        }
        $db->transComplete();

        return $this->response->setJSON(['ok' => $db->transStatus()]);
    }

    /** Escena rápida desde la cabecera de una columna: solo descripción. Devuelve la tarjeta. */
    public function escena($proyectoId)
    {
        $pid = (int) $proyectoId;
        if (!(new RodajesProyectoModel())->find($pid)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $in    = $this->request->getJSON(true) ?? [];
        $vista = (string) ($in['vista'] ?? '');
        $desc  = trim((string) ($in['descripcion'] ?? ''));
        $valor = trim((string) ($in['valor'] ?? ''));
        if (!isset(self::VISTAS[$vista]) || $desc === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        if ($vista === 'produccion' && $valor !== '' && !in_array($valor, RodajesEscenaModel::TOMA_ESTADOS, true)) $valor = '';

        $model  = new RodajesEscenaModel();
        $ultima = $model->select('orden')->where('proyecto_id', $pid)->orderBy('orden', 'DESC')->first();
        $datos  = [
            'proyecto_id'        => $pid,
            'escena_descripcion' => $desc,
            'orden'              => (int) ($ultima['orden'] ?? 0) + 1,
        ];
        if ($valor !== '') {
            $datos[self::VISTAS[$vista][2]] = $valor;
            if ($vista === 'produccion') $datos['toma_estado_at'] = date('Y-m-d H:i:s');
        }
        $id = (int) $model->insert($datos);

        return $this->response->setJSON([
            'ok'   => true,
            'html' => view('rodajes/pizarra/_tarjeta', ['e' => $model->find($id), 'pid' => $pid, 'vista' => $vista, 'mini' => null]),
        ]);
    }

    /** Columnas de la vista en orden: [['valor', 'nombre', 'escenas', 'fija'], ...]. */
    private function columnas(string $vista, array $escenas): array
    {
        [, , $campo, $bandeja, $fijas] = self::VISTAS[$vista];

        if ($vista === 'produccion') {
            $cols = [];
            foreach (rod_toma_estados() as $k => [$txt]) {
                $cols[$k] = ['valor' => $k === 'pendiente' ? '' : $k, 'nombre' => $txt, 'escenas' => [], 'fija' => true];
            }
            foreach ($escenas as $e) {
                $k = in_array($e['toma_estado'], RodajesEscenaModel::TOMA_ESTADOS, true) ? $e['toma_estado'] : 'pendiente';
                $cols[$k]['escenas'][] = $e;
            }
            return array_values($cols);
        }

        // La bandeja siempre primero; el resto, en el orden en que aparece su primera tarjeta.
        $cols = ['' => ['valor' => '', 'nombre' => $bandeja, 'escenas' => [], 'fija' => true]];
        foreach ($escenas as $e) {
            $valor = trim((string) $e[$campo]);
            $clave = mb_strtolower($valor);
            $cols[$clave] ??= ['valor' => $valor, 'nombre' => $valor, 'escenas' => [], 'fija' => false];
            $cols[$clave]['escenas'][] = $e;
        }
        foreach ($fijas as $f) {
            $cols[mb_strtolower($f)] ??= ['valor' => $f, 'nombre' => $f, 'escenas' => [], 'fija' => false];
        }
        return array_values($cols);
    }

    /** Primera imagen de cada escena (o vídeo si no hay imágenes): [escena_id => ['ruta', 'video']]. */
    private function miniaturas(array $ids): array
    {
        if (!$ids) return [];
        $mapa = [];
        foreach ((new RodajesEscenaImagenModel())->whereIn('escena_id', $ids)->orderBy('id', 'ASC')->findAll() as $f) {
            $eid   = (int) $f['escena_id'];
            $video = rod_es_video($f['ruta']);
            if (!isset($mapa[$eid]) || ($mapa[$eid]['video'] && !$video)) {
                $mapa[$eid] = ['ruta' => $f['ruta'], 'video' => $video];
            }
        }
        return $mapa;
    }
}
