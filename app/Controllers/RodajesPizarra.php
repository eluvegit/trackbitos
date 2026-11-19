<?php

namespace App\Controllers;

use App\Models\{RodajesProyectoModel, RodajesEscenaModel, RodajesEscenaImagenModel, RodajesPizarraOrdenModel, RodajesSetupModel, RodajesUbicacionModel};

/**
 * Pizarra de escenas: columnas por un campo de la escena y tarjetas que se
 * arrastran. Mover una tarjeta de columna cambia ese campo; el orden de las
 * tarjetas es propio de cada vista (la narrativa usa rodajes_escenas.orden,
 * el resto rodajes_pizarra_orden) para que reorganizar por ubicación no
 * descoloque la historia.
 *
 * En Producción cada tarjeta es una parte con estado propio: las escenas
 * «foto y vídeo» salen dos veces (una por parte). Su orden se guarda con la
 * clave de vista 'produccion' (columna toma_estado) o 'produccion_f'
 * (columna toma_estado_foto).
 */
class RodajesPizarra extends BaseController
{
    protected $helpers = ['rodajes'];

    /** vista => [título, icono, campo, nombre de la bandeja (valor vacío), columnas fijas] */
    public const VISTAS = [
        'narrativa'  => ['Narrativa', 'bi-book', 'escena_bloque', 'Sin bloque', []],
        // El plan de rodaje como tablero (franja · zona en columnas, setups dentro). Tiene su propia plantilla.
        'rodaje'     => ['Rodaje', 'bi-list-ol', 'franja', null, []],
        'produccion' => ['Producción', 'bi-clipboard-check', 'toma_estado', null, []],
    ];

    /** Clave de orden en Producción según la columna de estado de la parte. */
    private static function claveProduccion(string $columna): string
    {
        return $columna === 'toma_estado_foto' ? 'produccion_f' : 'produccion';
    }

