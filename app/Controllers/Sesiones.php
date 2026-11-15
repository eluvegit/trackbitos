<?php

namespace App\Controllers;

use App\Models\MoodboardItemModel;
use App\Models\ModelReleaseModel;
use App\Models\SesionEquipoModel;
use App\Models\SesionMensajeModeloModel;
use App\Models\SesionModel;
use App\Models\SituacionModel;

class Sesiones extends BaseController
{
    // Carpeta pública donde se guardan moodboard y model releases (dentro de /public)
    protected string $publicDir = 'uploads/sesiones';

    protected $helpers = ['sesiones'];

    // ========== SESIONES ==========

    public function index()
    {
        // Una fila por sesión; la vista coloca el chip de cada parte (foto/vídeo)
        // en la columna de SU estado real, así ambas quedan a la misma altura
        // y no se pueden perder entre sesiones distintas. Las que están en
        // estado 'idea' vienen incluidas: la vista las oculta por defecto y
        // el filtro "Ideas" las muestra.
        $sesiones = (new SesionModel())->orderBy('actualizada_at', 'DESC')->findAll();

        $vista = ses_vista('sesiones/index');
        $datos = ['sesiones' => $sesiones];
        if ($vista === 'sesiones/index_nueva') {
            $datos['resumen'] = $this->resumenTablero(array_column($sesiones, 'id'));
        }

        return view($vista, $datos);
    }

    /**
     * Contadores por sesión para el tablero (una consulta agrupada por tabla):
     * [sesion_id => situaciones, moodboard, portada, equipo, equipo_ok,
     *               releases, rodaje_total, rodaje_hechas].
     */
    private function resumenTablero(array $ids): array
    {
        $out = [];
        if (!$ids) return $out;
        $db  = db_connect();
        $set = static function (array $filas, array $campos) use (&$out) {
            foreach ($filas as $f) {
                foreach ($campos as $c) $out[(int) $f['sesion_id']][$c] = $f[$c];
            }
        };

        $set($db->table('situaciones')->select('sesion_id, COUNT(*) AS situaciones')->whereIn('sesion_id', $ids)->groupBy('sesion_id')->get()->getResultArray(), ['situaciones']);
        $set($db->table('moodboard_items')->select('sesion_id, COUNT(*) AS moodboard')->whereIn('sesion_id', $ids)->groupBy('sesion_id')->get()->getResultArray(), ['moodboard']);
        $set($db->table('sesion_equipo')->select('sesion_id, COUNT(*) AS equipo, SUM(marcado) AS equipo_ok', false)->whereIn('sesion_id', $ids)->groupBy('sesion_id')->get()->getResultArray(), ['equipo', 'equipo_ok']);
        $set($db->table('model_releases')->select('sesion_id, COUNT(*) AS releases')->whereIn('sesion_id', $ids)->groupBy('sesion_id')->get()->getResultArray(), ['releases']);

        // Desde cuándo está cada parte en su etapa actual: último cambio
        // registrado en el historial (desde_foto / desde_video).
        foreach ($db->table('sesion_historial_estados')->select('sesion_id, parte, MAX(cambiado_at) AS desde', false)
            ->whereIn('sesion_id', $ids)->groupBy('sesion_id, parte')->get()->getResultArray() as $f) {
            $out[(int) $f['sesion_id']]['desde_' . $f['parte']] = $f['desde'];
        }

        // Portada: la primera imagen subida al moodboard.
        $primeras = $db->table('moodboard_items')->select('MIN(id) AS id', false)
            ->whereIn('sesion_id', $ids)->where('origen', 'archivo')->groupBy('sesion_id')->get()->getResultArray();
        if ($primeras) {
            $set($db->table('moodboard_items')->select('sesion_id, ruta_archivo AS portada')
                ->whereIn('id', array_column($primeras, 'id'))->get()->getResultArray(), ['portada']);
        }

        // Progreso de los rodajes vinculados (módulo Rodajes).
        $set($db->table('rodajes_proyectos p')
            ->select("p.sesion_id, COUNT(e.id) AS rodaje_total, SUM(e.toma_estado = 'hecha' AND (e.formato IS NULL OR e.formato <> 'ambas' OR e.toma_estado_foto = 'hecha')) AS rodaje_hechas", false)
            ->join('rodajes_escenas e', 'e.proyecto_id = p.id AND e.deleted_at IS NULL', 'left')
            ->whereIn('p.sesion_id', $ids)->where('p.deleted_at', null)
            ->groupBy('p.sesion_id')->get()->getResultArray(), ['rodaje_total', 'rodaje_hechas']);

        return $out;
    }

