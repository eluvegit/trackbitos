<?php

namespace App\Controllers;

use App\Models\{MoodboardItemModel, RodajesProyectoModel, RodajesEscenaModel, RodajesEscenaImagenModel, RodajesPuntoModel, RodajesSetupModel, RodajesUbicacionModel, RodajesVarianteModel, SesionEquipoModel};

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

        RodajesFlujo::sincronizarBloques((int) $proyectoId);
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

        // Las tomas creadas con el flujo (ingesta → revisar) tienen su foto en el
        // moodboard de la sesión, no en rodajes_escena_imagenes. Si la escena no
        // tiene imagen propia: foto de la toma → foto de una variante → imagen
        // guía de su ubicación. Así la lista no sale llena de huecos grises.
        $escenas  = RodajesFlujo::conVariantes($escenas);
        $fotos    = RodajesFlujo::fotosDeTomas(array_column($escenas, 'id'));
        $ubicImg  = [];
        $setups   = [];
        if ($escenas) {
            foreach ((new \App\Models\RodajesUbicacionModel())->where('proyecto_id', (int) $proyectoId)->findAll() as $z) {
                if (!empty($z['imagen'])) $ubicImg[(int) $z['id']] = $z['imagen'];
            }
            foreach ((new \App\Models\RodajesSetupModel())->where('proyecto_id', (int) $proyectoId)->findAll() as $s) {
                $setups[(int) $s['id']] = $s;
            }
        }
        $esFoto = static fn ($i) => $i && $i['origen'] === 'archivo' && !empty($i['ruta_archivo']);
        $varFotos = [];
        foreach ($escenas as $e) {
            $eid = (int) $e['id'];
            $deToma = array_values(array_filter($fotos['t'][$eid] ?? [], $esFoto));
            foreach ($e['variantes'] as $v) {
                $i = $v['moodboard_item_id'] ? ($fotos['v'][(int) $v['moodboard_item_id']] ?? null) : null;
                if ($esFoto($i)) $varFotos[$eid][] = ['ruta' => $i['ruta_archivo'], 'texto' => (string) $v['texto']];
            }
            $numMedia[$eid] = ($numMedia[$eid] ?? 0) + count($deToma) + count($varFotos[$eid] ?? []);
            if (isset($multimedia[$eid])) continue;
            $ruta = $deToma[0]['ruta_archivo'] ?? ($varFotos[$eid][0]['ruta'] ?? null);
            $guia = false;
            if (!$ruta && !empty($e['ubicacion_id']) && isset($ubicImg[(int) $e['ubicacion_id']])) {
                $ruta = $ubicImg[(int) $e['ubicacion_id']];
                $guia = true;
            }
            if ($ruta) {
                $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
                $multimedia[$eid] = ['ruta' => $ruta, 'es_video' => in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true), 'ext' => $ext, 'guia' => $guia];
            } elseif ($idea = ($fotos['t'][$eid][0] ?? null)) {
                // Idea de texto o enlace: al menos se ve qué era.
                $ideas[$eid] = ['texto' => rod_texto_idea($idea), 'enlace' => $idea['origen'] === 'enlace'];
            }
        }

        $sesion = !empty($proyecto['sesion_id'])
            ? (new \App\Models\SesionModel())->find((int) $proyecto['sesion_id'])
            : null;

        // Dos órdenes: narrativo (por temáticas, el de siempre) o el de rodaje (el del Plan).
        $ordenLista = $this->request->getGet('orden') === 'rodaje' ? 'rodaje' : 'narrativo';
        $gruposRodaje = [];
        if ($ordenLista === 'rodaje') {
            foreach (RodajesFlujo::construirPlan((int) $proyectoId, $escenas) as $f) foreach ($f['zonas'] as $z) foreach ($z['setups'] as $st) {
                $gruposRodaje[] = [
                    'nombre'  => implode(' · ', array_filter([$f['nombre'], $z['zona'] ? $z['n'] . '. ' . $z['zona']['nombre'] : 'Sin zona', $st['setup']['nombre'] ?? 'Sin setup'])),
                    'escenas' => $st['escenas'],
                ];
            }
        }

        return view(rod_vista('rodajes/escenas/index'), [
            'ordenLista'   => $ordenLista,
            'gruposRodaje' => $gruposRodaje,
            'proyecto'   => $proyecto,
            'sesion'     => $sesion,
            'escenas'    => $escenas,
            'multimedia' => $multimedia, // Ahora sí viaja con la info de extensión
            'numMedia'   => $numMedia ?? [],
            'varFotos'   => $varFotos,
            'ordenBloques' => RodajesFlujo::ordenBloques($sesion),
            'ideas'      => $ideas ?? [],
            'setups'     => $setups,
            'pasos'      => RodajesFlujo::pasos((int) $proyectoId, $sesion),
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
        $data += $this->datosFlujo($data['proyecto'], null);
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
        $proyecto = (new RodajesProyectoModel())->find($proyectoId);
        if (!empty($proyecto['sesion_id'])) {
            return array_column((new \App\Models\SituacionModel())->where('sesion_id', (int) $proyecto['sesion_id'])->orderBy('orden', 'ASC')->findAll(), 'nombre');
        }
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
        $this->aplicarFlujo($post, (int) $proyectoId);
        $this->bloqueATematica($post);

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }
        $escenaId = $escenas->getInsertID();
        $this->guardarVariantes((int) $escenaId);

        $idea = $this->ideaDelPost((int) $proyectoId);
        if ($idea) {
            (new MoodboardItemModel())->update($idea['id'], ['estado' => 'toma', 'escena_id' => $escenaId]);
            if ($this->request->getPost('situacion_id') === null && $idea['situacion_id']) {
                $escenas->skipValidation(true)->update($escenaId, ['situacion_id' => $idea['situacion_id']]);
            }
        }
        RodajesFlujo::sincronizarBloques((int) $proyectoId);
        $this->handleUploads($proyectoId, $escenaId, 'lugar_objetos');
        $this->handleUploads($proyectoId, $escenaId, 'inspiracion');
        if ($idea) {
            $flujo = new RodajesFlujo();
            $items = (new MoodboardItemModel())->where('sesion_id', $idea['sesion_id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
            $sig = $flujo->siguientePendiente($items, $idea);
            return redirect()->to(site_url("rodajes/$proyectoId/revisar" . ($sig ? '?idea=' . $sig['id'] : '')))->with('success', 'Toma creada.');
        }
        if ($this->request->getPost('volver') === 'ubicaciones') {
            return redirect()->to(site_url("rodajes/$proyectoId/ubicaciones"))->with('success', 'Toma creada.');
        }


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
        $data += $this->datosFlujo($data['proyecto'], $data['escena']);

        return view(rod_vista('rodajes/escenas/form'), $data);
    }

    public function update($proyectoId, $id)
    {
        $escenas = new RodajesEscenaModel();

        $post = $this->request->getPost();
        unset($post['orden']);   // el nº en la historia lo pone la pizarra Narrativa (renumerarNarrativo)
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
        $this->aplicarFlujo($post, (int) $proyectoId);
        $this->bloqueATematica($post);

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }
        $this->guardarVariantes((int) $id);
        RodajesFlujo::sincronizarBloques((int) $proyectoId);

        // Nuevas subidas (opcionales)
        $this->handleUploads($proyectoId, $id, 'lugar_objetos');
        $this->handleUploads($proyectoId, $id, 'inspiracion');

        return redirect()->to(site_url("rodajes/$proyectoId/escenas/edit/$id"));
    }

    public function delete($proyectoId, $id)
    {
        $escenas = new RodajesEscenaModel();
        if ($escenas->where('proyecto_id', (int) $proyectoId)->find((int) $id)) {
            $escenas->delete($id);
            (new RodajesVarianteModel())->where('escena_id', (int) $id)->delete();
            (new MoodboardItemModel())->where('escena_id', (int) $id)->set(['estado' => 'pendiente', 'escena_id' => null, 'variante_id' => null])->update();
        }
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

    private function datosFlujo(array $proyecto, ?array $escena): array
    {
        $pid    = (int) $proyecto['id'];
        $zonas  = (new RodajesUbicacionModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        $puntos = $zonas ? (new RodajesPuntoModel())->whereIn('ubicacion_id', array_column($zonas, 'id'))->orderBy('orden', 'ASC')->findAll() : [];
        $req    = $this->request;
        $idea   = null;
        if (!$escena && $req->getGet('idea')) $idea = $this->ideaDelPost($pid, (int) $req->getGet('idea'));
        $refs = [];
        if ($escena) $refs = (new MoodboardItemModel())->where('escena_id', $escena['id'])->findAll();
        elseif ($idea) $refs = [$idea];
        $zonaInicial = '';
        if ($escena && $escena['ubicacion_id']) {
            foreach ($zonas as $z) if ((int) $z['id'] === (int) $escena['ubicacion_id']) $zonaInicial = $z['nombre'];
        } elseif (!$escena && $req->getGet('ubicacion')) {
            foreach ($zonas as $z) if ((int) $z['id'] === (int) $req->getGet('ubicacion')) $zonaInicial = $z['nombre'];
        }
        return [
            'zonas'       => $zonas,
            'puntos'      => $puntos,
            'setups'      => (new RodajesSetupModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll(),
            'propsLista'  => RodajesFlujo::propsConocidos($pid, $proyecto['sesion_id'] ? (int) $proyecto['sesion_id'] : null),
            'variantes'   => $escena ? (new RodajesVarianteModel())->where('escena_id', $escena['id'])->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() : [],
            'idea'        => $idea,
            'refsIdeas'   => $refs,
            'zonaInicial' => $zonaInicial,
            // nº de tomas por setup: el aviso «cambia también en sus otras N tomas»
            'tomasPorSetup' => array_column(db_connect()->query('SELECT setup_id, COUNT(*) n FROM rodajes_escenas WHERE proyecto_id = ? AND setup_id IS NOT NULL AND deleted_at IS NULL GROUP BY setup_id', [$pid])->getResultArray(), 'n', 'setup_id'),
            'volver'      => $req->getGet('ubicacion') ? 'ubicaciones' : '',
        ];
    }

    private function ideaDelPost(int $pid, ?int $id = null): ?array
    {
        $id ??= (int) $this->request->getPost('idea_id');
        if (!$id) return null;
        $proyecto = (new RodajesProyectoModel())->find($pid);
        if (!$proyecto || !$proyecto['sesion_id']) return null;
        return (new MoodboardItemModel())->where('sesion_id', $proyecto['sesion_id'])->find($id);
    }

    /** Campos nuevos de la ficha corta (solo si los envía el formulario nuevo). */
    /** Bloque vacío en el formulario = la toma sale de su temática (si escribe otro, sincronizarBloques la mueve). */
    private function bloqueATematica(array &$post): void
    {
        if (array_key_exists('escena_bloque', $post) && trim((string) $post['escena_bloque']) === '') $post['situacion_id'] = null;
    }

    private function aplicarFlujo(array &$post, int $pid): void
    {
        if (!array_key_exists('flujo', $post)) return;
        unset($post['flujo']);

        $zona = trim((string) ($post['zona_nombre'] ?? ''));
        $post['ubicacion_id'] = $zona !== '' ? RodajesFlujo::zonaId($pid, $zona) : null;
        if ($post['ubicacion_id']) {
            $z = (new RodajesUbicacionModel())->find($post['ubicacion_id']);
            $post['escena_ubicacion'] = $z['nombre'];
        } else {
            $post['escena_ubicacion'] = '';
        }
        $punto = (int) ($post['punto_id'] ?? 0);
        $p = $punto ? (new RodajesPuntoModel())->find($punto) : null;
        $post['punto_id'] = $p && (int) $p['ubicacion_id'] === (int) $post['ubicacion_id'] ? $punto : null;

        $setup = (string) ($post['setup_id'] ?? '');
        if ($setup === 'nuevo') {
            $n = trim((string) ($post['setup_nuevo'] ?? ''));
            $post['setup_id'] = $n !== '' ? RodajesFlujo::nuevoSetup($pid, $n) : null;
        } else {
            $post['setup_id'] = (int) $setup && (new RodajesSetupModel())->where('proyecto_id', $pid)->find((int) $setup) ? (int) $setup : null;
        }
        // Setup y cámara al unísono (óptica / fps / soporte).
        if ($post['setup_id']) $post = RodajesFlujo::alinearTomaConSetup($post, (int) $post['setup_id']);
        $sv = (int) ($post['setup_video_id'] ?? 0);
        $post['setup_video_id'] = ($post['formato'] ?? null) === 'ambas' && $sv && (new RodajesSetupModel())->where('proyecto_id', $pid)->find($sv) ? $sv : null;

        $post['franja'] = array_key_exists((string) ($post['franja'] ?? ''), RodajesEscenaModel::FRANJAS) ? $post['franja'] : null;
        $post['prioridad'] = ($post['prioridad'] ?? '') === 'opc' ? 'opc' : 'imp';

        $props = array_map('trim', (array) ($post['props'] ?? []));
        foreach (explode(',', (string) ($post['props_nuevo'] ?? '')) as $n) $props[] = trim($n);
        $unicos = [];
        foreach ($props as $pr) if ($pr !== '') $unicos[rod_normalizar($pr)] ??= mb_substr($pr, 0, 80);
        $post['props'] = $unicos ? json_encode(array_values($unicos), JSON_UNESCAPED_UNICODE) : null;

        $proyecto = (new RodajesProyectoModel())->find($pid);
        if ($unicos && $proyecto && $proyecto['sesion_id']) {
            $eq = new SesionEquipoModel();
            $ya = array_map(static fn ($r) => rod_normalizar($r['item']), $eq->where('sesion_id', $proyecto['sesion_id'])->where('tipo', 'props')->findAll());
            foreach ($unicos as $k => $pr) {
                if (!in_array($k, $ya, true)) $eq->insert(['sesion_id' => $proyecto['sesion_id'], 'item' => $pr, 'tipo' => 'props', 'orden' => 999]);
            }
        }
    }

    private function guardarVariantes(int $escenaId): void
    {
        $post = $this->request->getPost();
        if (!array_key_exists('flujo', $post)) return;
        $vm = new RodajesVarianteModel();
        $actuales = array_column($vm->where('escena_id', $escenaId)->findAll(), null, 'id');
        $vistas = [];
        $orden = 0;
        foreach ((array) ($post['variantes'] ?? []) as $v) {
            $texto = mb_substr(trim((string) ($v['texto'] ?? '')), 0, 255);
            if ($texto === '') continue;
            $datos = ['texto' => $texto, 'prioridad' => ($v['prioridad'] ?? '') === 'opc' ? 'opc' : 'imp', 'orden' => $orden++];
            $id = (int) ($v['id'] ?? 0);
            if ($id && isset($actuales[$id])) {
                $vm->update($id, $datos);
                $vistas[$id] = true;
            } else {
                $vm->insert($datos + ['escena_id' => $escenaId]);
            }
        }
        foreach ($actuales as $id => $v) {
            if (isset($vistas[$id])) continue;
            $vm->delete($id);
            if ($v['moodboard_item_id']) {
                (new MoodboardItemModel())->update((int) $v['moodboard_item_id'], ['estado' => 'pendiente', 'escena_id' => null, 'variante_id' => null]);
            }
        }
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

        // 4. Lo que viene del flujo: foto(s) de la idea, variantes, zona con su
        //    imagen guía y punto, setup. Antes la ficha solo miraba las imágenes
        //    subidas a mano y una toma creada al revisar ideas salía vacía.
        $escena = RodajesFlujo::conVariantes([$escena])[0];
        $fotos  = RodajesFlujo::fotosDeTomas([(int) $escena['id']]);
        $zona   = !empty($escena['ubicacion_id']) ? (new RodajesUbicacionModel())->find((int) $escena['ubicacion_id']) : null;
        $punto  = !empty($escena['punto_id']) ? (new RodajesPuntoModel())->find((int) $escena['punto_id']) : null;
        $setup  = !empty($escena['setup_id']) ? (new RodajesSetupModel())->find((int) $escena['setup_id']) : null;

        return view(rod_vista('rodajes/escenas/show'), [
            'proyecto'        => $proyecto,
            'escena'          => $escena,
            'nextId'          => $next['id'] ?? null,
            'prevId'          => $prev['id'] ?? null,
            'imagenes_lugar'  => $imagenes_lugar,
            'imagenes_insp'   => $imagenes_insp,
            'ideas'           => $fotos['t'][(int) $escena['id']] ?? [],
            'fotosVar'        => $fotos['v'],
            'zona'            => $zona,
            'punto'           => $punto,
            'setup'           => $setup,
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
            'colores'    => rod_colores($escenas, $agrupar === 'clasificacion' ? 'plano_hora_dia' : 'escena_bloque'),
        ]);
    }

    /** Antiguo «plan por clasificación»: ahora es el storyboard agrupado por clasificación. */
    public function storyboardPorClasificacion($proyectoId)
    {
        return redirect()->to(site_url('rodajes/' . (int) $proyectoId . '/escenas/storyboard?agrupar=clasificacion'));
    }
}
