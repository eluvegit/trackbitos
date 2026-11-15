<?php

namespace App\Controllers;

use App\Models\{RodajesProyectoModel, RodajesEscenaModel, RodajesEscenaImagenModel};

class RodajesEscenas extends BaseController
{
    protected $helpers = ['rodajes'];

    // Carpeta pública donde se guardarán las imágenes
    protected $publicDir = 'images/rodajes'; // dentro de /public

    public function index($proyectoId)
    {
        $proyectos = new RodajesProyectoModel();
        $escenasModel = new RodajesEscenaModel();
        $imgsModel = new RodajesEscenaImagenModel();

        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));

        $escenas = $escenasModel->where('proyecto_id', $proyectoId)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $multimedia = [];
        if (!empty($escenas)) {
            $ids = array_column($escenas, 'id');

            // Traemos todos los archivos asociados a las escenas de este proyecto
            $files = $imgsModel->whereIn('escena_id', $ids)->findAll();

            foreach ($files as $f) {
                $eid = $f['escena_id'];
                $ruta = $f['ruta'];
                $numMedia[$eid] = ($numMedia[$eid] ?? 0) + 1;

                // Extraer extensión de forma segura
                $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

                // Definir si es video
                $esVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);

                /**
                 * LÓGICA DE PRIORIDAD:
                 * 1. Si no hay nada para esta escena, lo guardamos.
                 * 2. Si lo que hay es una imagen y lo nuevo es un vídeo, lo pisamos (prioridad vídeo).
                 */
                if (!isset($multimedia[$eid]) || ($esVideo && !$multimedia[$eid]['es_video'])) {
                    $multimedia[$eid] = [
                        'ruta'     => $ruta,
                        'es_video' => $esVideo,
                        'ext'      => $ext // Fundamental para el <source type="video/...">
                    ];
                }
            }
        }

        $sesion = !empty($proyecto['sesion_id'])
            ? (new \App\Models\SesionModel())->find((int) $proyecto['sesion_id'])
            : null;

        return view(rod_vista('rodajes/escenas/index'), [
            'proyecto'   => $proyecto,
            'sesion'     => $sesion,
            'escenas'    => $escenas,
            'multimedia' => $multimedia, // Ahora sí viaja con la info de extensión
            'numMedia'   => $numMedia ?? [],
        ]);
    }

    public function dialogos($proyectoId)
    {
        $proyectos = new \App\Models\RodajesProyectoModel();
        $escenasModel = new \App\Models\RodajesEscenaModel();

        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) {
            return redirect()->to(site_url('rodajes'));
        }

        // Buscamos solo escenas con diálogos, ordenadas por el plan de rodaje
        $escenas = $escenasModel->where('proyecto_id', $proyectoId)
            ->where('sonido_dialogo_escrito !=', '')
            ->where('sonido_dialogo_escrito IS NOT NULL')
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return view(rod_vista('rodajes/dialogos'), [
            'proyecto' => $proyecto,
            'escenas'  => $escenas
        ]);
    }

    public function create($proyectoId)
    {
        $proyectos = new RodajesProyectoModel();
        $data['proyecto'] = $proyectos->find($proyectoId);
        if (!$data['proyecto']) {
            return redirect()->to(site_url('rodajes'));
        }
        $data['escena'] = null;
        $data['bloques'] = $this->bloquesDelProyecto((int) $proyectoId);
        $ultima = (new RodajesEscenaModel())->select('orden')->where('proyecto_id', $proyectoId)->orderBy('orden', 'DESC')->first();
        $data['ordenSiguiente'] = (int) ($ultima['orden'] ?? 0) + 1;
        return view(rod_vista('rodajes/escenas/form'), $data);
    }

    /** Formato enviado por el formulario, normalizado (NULL = sin definir). */
    private function formatoPost(): ?string
    {
        $f = (string) $this->request->getPost('formato');
        return in_array($f, RodajesEscenaModel::FORMATOS, true) ? $f : null;
    }

    /** Nombres de bloque ya usados en el proyecto (para sugerirlos en el formulario). */
    private function bloquesDelProyecto(int $proyectoId): array
    {
        $filas = (new RodajesEscenaModel())->select('escena_bloque')
            ->where('proyecto_id', $proyectoId)->where('escena_bloque !=', '')->where('escena_bloque IS NOT NULL')
            ->groupBy('escena_bloque')->findAll();
        return array_column($filas, 'escena_bloque');
    }

    public function store($proyectoId)
    {
        $escenas  = new RodajesEscenaModel();
        $this->request->setGlobal('post', array_merge(
            $this->request->getPost(),
            [
                'proyecto_id'      => $proyectoId,
                'plano_actores'    => $this->request->getPost('plano_actores') ? 'S' : 'N',
                'sonido_ambiente'  => $this->request->getPost('sonido_ambiente') ? 'S' : 'N',
                'sonido_antiviento' => $this->request->getPost('sonido_antiviento') ? 'S' : 'N',
            ]
        ));

        $post = $this->request->getPost();
        if (array_key_exists('formato', $post)) $post['formato'] = $this->formatoPost();

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }
        $escenaId = $escenas->getInsertID();

        // Subidas múltiples por categoría -> a /public/images/rodajes/{escenaId}/
        $this->handleUploads($proyectoId, $escenaId, 'lugar_objetos');
        $this->handleUploads($proyectoId, $escenaId, 'inspiracion');

        // "Guardar y otra": encadenar escenas rápido (la lista de tomas se escribe de corrido).
        if ($this->request->getPost('otra')) {
            return redirect()->to(site_url("rodajes/$proyectoId/escenas/create?bloque=" . rawurlencode((string) $this->request->getPost('escena_bloque'))
                . '&formato=' . rawurlencode((string) $this->formatoPost())))
                ->with('success', 'Escena #' . (int) $this->request->getPost('orden') . ' guardada.');
        }

        return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
    }

    public function edit($proyectoId, $id)
    {
        $proyectos = new RodajesProyectoModel();
        $escenas   = new RodajesEscenaModel();
        $imgs      = new RodajesEscenaImagenModel();

        $data['proyecto'] = $proyectos->find($proyectoId);
        $data['escena']   = $escenas->find($id);
        if (!$data['proyecto'] || !$data['escena']) {
            return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
        }

        // --- Navegación anterior/siguiente dentro del proyecto (orden, id) ---
        $curOrden = (int) $data['escena']['orden'];
        $curId    = (int) $data['escena']['id'];

        $escenasModel = new \App\Models\RodajesEscenaModel();

        // Siguiente: (orden > actual) OR (orden = actual AND id > actual) - asc
        $next = $escenasModel->where('proyecto_id', $proyectoId)
            ->groupStart()
            ->where('orden >', $curOrden)
            ->orGroupStart()
            ->where('orden', $curOrden)
            ->where('id >', $curId)
            ->groupEnd()
            ->groupEnd()
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->first();

        // Anterior: (orden < actual) OR (orden = actual AND id < actual) - desc
        $prev = $escenasModel->where('proyecto_id', $proyectoId)
            ->groupStart()
            ->where('orden <', $curOrden)
            ->orGroupStart()
            ->where('orden', $curOrden)
            ->where('id <', $curId)
            ->groupEnd()
            ->groupEnd()
            ->orderBy('orden', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        $data['nextId'] = $next['id'] ?? null;
        $data['prevId'] = $prev['id'] ?? null;


        $data['imagenes_lugar'] = $imgs->where(['escena_id' => $id, 'categoria' => 'lugar_objetos'])->findAll();
        $data['imagenes_insp']  = $imgs->where(['escena_id' => $id, 'categoria' => 'inspiracion'])->findAll();
        $data['bloques']        = $this->bloquesDelProyecto((int) $proyectoId);

        return view(rod_vista('rodajes/escenas/form'), $data);
    }

    public function update($proyectoId, $id)
    {
        $escenas = new RodajesEscenaModel();

        $post = $this->request->getPost();
        $post['id']               = $id;
        $post['proyecto_id']      = $proyectoId;
        $post['plano_actores']    = $this->request->getPost('plano_actores') ? 'S' : 'N';
        $post['sonido_ambiente']  = $this->request->getPost('sonido_ambiente') ? 'S' : 'N';
        $post['sonido_antiviento'] = $this->request->getPost('sonido_antiviento') ? 'S' : 'N';

        // Formato: solo lo trae el formulario nuevo; al cambiarlo, el estado de toma se mueve de parte.
        if (array_key_exists('formato', $post)) {
            $post['formato'] = $this->formatoPost();
            $previa = $escenas->where('proyecto_id', (int) $proyectoId)->find((int) $id);
            if ($previa) $post = rod_cambio_formato($previa, $post['formato']) + $post;
        }

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }

        // Nuevas subidas (opcionales)
        $this->handleUploads($proyectoId, $id, 'lugar_objetos');
        $this->handleUploads($proyectoId, $id, 'inspiracion');

        return redirect()->to(site_url("rodajes/$proyectoId/escenas/edit/$id"));
    }

    public function delete($proyectoId, $id)
    {
        $escenas = new RodajesEscenaModel();
        $escenas->delete($id);
        return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
    }

    public function deleteImage($proyectoId, $escenaId, $imageId)
    {
        $imgs = new RodajesEscenaImagenModel();
        $img  = $imgs->find($imageId);
        if ($img) {
            // Ruta pública
            $path = FCPATH . ltrim($img['ruta'], '/'); // ruta relativa guardada a /public
            if (is_file($path)) {
                @unlink($path);
            }
            $imgs->delete($imageId);
        }
        return redirect()->to(site_url("rodajes/$proyectoId/escenas/edit/$escenaId"));
    }

    /**
     * Guarda imágenes en /public/images/rodajes/{escenaId}/ y persiste rutas públicas.
     * $categoria: 'lugar_objetos' | 'inspiracion'
     */
    protected function handleUploads(int $proyectoId, int $escenaId, string $categoria): void
    {
        $files = $this->request->getFiles();
        if (!isset($files[$categoria])) {
            return;
        }

        $targetDir = rtrim($this->publicDir, '/') . '/' . $escenaId;
        $absDir    = rtrim(FCPATH, '/') . '/' . $targetDir;

        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }

        $imgs = new RodajesEscenaImagenModel();

        foreach ($files[$categoria] as $file) {
            if (!$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $mime = $file->getMimeType();

            // --- CAMBIO AQUÍ: Aceptar imágenes Y vídeos ---
            $isImage = strpos($mime, 'image/') === 0;
            $isVideo = strpos($mime, 'video/') === 0;

            if (!$isImage && !$isVideo) {
                continue;
            }
            // ----------------------------------------------

            $newName = $file->getRandomName();
            $file->move($absDir, $newName);

            $relative = $targetDir . '/' . $newName;

            $imgs->insert([
                'escena_id' => $escenaId,
                'categoria' => $categoria,
                'ruta'      => $relative,
            ]);
        }
    }

    public function show($proyectoId, $id)
    {
        $proyectos = new \App\Models\RodajesProyectoModel();
        $escenasModel   = new \App\Models\RodajesEscenaModel();
        $imgs      = new \App\Models\RodajesEscenaImagenModel();

        // 1. Validar existencia del proyecto y la escena
        $proyecto = $proyectos->find($proyectoId);
        $escena   = $escenasModel->find($id);

        if (!$proyecto || !$escena || (int)$escena['proyecto_id'] !== (int)$proyectoId) {
            return redirect()->to(site_url("rodajes/$proyectoId/escenas"));
        }

        // 2. Lógica de navegación (Anterior / Siguiente)
        $curOrden = (int) $escena['orden'];
        $curId    = (int) $escena['id'];

        // Siguiente escena: (orden superior) O (mismo orden pero ID superior)
        $next = $escenasModel->where('proyecto_id', $proyectoId)
            ->groupStart()
            ->where('orden >', $curOrden)
            ->orGroupStart()
            ->where('orden', $curOrden)
            ->where('id >', $curId)
            ->groupEnd()
            ->groupEnd()
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->first();

        // Escena anterior: (orden inferior) O (mismo orden pero ID inferior)
        $prev = $escenasModel->where('proyecto_id', $proyectoId)
            ->groupStart()
            ->where('orden <', $curOrden)
            ->orGroupStart()
            ->where('orden', $curOrden)
            ->where('id <', $curId)
            ->groupEnd()
            ->groupEnd()
            ->orderBy('orden', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        // 3. Obtener archivos multimedia por categoría
        $imagenes_lugar = $imgs->where(['escena_id' => $id, 'categoria' => 'lugar_objetos'])->findAll();
        $imagenes_insp  = $imgs->where(['escena_id' => $id, 'categoria' => 'inspiracion'])->findAll();

        // 4. Pasar todo a la vista
        return view(rod_vista('rodajes/escenas/show'), [
            'proyecto'        => $proyecto,
            'escena'          => $escena,
            'nextId'          => $next['id'] ?? null,
            'prevId'          => $prev['id'] ?? null,
            'imagenes_lugar'  => $imagenes_lugar,
            'imagenes_insp'   => $imagenes_insp,
        ]);
    }

    /**
     * Storyboard: tarjetas con la primera imagen de lugar y de inspiración de
     * cada escena, agrupadas por bloque (cada transición, aparte) o por
     * clasificación (?agrupar=clasificacion). También sirve para imprimir.
     * Sustituye al antiguo «plan por clasificación» (ruta ordenrodaje).
     */
    public function storyboard($proyectoId)
    {
        $pid      = (int) $proyectoId;
        $proyecto = (new RodajesProyectoModel())->find($pid);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));

        $agrupar = $this->request->getGet('agrupar') === 'clasificacion' ? 'clasificacion' : 'bloque';
        $escenas = (new RodajesEscenaModel())->where('proyecto_id', $pid)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();

        // Portadas: primera imagen de cada categoría (un vídeo solo si no hay imagen).
        $portadas = [];
        if ($escenas) {
            $files = (new RodajesEscenaImagenModel())->whereIn('escena_id', array_column($escenas, 'id'))
                ->orderBy('id', 'ASC')->findAll();
            foreach ($files as $f) {
                $cat    = $f['categoria'] === 'inspiracion' ? 'insp' : 'lugar';
                $actual = $portadas[$f['escena_id']][$cat] ?? null;
                if ($actual === null || (rod_es_video($actual) && !rod_es_video($f['ruta']))) {
                    $portadas[$f['escena_id']][$cat] = $f['ruta'];
                }
            }
        }

        // Grupos en el orden en que aparece su primera escena (las escenas ya vienen en orden narrativo).
        $grupos = [];
        foreach ($escenas as $e) {
            $transicion = false;
            if ($agrupar === 'clasificacion') {
                $raw    = trim((string) $e['plano_hora_dia']);
                $clave  = $raw === '' ? '~' : 'C:' . rod_normalizar($raw);
                $titulo = $raw !== '' ? $raw : 'Sin clasificación';
            } else {
                $raw = trim((string) $e['escena_bloque']);
                $n   = rod_normalizar($raw);
                $transicion = $n !== '' && (str_starts_with($n, 'TRANS') || str_contains($n, 'TRANSICION'));
                $clave  = $transicion ? 'T:' . $e['id'] : ($n === '' ? '~' : 'B:' . $n);
                $titulo = $raw !== '' ? $raw : 'Sin bloque';
            }
            $grupos[$clave] ??= ['_title' => $titulo, '_first' => (int) $e['orden'], 'transicion' => $transicion, 'items' => []];
            $grupos[$clave]['items'][] = [
                'escena'      => $e,
                'cover_lugar' => $portadas[$e['id']]['lugar'] ?? null,
                'cover_insp'  => $portadas[$e['id']]['insp'] ?? null,
            ];
        }

        return view(rod_vista('rodajes/escenas/storyboard'), [
            'proyecto'   => $proyecto,
            'groups'     => $grupos,
            'agrupar'    => $agrupar,
            'hayFormato' => (bool) array_filter($escenas, static fn ($e) => !empty($e['formato'])),
        ]);
    }

    /** Antiguo «plan por clasificación»: ahora es el storyboard agrupado por clasificación. */
    public function storyboardPorClasificacion($proyectoId)
    {
        return redirect()->to(site_url('rodajes/' . (int) $proyectoId . '/escenas/storyboard?agrupar=clasificacion'));
    }
}
