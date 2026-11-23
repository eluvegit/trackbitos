<?php

namespace App\Controllers;

use App\Models\MoodboardItemModel;
use App\Models\ModelReleaseModel;
use App\Models\SesionEquipoModel;
use App\Models\SesionMensajeModeloModel;
use App\Models\SesionModeloPerfilModel;
use App\Models\SesionModeloRopaModel;
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
            'hora_sesion'  => ($post['hora_sesion'] ?? '') ?: null,
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
            // Brief: ubicaciones de los rodajes vinculados (con su hora) y modelos.
            $detalle['ubicaciones'] = $this->ubicacionesRodaje(array_column($detalle['rodajes'], 'id'));
            $detalle['modelos']     = (new SesionModeloPerfilModel())->deSesion($id);
            // Perfiles que aún no están en esta sesión, para añadirlos con un toque.
            $detalle['perfiles_libres'] = (new SesionModeloPerfilModel())
                ->select('id, nombre')
                ->whereNotIn('id', array_merge([0], array_column($detalle['modelos'], 'id')))
                ->orderBy('nombre', 'ASC')->findAll();

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

        foreach (['notas', 'briefing', 'hora_sesion'] as $campo) {
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

    // ========== BRIEF: HORA Y UBICACIONES ==========

    /**
     * Ubicaciones (zonas) de los rodajes vinculados, ordenadas por hora
     * (las que no tienen hora, al final en su orden del rodaje).
     */
    private function ubicacionesRodaje(array $rodajeIds): array
    {
        if (!$rodajeIds) return [];

        return db_connect()->table('rodajes_ubicaciones u')
            ->select('u.id, u.proyecto_id, u.nombre, u.notas, u.hora, u.imagen, p.titulo AS rodaje')
            ->join('rodajes_proyectos p', 'p.id = u.proyecto_id')
            ->whereIn('u.proyecto_id', $rodajeIds)
            ->orderBy('u.hora IS NULL', 'ASC', false)->orderBy('u.hora', 'ASC')->orderBy('u.orden', 'ASC')
            ->get()->getResultArray();
    }

    /** Hora prevista en una ubicación del rodaje vinculado (POST {hora}, vacío = sin hora). */
    public function ubicacionHora(int $sesionId, int $ubicacionId)
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $hora  = trim((string) ($input['hora'] ?? ''));
        if ($hora !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        // Solo ubicaciones de un rodaje vinculado a esta sesión.
        $valida = db_connect()->table('rodajes_ubicaciones u')
            ->join('rodajes_proyectos p', 'p.id = u.proyecto_id')
            ->where('u.id', $ubicacionId)->where('p.sesion_id', $sesionId)->where('p.deleted_at', null)
            ->countAllResults();
        if (!$valida) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        (new \App\Models\RodajesUbicacionModel())->update($ubicacionId, ['hora' => $hora !== '' ? $hora : null]);

        return $this->response->setJSON(['ok' => true, 'hora' => $hora]);
    }

    // ========== MODELOS (perfiles reutilizables + opciones de ropa) ==========

    /** HTML de la tarjeta de un modelo dentro de esta sesión (o '' si no está). */
    private function tarjetaModelo(int $sesionId, int $modeloId): string
    {
        $m = (new SesionModeloPerfilModel())->deSesion($sesionId, $modeloId);

        return $m ? view('sesiones/_modelo_nueva', ['m' => $m[0], 'sid' => $sesionId]) : '';
    }

    private function modeloEnSesion(int $sesionId, int $modeloId): bool
    {
        return db_connect()->table('sesion_modelos_sesion')
            ->where('sesion_id', $sesionId)->where('modelo_id', $modeloId)->countAllResults() > 0;
    }

    /**
     * Añade un modelo a la sesión: uno existente (modelo_id) o un perfil
     * nuevo con ese nombre (nombre; si ya existe uno igual, se reutiliza).
     */
    public function modeloAgregar(int $sesionId)
    {
        $input  = $this->request->getJSON(true) ?: $this->request->getPost();
        $perfil = new SesionModeloPerfilModel();
        $mid    = (int) ($input['modelo_id'] ?? 0);
        $nombre = trim((string) ($input['nombre'] ?? ''));

        if (!(new SesionModel())->find($sesionId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        if ($mid) {
            if (!$perfil->find($mid)) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        } else {
            if ($nombre === '' || mb_strlen($nombre) > 150) return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            $ya  = $perfil->where('nombre', $nombre)->first();
            $mid = $ya ? (int) $ya['id'] : (int) $perfil->insert(['nombre' => $nombre]);
        }

        if (!$this->modeloEnSesion($sesionId, $mid)) {
            db_connect()->table('sesion_modelos_sesion')->insert(['sesion_id' => $sesionId, 'modelo_id' => $mid]);
        }

        return $this->response->setJSON(['ok' => true, 'id' => $mid, 'html' => $this->tarjetaModelo($sesionId, $mid)]);
    }

    /** Quita el modelo de ESTA sesión (el perfil y su ropa se conservan). */
    public function modeloQuitar(int $sesionId, int $modeloId)
    {
        $db = db_connect();
        $ropaIds = array_column($db->table('sesion_modelo_ropa')->select('id')->where('modelo_id', $modeloId)->get()->getResultArray(), 'id');
        if ($ropaIds) $db->table('sesion_ropa_elegida')->where('sesion_id', $sesionId)->whereIn('ropa_id', $ropaIds)->delete();
        $db->table('sesion_modelos_sesion')->where('sesion_id', $sesionId)->where('modelo_id', $modeloId)->delete();

        $p = (new SesionModeloPerfilModel())->select('id, nombre')->find($modeloId);

        return $this->response->setJSON(['ok' => true, 'perfil' => $p]);
    }

    /** Guarda nombre/contacto/notas del perfil (vale para todas sus sesiones). */
    public function modeloGuardar(int $sesionId, int $modeloId)
    {
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $data  = [];
        foreach (['nombre', 'contacto', 'notas'] as $c) {
            if (array_key_exists($c, $input)) $data[$c] = trim((string) $input[$c]) !== '' ? trim((string) $input[$c]) : null;
        }
        if (!$data || !$this->modeloEnSesion($sesionId, $modeloId)
            || (array_key_exists('nombre', $data) && $data['nombre'] === null)
            || mb_strlen((string) ($data['nombre'] ?? '')) > 150 || mb_strlen((string) ($data['contacto'] ?? '')) > 150) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        (new SesionModeloPerfilModel())->update($modeloId, $data);

        // Si el contacto es un Instagram, se devuelve el usuario para mostrar el enlace al perfil.
        $out = ['ok' => true];
        if (array_key_exists('contacto', $data)) $out['instagram'] = ses_instagram($data['contacto']);

        return $this->response->setJSON($out);
    }

    /** Nueva opción de ropa en el perfil (texto y/o foto). Devuelve la tarjeta. */
    public function ropaCrear(int $sesionId, int $modeloId)
    {
        $texto  = trim((string) $this->request->getPost('texto'));
        $file   = $this->request->getFile('imagen');
        $hayImg = $file && $file->isValid() && !$file->hasMoved() && strpos((string) $file->getMimeType(), 'image/') === 0;

        if (!$this->modeloEnSesion($sesionId, $modeloId) || mb_strlen($texto) > 150 || ($texto === '' && !$hayImg)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
        }

        $ruta = null;
        if ($hayImg) {
            $dir = rtrim($this->publicDir, '/') . '/modelos/' . $modeloId;
            if (!is_dir(FCPATH . $dir)) @mkdir(FCPATH . $dir, 0775, true);
            $nombre = $file->getRandomName();
            $file->move(FCPATH . $dir, $nombre);
            $ruta = "$dir/$nombre";
        }

        $m = new SesionModeloRopaModel();
        $m->insert([
            'modelo_id' => $modeloId,
            'texto'     => $texto !== '' ? $texto : 'Outfit',
            'imagen'    => $ruta,
            'orden'     => $m->where('modelo_id', $modeloId)->countAllResults(),
        ]);

        return $this->response->setJSON(['ok' => true, 'html' => $this->tarjetaModelo($sesionId, $modeloId)]);
    }

    /** Sube una o varias fotos al perfil del modelo (campo fotos[]). Devuelve la tarjeta. */
    public function fotoPerfilSubir(int $sesionId, int $modeloId)
    {
        if (!$this->modeloEnSesion($sesionId, $modeloId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $files = $this->request->getFileMultiple('fotos') ?? [];
        $dir   = rtrim($this->publicDir, '/') . '/modelos/' . $modeloId . '/perfil';
        $db    = db_connect();
        $orden = $db->table('sesion_modelo_fotos')->where('modelo_id', $modeloId)->countAllResults();
        $n = 0;
        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved() || strpos((string) $file->getMimeType(), 'image/') !== 0) continue;
            if (!is_dir(FCPATH . $dir)) @mkdir(FCPATH . $dir, 0775, true);
            $nombre = $file->getRandomName();
            $file->move(FCPATH . $dir, $nombre);
            $db->table('sesion_modelo_fotos')->insert(['modelo_id' => $modeloId, 'imagen' => "$dir/$nombre", 'orden' => $orden++]);
            $n++;
        }
        if (!$n) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Ninguna imagen válida.']);
        }

        return $this->response->setJSON(['ok' => true, 'html' => $this->tarjetaModelo($sesionId, $modeloId)]);
    }

    /** Borra una foto del perfil (y su archivo). */
    public function fotoPerfilBorrar(int $sesionId, int $modeloId, int $fotoId)
    {
        $t    = db_connect()->table('sesion_modelo_fotos');
        $foto = $t->where('modelo_id', $modeloId)->where('id', $fotoId)->get()->getRowArray();
        if (!$foto || !$this->modeloEnSesion($sesionId, $modeloId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $this->borrarArchivoFisico($foto['imagen']);
        db_connect()->table('sesion_modelo_fotos')->where('id', $fotoId)->delete();

        return $this->response->setJSON(['ok' => true, 'html' => $this->tarjetaModelo($sesionId, $modeloId)]);
    }

    /** Pone una foto como principal (avatar): pasa a la primera posición. */
    public function fotoPerfilPrincipal(int $sesionId, int $modeloId, int $fotoId)
    {
        $db  = db_connect();
        $ids = array_map('intval', array_column($db->table('sesion_modelo_fotos')->select('id')->where('modelo_id', $modeloId)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray(), 'id'));
        if (!in_array($fotoId, $ids, true) || !$this->modeloEnSesion($sesionId, $modeloId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $ids = array_merge([$fotoId], array_values(array_diff($ids, [$fotoId])));
        foreach ($ids as $i => $id) $db->table('sesion_modelo_fotos')->where('id', $id)->update(['orden' => $i]);

        return $this->response->setJSON(['ok' => true, 'html' => $this->tarjetaModelo($sesionId, $modeloId)]);
    }

    /** Borra una opción de ropa del perfil (de todas sus sesiones) y su foto. */
    public function ropaBorrar(int $sesionId, int $modeloId, int $ropaId)
    {
        $m    = new SesionModeloRopaModel();
        $ropa = $m->where('modelo_id', $modeloId)->find($ropaId);
        if (!$ropa || !$this->modeloEnSesion($sesionId, $modeloId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        if ($ropa['imagen']) $this->borrarArchivoFisico($ropa['imagen']);
        $m->delete($ropaId);

        return $this->response->setJSON(['ok' => true, 'html' => $this->tarjetaModelo($sesionId, $modeloId)]);
    }

    /** Marca / desmarca una opción de ropa como elegida para ESTA sesión. */
    public function ropaElegir(int $sesionId, int $modeloId, int $ropaId)
    {
        if (!$this->modeloEnSesion($sesionId, $modeloId) || !(new SesionModeloRopaModel())->where('modelo_id', $modeloId)->find($ropaId)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }
        $db = db_connect();
        $ya = $db->table('sesion_ropa_elegida')->where('sesion_id', $sesionId)->where('ropa_id', $ropaId)->countAllResults() > 0;
        if ($ya) $db->table('sesion_ropa_elegida')->where('sesion_id', $sesionId)->where('ropa_id', $ropaId)->delete();
        else     $db->table('sesion_ropa_elegida')->insert(['sesion_id' => $sesionId, 'ropa_id' => $ropaId]);

        return $this->response->setJSON(['ok' => true, 'elegida' => !$ya]);
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

        $rodajeIds = array_column((new \App\Models\RodajesProyectoModel())->select('id')->where('sesion_id', $id)->findAll(), 'id');

        return view('sesiones/exportar', [
            'sesion'      => $detalle['sesion'],
            'grupos'      => $grupos,
            'ubicaciones' => $this->ubicacionesRodaje($rodajeIds),
            'modelos'     => (new SesionModeloPerfilModel())->deSesion($id),
        ]);
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
