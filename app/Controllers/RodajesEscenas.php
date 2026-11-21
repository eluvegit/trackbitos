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

        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));

        RodajesFlujo::sincronizarBloques((int) $proyectoId);
        $escenas = $escenasModel->where('proyecto_id', $proyectoId)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $escenas = RodajesFlujo::conVariantes($escenas);
        $p = $this->portadas((int) $proyectoId, $escenas);

        $sesion = !empty($proyecto['sesion_id'])
            ? (new \App\Models\SesionModel())->find((int) $proyecto['sesion_id'])
            : null;

        // Dos órdenes: narrativo (por temáticas, el de siempre) o el de rodaje (el del Plan).
        $ordenLista   = $this->ordenPedido();
        $gruposRodaje = $ordenLista === 'rodaje' ? $this->gruposRodaje((int) $proyectoId, $escenas) : [];

        return view(rod_vista('rodajes/escenas/index'), [
            'ordenLista'   => $ordenLista,
            'gruposRodaje' => $gruposRodaje,
            'proyecto'   => $proyecto,
            'sesion'     => $sesion,
            'escenas'    => $escenas,
            'multimedia' => $p['multimedia'],
            'varFotos'   => $p['varFotos'],
            'ordenBloques' => RodajesFlujo::ordenBloques($sesion),
            'ideas'      => $p['ideas'],
            'setups'     => $p['setups'],
            'puntos'     => $p['puntos'],
            'pasos'      => RodajesFlujo::pasos((int) $proyectoId, $sesion),
        ]);
    }

    /** Orden pedido por la URL: 'rodaje' (el del Plan) o 'narrativo' (el de la historia). */
    private function ordenPedido(): string
    {
        return $this->request->getGet('orden') === 'rodaje' ? 'rodaje' : 'narrativo';
    }

    /** Grupos del Plan (franja · zona · setup) en su orden, con su lado de la jornada. */
    private function gruposRodaje(int $pid, array $escenas): array
    {
        $out = [];
        foreach (RodajesFlujo::construirPlan($pid, $escenas) as $f) foreach ($f['zonas'] as $z) foreach ($z['setups'] as $st) {
            $out[] = [
                'nombre'  => implode(' · ', array_filter([$f['nombre'], $z['zona'] ? $z['n'] . '. ' . $z['zona']['nombre'] : 'Sin zona', $st['setup']['nombre'] ?? 'Sin setup'])),
                'zona'    => $z['zona']['nombre'] ?? '',
                'dn'      => rod_dia_noche($st['escenas'][0]['franja'] ?? null),
                'escenas' => $st['escenas'],
            ];
        }
        return $out;
    }

    /**
     * Portada de cada toma y fotos de sus variantes, igual en lista y storyboard.
     * Portada: imagen subida a mano (un vídeo manda sobre una imagen) → foto de
     * la idea (las tomas del flujo la guardan en el moodboard) → foto de una
     * variante → imagen guía de su zona. Sin nada de eso, el texto de la idea.
     * $escenas deben venir con variantes (RodajesFlujo::conVariantes).
     */
    private function portadas(int $pid, array $escenas): array
    {
        $out = ['multimedia' => [], 'varFotos' => [], 'ideas' => [], 'setups' => [], 'puntos' => []];
        if (!$escenas) return $out;
        $ids = array_column($escenas, 'id');
        $esVid = static fn (string $r) => rod_es_video($r);
        foreach ((new RodajesEscenaImagenModel())->whereIn('escena_id', $ids)->orderBy('id', 'ASC')->findAll() as $f) {
            $eid = (int) $f['escena_id'];
            $v = $esVid($f['ruta']);
            if (!isset($out['multimedia'][$eid]) || ($v && !$out['multimedia'][$eid]['es_video'])) {
                $out['multimedia'][$eid] = ['ruta' => $f['ruta'], 'es_video' => $v, 'ext' => strtolower(pathinfo($f['ruta'], PATHINFO_EXTENSION)), 'guia' => false];
            }
        }
        $fotos = RodajesFlujo::fotosDeTomas($ids);
        $ubicImg = [];
        $zonas = (new RodajesUbicacionModel())->where('proyecto_id', $pid)->findAll();
        foreach ($zonas as $z) if (!empty($z['imagen'])) $ubicImg[(int) $z['id']] = $z['imagen'];
        foreach ((new RodajesSetupModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll() as $st) $out['setups'][(int) $st['id']] = $st;
        if ($zonas) $out['puntos'] = array_column((new RodajesPuntoModel())->whereIn('ubicacion_id', array_column($zonas, 'id'))->findAll(), 'nombre', 'id');

        $esFoto = static fn ($i) => $i && $i['origen'] === 'archivo' && !empty($i['ruta_archivo']);
        foreach ($escenas as $e) {
            $eid = (int) $e['id'];
            foreach ($e['variantes'] ?? [] as $va) {
                $i = $va['moodboard_item_id'] ? ($fotos['v'][(int) $va['moodboard_item_id']] ?? null) : null;
                if ($esFoto($i)) $out['varFotos'][$eid][(int) $va['id']] = $i['ruta_archivo'];
            }
            if (isset($out['multimedia'][$eid])) continue;
            $deToma = array_values(array_filter($fotos['t'][$eid] ?? [], $esFoto));
            $ruta = $deToma[0]['ruta_archivo'] ?? (array_values($out['varFotos'][$eid] ?? [])[0] ?? null);
            $guia = false;
            if (!$ruta && !empty($e['ubicacion_id']) && isset($ubicImg[(int) $e['ubicacion_id']])) {
                $ruta = $ubicImg[(int) $e['ubicacion_id']];
                $guia = true;
            }
            if ($ruta) {
                $out['multimedia'][$eid] = ['ruta' => $ruta, 'es_video' => $esVid($ruta), 'ext' => strtolower(pathinfo($ruta, PATHINFO_EXTENSION)), 'guia' => $guia];
            } elseif ($idea = ($fotos['t'][$eid][0] ?? null)) {
                $out['ideas'][$eid] = ['texto' => rod_texto_idea($idea), 'enlace' => $idea['origen'] === 'enlace'];
            }
        }
        return $out;
    }

    /** [anterior, siguiente] de una toma en el orden pedido (narrativo o el del Plan). */
    private function vecinas(int $pid, int $id, string $orden): array
    {
        $escenas = (new RodajesEscenaModel())->where('proyecto_id', $pid)->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        $ids = array_map('intval', array_column($escenas, 'id'));
        if ($orden === 'rodaje') {
            $ids = [];
            foreach ($this->gruposRodaje($pid, RodajesFlujo::conVariantes($escenas)) as $g) foreach ($g['escenas'] as $e) $ids[] = (int) $e['id'];
        }
        $k = array_search($id, $ids, true);
        if ($k === false) return [null, null];
        return [$ids[$k - 1] ?? null, $ids[$k + 1] ?? null];
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

        $this->propsAlEquipo($pid, array_values($unicos));
    }

    /** Props nuevos al «Qué llevar» de la sesión vinculada (si no estaban ya). */
    private function propsAlEquipo(int $pid, array $props): void
    {
        $proyecto = $props ? (new RodajesProyectoModel())->find($pid) : null;
        if (!$proyecto || !$proyecto['sesion_id']) return;
        $eq = new SesionEquipoModel();
        $ya = array_map(static fn ($r) => rod_normalizar($r['item']), $eq->where('sesion_id', $proyecto['sesion_id'])->where('tipo', 'props')->findAll());
        foreach ($props as $pr) {
            $k = rod_normalizar($pr);
            if (in_array($k, $ya, true)) continue;
            $eq->insert(['sesion_id' => $proyecto['sesion_id'], 'item' => $pr, 'tipo' => 'props', 'orden' => 999]);
            $ya[] = $k;
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
        $toma = (new RodajesEscenaModel())->find($escenaId);
        $deToma = array_map('rod_normalizar', $toma ? rod_props($toma) : []);
        $nuevosProps = [];
        foreach ((array) ($post['variantes'] ?? []) as $v) {
            $texto = mb_substr(trim((string) ($v['texto'] ?? '')), 0, 255);
            if ($texto === '') continue;
            $datos = ['texto' => $texto, 'prioridad' => ($v['prioridad'] ?? '') === 'opc' ? 'opc' : 'imp', 'orden' => $orden++];
            // Props que lleva ADEMÁS de su toma (lo que ya lleva la toma sobra aquí).
            if (array_key_exists('props', $v)) {
                $extra = [];
                foreach (explode(',', (string) $v['props']) as $pr) {
                    $pr = trim($pr);
                    if ($pr !== '' && !in_array(rod_normalizar($pr), $deToma, true)) $extra[rod_normalizar($pr)] ??= mb_substr($pr, 0, 80);
                }
                $datos['props'] = $extra ? json_encode(array_values($extra), JSON_UNESCAPED_UNICODE) : null;
                $nuevosProps = array_merge($nuevosProps, array_values($extra));
            }
            $id = (int) ($v['id'] ?? 0);
            if ($id && isset($actuales[$id])) {
                $vm->update($id, $datos);
                $vistas[$id] = true;
            } else {
                $vm->insert($datos + ['escena_id' => $escenaId]);
            }
        }
        $this->propsAlEquipo((int) ($toma['proyecto_id'] ?? 0), $nuevosProps);
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

        // 2. Anterior / siguiente en el orden desde el que se llega (narrativo o el del Plan).
        $orden = $this->ordenPedido();
        [$prevId, $nextId] = $this->vecinas((int) $proyectoId, (int) $escena['id'], $orden);

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

        return view(rod_vista('rodajes/escenas/show'), [
            'proyecto'        => $proyecto,
            'escena'          => $escena,
            'nextId'          => $nextId,
            'prevId'          => $prevId,
            'orden'           => $orden,
            'setups'          => array_column((new RodajesSetupModel())->where('proyecto_id', (int) $proyectoId)->findAll(), null, 'id'),
            'imagenes_lugar'  => $imagenes_lugar,
            'imagenes_insp'   => $imagenes_insp,
            'ideas'           => $fotos['t'][(int) $escena['id']] ?? [],
            'fotosVar'        => $fotos['v'],
            'zona'            => $zona,
            'punto'           => $punto,
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
        // El antiguo «por clasificación» ya no existe: solo los dos órdenes.
        if ($this->request->getGet('agrupar') === 'clasificacion') return redirect()->to(site_url("rodajes/$pid/escenas/storyboard?orden=rodaje"));

        RodajesFlujo::sincronizarBloques($pid);
        $orden   = $this->ordenPedido();
        $escenas = RodajesFlujo::conVariantes((new RodajesEscenaModel())->where('proyecto_id', $pid)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll());
        $sesion  = !empty($proyecto['sesion_id']) ? (new \App\Models\SesionModel())->find((int) $proyecto['sesion_id']) : null;

        if ($orden === 'rodaje') {
            $grupos  = $this->gruposRodaje($pid, $escenas);
            $colores = rod_colores($escenas, 'escena_ubicacion');
        } else {
            // Temáticas (= bloques) en su orden; lo suelto, al final.
            $grupos = [];
            foreach ($escenas as $e) {
                $nombre = trim((string) $e['escena_bloque']);
                $grupos[mb_strtolower($nombre)] ??= ['nombre' => $nombre !== '' ? $nombre : ($sesion ? 'Sin temática' : 'Sin bloque'), 'zona' => $nombre, 'dn' => '', 'escenas' => []];
                $grupos[mb_strtolower($nombre)]['escenas'][] = $e;
            }
            $ordenBloques = RodajesFlujo::ordenBloques($sesion);
            $pos = array_flip(array_keys($grupos));
            uksort($grupos, static fn ($a, $b) => [$a === '', $ordenBloques[$a] ?? PHP_INT_MAX, $pos[$a]] <=> [$b === '', $ordenBloques[$b] ?? PHP_INT_MAX, $pos[$b]]);
            $grupos  = array_values($grupos);
            $colores = rod_colores($escenas, 'escena_bloque');
        }
        // Color del grupo: el de su zona (rodaje) o el de su temática (narrativo), como en pizarra y plató.
        foreach ($grupos as &$g) $g['color'] = rod_color($colores, $g['zona']);
        unset($g);

        $p = $this->portadas($pid, $escenas);
        // La vista clásica (?nueva=0) sigue esperando $groups con items y portada.
        $groups = [];
        foreach ($grupos as $g) {
            $groups[] = ['_title' => $g['nombre'], 'items' => array_map(static fn ($e) => [
                'escena' => $e, 'cover_lugar' => ($p['multimedia'][(int) $e['id']]['ruta'] ?? null), 'cover_insp' => null,
            ], $g['escenas'])];
        }

        return view(rod_vista('rodajes/escenas/storyboard'), [
            'proyecto'   => $proyecto,
            'groups'     => $groups,
            'grupos'     => $grupos,
            'orden'      => $orden,
            'hayFormato' => (bool) array_filter($escenas, static fn ($e) => !empty($e['formato'])),
        ] + $p);
    }

    /** Antiguo «plan por clasificación»: ahora es el storyboard agrupado por clasificación. */
    public function storyboardPorClasificacion($proyectoId)
    {
        return redirect()->to(site_url('rodajes/' . (int) $proyectoId . '/escenas/storyboard?orden=rodaje'));
    }
}
