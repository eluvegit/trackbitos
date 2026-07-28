<?php

namespace App\Controllers;

use App\Models\IdeaModel;
use App\Models\IdeaMoodboardItemModel;
use App\Models\MoodboardItemModel;
use App\Models\SesionModel;

class SesionIdeas extends BaseController
{
    // Carpeta pública donde se guarda el moodboard de las ideas (dentro de /public)
    protected string $publicDir = 'uploads/sesiones/ideas';

    public function index()
    {
        // Las ideas ya se listan integradas en Sesiones::index (filtro "Ideas");
        // este listado separado queda obsoleto, se redirige al unificado.
        return redirect()->to(site_url('sesiones'));
    }

    public function create()
    {
        return view('sesiones/ideas/form', ['idea' => null]);
    }

    public function store()
    {
        $post   = $this->request->getPost();
        $partes = (array) ($post['partes'] ?? []);

        if (empty(array_intersect($partes, SesionModel::PARTES))) {
            return redirect()->back()->withInput()->with('errors', ['Selecciona al menos una parte: fotografía o vídeo.']);
        }

        $model = new IdeaModel();
        $data  = [
            'titulo'      => $post['titulo'] ?? '',
            'notas'       => $post['notas'] ?? null,
            'tiene_foto'  => in_array('foto', $partes, true) ? 1 : 0,
            'tiene_video' => in_array('video', $partes, true) ? 1 : 0,
        ];

        if (!$model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to(site_url('sesiones/ideas/' . $model->getInsertID()))->with('success', 'Idea guardada correctamente.');
    }

    public function show(int $id)
    {
        $idea = (new IdeaModel())->find($id);
        if (!$idea) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Idea no encontrada.');
        }

        $moodboard = (new IdeaMoodboardItemModel())
            ->where('idea_id', $id)
            ->orderBy('orden', 'ASC')
            ->findAll();

        return view('sesiones/ideas/show', ['idea' => $idea, 'moodboard' => $moodboard]);
    }

    public function edit(int $id)
    {
        $idea = (new IdeaModel())->find($id);
        if (!$idea) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Idea no encontrada.');
        }

        return view('sesiones/ideas/form', ['idea' => $idea]);
    }

    public function update(int $id)
    {
        $model = new IdeaModel();
        $post  = $this->request->getPost();

        $data = [
            'id'     => $id,
            'titulo' => $post['titulo'] ?? '',
            'notas'  => $post['notas'] ?? null,
        ];

        if (!$model->save($data)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }

        return redirect()->to(site_url('sesiones/ideas/' . $id))->with('success', 'Idea actualizada correctamente.');
    }

    public function delete(int $id)
    {
        $items = (new IdeaMoodboardItemModel())->where('idea_id', $id)->findAll();
        foreach ($items as $item) {
            if ($item['origen'] === 'archivo' && $item['ruta_archivo']) {
                $this->borrarArchivoFisico($item['ruta_archivo']);
            }
        }

        (new IdeaModel())->delete($id);

        return redirect()->to(site_url('sesiones'))->with('success', 'Idea eliminada.');
    }

    /**
     * Promueve una idea a sesión real: crea la sesión en 'planificacion' para
     * las partes que aplican, traslada su moodboard (como moodboard general,
     * sin situación) y borra la idea.
     */
    public function promover(int $id)
    {
        $ideaModel = new IdeaModel();
        $idea      = $ideaModel->find($id);
        if (!$idea) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Idea no encontrada.');
        }

        $sesionModel = new SesionModel();
        $sesionModel->insert([
            'titulo'       => $idea['titulo'],
            'notas'        => $idea['notas'],
            'estado_foto'  => (int) $idea['tiene_foto'] === 1 ? 'planificacion' : null,
            'estado_video' => (int) $idea['tiene_video'] === 1 ? 'planificacion' : null,
        ]);
        $sesionId = $sesionModel->getInsertID();

        $moodboardModel = new MoodboardItemModel();
        $ideaMoodboard  = (new IdeaMoodboardItemModel())->where('idea_id', $id)->orderBy('orden', 'ASC')->findAll();

        foreach ($ideaMoodboard as $item) {
            $moodboardModel->insert([
                'sesion_id'    => $sesionId,
                'situacion_id' => null,
                'origen'       => $item['origen'],
                'ruta_archivo' => $item['ruta_archivo'],
                'url_externa'  => $item['url_externa'],
                'nota'         => $item['nota'],
                'orden'        => $item['orden'],
            ]);
        }

        // Cascada: borra las filas de idea_moodboard_items (ya copiadas arriba).
        $ideaModel->delete($id);

        return redirect()->to(site_url('sesiones/' . $sesionId))->with('success', 'Idea promovida a sesión.');
    }

    // ========== MOODBOARD (mismo patrón que Sesiones::moodboard*, con idea_id) ==========

    public function moodboardSubir(int $ideaId)
    {
        $nota = trim((string) $this->request->getPost('nota')) ?: null;

        $files = $this->request->getFileMultiple('archivo') ?? [];
        if (empty($files) && $this->request->getFile('archivo')) {
            $files = [$this->request->getFile('archivo')];
        }

        if (empty($files)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Ningún archivo recibido.']);
        }

        $model      = new IdeaMoodboardItemModel();
        $orden      = (int) ($model->where('idea_id', $ideaId)->countAllResults() ?: 0);
        $insertados = [];

        $targetDir = rtrim($this->publicDir, '/') . '/' . $ideaId;
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
                'idea_id'      => $ideaId,
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

    public function moodboardAgregarEnlace(int $ideaId)
    {
        $url  = trim((string) $this->request->getPost('url_externa'));
        $nota = trim((string) $this->request->getPost('nota')) ?: null;

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'URL inválida.']);
        }

        $model = new IdeaMoodboardItemModel();
        $orden = (int) ($model->where('idea_id', $ideaId)->countAllResults() ?: 0);

        $model->insert([
            'idea_id'     => $ideaId,
            'origen'      => 'enlace',
            'url_externa' => $url,
            'nota'        => $nota,
            'orden'       => $orden,
        ]);

        return $this->response->setJSON(['ok' => true, 'item' => $model->find($model->getInsertID())]);
    }

    public function moodboardBorrar(int $ideaId, int $itemId)
    {
        $model = new IdeaMoodboardItemModel();
        $item  = $model->where('idea_id', $ideaId)->find($itemId);

        if ($item) {
            if ($item['origen'] === 'archivo' && $item['ruta_archivo']) {
                $this->borrarArchivoFisico($item['ruta_archivo']);
            }
            $model->delete($itemId);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Borra el archivo físico bajo /public correspondiente a una ruta
     * relativa guardada en BD (mismo patrón que Sesiones::borrarArchivoFisico()).
     */
    protected function borrarArchivoFisico(string $rutaRelativa): void
    {
        $path = FCPATH . ltrim($rutaRelativa, '/');
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
