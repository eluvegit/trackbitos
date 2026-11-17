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
        'ubicaciones' => ['Ubicaciones', 'bi-geo-alt'],
        'setups'      => ['Setups', 'bi-camera'],
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
        return view('rodajes/flujo/tematicas', $this->base($proyecto, $sesion, 'tematicas') + [
            'situaciones' => (new SituacionModel())->where('sesion_id', $sesion['id'])->orderBy('orden', 'ASC')->findAll(),
            'porSit'      => $por,
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
        if ($destino === 'deshacer') {
            if ($item['estado'] === 'variante' && $item['variante_id']) (new RodajesVarianteModel())->delete((int) $item['variante_id']);
            $model->update($item['id'], ['estado' => 'pendiente', 'escena_id' => null, 'variante_id' => null]);
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
        $vm->update($vid, ['prioridad' => $e['prioridad'] ?: 'imp']);
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
        if ($nombre !== '') self::zonaId((int) $pid, $nombre);
        return redirect()->to(site_url("rodajes/$pid/ubicaciones"));
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

    /** POST JSON {campo: ubicacion_id|punto_id|setup_id, valor} */
    public function asignar($pid, $eid)
    {
        $em = new RodajesEscenaModel();
        $e  = $em->where('proyecto_id', (int) $pid)->find((int) $eid);
        $in = $this->request->getJSON(true) ?: $this->request->getPost();
        $campo = (string) ($in['campo'] ?? '');
        $valor = (int) ($in['valor'] ?? 0) ?: null;
        if (!$e || !in_array($campo, ['ubicacion_id', 'punto_id', 'setup_id'], true)) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        $datos = [$campo => $valor];
        if ($campo === 'ubicacion_id') {
            $z = $valor ? (new RodajesUbicacionModel())->where('proyecto_id', (int) $pid)->find($valor) : null;
            $datos = ['ubicacion_id' => $z['id'] ?? null, 'escena_ubicacion' => $z['nombre'] ?? '', 'punto_id' => null];
        } elseif ($campo === 'punto_id' && $valor) {
            $p = (new RodajesPuntoModel())->find($valor);
            if (!$p || (int) $p['ubicacion_id'] !== (int) $e['ubicacion_id']) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        } elseif ($campo === 'setup_id' && $valor && !(new RodajesSetupModel())->where('proyecto_id', (int) $pid)->find($valor)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }
        $em->skipValidation(true)->update($e['id'], $datos);
        return $this->response->setJSON(['ok' => true]);
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
                'fps' => $p('fps', 10), 'luz' => $p('luz', 255), 'notas' => trim((string) $this->request->getPost('notas')) ?: null,
            ]);
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
        ]);
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

    /** Franja → zona (orden de ruta) → setup → escenas ordenadas por parecido de props. */
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
                foreach (array_merge($setups, [null]) as $s) {
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
        if (!$sid) return;
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
        $out = [];
        foreach ($lista as $p) $out[rod_normalizar($p)] ??= $p;
        return array_values($out);
    }

    public static function pasos(int $pid, ?array $sesion): array
    {
        $db = db_connect();
        $c  = ['ideas' => '', 'tematicas' => '', 'revisar' => ''];
        if ($sesion) {
            $r = $db->query("SELECT COUNT(*) t, SUM(situacion_id IS NOT NULL OR estado = 'descartada') a, SUM(estado <> 'pendiente') r FROM moodboard_items WHERE sesion_id = ?", [$sesion['id']])->getRowArray();
            $t = (int) $r['t'];
            $c = ['ideas' => (string) $t, 'tematicas' => $t ? (int) $r['a'] . "/$t" : '', 'revisar' => $t ? (int) $r['r'] . "/$t" : ''];
        }
        $e = $db->query('SELECT COUNT(*) t, SUM(ubicacion_id IS NOT NULL) u, SUM(setup_id IS NOT NULL) s, SUM(franja IS NOT NULL) f, ' . rod_sql_hechas() . ' h FROM rodajes_escenas WHERE proyecto_id = ? AND deleted_at IS NULL', [$pid])->getRowArray();
        $t = (int) $e['t'];
        foreach (['ubicaciones' => 'u', 'setups' => 's', 'plan' => 'f', 'plato' => 'h'] as $k => $col) $c[$k] = $t ? (int) $e[$col] . "/$t" : '';
        $out = [];
        foreach (self::PASOS as $k => [$txt, $ico]) {
            [$a, $b] = array_pad(explode('/', $c[$k]), 2, null);
            $out[$k] = ['txt' => $txt, 'ico' => $ico, 'cuenta' => $c[$k], 'ok' => $b !== null && $b !== '' && (int) $b > 0 && $a === $b];
        }
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