    public function create()
    {
        return view(ses_vista('sesiones/form'), ['sesion' => null, 'defaultIdea' => (bool) $this->request->getGet('idea')]);
    }

    public function store()
    {
        $post   = $this->request->getPost();
        $partes = (array) ($post['partes'] ?? []);

        if (empty(array_intersect($partes, SesionModel::PARTES))) {
            return redirect()->back()->withInput()->with('errors', ['Selecciona al menos una parte: fotografía o vídeo.']);
        }

        $estadoInicial = !empty($post['es_idea']) ? 'idea' : 'planificacion';

        $model = new SesionModel();
        $data  = [
            'titulo'       => $post['titulo'] ?? '',
            'fecha_sesion' => ($post['fecha_sesion'] ?? '') ?: null,
            'notas'        => $post['notas'] ?? null,
            'briefing'     => $post['briefing'] ?? null,
            'estado_foto'  => in_array('foto', $partes, true) ? $estadoInicial : null,
            'estado_video' => in_array('video', $partes, true) ? $estadoInicial : null,
        ];

        if (!$model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to(site_url('sesiones/' . $model->getInsertID()))->with('success', 'Sesión creada correctamente.');
    }

    public function show(int $id)
    {
        $detalle = (new SesionModel())->detalleCompleto($id);
        if (!$detalle) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Sesión no encontrada.');
        }

        // Rodajes vinculados (módulo Rodajes, independiente): con su progreso de plató.
        $detalle['rodajes'] = (new \App\Models\RodajesProyectoModel())
            ->select("rodajes_proyectos.id, rodajes_proyectos.titulo, COUNT(e.id) AS total, SUM(e.toma_estado = 'hecha' AND (e.formato IS NULL OR e.formato <> 'ambas' OR e.toma_estado_foto = 'hecha')) AS hechas", false)
            ->join('rodajes_escenas e', 'e.proyecto_id = rodajes_proyectos.id AND e.deleted_at IS NULL', 'left')
            ->where('rodajes_proyectos.sesion_id', $id)
            ->groupBy('rodajes_proyectos.id, rodajes_proyectos.titulo')
            ->findAll();

        $vista = ses_vista('sesiones/show');
        if ($vista === 'sesiones/show_nueva') {
            // Otras sesiones con equipo, para poder copiar su lista.
            $detalle['equipo_de_otras'] = db_connect()->table('sesion_equipo q')
                ->select('s.id, s.titulo, COUNT(*) AS n')->join('sesiones s', 's.id = q.sesion_id')
                ->where('q.sesion_id !=', $id)->groupBy('s.id, s.titulo')->orderBy('MAX(s.actualizada_at)', 'DESC', false)
                ->get()->getResultArray();
        }

        return view($vista, $detalle);
    }

    public function edit(int $id)
    {
        $sesion = (new SesionModel())->find($id);
        if (!$sesion) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Sesión no encontrada.');
        }

