<?php

namespace App\Controllers;

use App\Models\{MoodboardItemModel, RodajesEscenaModel, RodajesProyectoModel, RodajesPuntoModel, RodajesSetupModel, RodajesUbicacionModel, RodajesVarianteModel, SesionEquipoModel, SesionModel, SituacionModel};

class RodajesFlujo extends BaseController
{
    protected $helpers = ['rodajes'];

    public const PASOS = [
        'ideas'       => ['Ingesta', 'bi-inbox'],
        'tematicas'   => ['Temáticas', 'bi-collection'],
        'revisar'     => ['Revisar ideas', 'bi-check2-square'],
        'formato'     => ['Foto o vídeo', 'bi-camera-reels'],
        'ubicaciones' => ['Ubicaciones', 'bi-geo-alt'],
        'momento'     => ['Momento', 'bi-sun'],
        'setups'      => ['Setups', 'bi-camera'],
        'props'       => ['Props', 'bi-box-seam'],
        'plan'        => ['Plan', 'bi-list-ol'],
        'plato'       => ['Plató', 'bi-phone'],
    ];

    // ---------- ingesta ----------

    public function ideas($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $items = $sesion ? $this->items((int) $sesion['id']) : [];
        return view('rodajes/flujo/ideas', $this->base($proyecto, $sesion, 'ideas') + [
            'bandeja'   => array_reverse(array_values(array_filter($items, static fn ($i) => $i['situacion_id'] === null && $i['estado'] !== 'descartada'))),
            'agrupadas' => count(array_filter($items, static fn ($i) => $i['situacion_id'] !== null)),
            'sesiones'  => $sesion ? [] : (new SesionModel())->orderBy('actualizada_at', 'DESC')->findAll(),
        ]);
    }

    public function crearSesion($pid)
    {
        $proyecto = (new RodajesProyectoModel())->find((int) $pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $existente = (int) $this->request->getPost('sesion_id');
        if ($existente && (new SesionModel())->find($existente)) {
            $sid = $existente;
        } else {
            $m = new SesionModel();
            $m->insert(['titulo' => mb_substr($proyecto['titulo'], 0, 150), 'estado_foto' => 'idea', 'estado_video' => 'idea']);
            $sid = (int) $m->getInsertID();
        }
        (new RodajesProyectoModel())->update((int) $pid, ['sesion_id' => $sid]);
        return redirect()->to(site_url("rodajes/$pid/ideas"))->with('success', 'Sesión vinculada. Ya puedes echar ideas.');
    }

    public function ideasSubir($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$sesion) return redirect()->to(site_url("rodajes/$pid/ideas"));
        $sid   = (int) $sesion['id'];
        $dir   = "uploads/sesiones/$sid";
        $abs   = rtrim(FCPATH, '/') . '/' . $dir;
        if (!is_dir($abs)) @mkdir($abs, 0775, true);
        $model = new MoodboardItemModel();
        $orden = $model->where('sesion_id', $sid)->countAllResults();
        $n = 0;
        foreach ($this->request->getFileMultiple('archivo') ?? [] as $f) {
            if (!$f || !$f->isValid() || $f->hasMoved() || !str_starts_with((string) $f->getMimeType(), 'image/')) continue;
            $nombre = $f->getRandomName();
            $f->move($abs, $nombre);
            $model->insert(['sesion_id' => $sid, 'situacion_id' => null, 'origen' => 'archivo', 'ruta_archivo' => "$dir/$nombre", 'orden' => $orden++, 'estado' => 'pendiente']);
            $n++;
        }
        return redirect()->to(site_url("rodajes/$pid/ideas"))->with($n ? 'success' : 'error', $n ? "$n " . ($n === 1 ? 'imagen añadida' : 'imágenes añadidas') . ' a la bandeja.' : 'No se ha subido ninguna imagen válida.');
    }