    public function index($proyectoId, $vista = 'narrativa')
    {
        $pid      = (int) $proyectoId;
        $proyecto = (new RodajesProyectoModel())->find($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        if (!isset(self::VISTAS[$vista])) return redirect()->to(site_url("rodajes/$pid/pizarra"));
        RodajesFlujo::sincronizarBloques($pid);

        $escenas = self::conSetupNombre((new RodajesEscenaModel())->where('proyecto_id', $pid)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll());

        if ($vista === 'rodaje') {
            $setups = (new RodajesSetupModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
            return view('rodajes/pizarra/rodaje', [
                'proyecto'   => $proyecto,
                'vista'      => $vista,
                'vistas'     => self::VISTAS,
                'plan'       => RodajesFlujo::construirPlan($pid, RodajesFlujo::conVariantes($escenas)),
                'setups'     => $setups,
                'miniaturas' => $this->miniaturas(array_column($escenas, 'id')),
                'total'      => count($escenas),
            ]);
        }

        // Tarjetas: una por escena, o una por parte en Producción.
        $tarjetas = [];
        foreach ($escenas as $e) {
            if ($vista === 'produccion') {
                foreach (rod_partes($e) as $parte => $col) {
                    $tarjetas[] = ['e' => $e, 'parte' => (string) $parte, 'clave' => self::claveProduccion($col), 'estado' => ($e[$col] ?? null) ?: 'pendiente'];
                }
            } else {
                $tarjetas[] = ['e' => $e, 'parte' => null, 'clave' => $vista];
            }
        }

        $tarjetas = self::ordenar($vista, $tarjetas);

        $hayFormato = (bool) array_filter($escenas, static fn ($e) => !empty($e['formato']));

        return view('rodajes/pizarra/index', [
            'proyecto'   => $proyecto,
            'vista'      => $vista,
            'vistas'     => self::VISTAS,
            'columnas'   => self::columnas($vista, $tarjetas, $pid),
            'miniaturas' => $this->miniaturas(array_column($escenas, 'id')),
            'total'      => count($escenas),
            'hayFormato' => $hayFormato,
            'colores'    => $vista === 'produccion' ? [] : rod_colores($escenas, self::VISTAS[$vista][2]),
        ]);
    }

    /**
     * Guarda el tablero entero: {vista, columnas: [{valor, ids: ["12", "13:foto", ...]}, ...]}.
     * Asigna el campo de la vista a cada escena (o el estado a cada parte en
     * Producción) y renumera las posiciones.
     */
    public function guardar($proyectoId)
    {
        $pid   = (int) $proyectoId;
        $in    = $this->request->getJSON(true) ?? [];
        $vista = (string) ($in['vista'] ?? '');
        if ($vista === 'rodaje') return $this->guardarRodaje($pid, (array) ($in['listas'] ?? []));
        if (!isset(self::VISTAS[$vista]) || !is_array($in['columnas'] ?? null)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        $campo = self::VISTAS[$vista][2];

        $model  = new RodajesEscenaModel();
        $actual = [];
        foreach (self::conSetupNombre($model->where('proyecto_id', $pid)->findAll()) as $e) {
            $actual[(int) $e['id']] = $e;
        }

        // Columnas normalizadas: cada tarjeta = [id, columna donde va su valor].
        $columnas = [];
        $vistos   = [];
        foreach ($in['columnas'] as $c) {
            $valor = trim((string) ($c['valor'] ?? ''));
            if ($vista === 'produccion' && $valor !== '' && !in_array($valor, RodajesEscenaModel::TOMA_ESTADOS, true)) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            }
            $tarjetas = [];
            foreach ((array) ($c['ids'] ?? []) as $ref) {
                [$id, $parte] = array_pad(explode(':', (string) $ref, 2), 2, '');
                $id = (int) $id;
                if (!isset($actual[$id])) continue;
                $col = $vista === 'produccion' ? (rod_partes($actual[$id])[$parte] ?? 'toma_estado') : $campo;
                if (isset($vistos["$id:$col"])) continue;
                $vistos["$id:$col"] = true;
                $tarjetas[] = [$id, $col];
            }
            $columnas[] = ['valor' => mb_substr($valor, 0, 255), 'tarjetas' => $tarjetas];
        }
        // En la narrativa, la bandeja (sin bloque) va al final del orden de la historia.
        if ($vista === 'narrativa') {
            usort($columnas, static fn ($a, $b) => ($a['valor'] === '') <=> ($b['valor'] === ''));
        }

        $db = db_connect();
        $db->transStart();
        $model->skipValidation(true);
        $cambios    = [];
        $posiciones = [];
        $n = 0;
        foreach ($columnas as $c) {
            foreach ($c['tarjetas'] as [$id, $col]) {
                $n++;
                $previo = trim((string) ($actual[$id][$col] ?? ''));
                if ($previo !== $c['valor']) {
                    $cambios[$id][$col] = $c['valor'] === '' ? ($vista === 'produccion' ? null : '') : $c['valor'];
                    // Narrativa: el bloque es la temática; a «Sin bloque» = fuera de su temática.
                    if ($vista === 'narrativa' && $c['valor'] === '') $cambios[$id]['situacion_id'] = null;
                    if ($vista === 'ubicacion') {
                        $cambios[$id]['ubicacion_id'] = $c['valor'] === '' ? null : RodajesFlujo::zonaId($pid, $c['valor']);
                        $cambios[$id]['punto_id'] = null;
                    }
                    if ($vista === 'montaje') $cambios[$id] = self::datosSetup($pid, $c['valor']);
                    if ($vista === 'produccion') $cambios[$id][$col . '_at'] = $c['valor'] === '' ? null : date('Y-m-d H:i:s');
                }
                if ($vista === 'narrativa') {
                    if ((int) $actual[$id]['orden'] !== $n) $cambios[$id]['orden'] = $n;
                } else {
                    $clave = $vista === 'produccion' ? self::claveProduccion($col) : $vista;
                    $posiciones[$clave][] = ['escena_id' => $id, 'vista' => $clave, 'posicion' => $n];
                }
            }
        }
        foreach ($cambios as $id => $datos) $model->update($id, $datos);
        // Ubicación / montaje: el orden de las columnas es el orden de ruta de las zonas o el de los setups.
        if ($vista === 'ubicacion' || $vista === 'montaje') {
            $m = $vista === 'ubicacion' ? new RodajesUbicacionModel() : new RodajesSetupModel();
            $porNombre = [];
            foreach ($m->where('proyecto_id', $pid)->findAll() as $f) $porNombre[mb_strtolower(trim((string) $f['nombre']))] ??= (int) $f['id'];
            $i = 0;
            foreach ($columnas as $c) {
                $k = mb_strtolower($c['valor']);
                if ($k !== '' && isset($porNombre[$k])) { $m->update($porNombre[$k], ['orden' => $i++]); unset($porNombre[$k]); }
            }
        }
        $orden = new RodajesPizarraOrdenModel();
        foreach ($posiciones as $clave => $filas) {
            $orden->where('vista', $clave)->whereIn('escena_id', array_column($filas, 'escena_id'))->delete();
            $orden->insertBatch($filas);
        }
        $db->transComplete();
        if ($vista === 'narrativa') {
            RodajesFlujo::sincronizarBloques($pid);
            // El orden de las columnas es el de las temáticas (capítulos de la historia).
            $proyecto = (new RodajesProyectoModel())->find($pid);
            if (!empty($proyecto['sesion_id'])) {
                $sm = new \App\Models\SituacionModel();
                $porNombre = [];
                foreach ($sm->where('sesion_id', (int) $proyecto['sesion_id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $st) $porNombre[mb_strtolower(trim((string) $st['nombre']))] ??= (int) $st['id'];
                $i = 0;
                foreach ($columnas as $c) {
                    $k = mb_strtolower($c['valor']);
                    if ($k !== '' && isset($porNombre[$k])) { $sm->update($porNombre[$k], ['orden' => $i++]); unset($porNombre[$k]); }
                }
                foreach ($porNombre as $sid) $sm->update($sid, ['orden' => $i++]);
                RodajesFlujo::renumerarNarrativo($pid);
            }
        }

        return $this->response->setJSON(['ok' => $db->transStatus()]);
    }

    /**
     * Pizarra de rodaje: {listas: [{franja, zona, setup, ids: [...]}, ...]}. Cada lista
     * es un setup dentro de una columna franja · zona: la toma toma esos tres valores
     * y su posición en la lista es su orden de rodaje dentro del setup.
     */
    private function guardarRodaje(int $pid, array $listas)
    {
        $em = (new RodajesEscenaModel())->skipValidation(true);
        $actual = array_column($em->where('proyecto_id', $pid)->findAll(), null, 'id');
        $zonas  = array_column((new RodajesUbicacionModel())->where('proyecto_id', $pid)->findAll(), null, 'id');
        $setups = array_column((new RodajesSetupModel())->where('proyecto_id', $pid)->findAll(), null, 'id');
        $db = db_connect();
        $db->transStart();
        foreach ($listas as $l) {
            $franja = (string) ($l['franja'] ?? '');
            $franja = array_key_exists($franja, RodajesEscenaModel::FRANJAS) ? $franja : null;
            $zid = (int) ($l['zona'] ?? 0); $z = $zonas[$zid] ?? null;
            $sid = (int) ($l['setup'] ?? 0); $st = $setups[$sid] ?? null;
            foreach (array_values((array) ($l['ids'] ?? [])) as $n => $id) {
                $e = $actual[(int) $id] ?? null;
                if (!$e) continue;
                $d = ['orden_rodaje' => $n];
                if ($e['franja'] !== $franja) $d['franja'] = $franja;
                if ((int) $e['ubicacion_id'] !== (int) ($z['id'] ?? 0)) $d += ['ubicacion_id' => $z['id'] ?? null, 'escena_ubicacion' => $z['nombre'] ?? '', 'punto_id' => null];
                if ((int) $e['setup_id'] !== (int) ($st['id'] ?? 0)) $d += ['setup_id' => $st['id'] ?? null] + RodajesFlujo::camaraDeSetup($st);
                $em->update($e['id'], $d);
            }
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
        if ($valor !== '' && $vista === 'montaje') {
            $datos += self::datosSetup($pid, $valor);
        } elseif ($valor !== '') {
            $datos[self::VISTAS[$vista][2]] = $valor;
            if ($vista === 'produccion') $datos['toma_estado_at'] = date('Y-m-d H:i:s');
        }
        $id = (int) $model->insert($datos);
        if ($vista === 'narrativa' && $valor !== '') RodajesFlujo::sincronizarBloques($pid);
        $e  = self::conSetupNombre([$model->find($id)])[0];

        return $this->response->setJSON([
            'ok'   => true,
            'html' => view('rodajes/pizarra/_tarjeta', ['e' => $e, 'pid' => $pid, 'vista' => $vista, 'parte' => $vista === 'produccion' ? '' : null, 'mini' => null]),
        ]);
    }

    /**
     * Orden propio de la vista (lo que no tiene posición va detrás, en orden
     * narrativo). La narrativa ya viene ordenada por rodajes_escenas.orden.
     */
    private static function ordenar(string $vista, array $tarjetas): array
    {
        if ($vista === 'narrativa' || !$tarjetas) return $tarjetas;
        $pos   = [];
        $filas = (new RodajesPizarraOrdenModel())
            ->whereIn('vista', $vista === 'produccion' ? ['produccion', 'produccion_f'] : [$vista])
            ->whereIn('escena_id', array_unique(array_map(static fn ($t) => $t['e']['id'], $tarjetas)))->findAll();
        foreach ($filas as $f) $pos[$f['vista'] . ':' . $f['escena_id']] = (int) $f['posicion'];
        $clave = static fn ($t) => $pos[$t['clave'] . ':' . $t['e']['id']] ?? 1_000_000 + (int) $t['e']['orden'] * 2 + ($t['parte'] === 'video' ? 1 : 0);
        usort($tarjetas, static fn ($a, $b) => ($clave($a) <=> $clave($b)) ?: ($a['e']['id'] <=> $b['e']['id']));
        return $tarjetas;
    }

    /**
     * Escenas agrupadas como en la pizarra, para el plató: [['nombre', 'escenas'], ...].
     * Narrativa = tramos seguidos del mismo bloque en el orden de la historia;
     * ubicación / montaje = columnas de la pizarra en su orden, con la
     * bandeja («Sin ubicación», «Sin soporte») al final.
     *
     * @param array $escenas ya ordenadas por rodajes_escenas.orden
     */
    public static function grupos(array $escenas, string $vista): array
    {
        if ($vista === 'ubicacion' || $vista === 'montaje') {
            $escenas = self::conSetupNombre($escenas);
            $tarjetas = array_map(static fn ($e) => ['e' => $e, 'parte' => null, 'clave' => $vista], $escenas);
            $cols = self::columnas($vista, self::ordenar($vista, $tarjetas));
            $cols[] = array_shift($cols);   // la bandeja, al final
            $grupos = [];
            foreach ($cols as $c) {
                if ($c['tarjetas']) $grupos[] = ['nombre' => $c['nombre'], 'escenas' => array_column($c['tarjetas'], 'e')];
            }
            return $grupos;
        }

        $grupos = [];
        foreach ($escenas as $e) {
            $bloque = trim((string) $e['escena_bloque']);
            $ultimo = array_key_last($grupos);
            if ($ultimo === null || mb_strtolower($grupos[$ultimo]['nombre']) !== mb_strtolower($bloque)) {
                $grupos[] = ['nombre' => $bloque, 'escenas' => []];
                $ultimo = array_key_last($grupos);
            }
            $grupos[$ultimo]['escenas'][] = $e;
        }
        return $grupos;
    }

    /** Columnas de la vista en orden: [['valor', 'nombre', 'tarjetas', 'fija'], ...]. */
    private static function columnas(string $vista, array $tarjetas, ?int $pid = null): array
    {
        [, , $campo, $bandeja, $fijas] = self::VISTAS[$vista];

        if ($vista === 'produccion') {
            $cols = [];
            foreach (rod_toma_estados() as $k => [$txt]) {
                $cols[$k] = ['valor' => $k === 'pendiente' ? '' : $k, 'nombre' => $txt, 'tarjetas' => [], 'fija' => true];
            }
            foreach ($tarjetas as $t) {
                $cols[isset($cols[$t['estado']]) ? $t['estado'] : 'pendiente']['tarjetas'][] = $t;
            }
            return array_values($cols);
        }

        // La bandeja siempre primero; el resto, en el orden en que aparece su primera tarjeta.
        $cols = ['' => ['valor' => '', 'nombre' => $bandeja, 'tarjetas' => [], 'fija' => true]];
        foreach ($tarjetas as $t) {
            $valor = trim((string) $t['e'][$campo]);
            $clave = mb_strtolower($valor);
            $cols[$clave] ??= ['valor' => $valor, 'nombre' => $valor, 'tarjetas' => [], 'fija' => false];
            $cols[$clave]['tarjetas'][] = $t;
        }
        foreach ($fijas as $f) {
            $cols[mb_strtolower($f)] ??= ['valor' => $f, 'nombre' => $f, 'tarjetas' => [], 'fija' => false];
        }
        // Ubicación / montaje: las columnas siguen el orden de las zonas (ruta) o de los setups,
        // también las que aún están vacías; lo que no sea una zona, detrás.
        $pid ??= (int) ($tarjetas[0]['e']['proyecto_id'] ?? 0);
        if ($vista === 'narrativa' && $pid) {
            $proyecto = (new RodajesProyectoModel())->find($pid);
            if (!empty($proyecto['sesion_id'])) {
                $orden = ['' => $cols['']];
                foreach ((new \App\Models\SituacionModel())->where('sesion_id', (int) $proyecto['sesion_id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $st) {
                    $k = mb_strtolower(trim((string) $st['nombre']));
                    if ($k === '' || isset($orden[$k])) continue;
                    $orden[$k] = $cols[$k] ?? ['valor' => trim((string) $st['nombre']), 'nombre' => trim((string) $st['nombre']), 'tarjetas' => [], 'fija' => false];
                }
                $cols = $orden + $cols;
            }
        }
        if (($vista === 'ubicacion' || $vista === 'montaje') && $pid) {
            $orden = ['' => $cols['']];
            foreach (($vista === 'ubicacion' ? new RodajesUbicacionModel() : new RodajesSetupModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $z) {
                $k = mb_strtolower(trim((string) $z['nombre']));
                if ($k === '' || isset($orden[$k])) continue;
                $orden[$k] = $cols[$k] ?? ['valor' => trim((string) $z['nombre']), 'nombre' => trim((string) $z['nombre']), 'tarjetas' => [], 'fija' => false];
            }
            $cols = $orden + $cols;
        }
        return array_values($cols);
    }

    /** Añade a cada escena `setup_nombre` (el nombre de su setup) para agrupar el montaje por setup. */
    public static function conSetupNombre(array $escenas): array
    {
        if (!$escenas) return [];
        $ids = array_filter(array_map(static fn ($e) => (int) ($e['setup_id'] ?? 0), $escenas));
        $nombres = $ids ? array_column((new RodajesSetupModel())->whereIn('id', array_unique($ids))->findAll(), 'nombre', 'id') : [];
        foreach ($escenas as &$e) $e['setup_nombre'] = trim((string) ($nombres[(int) ($e['setup_id'] ?? 0)] ?? ''));
        return $escenas;
    }

    /** Datos de la toma al pasar a la columna del setup «$nombre» (se crea si no existe; '' = sin setup). */
    private static function datosSetup(int $pid, string $nombre): array
    {
        $nombre = trim($nombre);
        if ($nombre === '') return ['setup_id' => null];
        $st = null;
        foreach ((new RodajesSetupModel())->where('proyecto_id', $pid)->findAll() as $f) {
            if (mb_strtolower(trim((string) $f['nombre'])) === mb_strtolower($nombre)) { $st = $f; break; }
        }
        $st ??= (new RodajesSetupModel())->find(RodajesFlujo::nuevoSetup($pid, $nombre));
        return ['setup_id' => (int) $st['id']] + RodajesFlujo::camaraDeSetup($st);
    }

    /** Primera imagen de cada escena (o vídeo si no hay imágenes): [escena_id => ['ruta', 'video']]. */
    private function miniaturas(array $ids): array
    {
        if (!$ids) return [];
        $mapa = [];
        // La foto de la toma (su idea del moodboard o su primera imagen), como en el resto del flujo.
        foreach (RodajesFlujo::portadas(array_map(static fn ($id) => ['id' => $id], $ids)) as $eid => $ruta) {
            $mapa[(int) $eid] = ['ruta' => $ruta, 'video' => false];
        }
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