        return view(ses_vista('sesiones/form'), ['sesion' => $sesion]);
    }

    public function update(int $id)
    {
        $model = new SesionModel();
        $post  = $this->request->getPost();

        // Solo título y fecha van siempre (para pasar la validación de
        // 'titulo' aunque el formulario sea uno de los mini-formularios de
        // la ficha); el resto de campos de texto libre solo se tocan si el
        // formulario que ha hecho POST realmente los incluye, para que un
        // mini-formulario (p. ej. solo notas) no borre los demás.
        $data = [
            'id'           => $id,
            'titulo'       => $post['titulo'] ?? '',
            'fecha_sesion' => ($post['fecha_sesion'] ?? '') ?: null,
        ];

        foreach (['notas', 'briefing'] as $campo) {
            if (array_key_exists($campo, $post)) {
                $data[$campo] = $post[$campo] !== '' ? $post[$campo] : null;
            }
        }

        if (!$model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to(site_url('sesiones/' . $id))->with('success', 'Sesión actualizada correctamente.');
    }

    public function delete(int $id)
    {
        (new SesionModel())->delete($id);

        return redirect()->to(site_url('sesiones'))->with('success', 'Sesión eliminada correctamente.');
    }

    public function estado(int $id)
    {
        $input       = $this->request->getJSON(true) ?: $this->request->getPost();
        $parte       = trim((string) ($input['parte'] ?? ''));
        $nuevoEstado = trim((string) ($input['estado'] ?? ''));

        $ok = (new SesionModel())->cambiarEstado($id, $parte, $nuevoEstado);
        if (!$ok) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        return $this->response->setJSON([
            'ok'     => true,
            'parte'  => $parte,
            'estado' => $nuevoEstado,
        ]);
    }

    public function togglePausada(int $id)
    {
        $valor = (new SesionModel())->togglePausada($id);
        if ($valor === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        return $this->response->setJSON(['ok' => true, 'valor' => $valor]);
    }

    public function entregaModelos(int $id)
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $valor = trim((string) ($input['valor'] ?? ''));

        $ok = (new SesionModel())->cambiarEntregaModelos($id, $valor);
        if (!$ok) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        return $this->response->setJSON(['ok' => true, 'valor' => $valor]);
    }

    // ========== SITUACIONES ==========

    public function situacionCrear(int $sesionId)
    {
        $input  = $this->request->getJSON(true) ?: $this->request->getPost();
        $model  = new SituacionModel();
        $nombre = trim((string) ($input['nombre'] ?? ''));

        if ($nombre === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        $orden = (int) ($model->where('sesion_id', $sesionId)->countAllResults() ?: 0);

        $model->insert(['sesion_id' => $sesionId, 'nombre' => $nombre, 'orden' => $orden]);

        return $this->response->setJSON([
            'ok'         => true,
            'situacion'  => ['id' => $model->getInsertID(), 'nombre' => $nombre, 'orden' => $orden],
        ]);
    }

    public function situacionBorrar(int $sesionId, int $situacionId)
    {
        (new SituacionModel())->where('sesion_id', $sesionId)->delete($situacionId);

        return $this->response->setJSON(['ok' => true]);
    }

    public function situacionReordenar(int $sesionId)
    {
        $input = $this->request->getJSON(true) ?: [];
        $orden = $input['orden'] ?? [];

        $model = new SituacionModel();
        foreach ($orden as $posicion => $situacionId) {
            $model->where('sesion_id', $sesionId)->update((int) $situacionId, ['orden' => $posicion]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    // ========== EQUIPO ==========

    public function equipoAgregar(int $sesionId)
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $model = new SesionEquipoModel();
        $item  = trim((string) ($input['item'] ?? ''));
        $tipo  = in_array($input['tipo'] ?? '', SesionEquipoModel::TIPOS, true) ? $input['tipo'] : 'equipo';

        if ($item === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        $orden = (int) ($model->where('sesion_id', $sesionId)->countAllResults() ?: 0);

        $model->insert(['sesion_id' => $sesionId, 'item' => $item, 'tipo' => $tipo, 'marcado' => 0, 'orden' => $orden]);

        return $this->response->setJSON([
            'ok'    => true,
            'item'  => ['id' => $model->getInsertID(), 'item' => $item, 'tipo' => $tipo, 'marcado' => 0, 'orden' => $orden],
        ]);
    }

    /** Cambia el texto de un ítem de "Qué llevar". */
    public function equipoEditar(int $sesionId, int $itemId)
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $texto = trim((string) ($input['item'] ?? ''));
        $model = new SesionEquipoModel();

        if ($texto === '' || mb_strlen($texto) > 150 || !$model->where('sesion_id', $sesionId)->find($itemId)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        $model->update($itemId, ['item' => $texto]);

        return $this->response->setJSON(['ok' => true, 'item' => $texto]);
    }

    /** Pasa un ítem de una lista a la otra (equipo ↔ props). */
    public function equipoTipo(int $sesionId, int $itemId)
    {
        $model = new SesionEquipoModel();
        $item  = $model->where('sesion_id', $sesionId)->find($itemId);
        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $nuevo = ($item['tipo'] ?? 'equipo') === 'props' ? 'equipo' : 'props';
        $model->update($itemId, ['tipo' => $nuevo]);

        return $this->response->setJSON(['ok' => true, 'tipo' => $nuevo]);
    }

    public function equipoToggle(int $sesionId, int $itemId)
    {
        $model = new SesionEquipoModel();
        $item  = $model->where('sesion_id', $sesionId)->find($itemId);

        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $nuevo = !((bool) $item['marcado']);
        $model->update($itemId, ['marcado' => $nuevo]);

        return $this->response->setJSON(['ok' => true, 'marcado' => $nuevo]);
    }

    public function equipoBorrar(int $sesionId, int $itemId)
    {
        (new SesionEquipoModel())->where('sesion_id', $sesionId)->delete($itemId);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Copia la lista de equipo de otra sesión (sin marcar). Los ítems que ya
     * están en esta sesión (mismo texto, sin distinguir mayúsculas) no se
     * duplican. Devuelve los ítems añadidos.
     */
    public function equipoCopiar(int $sesionId)
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $desde = (int) ($input['desde'] ?? 0);
        $model = new SesionEquipoModel();

        if ($desde === $sesionId || !(new SesionModel())->find($desde) || !(new SesionModel())->find($sesionId)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        // Clave = lista + texto: el mismo nombre en equipo y en props no choca.
        $clave = static fn ($i) => ($i['tipo'] ?? 'equipo') . '|' . mb_strtolower(trim($i['item']));
        $ya = array_map($clave, $model->where('sesion_id', $sesionId)->findAll());
        $orden = count($ya);
        $nuevos = [];
        foreach ($model->where('sesion_id', $desde)->orderBy('orden', 'ASC')->findAll() as $i) {
            if (in_array($clave($i), $ya, true)) continue;
            $ya[] = $clave($i);
            $tipo = $i['tipo'] ?? 'equipo';
            $model->insert(['sesion_id' => $sesionId, 'item' => $i['item'], 'tipo' => $tipo, 'marcado' => 0, 'orden' => $orden++]);
            $nuevos[] = ['id' => $model->getInsertID(), 'item' => $i['item'], 'tipo' => $tipo, 'marcado' => 0];
        }

        return $this->response->setJSON(['ok' => true, 'items' => $nuevos]);
    }

    // ========== MOODBOARD ==========

    public function moodboardSubir(int $sesionId)
    {
        $situacionId = $this->request->getPost('situacion_id');
        $situacionId = ($situacionId !== null && $situacionId !== '') ? (int) $situacionId : null;
        $nota        = trim((string) $this->request->getPost('nota')) ?: null;

        $files = $this->request->getFileMultiple('archivo') ?? [];
        if (empty($files) && $this->request->getFile('archivo')) {
            $files = [$this->request->getFile('archivo')];
        }

        if (empty($files)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Ningún archivo recibido.']);
        }

        $model     = new MoodboardItemModel();
        $orden     = (int) ($model->where('sesion_id', $sesionId)->countAllResults() ?: 0);
        $insertados = [];

        $targetDir = rtrim($this->publicDir, '/') . '/' . $sesionId;
        $absDir    = rtrim(FCPATH, '/') . '/' . $targetDir;
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            if (strpos($file->getMimeType(), 'image/') !== 0) {
                continue;
            }

            $newName  = $file->getRandomName();
            $file->move($absDir, $newName);
            $relative = $targetDir . '/' . $newName;

            $model->insert([
                'sesion_id'    => $sesionId,
                'situacion_id' => $situacionId,
                'origen'       => 'archivo',
                'ruta_archivo' => $relative,
                'nota'         => $nota,
                'orden'        => $orden++,
            ]);

            $insertados[] = $model->find($model->getInsertID());
        }

        if (empty($insertados)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Ningún archivo válido.']);
        }

        return $this->response->setJSON(['ok' => true, 'items' => $insertados]);
    }

    public function moodboardAgregarEnlace(int $sesionId)
    {
        $input       = $this->request->getJSON(true) ?: $this->request->getPost();
        $situacionId = $input['situacion_id'] ?? null;
        $situacionId = ($situacionId !== null && $situacionId !== '') ? (int) $situacionId : null;
        $url         = trim((string) ($input['url_externa'] ?? ''));
        $nota        = trim((string) ($input['nota'] ?? '')) ?: null;

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'URL inválida.']);
        }

        $model = new MoodboardItemModel();
        $orden = (int) ($model->where('sesion_id', $sesionId)->countAllResults() ?: 0);

        $model->insert([
            'sesion_id'    => $sesionId,
            'situacion_id' => $situacionId,
            'origen'       => 'enlace',
            'url_externa'  => $url,
            'nota'         => $nota,
            'orden'        => $orden,
        ]);

        return $this->response->setJSON(['ok' => true, 'item' => $model->find($model->getInsertID())]);
    }

    public function moodboardBorrar(int $sesionId, int $itemId)
    {
        $model = new MoodboardItemModel();
        $item  = $model->where('sesion_id', $sesionId)->find($itemId);

        if ($item) {
            if ($item['origen'] === 'archivo' && $item['ruta_archivo']) {
                $this->borrarArchivoFisico($item['ruta_archivo']);
            }
            $model->delete($itemId);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Vincula (o desvincula, con situacion_id vacío) un item de moodboard
     * ya subido a una situación concreta, sin tener que borrarlo y
     * resubirlo dentro de esa situación.
     */
    public function moodboardVincular(int $sesionId, int $itemId)
    {
        $input       = $this->request->getJSON(true) ?: $this->request->getPost();
        $situacionId = $input['situacion_id'] ?? null;
        $situacionId = ($situacionId !== null && $situacionId !== '') ? (int) $situacionId : null;

        $model = new MoodboardItemModel();
        $item  = $model->where('sesion_id', $sesionId)->find($itemId);
        if (!$item) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        if ($situacionId !== null && !(new SituacionModel())->where('sesion_id', $sesionId)->find($situacionId)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Situación no válida.']);
        }

        $model->update($itemId, ['situacion_id' => $situacionId]);

        return $this->response->setJSON(['ok' => true, 'situacion_id' => $situacionId]);
    }

    // ========== MODEL RELEASES ==========

    public function releaseSubir(int $sesionId)
    {
        $sesion = (new SesionModel())->find($sesionId);
        if (!$sesion) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $nombreModelo = trim((string) $this->request->getPost('nombre_modelo'));
        $file         = $this->request->getFile('archivo');

        if ($nombreModelo === '' || !$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Datos inválidos.']);
        }

        $targetDir = rtrim($this->publicDir, '/') . '/' . $sesionId . '/releases';
        $absDir    = rtrim(FCPATH, '/') . '/' . $targetDir;
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $newName = $file->getRandomName();
        $file->move($absDir, $newName);

        $model = new ModelReleaseModel();
        $model->insert([
            'sesion_id'     => $sesionId,
            'nombre_modelo' => $nombreModelo,
            'ruta_archivo'  => $targetDir . '/' . $newName,
        ]);

        return $this->response->setJSON(['ok' => true, 'release' => $model->find($model->getInsertID())]);
    }

    public function releaseBorrar(int $sesionId, int $releaseId)
    {
        $model   = new ModelReleaseModel();
        $release = $model->where('sesion_id', $sesionId)->find($releaseId);

        if ($release) {
            $this->borrarArchivoFisico($release['ruta_archivo']);
            $model->delete($releaseId);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    // ========== MENSAJES A MODELOS ==========
    // Un registro por mensaje enviado; puede haber varios modelos/dueños
    // en la misma sesión y cada uno puede tener o no un model release.

    public function mensajeModeloCrear(int $sesionId)
    {
        $input        = $this->request->getJSON(true) ?: $this->request->getPost();
        $nombreModelo = trim((string) ($input['nombre_modelo'] ?? ''));
        $mensaje      = trim((string) ($input['mensaje'] ?? ''));
        $releaseId    = $input['model_release_id'] ?? null;
        $releaseId    = ($releaseId !== null && $releaseId !== '') ? (int) $releaseId : null;

        if ($nombreModelo === '' || $mensaje === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Faltan datos.']);
        }

        if ($releaseId !== null && !(new ModelReleaseModel())->where('sesion_id', $sesionId)->find($releaseId)) {
            $releaseId = null;
        }

        $model = new SesionMensajeModeloModel();
        $model->insert([
            'sesion_id'        => $sesionId,
            'model_release_id' => $releaseId,
            'nombre_modelo'    => $nombreModelo,
            'mensaje'          => $mensaje,
        ]);

        return $this->response->setJSON(['ok' => true, 'item' => $model->find($model->getInsertID())]);
    }

    public function mensajeModeloBorrar(int $sesionId, int $mensajeId)
    {
        (new SesionMensajeModeloModel())->where('sesion_id', $sesionId)->delete($mensajeId);

        return $this->response->setJSON(['ok' => true]);
    }

    // ========== EXPORTAR ==========

    public function exportar(int $id)
    {
        $detalle = (new SesionModel())->detalleCompleto($id);
        if (!$detalle) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Sesión no encontrada.');
        }

        $grupos = [];
        foreach ($detalle['situaciones'] as $sit) {
            $grupos[] = [
                'nombre' => $sit['nombre'],
                'items'  => $detalle['moodboard_por_situacion'][$sit['id']] ?? [],
            ];
        }

        $general = $detalle['moodboard_por_situacion']['general'] ?? [];
        if (!empty($general)) {
            $grupos[] = ['nombre' => 'General', 'items' => $general];
        }

        return view('sesiones/exportar', ['sesion' => $detalle['sesion'], 'grupos' => $grupos]);
    }

    public function exportarSituacion(int $sesionId, int $situacionId)
    {
        $sesion    = (new SesionModel())->find($sesionId);
        $situacion = (new SituacionModel())->where('sesion_id', $sesionId)->find($situacionId);

        if (!$sesion || !$situacion) {
            return redirect()->to(site_url('sesiones/' . $sesionId))->with('error', 'Situación no encontrada.');
        }

        $items = (new MoodboardItemModel())
            ->where('sesion_id', $sesionId)
            ->where('situacion_id', $situacionId)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $grupos = [['nombre' => $situacion['nombre'], 'items' => $items]];

        return view('sesiones/exportar', ['sesion' => $sesion, 'grupos' => $grupos]);
    }

    /**
     * Borra el archivo físico bajo /public correspondiente a una ruta
     * relativa guardada en BD (mismo patrón que RodajesEscenas::deleteImage()).
     */
    protected function borrarArchivoFisico(string $rutaRelativa): void
    {
        $path = FCPATH . ltrim($rutaRelativa, '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