    public function ideasTexto($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$sesion) return redirect()->to(site_url("rodajes/$pid/ideas"));
        $sid    = (int) $sesion['id'];
        $buscar = $this->request->getPost('buscar_ref') ? 1 : 0;
        $model  = new MoodboardItemModel();
        $orden  = $model->where('sesion_id', $sid)->countAllResults();
        $n = 0;
        foreach (preg_split('/\R/', (string) $this->request->getPost('texto')) as $linea) {
            $linea = trim($linea);
            if ($linea === '') continue;
            $esUrl = (bool) preg_match('~^https?://\S+$~i', $linea);
            $model->insert([
                'sesion_id' => $sid, 'situacion_id' => null, 'orden' => $orden++, 'estado' => 'pendiente',
                'origen' => $esUrl ? 'enlace' : 'texto', 'url_externa' => $esUrl ? mb_substr($linea, 0, 500) : null,
                'nota' => $esUrl ? null : mb_substr($linea, 0, 255), 'buscar_ref' => $esUrl ? 0 : $buscar,
            ]);
            $n++;
        }
        return redirect()->to(site_url("rodajes/$pid/ideas"))->with('success', $n ? "$n " . ($n === 1 ? 'idea añadida' : 'ideas añadidas') . ' a la bandeja.' : 'No había nada que añadir.');
    }

    // ---------- temáticas ----------

    public function tematicas($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        if (!$sesion) return redirect()->to(site_url("rodajes/$pid/ideas"));
        $items = $this->items((int) $sesion['id']);
        $por = ['' => []];
        foreach ($items as $i) $por[$i['situacion_id'] ?? ''][] = $i;
        // Títulos de las tomas a las que ya pertenecen las ideas (como toma o como variante), para agruparlas.
        $eids  = array_values(array_unique(array_filter(array_map(static fn ($i) => in_array($i['estado'], ['toma', 'variante'], true) ? (int) $i['escena_id'] : 0, $items))));
        $tomas = [];
        if ($eids) foreach ((new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->whereIn('id', $eids)->findAll() as $e) $tomas[(int) $e['id']] = rod_titulo_escena($e);
        return view('rodajes/flujo/tematicas', $this->base($proyecto, $sesion, 'tematicas') + [
            'situaciones' => (new SituacionModel())->where('sesion_id', $sesion['id'])->orderBy('orden', 'ASC')->findAll(),
            'porSit'      => $por,
            'tomas'       => $tomas,
        ]);
    }

    public function tematicasMover($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$sesion) return redirect()->to(site_url("rodajes/$pid/ideas"));
        $sid    = (int) $sesion['id'];
        $ids    = array_map('intval', (array) $this->request->getPost('ids'));
        $accion = (string) $this->request->getPost('accion');
        $volver = redirect()->to(site_url("rodajes/$pid/tematicas"));
        $model  = new MoodboardItemModel();

        if ($accion === 'nueva') {
            $nombre = trim((string) $this->request->getPost('nombre'));
            if ($nombre === '') return $volver->with('error', 'Escribe el nombre de la temática.');
            $sm = new SituacionModel();
            $sm->insert(['sesion_id' => $sid, 'nombre' => mb_substr($nombre, 0, 150), 'orden' => $sm->where('sesion_id', $sid)->countAllResults()]);
            $destino = (int) $sm->getInsertID();
            if ($ids) {
                $model->where('sesion_id', $sid)->whereIn('id', $ids)->set(['situacion_id' => $destino])->update();
                $this->tomasALaTematica((int) $pid, $sid, $ids, $destino);
            }
            return $volver->with('success', "Temática «{$nombre}» creada" . ($ids ? ' con ' . count($ids) . ' ' . (count($ids) === 1 ? 'idea' : 'ideas') : '') . '.');
        }
        if (!$ids) return $volver->with('error', 'Selecciona alguna idea primero.');

        if ($accion === 'mover') {
            $dest = (string) $this->request->getPost('destino');
            if ($dest === '') return $volver->with('error', 'Elige a qué temática moverlas.');
            $destino = $dest === '0' ? null : (int) $dest;
            if ($destino && !(new SituacionModel())->where('sesion_id', $sid)->find($destino)) return $volver;
            $model->where('sesion_id', $sid)->whereIn('id', $ids)->set(['situacion_id' => $destino])->update();
            $this->tomasALaTematica((int) $pid, $sid, $ids, $destino);
            return $volver->with('success', count($ids) . ' movidas.');
        }
        if ($accion === 'descartar' || $accion === 'recuperar') {
            $model->where('sesion_id', $sid)->whereIn('id', $ids)->whereIn('estado', $accion === 'descartar' ? ['pendiente'] : ['descartada'])
                ->set(['estado' => $accion === 'descartar' ? 'descartada' : 'pendiente'])->update();
            return $volver->with('success', count($ids) . ($accion === 'descartar' ? ' descartadas.' : ' recuperadas.'));
        }
        return $volver;
    }

    public function tematicaGuardar($pid, $sitId)
    {
        [, $sesion] = $this->contexto($pid);
        $sm = new SituacionModel();
        $sit = $sesion ? $sm->where('sesion_id', $sesion['id'])->find((int) $sitId) : null;
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 150);
        if ($sit && $nombre !== '') {
            $sm->update($sit['id'], ['nombre' => $nombre]);
            // La temática es el bloque de sus tomas.
            (new RodajesEscenaModel())->skipValidation(true)->where('proyecto_id', (int) $pid)->where('situacion_id', $sit['id'])->set(['escena_bloque' => $nombre])->update();
        }
        return redirect()->to(site_url("rodajes/$pid/tematicas"))->with('success', 'Temática renombrada.');
    }

    public function tematicaBorrar($pid, $sitId)
    {
        [, $sesion] = $this->contexto($pid);
        $sm = new SituacionModel();
        $sit = $sesion ? $sm->where('sesion_id', $sesion['id'])->find((int) $sitId) : null;
        if (!$sit) return redirect()->to(site_url("rodajes/$pid/tematicas"));
        $n = (new MoodboardItemModel())->where('situacion_id', $sit['id'])->countAllResults();
        (new MoodboardItemModel())->where('situacion_id', $sit['id'])->set(['situacion_id' => null])->update();
        (new RodajesEscenaModel())->skipValidation(true)->where('situacion_id', $sit['id'])->set(['situacion_id' => null, 'escena_bloque' => ''])->update();
        $sm->delete($sit['id']);
        return redirect()->to(site_url("rodajes/$pid/tematicas"))->with('success', "Temática «{$sit['nombre']}» borrada" . ($n ? ($n === 1 ? ': su idea vuelve a la bandeja.' : ": sus $n ideas vuelven a la bandeja.") : '.'));
    }

    // ---------- revisar ----------

    public function revisar($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        if (!$sesion) return redirect()->to(site_url("rodajes/$pid/ideas"));
        $items = $this->items((int) $sesion['id']);
        $porId = array_column($items, null, 'id');
        $grupos = [];
        foreach ((new SituacionModel())->where('sesion_id', $sesion['id'])->orderBy('orden', 'ASC')->findAll() as $s) {
            $grupos[(int) $s['id']] = ['nombre' => $s['nombre'], 'items' => [], 'pend' => 0];
        }
        $grupos[0] = ['nombre' => 'Sin temática', 'items' => [], 'pend' => 0];
        foreach ($items as $i) {
            $g = (int) ($i['situacion_id'] ?? 0);
            if (!isset($grupos[$g])) $g = 0;
            $grupos[$g]['items'][] = $i;
            if ($i['estado'] === 'pendiente') $grupos[$g]['pend']++;
        }
        if (!$grupos[0]['items']) unset($grupos[0]);

        $sel = $this->request->getGet('tematica');
        if ($sel === null && ($idea = $porId[(int) $this->request->getGet('idea')] ?? null)) $sel = (int) ($idea['situacion_id'] ?? 0);
        $sel = $sel !== null && isset($grupos[(int) $sel]) ? (int) $sel : null;
        if ($sel === null) foreach ($grupos as $gid => $g) if ($g['pend']) { $sel = $gid; break; }
        $sel ??= array_key_first($grupos);

        $tomas = [];
        if ($sel !== null) {
            $its = $grupos[$sel]['items'];
            $ids = array_values(array_filter(array_map(static fn ($i) => (int) $i['escena_id'], $its)));
            $q = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->groupStart();
            if ($sel) $q->where('situacion_id', $sel);
            else $q->where('1 = 0', null, false);
            if ($ids) $q->orWhereIn('id', $ids);
            $tomas = self::conVariantes($q->groupEnd()->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll());
        }

        return view('rodajes/flujo/revisar', $this->base($proyecto, $sesion, 'revisar') + [
            'grupos'     => $grupos,
            'selId'      => $sel,
            'tomas'      => $tomas,
            'porId'      => $porId,
            'fotos'      => self::fotosDeTomas(array_column($tomas, 'id')),
            'pendientes' => count(array_filter($items, static fn ($i) => $i['estado'] === 'pendiente')),
        ]);
    }

    public function revisarAccion($pid, $itemId)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$sesion) return redirect()->to(site_url("rodajes/$pid/ideas"));
        $model = new MoodboardItemModel();
        $item  = $model->where('sesion_id', $sesion['id'])->find((int) $itemId);
        if (!$item) return redirect()->to(site_url("rodajes/$pid/revisar"));
        $destino = (string) ($this->request->getPost('destino') ?? $this->request->getPost('accion'));
        $texto   = mb_substr(trim((string) $this->request->getPost('texto')), 0, 255);
        $volver  = site_url("rodajes/$pid/revisar?tematica=" . (int) ($item['situacion_id'] ?? 0));
        $em = new RodajesEscenaModel();

        if ($destino === 'toma' && $item['estado'] === 'pendiente') {
            $ultima = $em->select('orden')->where('proyecto_id', (int) $pid)->orderBy('orden', 'DESC')->first();
            $eid = (int) $em->insert([
                'proyecto_id' => (int) $pid, 'escena_descripcion' => $texto ?: (rod_texto_idea($item) ?: 'Toma nueva'),
                'situacion_id' => $item['situacion_id'], 'prioridad' => 'imp', 'orden' => (int) ($ultima['orden'] ?? 0) + 1,
                'escena_bloque' => $item['situacion_id'] ? (string) ((new SituacionModel())->find((int) $item['situacion_id'])['nombre'] ?? '') : '',
            ]);
            $model->update($item['id'], ['estado' => 'toma', 'escena_id' => $eid]);
            return redirect()->to($volver . "#t$eid");
        }
        if (str_starts_with($destino, 'v:') && $item['estado'] === 'pendiente') {
            $escena = $em->where('proyecto_id', (int) $pid)->find((int) substr($destino, 2));
            if ($escena) {
                $vid = $this->nuevaVariante((int) $escena['id'], $texto ?: (rod_texto_idea($item) ?: 'Variante'), (int) $item['id']);
                $model->update($item['id'], ['estado' => 'variante', 'escena_id' => $escena['id'], 'variante_id' => $vid]);
                return redirect()->to($volver . '#t' . $escena['id']);
            }
        }
        if ($destino === 'descartar' && $item['estado'] === 'pendiente') {
            $model->update($item['id'], ['estado' => 'descartada']);
        }
        // Foto por asignar a otra temática (m:0 = sin temática).
        $mover = (string) $this->request->getPost('mover');
        if ($destino === '' && str_starts_with($mover, 'm:') && $item['estado'] === 'pendiente') {
            $sit = (int) substr($mover, 2);
            if ($sit && !(new SituacionModel())->where('sesion_id', $sesion['id'])->find($sit)) return redirect()->to($volver . '#fotos');
            $model->update($item['id'], ['situacion_id' => $sit ?: null]);
            return redirect()->to($volver . '#fotos')->with('success', 'Foto movida de temática.');
        }
        if ($destino === 'deshacer') {
            $eid = (int) $item['escena_id'];
            if ($item['estado'] === 'variante' && $item['variante_id']) (new RodajesVarianteModel())->delete((int) $item['variante_id']);
            $model->update($item['id'], ['estado' => 'pendiente', 'escena_id' => null, 'variante_id' => null]);
            // Soltar la foto principal: si la toma se queda sin nada (ni fotos, ni variantes, ni zona/franja/setup), sobra.
            if ($item['estado'] === 'toma' && $eid) {
                $e = $em->where('proyecto_id', (int) $pid)->find($eid);
                $vacia = $e && !$e['ubicacion_id'] && !$e['franja'] && !$e['setup_id']
                    && !$model->where('escena_id', $eid)->countAllResults()
                    && !(new RodajesVarianteModel())->where('escena_id', $eid)->countAllResults();
                if ($vacia) $em->delete($eid);
            }
        }
        return redirect()->to($volver . '#fotos');
    }

    /** Variante sin foto, escrita desde la tarjeta de la toma. */
    public function varianteCrear($pid, $eid)
    {
        $e = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->find((int) $eid);
        $texto = mb_substr(trim((string) $this->request->getPost('texto')), 0, 255);
        if ($e && $texto !== '') $this->nuevaVariante((int) $e['id'], $texto, null);
        return redirect()->to(site_url("rodajes/$pid/revisar?tematica=" . (int) ($e['situacion_id'] ?? 0)) . '#t' . (int) $eid);
    }

    public function varianteBorrar($pid, $vid)
    {
        $vm = new RodajesVarianteModel();
        $v  = $vm->find((int) $vid);
        $e  = $v ? (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->find((int) $v['escena_id']) : null;
        if ($e) {
            $vm->delete($v['id']);
            if ($v['moodboard_item_id']) (new MoodboardItemModel())->update((int) $v['moodboard_item_id'], ['estado' => 'pendiente', 'escena_id' => null, 'variante_id' => null]);
        }
        return redirect()->to(site_url("rodajes/$pid/revisar?tematica=" . (int) ($e['situacion_id'] ?? 0)) . ($e ? '#t' . $e['id'] : ''));
    }

    /** La variante pasa a principal y la principal a variante: se intercambian texto, foto y estado de plató. */
    public function variantePrincipal($pid, $vid)
    {
        [$v, $e] = $this->varianteDe((int) $pid, (int) $vid);
        if (!$e) return redirect()->to(site_url("rodajes/$pid/revisar"));
        $mm = new MoodboardItemModel();
        $fotoT = $mm->where('escena_id', $e['id'])->where('estado', 'toma')->first();
        $fotoV = $v['moodboard_item_id'] ? $mm->find((int) $v['moodboard_item_id']) : null;
        $cols = ['toma_estado', 'toma_estado_at', 'toma_estado_foto', 'toma_estado_foto_at'];
        $db = db_connect();
        $db->transStart();
        (new RodajesEscenaModel())->skipValidation(true)->update($e['id'], ['escena_descripcion' => $v['texto']] + array_combine($cols, array_map(static fn ($c) => $v[$c], $cols)));
        (new RodajesVarianteModel())->update($v['id'], ['texto' => mb_substr(rod_titulo_escena($e), 0, 255), 'moodboard_item_id' => $fotoT['id'] ?? null] + array_combine($cols, array_map(static fn ($c) => $e[$c], $cols)));
        if ($fotoV) $mm->update($fotoV['id'], ['estado' => 'toma', 'variante_id' => null]);
        if ($fotoT) $mm->update($fotoT['id'], ['estado' => 'variante', 'variante_id' => $v['id']]);
        $db->transComplete();
        return redirect()->to($this->volverToma($pid, $e))->with('success', '«' . $v['texto'] . '» es ahora la principal.');
    }

    /** La variante se convierte en una toma independiente (hereda zona, franja, setup, formato y prioridad). */
    public function varianteSeparar($pid, $vid)
    {
        [$v, $e] = $this->varianteDe((int) $pid, (int) $vid);
        if (!$e) return redirect()->to(site_url("rodajes/$pid/revisar"));
        $em = new RodajesEscenaModel();
        $ultima = $em->select('orden')->where('proyecto_id', (int) $pid)->orderBy('orden', 'DESC')->first();
        $nuevo = ['proyecto_id' => (int) $pid, 'escena_descripcion' => $v['texto'], 'orden' => (int) ($ultima['orden'] ?? 0) + 1];
        foreach (['situacion_id', 'escena_bloque', 'ubicacion_id', 'escena_ubicacion', 'punto_id', 'setup_id', 'franja', 'formato', 'props'] as $c) $nuevo[$c] = $e[$c];
        foreach (['toma_estado', 'toma_estado_at', 'toma_estado_foto', 'toma_estado_foto_at'] as $c) $nuevo[$c] = $v[$c];
        $nuevo['prioridad'] = $v['prioridad'];
        $nuevo['props'] = ($p = rod_props_variante($e, $v)) ? json_encode($p, JSON_UNESCAPED_UNICODE) : null;
        $nid = (int) $em->insert($nuevo);
        if ($v['moodboard_item_id']) (new MoodboardItemModel())->update((int) $v['moodboard_item_id'], ['estado' => 'toma', 'escena_id' => $nid, 'variante_id' => null]);
        (new RodajesVarianteModel())->delete($v['id']);
        return redirect()->to($this->volverToma($pid, ['id' => $nid] + $e))->with('success', '«' . $v['texto'] . '» es ahora una toma propia.');
    }

    /** La toma entera pasa a variante de otra: su foto y sus variantes se van a la toma destino. */
    public function tomaAVariante($pid, $eid)
    {
        $em = new RodajesEscenaModel();
        $e = $em->where('proyecto_id', (int) $pid)->find((int) $eid);
        $d = $em->where('proyecto_id', (int) $pid)->find((int) $this->request->getPost('destino'));
        if (!$e || !$d || $e['id'] === $d['id']) return redirect()->to(site_url("rodajes/$pid/revisar"));
        $mm = new MoodboardItemModel();
        $vm = new RodajesVarianteModel();
        $db = db_connect();
        $db->transStart();
        $foto = $mm->where('escena_id', $e['id'])->where('estado', 'toma')->first();
        $vid = $this->nuevaVariante((int) $d['id'], mb_substr(rod_titulo_escena($e), 0, 255), $foto['id'] ?? null);
        $vm->update($vid, ['prioridad' => $e['prioridad'] ?: 'imp', 'props' => ($x = rod_props_extra($d, $e)) ? json_encode($x, JSON_UNESCAPED_UNICODE) : null]);
        if ($foto) $mm->update($foto['id'], ['estado' => 'variante', 'escena_id' => $d['id'], 'variante_id' => $vid]);
        $mm->where('escena_id', $e['id'])->where('estado', 'toma')->set(['escena_id' => $d['id'], 'estado' => 'variante'])->update();
        $orden = $vm->where('escena_id', $d['id'])->countAllResults();
        foreach ($vm->where('escena_id', $e['id'])->orderBy('orden', 'ASC')->findAll() as $v) {
            $vm->update($v['id'], ['escena_id' => $d['id'], 'orden' => $orden++]);
            if ($v['moodboard_item_id']) $mm->update((int) $v['moodboard_item_id'], ['escena_id' => $d['id']]);
        }
        $em->delete($e['id']);
        $db->transComplete();
        return redirect()->to($this->volverToma($pid, $d))->with('success', '«' . rod_titulo_escena($e) . '» es ahora variante de «' . rod_titulo_escena($d) . '».');
    }

    /** POST JSON {texto} */
    public function tomaTitulo($pid, $eid)
    {
        $em = new RodajesEscenaModel();
        $e = $em->where('proyecto_id', (int) $pid)->find((int) $eid);
        $t = $this->textoJson();
        if (!$e || $t === '') return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        $em->skipValidation(true)->update($e['id'], ['escena_descripcion' => $t]);
        return $this->response->setJSON(['ok' => true, 'texto' => $t]);
    }

    /** La toma entera (con sus fotos y variantes) pasa a otra temática; 0 = sin temática. */
    public function tomaTematica($pid, $eid)
    {
        [, $sesion] = $this->contexto($pid);
        $em = new RodajesEscenaModel();
        $e  = $sesion ? $em->where('proyecto_id', (int) $pid)->find((int) $eid) : null;
        $sit = (int) $this->request->getPost('situacion_id');
        $s  = $sit ? (new SituacionModel())->where('sesion_id', $sesion['id'] ?? 0)->find($sit) : null;
        if (!$e || ($sit && !$s)) return redirect()->to(site_url("rodajes/$pid/revisar"));
        $em->skipValidation(true)->update($e['id'], ['situacion_id' => $s ? $sit : null, 'escena_bloque' => $s['nombre'] ?? '']);
        (new MoodboardItemModel())->where('escena_id', $e['id'])->whereIn('estado', ['toma', 'variante'])->set(['situacion_id' => $s ? $sit : null])->update();
        return redirect()->to(site_url("rodajes/$pid/revisar?tematica=" . (int) ($e['situacion_id'] ?? 0)))
            ->with('success', '«' . rod_titulo_escena($e) . '» movida a ' . ($s ? '«' . $s['nombre'] . '»' : 'sin temática') . '.');
    }

    /** POST JSON {texto} */
    public function varianteTexto($pid, $vid)
    {
        [$v, $e] = $this->varianteDe((int) $pid, (int) $vid);
        $t = mb_substr($this->textoJson(), 0, 255);
        if (!$e || $t === '') return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        (new RodajesVarianteModel())->update($v['id'], ['texto' => $t]);
        return $this->response->setJSON(['ok' => true, 'texto' => $t]);
    }

    /** POST JSON {ids: [...]} — nuevo orden de las variantes de una toma (arrastrando). */
    public function variantesOrden($pid, $eid)
    {
        $e = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->find((int) $eid);
        $in = $this->request->getJSON(true) ?: $this->request->getPost();
        if (!$e || !is_array($in['ids'] ?? null)) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        $vm = new RodajesVarianteModel();
        $mias = array_map('intval', array_column($vm->select('id')->where('escena_id', $e['id'])->findAll(), 'id'));
        $orden = 0;
        foreach (array_map('intval', $in['ids']) as $vid) {
            if (in_array($vid, $mias, true)) $vm->update($vid, ['orden' => $orden++]);
        }
        return $this->response->setJSON(['ok' => true]);
    }

    private function textoJson(): string
    {
        $in = $this->request->getJSON(true) ?: $this->request->getPost();
        return trim((string) ($in['texto'] ?? ''));
    }

    private function varianteDe(int $pid, int $vid): array
    {
        $v = (new RodajesVarianteModel())->find($vid);
        $e = $v ? (new RodajesEscenaModel())->where('proyecto_id', $pid)->find((int) $v['escena_id']) : null;
        return [$v, $e];
    }

    private function volverToma($pid, array $e): string
    {
        return site_url("rodajes/$pid/revisar?tematica=" . (int) ($e['situacion_id'] ?? 0)) . '#t' . (int) $e['id'];
    }

    private function nuevaVariante(int $eid, string $texto, ?int $itemId): int
    {
        $vm = new RodajesVarianteModel();
        $vm->insert(['escena_id' => $eid, 'texto' => $texto, 'prioridad' => 'imp', 'moodboard_item_id' => $itemId, 'orden' => $vm->where('escena_id', $eid)->countAllResults()]);
        return (int) $vm->getInsertID();
    }

    /** [escena_id => [items de moodboard que son esa toma]] y ['v' => [moodboard_item_id => item]] para las variantes. */
    public static function fotosDeTomas(array $escenaIds): array
    {
        $out = ['t' => [], 'v' => []];
        if (!$escenaIds) return $out;
        foreach ((new MoodboardItemModel())->whereIn('escena_id', $escenaIds)->findAll() as $i) {
            if ($i['estado'] === 'toma') $out['t'][(int) $i['escena_id']][] = $i;
            else $out['v'][(int) $i['id']] = $i;
        }
        return $out;
    }

    /** Siguiente idea sin revisar: primero en la misma temática. */
    public function siguientePendiente(array $items, ?array $actual): ?array
    {
        $pend = array_values(array_filter($items, static fn ($i) => $i['estado'] === 'pendiente' && (!$actual || $i['id'] != $actual['id'])));
        if (!$pend) return null;
        foreach ($pend as $i) if ($actual && $i['situacion_id'] == $actual['situacion_id']) return $i;
        return $pend[0];
    }

    // ---------- ubicaciones ----------

    public function ubicaciones($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $zonas   = $this->zonas((int) $pid);
        $puntos  = $zonas ? (new RodajesPuntoModel())->whereIn('ubicacion_id', array_column($zonas, 'id'))->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() : [];
        $porZona = [];
        foreach ($puntos as $p) $porZona[(int) $p['ubicacion_id']][] = $p;
        $escenas = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll();
        return view('rodajes/flujo/ubicaciones', $this->base($proyecto, $sesion, 'ubicaciones') + [
            'zonas' => $zonas, 'puntos' => $porZona, 'escenas' => $escenas,
            'portadas' => self::portadas($escenas),
        ]);
    }

    public function ubicacionCrear($pid)
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        $zid = $nombre !== '' ? self::zonaId((int) $pid, $nombre) : null;
        // Desde la bandeja de asignar (fetch): devuelve el id para asignarle las tomas marcadas.
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode($zid ? 200 : 422)->setJSON(['ok' => (bool) $zid, 'id' => $zid]);
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones" . ($zid ? "#z$zid" : '')));
    }

    public function ubicacionGuardar($pid, $zid)
    {
        $m = new RodajesUbicacionModel();
        $z = $m->where('proyecto_id', (int) $pid)->find((int) $zid);
        if (!$z) return redirect()->to(site_url("rodajes/$pid/ubicaciones"));
        $nombre = trim((string) $this->request->getPost('nombre')) ?: $z['nombre'];
        $m->update($z['id'], ['nombre' => mb_substr($nombre, 0, 150), 'notas' => trim((string) $this->request->getPost('notas')) ?: null]);
        if ($nombre !== $z['nombre']) {
            (new RodajesEscenaModel())->skipValidation(true)->where('ubicacion_id', $z['id'])->set(['escena_ubicacion' => $nombre])->update();
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones#z" . $z['id']))->with('success', 'Zona guardada.');
    }

    public function ubicacionMover($pid, $zid)
    {
        $zonas = $this->zonas((int) $pid);
        $i = array_search((int) $zid, array_map('intval', array_column($zonas, 'id')), true);
        $j = $i === false ? false : $i + ((int) $this->request->getPost('dir') < 0 ? -1 : 1);
        if ($i !== false && isset($zonas[$j])) {
            [$zonas[$i], $zonas[$j]] = [$zonas[$j], $zonas[$i]];
            $m = new RodajesUbicacionModel();
            foreach ($zonas as $k => $z) if ((int) $z['orden'] !== $k) $m->update($z['id'], ['orden' => $k]);
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones#z" . (int) $zid));
    }

    public function ubicacionImagen($pid, $zid)
    {
        $m = new RodajesUbicacionModel();
        $z = $m->where('proyecto_id', (int) $pid)->find((int) $zid);
        $f = $this->request->getFile('imagen');
        if ($z && $f && $f->isValid() && !$f->hasMoved() && str_starts_with((string) $f->getMimeType(), 'image/')) {
            $dir = "images/rodajes/ubicaciones/$pid";
            $abs = rtrim(FCPATH, '/') . '/' . $dir;
            if (!is_dir($abs)) @mkdir($abs, 0775, true);
            $nombre = $f->getRandomName();
            $f->move($abs, $nombre);
            if ($z['imagen'] && is_file(FCPATH . $z['imagen'])) @unlink(FCPATH . $z['imagen']);
            $m->update($z['id'], ['imagen' => "$dir/$nombre"]);
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones#z" . (int) $zid));
    }

    public function ubicacionFusionar($pid, $zid)
    {
        $m = new RodajesUbicacionModel();
        $a = $m->where('proyecto_id', (int) $pid)->find((int) $zid);
        $b = $m->where('proyecto_id', (int) $pid)->find((int) $this->request->getPost('destino'));
        if (!$a || !$b || $a['id'] === $b['id']) return redirect()->to(site_url("rodajes/$pid/ubicaciones"));
        (new RodajesEscenaModel())->skipValidation(true)->where('ubicacion_id', $a['id'])->set(['ubicacion_id' => $b['id'], 'escena_ubicacion' => $b['nombre']])->update();
        (new RodajesPuntoModel())->where('ubicacion_id', $a['id'])->set(['ubicacion_id' => $b['id']])->update();
        if (!$b['notas'] && $a['notas']) $m->update($b['id'], ['notas' => $a['notas']]);
        if (!$b['imagen'] && $a['imagen']) $m->update($b['id'], ['imagen' => $a['imagen']]);
        $m->delete($a['id']);
        return redirect()->to(site_url("rodajes/$pid/ubicaciones#z" . $b['id']))->with('success', "«{$a['nombre']}» fusionada en «{$b['nombre']}».");
    }

    public function ubicacionBorrar($pid, $zid)
    {
        $m = new RodajesUbicacionModel();
        $z = $m->where('proyecto_id', (int) $pid)->find((int) $zid);
        if ($z && !(new RodajesEscenaModel())->where('ubicacion_id', $z['id'])->countAllResults()) {
            (new RodajesPuntoModel())->where('ubicacion_id', $z['id'])->delete();
            if ($z['imagen'] && is_file(FCPATH . $z['imagen'])) @unlink(FCPATH . $z['imagen']);
            $m->delete($z['id']);
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones"));
    }

    public function puntoCrear($pid, $zid)
    {
        $z = (new RodajesUbicacionModel())->where('proyecto_id', (int) $pid)->find((int) $zid);
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($z && $nombre !== '') {
            $x = $this->request->getPost('x');
            $y = $this->request->getPost('y');
            $pm = new RodajesPuntoModel();
            $pm->insert([
                'ubicacion_id' => $z['id'], 'nombre' => mb_substr($nombre, 0, 150),
                'x' => is_numeric($x) ? max(0, min(100, (float) $x)) : null, 'y' => is_numeric($y) ? max(0, min(100, (float) $y)) : null,
                'orden' => $pm->where('ubicacion_id', $z['id'])->countAllResults(),
            ]);
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones#z" . (int) $zid));
    }

    public function puntoBorrar($pid, $puntoId)
    {
        $p = (new RodajesPuntoModel())->find((int) $puntoId);
        $z = $p ? (new RodajesUbicacionModel())->where('proyecto_id', (int) $pid)->find((int) $p['ubicacion_id']) : null;
        if ($z) {
            (new RodajesEscenaModel())->skipValidation(true)->where('punto_id', $p['id'])->set(['punto_id' => null])->update();
            (new RodajesPuntoModel())->delete($p['id']);
        }
        return redirect()->to(site_url("rodajes/$pid/ubicaciones" . ($z ? '#z' . $z['id'] : '')));
    }

    /**
     * POST JSON {campo: ubicacion_id|punto_id|setup_id|franja, valor}.
     * franja admite las de FRANJAS, '' (sin momento) y 'dia_noche': la toma se
     * desdobla en dos (esta de día + una copia de noche).
     */
    public function asignar($pid, $eid)
    {
        $em = new RodajesEscenaModel();
        $e  = $em->where('proyecto_id', (int) $pid)->find((int) $eid);
        $in = $this->request->getJSON(true) ?: $this->request->getPost();
        $campo = (string) ($in['campo'] ?? '');
        if ($e && $campo === 'formato') {
            $f = (string) ($in['valor'] ?? '') ?: null;
            if ($f !== null && !array_key_exists($f, rod_formatos())) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            $em->skipValidation(true)->update($e['id'], ['formato' => $f] + rod_cambio_formato($e, $f));
            $vm = new RodajesVarianteModel();
            foreach ($vm->where('escena_id', $e['id'])->findAll() as $va) {
                $d = rod_cambio_formato(['formato' => $e['formato']] + $va, $f);
                if ($d) $vm->skipValidation(true)->update($va['id'], $d);
            }
            return $this->response->setJSON(['ok' => true]);
        }
        if ($e && $campo === 'franja') {
            $f = (string) ($in['valor'] ?? '');
            if ($f === 'dia_noche') {
                $nid = $this->desdoblarDiaNoche((int) $pid, $e);
                return $this->response->setJSON(['ok' => true, 'copia' => $nid]);
            }
            if ($f !== '' && !array_key_exists($f, RodajesEscenaModel::FRANJAS)) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            $em->skipValidation(true)->update($e['id'], ['franja' => $f ?: null, 'orden_rodaje' => null]);
            return $this->response->setJSON(['ok' => true]);
        }
        $valor = (int) ($in['valor'] ?? 0) ?: null;
        if (!$e || !in_array($campo, ['ubicacion_id', 'punto_id', 'setup_id'], true)) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        $datos = [$campo => $valor];
        if ($campo !== 'punto_id') $datos['orden_rodaje'] = null;   // cambia de grupo en el plan
        if ($campo === 'ubicacion_id') {
            $z = $valor ? (new RodajesUbicacionModel())->where('proyecto_id', (int) $pid)->find($valor) : null;
            $datos = ['ubicacion_id' => $z['id'] ?? null, 'escena_ubicacion' => $z['nombre'] ?? '', 'punto_id' => null];
        } elseif ($campo === 'punto_id' && $valor) {
            $p = (new RodajesPuntoModel())->find($valor);
            if (!$p || (int) $p['ubicacion_id'] !== (int) $e['ubicacion_id']) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        } elseif ($campo === 'setup_id' && $valor) {
            $st = (new RodajesSetupModel())->where('proyecto_id', (int) $pid)->find($valor);
            if (!$st) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            $datos += self::camaraDeSetup($st);
        }
        $em->skipValidation(true)->update($e['id'], $datos);
        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * «Día y noche»: la toma queda de día y se crea una copia de noche con los
     * mismos datos (zona, punto, setup, formato, cámara…) y sus variantes, sin
     * el estado del plató. La foto de referencia se copia como archivo propio
     * (borrar la de una no deja a la otra sin imagen). Devuelve el id nuevo.
     */
    private function desdoblarDiaNoche(int $pid, array $e): int
    {
        $em = new RodajesEscenaModel();
        $portada = self::portadas([$e])[(int) $e['id']] ?? null;
        // El insert del modelo ya descarta lo que no está en allowedFields.
        $sinCopiar = ['id', 'created_at', 'updated_at', 'deleted_at', 'proyecto_id', 'orden', 'franja',
            'toma_estado', 'toma_estado_at', 'toma_nota', 'toma_estado_foto', 'toma_estado_foto_at', 'orden_rodaje'];
        $nuevo = array_diff_key($e, array_flip($sinCopiar));
        $ultima = $em->select('orden')->where('proyecto_id', $pid)->orderBy('orden', 'DESC')->first();
        $nuevo += ['proyecto_id' => $pid, 'orden' => (int) ($ultima['orden'] ?? 0) + 1, 'franja' => 'noche'];

        $db = db_connect();
        $db->transStart();
        $em->skipValidation(true)->update($e['id'], ['franja' => 'dia']);
        $nid = (int) $em->skipValidation(true)->insert($nuevo);
        $vm = new RodajesVarianteModel();
        $mm = new MoodboardItemModel();
        // Las fotos (ideas del moodboard) de la toma y de sus variantes se clonan con archivo propio:
        // así la copia de noche tiene su foto en todas partes y borrar una no deja a la otra sin imagen.
        $clonar = function (array $i, array $cambios) use ($mm): void {
            $fila = array_intersect_key($i, array_flip(['sesion_id', 'situacion_id', 'origen', 'ruta_archivo', 'url_externa', 'nota', 'orden', 'buscar_ref', 'estado']));
            if ($i['origen'] === 'archivo' && $i['ruta_archivo'] && is_file(FCPATH . $i['ruta_archivo'])) {
                $destino = dirname($i['ruta_archivo']) . '/' . bin2hex(random_bytes(4)) . '_' . basename($i['ruta_archivo']);
                if (@copy(FCPATH . $i['ruta_archivo'], FCPATH . $destino)) $fila['ruta_archivo'] = $destino;
            }
            $mm->skipValidation(true)->insert($cambios + $fila);
        };
        $conFoto = false;
        foreach ($mm->where('escena_id', $e['id'])->where('estado', 'toma')->orderBy('id', 'ASC')->findAll() as $i) {
            $clonar($i, ['escena_id' => $nid, 'variante_id' => null]);
            $conFoto = true;
        }
        foreach ($vm->where('escena_id', $e['id'])->orderBy('orden', 'ASC')->findAll() as $v) {
            $vm->insert(['escena_id' => $nid, 'texto' => $v['texto'], 'prioridad' => $v['prioridad'], 'props' => $v['props'] ?? null, 'orden' => $v['orden']]);
            $nv = (int) $vm->getInsertID();
            $iv = $v['moodboard_item_id'] ? $mm->find((int) $v['moodboard_item_id']) : null;
            if ($iv) {
                $clonar($iv, ['escena_id' => $nid, 'variante_id' => $nv]);
                $vm->update($nv, ['moodboard_item_id' => (int) $mm->getInsertID()]);
            }
        }
        $db->transComplete();

        // Sin foto en el moodboard (toma creada a mano): se copia su primera imagen como referencia.
        if ($nid && !$conFoto && $portada && is_file(FCPATH . $portada)) {
            $dir = "images/rodajes/$nid";
            if (!is_dir(FCPATH . $dir)) @mkdir(FCPATH . $dir, 0775, true);
            $destino = $dir . '/' . bin2hex(random_bytes(6)) . '_' . basename($portada);
            if (@copy(FCPATH . $portada, FCPATH . $destino)) {
                (new \App\Models\RodajesEscenaImagenModel())->insert(['escena_id' => $nid, 'categoria' => 'inspiracion', 'ruta' => $destino, 'titulo' => 'Referencia (de la toma de día)']);
            }
        }
        return $nid;
    }

    // ---------- momento del día ----------

    public function formato($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $escenas = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll();
        return view('rodajes/flujo/formato', $this->base($proyecto, $sesion, 'formato') + [
            'escenas' => $escenas, 'zonas' => array_column($this->zonas((int) $pid), null, 'id'),
            'portadas' => self::portadas($escenas),
        ]);
    }

    public function momento($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $escenas = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll();
        return view('rodajes/flujo/momento', $this->base($proyecto, $sesion, 'momento') + [
            'escenas' => $escenas, 'zonas' => array_column($this->zonas((int) $pid), null, 'id'),
            'portadas' => self::portadas($escenas),
        ]);
    }

    // ---------- setups ----------

    public function setups($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $setups  = (new RodajesSetupModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        $escenas = (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll();
        return view('rodajes/flujo/setups', $this->base($proyecto, $sesion, 'setups') + [
            'setups' => $setups, 'escenas' => $escenas, 'zonas' => array_column($this->zonas((int) $pid), null, 'id'),
            'portadas' => self::portadas($escenas),
        ]);
    }

    /** Foto del montaje: la que distingue un setup de otro al elegirlo. */
    public function setupImagen($pid, $sid)
    {
        $m = new RodajesSetupModel();
        $s = $m->where('proyecto_id', (int) $pid)->find((int) $sid);
        $f = $this->request->getFile('imagen');
        if ($s && $f && $f->isValid() && !$f->hasMoved() && str_starts_with((string) $f->getMimeType(), 'image/')) {
            $dir = "images/rodajes/setups/$pid";
            $abs = rtrim(FCPATH, '/') . '/' . $dir;
            if (!is_dir($abs)) @mkdir($abs, 0775, true);
            $nombre = $f->getRandomName();
            $f->move($abs, $nombre);
            if (!empty($s['imagen']) && is_file(FCPATH . $s['imagen'])) @unlink(FCPATH . $s['imagen']);
            $m->update($s['id'], ['imagen' => "$dir/$nombre"]);
        }
        return redirect()->to(site_url("rodajes/$pid/setups#s" . (int) $sid));
    }

    /**
     * [escena_id => ruta de imagen] para reconocer cada toma a simple vista:
     * imagen subida a la escena → foto de la idea → foto de una variante.
     */
    public static function portadas(array $escenas): array
    {
        $ids = array_map('intval', array_column($escenas, 'id'));
        if (!$ids) return [];
        $out = [];
        foreach ((new \App\Models\RodajesEscenaImagenModel())->whereIn('escena_id', $ids)->orderBy('id', 'ASC')->findAll() as $f) {
            if (!rod_es_video((string) $f['ruta'])) $out[(int) $f['escena_id']] ??= $f['ruta'];
        }
        $fotos = self::fotosDeTomas($ids);
        foreach ($fotos['t'] as $eid => $items) {
            foreach ($items as $i) if ($i['origen'] === 'archivo' && $i['ruta_archivo']) { $out[$eid] ??= $i['ruta_archivo']; break; }
        }
        foreach ($fotos['v'] as $i) {
            if ($i['origen'] === 'archivo' && $i['ruta_archivo']) $out[(int) $i['escena_id']] ??= $i['ruta_archivo'];
        }
        return $out;
    }

    public function setupCrear($pid)
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre !== '') self::nuevoSetup((int) $pid, $nombre);
        return redirect()->to(site_url("rodajes/$pid/setups"));
    }

    public function setupGuardar($pid, $sid)
    {
        $m = new RodajesSetupModel();
        $s = $m->where('proyecto_id', (int) $pid)->find((int) $sid);
        if ($s) {
            $p = fn ($k, $n) => mb_substr(trim((string) $this->request->getPost($k)), 0, $n) ?: null;
            $m->update($s['id'], [
                'nombre' => $p('nombre', 150) ?? $s['nombre'], 'optica' => $p('optica', 60), 'soporte' => $p('soporte', 30),
                'luz' => $p('luz', 255), 'notas' => trim((string) $this->request->getPost('notas')) ?: null,
            ]);
            self::setupATomas((int) $s['id']); // óptica / soporte → cámara de sus tomas
        }
        return redirect()->to(site_url("rodajes/$pid/setups#s" . (int) $sid))->with('success', 'Setup guardado.');
    }

    public function setupBorrar($pid, $sid)
    {
        $m = new RodajesSetupModel();
        if ($m->where('proyecto_id', (int) $pid)->find((int) $sid) && !(new RodajesEscenaModel())->where('setup_id', (int) $sid)->countAllResults()) {
            $m->delete((int) $sid);
        }
        return redirect()->to(site_url("rodajes/$pid/setups"));
    }

    // ---------- plan ----------

    // ---------- props ----------

    public function props($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $escenas = self::conVariantes((new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll());
        return view('rodajes/flujo/props', $this->base($proyecto, $sesion, 'props') + [
            'escenas'  => $escenas,
            'lista'    => self::propsConocidos((int) $pid, $sesion ? (int) $sesion['id'] : null),
            'portadas' => self::portadas($escenas),
            'fotos'    => self::fotosDeTomas(array_column($escenas, 'id')),
        ]);
    }

    /** POST JSON {tipo: t|v, id, prop, on} — pone o quita un prop a una toma o a una variante (lo que lleva además de su toma). */
    public function propMarcar($pid)
    {
        $in   = $this->request->getJSON(true) ?: $this->request->getPost();
        $prop = mb_substr(trim((string) ($in['prop'] ?? '')), 0, 80);
        $id   = (int) ($in['id'] ?? 0);
        $on   = !empty($in['on']);
        $em   = new RodajesEscenaModel();
        if ($prop === '' || !$id) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        if (($in['tipo'] ?? '') === 'v') {
            $vm = new RodajesVarianteModel();
            $fila = $vm->find($id);
            $e = $fila ? $em->where('proyecto_id', (int) $pid)->find((int) $fila['escena_id']) : null;
            if (!$e) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            $vm->update($id, ['props' => self::propsCambiar(rod_props($fila), $prop, $on)]);
        } else {
            $e = $em->where('proyecto_id', (int) $pid)->find($id);
            if (!$e) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            $em->skipValidation(true)->update($id, ['props' => self::propsCambiar(rod_props($e), $prop, $on)]);
            // Si la toma ya lo lleva, sobra como extra en sus variantes.
            if ($on) {
                $vm = new RodajesVarianteModel();
                foreach ($vm->where('escena_id', $id)->findAll() as $v) {
                    $p = rod_props($v);
                    if (in_array(rod_normalizar($prop), array_map('rod_normalizar', $p), true)) $vm->update($v['id'], ['props' => self::propsCambiar($p, $prop, false)]);
                }
            }
        }
        if ($on) $this->propAlEquipo((int) $pid, $prop);
        return $this->response->setJSON(['ok' => true]);
    }

    /** POST {prop} — añade props (separados por comas) a la lista: el «Qué llevar» de la sesión. */
    public function propNuevo($pid)
    {
        foreach (explode(',', (string) $this->request->getPost('prop')) as $p) {
            $p = mb_substr(trim($p), 0, 80);
            if ($p !== '') $this->propAlEquipo((int) $pid, $p);
        }
        return redirect()->to(site_url("rodajes/$pid/props"));
    }

    /**
     * POST JSON — edición rápida desde el plató: título, setup, cámara, luz, notas,
     * props de la toma y extras de cada variante ({variantes: {id: [props]}}).
     */
    public function tomaRapida($pid, $eid)
    {
        $em = new RodajesEscenaModel();
        $e  = $em->where('proyecto_id', (int) $pid)->find((int) $eid);
        $in = $this->request->getJSON(true) ?: [];
        if (!$e) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        $txt = static fn ($k, $n = 255) => mb_substr(trim((string) ($in[$k] ?? '')), 0, $n);
        $limpiar = static function ($l): array {
            $out = [];
            foreach ((array) $l as $p) { $p = mb_substr(trim((string) $p), 0, 80); if ($p !== '') $out[rod_normalizar($p)] ??= $p; }
            return array_values($out);
        };
        $datos = [
            'escena_descripcion'        => $txt('titulo'),
            'camara_optica'             => $txt('optica', 100),
            'camara_soporte'            => $txt('soporte', 100),
            'camara_apertura'           => $txt('apertura', 50),
            'camara_velocidad'          => $txt('velocidad', 50),
            'camara_iso'                => $txt('iso', 50),
            'camara_fps'                => $txt('fps', 50),
            'camara_nd'                 => $txt('nd', 50),
            'plano_esquema_iluminacion' => $txt('luz', 2000),
            'plano_notas'               => $txt('notas', 5000),
        ];
        $sid = (int) ($in['setup_id'] ?? 0) ?: null;
        $st  = $sid ? (new RodajesSetupModel())->where('proyecto_id', (int) $pid)->find($sid) : null;
        $datos['setup_id'] = $st['id'] ?? null;
        // Setup nuevo: lo que se deje vacío de lente/soporte lo pone el setup.
        if ((int) ($st['id'] ?? 0) !== (int) $e['setup_id']) $datos['orden_rodaje'] = null;
        if ($st && (int) $st['id'] !== (int) $e['setup_id']) {
            foreach (self::camaraDeSetup($st) as $c => $v) if (($datos[$c] ?? '') === '') $datos[$c] = $v;
        }
        $props = $limpiar($in['props'] ?? []);
        $datos['props'] = $props ? json_encode($props, JSON_UNESCAPED_UNICODE) : null;
        $em->skipValidation(true)->update($e['id'], $datos);

        $vm = new RodajesVarianteModel();
        $deToma = array_map('rod_normalizar', $props);
        foreach ((array) ($in['variantes'] ?? []) as $vid => $lista) {
            $v = $vm->where('escena_id', $e['id'])->find((int) $vid);
            if (!$v) continue;
            $extra = array_values(array_filter($limpiar($lista), static fn ($p) => !in_array(rod_normalizar($p), $deToma, true)));
            $vm->update($v['id'], ['props' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null]);
        }
        foreach ($props as $p) $this->propAlEquipo((int) $pid, $p);
        return $this->response->setJSON(['ok' => true]);
    }

    private static function propsCambiar(array $props, string $prop, bool $on): ?string
    {
        $k = rod_normalizar($prop);
        $props = array_values(array_filter($props, static fn ($p) => rod_normalizar($p) !== $k));
        if ($on) $props[] = $prop;
        return $props ? json_encode($props, JSON_UNESCAPED_UNICODE) : null;
    }

    private function propAlEquipo(int $pid, string $prop): void
    {
        $proyecto = (new RodajesProyectoModel())->find($pid);
        if (!$proyecto || !$proyecto['sesion_id']) return;
        $eq = new SesionEquipoModel();
        $ya = array_map(static fn ($r) => rod_normalizar($r['item']), $eq->where('sesion_id', $proyecto['sesion_id'])->where('tipo', 'props')->findAll());
        if (!in_array(rod_normalizar($prop), $ya, true)) $eq->insert(['sesion_id' => $proyecto['sesion_id'], 'item' => $prop, 'tipo' => 'props', 'orden' => 999]);
    }

    public function plan($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        $escenas = self::conVariantes((new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll());
        $soloImp = (bool) $this->request->getGet('imp');
        return view('rodajes/flujo/plan', $this->base($proyecto, $sesion, 'plan') + [
            'plan'    => self::construirPlan((int) $pid, $escenas, $soloImp),
            'soloImp' => $soloImp,
            'puntos'  => $this->puntosDe((int) $pid),
            'fotos'   => self::fotosDeTomas(array_column($escenas, 'id')),
            'portadas' => self::portadas($escenas),
        ]);
    }

    /**
     * POST multipart {eid, vid?, foto} — foto para una toma o variante que no tiene
     * (desde el cuadrado vacío del Plan: subir, arrastrar o pegar). Si su idea era
     * solo texto o enlace, esa misma idea pasa a ser la foto (el texto queda de nota).
     */
    public function planFoto($pid)
    {
        [$proyecto, $sesion] = $this->contexto($pid);
        $e = $proyecto ? (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->find((int) $this->request->getPost('eid')) : null;
        $f = $this->request->getFile('foto');
        if (!$e || !$f || !$f->isValid() || $f->hasMoved() || !str_starts_with((string) $f->getMimeType(), 'image/')) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Imagen no válida']);
        }
        $vm = new RodajesVarianteModel();
        $vid = (int) $this->request->getPost('vid');
        $v = $vid ? $vm->where('escena_id', $e['id'])->find($vid) : null;
        if ($vid && !$v) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);

        if (!$sesion) {
            // Sin sesión no hay moodboard: la foto de la toma va a sus imágenes (las variantes necesitan sesión).
            if ($v) return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Vincula una sesión para poner fotos a las variantes']);
            $dir = 'images/rodajes/' . (int) $e['id'];
            if (!is_dir(FCPATH . $dir)) @mkdir(FCPATH . $dir, 0775, true);
            $nombre = $f->getRandomName();
            $f->move(FCPATH . $dir, $nombre);
            (new \App\Models\RodajesEscenaImagenModel())->insert(['escena_id' => $e['id'], 'categoria' => 'inspiracion', 'ruta' => "$dir/$nombre", 'titulo' => 'Foto de la toma']);
            return $this->response->setJSON(['ok' => true, 'url' => base_url("$dir/$nombre")]);
        }

        $sid = (int) $sesion['id'];
        $dir = "uploads/sesiones/$sid";
        if (!is_dir(FCPATH . $dir)) @mkdir(FCPATH . $dir, 0775, true);
        $nombre = $f->getRandomName();
        $f->move(FCPATH . $dir, $nombre);
        $ruta = "$dir/$nombre";

        $mm = new MoodboardItemModel();
        if ($v) {
            $it = $v['moodboard_item_id'] ? $mm->find((int) $v['moodboard_item_id']) : null;
        } else {
            $it = null;
            foreach ($mm->where('escena_id', $e['id'])->where('estado', 'toma')->orderBy('id', 'ASC')->findAll() as $i) {
                if ($i['origen'] !== 'archivo') { $it ??= $i; continue; }
                $it = null; break;   // ya tiene foto: se añade otra
            }
        }
        if ($it && $it['origen'] !== 'archivo') {
            $mm->skipValidation(true)->update($it['id'], ['origen' => 'archivo', 'ruta_archivo' => $ruta, 'nota' => $it['nota'] ?: ($it['url_externa'] ?? null)]);
        } else {
            $mm->skipValidation(true)->insert([
                'sesion_id' => $sid, 'situacion_id' => $e['situacion_id'] ?? null, 'origen' => 'archivo', 'ruta_archivo' => $ruta,
                'orden' => $mm->where('sesion_id', $sid)->countAllResults(), 'estado' => $v ? 'variante' : 'toma',
                'escena_id' => $e['id'], 'variante_id' => $v['id'] ?? null,
            ]);
            if ($v) $vm->update($v['id'], ['moodboard_item_id' => (int) $mm->getInsertID()]);
        }
        return $this->response->setJSON(['ok' => true, 'url' => base_url($ruta)]);
    }

    /** POST JSON {estado, parte} — estado de una variante en el plató. */
    public function varianteToma($pid, $vid)
    {
        $vm = new RodajesVarianteModel();
        $v  = $vm->find((int) $vid);
        $e  = $v ? (new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->find((int) $v['escena_id']) : null;
        if (!$e) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        $in = $this->request->getJSON(true) ?: $this->request->getPost();
        $estado = (string) ($in['estado'] ?? '');
        if ($estado !== '' && !in_array($estado, RodajesEscenaModel::TOMA_ESTADOS, true)) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        $col = rod_partes($e)[(string) ($in['parte'] ?? '')] ?? 'toma_estado';
        $vm->update($v['id'], [$col => $estado ?: null, $col . '_at' => $estado ? date('Y-m-d H:i:s') : null]);
        return $this->response->setJSON(['ok' => true]);
    }

    // ---------- piezas compartidas ----------

    /** Setups en el orden propio de la zona (orden_setups); los que no están en él, detrás en el orden general. */
    public static function setupsDeZona(array $setups, ?array $zona): array
    {
        $propio = $zona ? (json_decode((string) ($zona['orden_setups'] ?? ''), true) ?: []) : [];
        if (!$propio) return $setups;
        $pos = array_flip(array_map('intval', $propio));
        $base = array_flip(array_map('intval', array_column($setups, 'id')));
        usort($setups, static fn ($a, $b) => [$pos[(int) $a['id']] ?? PHP_INT_MAX, $base[(int) $a['id']]] <=> [$pos[(int) $b['id']] ?? PHP_INT_MAX, $base[(int) $b['id']]]);
        return $setups;
    }

    /** Sube/baja un montaje dentro de una zona del plan (entre los setups que tienen tomas en esa zona). */
    public function zonaSetupMover($pid, $zid)
    {
        $zm = new RodajesUbicacionModel();
        $z  = $zm->where('proyecto_id', (int) $pid)->find((int) $zid);
        $sid = (int) $this->request->getPost('setup_id');
        $volver = redirect()->to(site_url("rodajes/$pid/plan" . ($this->request->getPost('imp') ? '?imp=1' : '')) . '#z' . (int) $zid);
        if (!$z || !$sid) return $volver;
        $todos = self::setupsDeZona((new RodajesSetupModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll(), $z);
        $ids = array_map('intval', array_column($todos, 'id'));
        $usados = array_map('intval', array_column((new RodajesEscenaModel())->select('setup_id')->distinct()->where('proyecto_id', (int) $pid)
            ->where('ubicacion_id', $z['id'])->where('setup_id IS NOT NULL')->findAll(), 'setup_id'));
        $vis = array_values(array_filter($ids, static fn ($id) => in_array($id, $usados, true)));
        $i = array_search($sid, $vis, true);
        $j = $i === false ? false : $i + ((int) $this->request->getPost('dir') < 0 ? -1 : 1);
        if ($i === false || !isset($vis[$j])) return $volver;
        [$vis[$i], $vis[$j]] = [$vis[$j], $vis[$i]];
        $orden = array_merge($vis, array_values(array_diff($ids, $vis)));
        $zm->update($z['id'], ['orden_setups' => json_encode($orden)]);
        return $volver;
    }

    /** POST JSON {tipo: t|v, id} — alterna imprescindible / si da tiempo de una toma o variante. Devuelve la nueva. */
    public function prioridad($pid)
    {
        $in = $this->request->getJSON(true) ?: [];
        $id = (int) ($in['id'] ?? 0);
        $em = new RodajesEscenaModel();
        if (($in['tipo'] ?? '') === 'v') {
            $vm = new RodajesVarianteModel();
            $v = $vm->find($id);
            if (!$v || !$em->where('proyecto_id', (int) $pid)->find((int) $v['escena_id'])) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            $nueva = $v['prioridad'] === 'opc' ? 'imp' : 'opc';
            $vm->update($id, ['prioridad' => $nueva]);
        } else {
            $e = $em->where('proyecto_id', (int) $pid)->find($id);
            if (!$e) return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
            $nueva = ($e['prioridad'] ?? 'imp') === 'opc' ? 'imp' : 'opc';
            $em->skipValidation(true)->update($id, ['prioridad' => $nueva]);
        }
        return $this->response->setJSON(['ok' => true, 'prioridad' => $nueva]);
    }

    /** POST {dir: -1|1} — sube o baja una toma dentro de su setup en el plan (orden a mano). */
    public function tomaRodajeMover($pid, $eid)
    {
        $dir = (int) $this->request->getPost('dir') < 0 ? -1 : 1;
        $escenas = self::conVariantes((new RodajesEscenaModel())->where('proyecto_id', (int) $pid)->orderBy('orden', 'ASC')->findAll());
        foreach (self::construirPlan((int) $pid, $escenas) as $f) foreach ($f['zonas'] as $z) foreach ($z['setups'] as $st) {
            $ids = array_map(static fn ($e) => (int) $e['id'], $st['escenas']);
            $i = array_search((int) $eid, $ids, true);
            if ($i === false) continue;
            $j = $i + $dir;
            if (isset($ids[$j])) {
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                $em = (new RodajesEscenaModel())->skipValidation(true);
                foreach ($ids as $n => $id) $em->update($id, ['orden_rodaje' => $n]);
            }
            break 3;
        }
        return redirect()->to(site_url("rodajes/$pid/plan") . '#t' . (int) $eid);
    }

    /** Franja → zona (orden de ruta) → setup (orden propio de la zona) → escenas ordenadas por parecido de props. */
    public static function construirPlan(int $pid, array $escenas, bool $soloImp = false): array
    {
        $zonas  = (new RodajesUbicacionModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        $setups = (new RodajesSetupModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        if ($soloImp) {
            $escenas = array_values(array_filter($escenas, static fn ($e) => ($e['prioridad'] ?? 'imp') !== 'opc'));
            foreach ($escenas as &$e) $e['variantes'] = array_values(array_filter($e['variantes'] ?? [], static fn ($v) => $v['prioridad'] !== 'opc'));
            unset($e);
        }
        $franjas = RodajesEscenaModel::FRANJAS + ['' => 'Sin franja'];
        $plan = [];
        foreach ($franjas as $fk => $fl) {
            $ef = array_filter($escenas, static fn ($e) => (string) ($e['franja'] ?? '') === (string) $fk);
            if (!$ef) continue;
            $bloqueZ = [];
            foreach (array_merge($zonas, [null]) as $zi => $z) {
                $ez = array_filter($ef, static fn ($e) => (int) ($e['ubicacion_id'] ?? 0) === (int) ($z['id'] ?? 0));
                if (!$ez) continue;
                $bloqueS = [];
                foreach (array_merge(self::setupsDeZona($setups, $z), [null]) as $s) {
                    $es = array_values(array_filter($ez, static fn ($e) => (int) ($e['setup_id'] ?? 0) === (int) ($s['id'] ?? 0)));
                    if ($es) $bloqueS[] = ['setup' => $s, 'escenas' => self::ordenarProps($es)];
                }
                $bloqueZ[] = ['zona' => $z, 'n' => $z ? $zi + 1 : null, 'setups' => $bloqueS];
            }
            $plan[] = ['franja' => $fk, 'nombre' => $fl, 'zonas' => $bloqueZ];
        }
        return $plan;
    }

    private static function ordenarProps(array $es): array
    {
        usort($es, static fn ($a, $b) => (($a['prioridad'] ?? 'imp') === 'opc') <=> (($b['prioridad'] ?? 'imp') === 'opc') ?: ((int) $a['orden'] <=> (int) $b['orden']));
        $out = [array_shift($es)];
        while ($es) {
            $prev = rod_props(end($out));
            $mejor = 0; $punt = -1;
            foreach ($es as $i => $e) {
                $p = rod_props($e);
                $u = count(array_unique(array_merge($prev, $p)));
                $s = $u ? count(array_intersect($prev, $p)) / $u : 1;
                if ($s > $punt + 1e-9) { $punt = $s; $mejor = $i; }
            }
            $out[] = $es[$mejor];
            array_splice($es, $mejor, 1);
        }
        // Orden a mano (flechas del Plan) por encima del automático; las que no lo tienen, detrás en su orden automático.
        if (array_filter($out, static fn ($e) => ($e['orden_rodaje'] ?? null) !== null)) {
            $clave = [];
            foreach ($out as $k => $e) $clave[(int) $e['id']] = ($e['orden_rodaje'] ?? null) !== null ? (int) $e['orden_rodaje'] : 1_000_000 + $k;
            usort($out, static fn ($a, $b) => $clave[(int) $a['id']] <=> $clave[(int) $b['id']]);
        }
        return $out;
    }

    /**
     * El bloque de una toma ES su temática (situación de la sesión vinculada):
     * `escena_bloque` es solo la copia del nombre, para las vistas que agrupan por él.
     *  - temática borrada → la toma queda sin temática y sin bloque;
     *  - con temática y sin bloque → el bloque toma el nombre de la temática;
     *  - bloque cambiado a mano (formulario, pizarra) → si TODAS las tomas de la
     *    temática pasan al mismo nombre nuevo, es un renombrado de la temática;
     *    si no, cada toma se mueve a la temática de ese nombre (se crea si falta);
     *  - sin temática y con bloque escrito → temática de ese nombre.
     * Al cambiar de temática, la idea de la que salió la toma la acompaña.
     * Sin sesión vinculada no hay temáticas y el bloque sigue siendo texto libre.
     */
    public static function sincronizarBloques(int $pid): void
    {
        $proyecto = (new RodajesProyectoModel())->find($pid);
        $sid = (int) ($proyecto['sesion_id'] ?? 0);
        if (!$sid) { self::renumerarNarrativo($pid); return; }
        $sm = new SituacionModel();
        $em = (new RodajesEscenaModel())->skipValidation(true);
        $clave = static fn ($s) => mb_strtolower(trim((string) $s));

        $sits = [];
        $porNombre = [];
        $nombres = [];
        foreach ($sm->where('sesion_id', $sid)->findAll() as $s) {
            $sits[(int) $s['id']] = $s;
            $porNombre[$clave($s['nombre'])] ??= (int) $s['id'];
            $nombres[(int) $s['id']] = $s['nombre'];
        }
        $escenas = $em->select('id, situacion_id, escena_bloque')->where('proyecto_id', $pid)->findAll();

        $mover = [];      // escena_id => situacion_id|null
        $bloque = [];     // escena_id => nombre de bloque
        $cambiadas = [];  // situacion_id => [escena_id => bloque nuevo]
        $total = [];      // situacion_id => nº de tomas
        foreach ($escenas as $e) {
            $id = (int) $e['id'];
            $s  = (int) ($e['situacion_id'] ?? 0);
            $b  = trim((string) $e['escena_bloque']);
            if ($s && !isset($sits[$s])) {
                $mover[$id] = null;
                if ($b !== '') $bloque[$id] = '';
            } elseif ($s) {
                $total[$s] = ($total[$s] ?? 0) + 1;
                if ($clave($b) !== $clave($sits[$s]['nombre']) && $b !== '') $cambiadas[$s][$id] = $b;
                elseif ($b !== $sits[$s]['nombre']) $bloque[$id] = $sits[$s]['nombre'];
            } elseif ($b !== '') {
                $mover[$id] = $b;
            }
        }
        foreach ($cambiadas as $s => $ids) {
            $nuevos = array_unique(array_map($clave, $ids));
            $nombre = mb_substr(reset($ids), 0, 150);
            if (count($ids) === $total[$s] && count($nuevos) === 1 && !isset($porNombre[$clave($nombre)])) {
                $sm->update($s, ['nombre' => $nombre]);
                unset($porNombre[$clave($sits[$s]['nombre'])]);
                $porNombre[$clave($nombre)] = $s;
                $nombres[$s] = $nombre;
                foreach ($ids as $id => $b) if ($b !== $nombre) $bloque[$id] = $nombre;
                continue;
            }
            foreach ($ids as $id => $b) $mover[$id] = $b;
        }

        $orden = count($sits);
        foreach ($mover as $id => $b) {
            $destino = null;
            if ($b !== null) {
                $nombre = mb_substr($b, 0, 150);
                if (!isset($porNombre[$clave($nombre)])) {
                    $sm->insert(['sesion_id' => $sid, 'nombre' => $nombre, 'orden' => $orden++]);
                    $porNombre[$clave($nombre)] = (int) $sm->getInsertID();
                    $nombres[$porNombre[$clave($nombre)]] = $nombre;
                }
                $destino = $porNombre[$clave($nombre)];
                if ($b !== $nombres[$destino]) $bloque[$id] = $nombres[$destino];
            }
            $em->update($id, ['situacion_id' => $destino]);
            (new MoodboardItemModel())->where('escena_id', $id)->whereIn('estado', ['toma', 'variante'])->set(['situacion_id' => $destino])->update();
        }
        foreach ($bloque as $id => $b) $em->update($id, ['escena_bloque' => $b]);
        self::renumerarNarrativo($pid);
    }

    /**
     * Orden narrativo = temáticas como capítulos: primero por el orden de las
     * temáticas (pestaña Temáticas / columnas de la pizarra Narrativa), dentro de
     * cada una por su orden, y lo que no tiene temática al final. Sin sesión, los
     * bloques en el orden en que aparecen. Renumera `orden` (1, 2, 3…) para que el
     * número de cada toma sea su sitio en la historia en todas las vistas.
     */
    public static function renumerarNarrativo(int $pid): void
    {
        $proyecto = (new RodajesProyectoModel())->find($pid);
        if (!$proyecto) return;
        $em = (new RodajesEscenaModel())->skipValidation(true);
        $escenas = $em->select('id, orden, situacion_id, escena_bloque')->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        if (!$escenas) return;
        $pos = [];
        if ($proyecto['sesion_id']) {
            foreach ((new SituacionModel())->where('sesion_id', (int) $proyecto['sesion_id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $k => $st) $pos['s' . $st['id']] = $k;
        }
        $clave = static fn ($e) => $proyecto['sesion_id']
            ? ($e['situacion_id'] ? 's' . $e['situacion_id'] : '')
            : mb_strtolower(trim((string) $e['escena_bloque']));
        foreach ($escenas as $e) { $c = $clave($e); if ($c !== '') $pos[$c] ??= count($pos) + 1000; }
        $idx = array_flip(array_column($escenas, 'id'));
        usort($escenas, static fn ($a, $b) => [$clave($a) === '', $pos[$clave($a)] ?? 0, $idx[$a['id']]] <=> [$clave($b) === '', $pos[$clave($b)] ?? 0, $idx[$b['id']]]);
        foreach ($escenas as $n => $e) if ((int) $e['orden'] !== $n + 1) $em->update($e['id'], ['orden' => $n + 1]);
    }

    /** Las ideas que ya son toma arrastran su toma (y con ella el bloque) a la temática nueva. */
    private function tomasALaTematica(int $pid, int $sid, array $ids, ?int $destino): void
    {
        $eids = array_column((new MoodboardItemModel())->select('escena_id')->where('sesion_id', $sid)->whereIn('id', $ids)
            ->where('estado', 'toma')->where('escena_id IS NOT NULL')->findAll(), 'escena_id');
        if (!$eids) return;
        $nombre = $destino ? (string) ((new SituacionModel())->find($destino)['nombre'] ?? '') : '';
        (new RodajesEscenaModel())->skipValidation(true)->where('proyecto_id', $pid)->whereIn('id', $eids)
            ->set(['situacion_id' => $destino, 'escena_bloque' => $nombre])->update();
        (new MoodboardItemModel())->whereIn('escena_id', $eids)->where('estado', 'variante')->set(['situacion_id' => $destino])->update();
    }

    /** [nombre de bloque en minúsculas => orden de su temática], para ordenar los grupos como las temáticas. */
    public static function ordenBloques(?array $sesion): array
    {
        if (!$sesion) return [];
        $out = [];
        foreach ((new SituacionModel())->where('sesion_id', $sesion['id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $k => $s) {
            $out[mb_strtolower(trim($s['nombre']))] ??= $k;
        }
        return $out;
    }

    public static function conVariantes(array $escenas): array
    {
        if (!$escenas) return [];
        $por = [];
        foreach ((new RodajesVarianteModel())->whereIn('escena_id', array_column($escenas, 'id'))->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $v) {
            $por[(int) $v['escena_id']][] = $v;
        }
        foreach ($escenas as &$e) $e['variantes'] = $por[(int) $e['id']] ?? [];
        return $escenas;
    }

    public static function zonaId(int $pid, string $nombre): ?int
    {
        $nombre = mb_substr(trim($nombre), 0, 150);
        if ($nombre === '') return null;
        $m = new RodajesUbicacionModel();
        foreach ($m->where('proyecto_id', $pid)->findAll() as $z) {
            if (rod_normalizar($z['nombre']) === rod_normalizar($nombre)) return (int) $z['id'];
        }
        $m->insert(['proyecto_id' => $pid, 'nombre' => $nombre, 'orden' => $m->where('proyecto_id', $pid)->countAllResults()]);
        return (int) $m->getInsertID();
    }

    /** Campos del setup que son también datos de cámara de sus tomas: van al unísono. */
    public const SETUP_CAMARA = ['optica' => 'camara_optica', 'soporte' => 'camara_soporte']; // los fps van solo en cada toma
    private const SETUP_LARGO = ['optica' => 60, 'soporte' => 30];

    /** Datos de cámara que impone un setup (solo los que tiene rellenos: uno vacío no borra lo de la toma). */
    public static function camaraDeSetup(?array $s): array
    {
        $out = [];
        if ($s) foreach (self::SETUP_CAMARA as $sc => $ec) {
            $v = trim((string) ($s[$sc] ?? ''));
            if ($v !== '') $out[$ec] = $v;
        }
        return $out;
    }

    /** Copia los datos de cámara del setup a todas sus tomas. */
    public static function setupATomas(int $setupId): void
    {
        $d = self::camaraDeSetup((new RodajesSetupModel())->find($setupId));
        if ($d) (new RodajesEscenaModel())->skipValidation(true)->where('setup_id', $setupId)->set($d)->update();
    }

    /**
     * Toma guardada con setup: lo que trae de óptica/soporte y difiere del
     * setup se escribe en el setup (y pasa a sus demás tomas); luego la toma
     * recibe los valores del setup. Devuelve el post con la cámara ya alineada.
     */
    public static function alinearTomaConSetup(array $post, int $setupId): array
    {
        $sm = new RodajesSetupModel();
        $s  = $sm->find($setupId);
        if (!$s) return $post;
        $cambio = [];
        foreach (self::SETUP_CAMARA as $sc => $ec) {
            if (!array_key_exists($ec, $post)) continue;
            $v = trim((string) $post[$ec]);
            if ($v !== '' && $v !== trim((string) $s[$sc])) $cambio[$sc] = mb_substr($v, 0, self::SETUP_LARGO[$sc]);
        }
        if ($cambio) {
            $sm->update($s['id'], $cambio);
            self::setupATomas((int) $s['id']);
            $s = array_merge($s, $cambio);
        }
        return array_merge($post, self::camaraDeSetup($s));
    }

    public static function nuevoSetup(int $pid, string $nombre): int
    {
        $m = new RodajesSetupModel();
        $m->insert(['proyecto_id' => $pid, 'nombre' => mb_substr($nombre, 0, 150), 'orden' => $m->where('proyecto_id', $pid)->countAllResults()]);
        return (int) $m->getInsertID();
    }

    /** Props conocidos: «Qué llevar → props» de la sesión + los usados en el rodaje. */
    public static function propsConocidos(int $pid, ?int $sesionId): array
    {
        $lista = [];
        if ($sesionId) {
            foreach ((new SesionEquipoModel())->where('sesion_id', $sesionId)->where('tipo', 'props')->orderBy('orden', 'ASC')->findAll() as $p) $lista[] = $p['item'];
        }
        foreach ((new RodajesEscenaModel())->select('props')->where('proyecto_id', $pid)->where('props IS NOT NULL')->findAll() as $e) {
            foreach (rod_props($e) as $p) $lista[] = $p;
        }
        $ids = array_column((new RodajesEscenaModel())->select('id')->where('proyecto_id', $pid)->findAll(), 'id');
        if ($ids) foreach ((new RodajesVarianteModel())->select('props')->whereIn('escena_id', $ids)->where('props IS NOT NULL')->findAll() as $v) {
            foreach (rod_props($v) as $p) $lista[] = $p;
        }
        $out = [];
        foreach ($lista as $p) $out[rod_normalizar($p)] ??= $p;
        return array_values($out);
    }

    public static function pasos(int $pid, ?array $sesion): array
    {
        $db = db_connect();
        $c  = ['ideas' => '', 'tematicas' => '', 'revisar' => '', 'plan' => ''];
        if ($sesion) {
            $r = $db->query("SELECT COUNT(*) t, SUM(situacion_id IS NOT NULL OR estado = 'descartada') a, SUM(estado <> 'pendiente') r FROM moodboard_items WHERE sesion_id = ?", [$sesion['id']])->getRowArray();
            $t = (int) $r['t'];
            $c = ['ideas' => (string) $t, 'tematicas' => $t ? (int) $r['a'] . "/$t" : '', 'revisar' => $t ? (int) $r['r'] . "/$t" : ''];
        }
        $e = $db->query('SELECT COUNT(*) t, SUM(ubicacion_id IS NOT NULL) u, SUM(setup_id IS NOT NULL) s, SUM(franja IS NOT NULL) f, SUM(formato IS NOT NULL) fo, ' . rod_sql_hechas() . ' h FROM rodajes_escenas WHERE proyecto_id = ? AND deleted_at IS NULL', [$pid])->getRowArray();
        $t = (int) $e['t'];
        foreach (['formato' => 'fo', 'ubicaciones' => 'u', 'momento' => 'f', 'setups' => 's', 'plato' => 'h'] as $k => $col) $c[$k] = $t ? (int) $e[$col] . "/$t" : '';
        $out = [];
        foreach (self::PASOS as $k => [$txt, $ico]) {
            $cuenta = $c[$k] ?? ''; // pasos sin contador (plan) → vacío
            [$a, $b] = array_pad(explode('/', $cuenta), 2, null);
            $out[$k] = ['txt' => $txt, 'ico' => $ico, 'cuenta' => $cuenta, 'ok' => $b !== null && $b !== '' && (int) $b > 0 && $a === $b];
        }
        // Ingesta muestra solo el total, pero está hecha cuando la bandeja queda vacía (todo con temática o descartado).
        $out['ideas']['ok'] = $out['tematicas']['ok'];
        return $out;
    }

    private function contexto($pid): array
    {
        $proyecto = (new RodajesProyectoModel())->find((int) $pid);
        $sesion = $proyecto && $proyecto['sesion_id'] ? (new SesionModel())->find((int) $proyecto['sesion_id']) : null;
        return [$proyecto, $sesion];
    }

    private function base(array $proyecto, ?array $sesion, string $paso): array
    {
        return ['proyecto' => $proyecto, 'sesion' => $sesion, 'paso' => $paso, 'pasos' => self::pasos((int) $proyecto['id'], $sesion)];
    }

    private function items(int $sid): array
    {
        return (new MoodboardItemModel())->where('sesion_id', $sid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    private function zonas(int $pid): array
    {
        return (new RodajesUbicacionModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    private function puntosDe(int $pid): array
    {
        $ids = array_column($this->zonas($pid), 'id');
        return $ids ? array_column((new RodajesPuntoModel())->whereIn('ubicacion_id', $ids)->findAll(), null, 'id') : [];
    }
}
