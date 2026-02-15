<?php

namespace App\Controllers;

use App\Models\{RodajesProyectoModel, RodajesEscenaModel, RodajesEscenaImagenModel};

class RodajesEscenas extends BaseController
{
    // Carpeta pública donde se guardarán las imágenes
    protected $publicDir = 'images/rodajes'; // dentro de /public

    public function index($proyectoId)
    {
        $proyectos = new RodajesProyectoModel();
        $escenas   = new RodajesEscenaModel();

        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) {
            return redirect()->to(site_url('rodajes'));
        }

        $data['proyecto'] = $proyecto;
        $data['escenas']  = $escenas->where('proyecto_id', $proyectoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return view('rodajes/escenas/index', $data);
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

        return view('rodajes/dialogos', [
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
        return view('rodajes/escenas/form', $data);
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

        if (!$escenas->save($post)) {
            return redirect()->back()->withInput()->with('errors', $escenas->errors());
        }
        $escenaId = $escenas->getInsertID();

        // Subidas múltiples por categoría -> a /public/images/rodajes/{escenaId}/
        $this->handleUploads($proyectoId, $escenaId, 'lugar_objetos');
        $this->handleUploads($proyectoId, $escenaId, 'inspiracion');

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

        return view('rodajes/escenas/form', $data);
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
        return view('rodajes/escenas/show', [
            'proyecto'        => $proyecto,
            'escena'          => $escena,
            'nextId'          => $next['id'] ?? null,
            'prevId'          => $prev['id'] ?? null,
            'imagenes_lugar'  => $imagenes_lugar,
            'imagenes_insp'   => $imagenes_insp,
        ]);
    }

    public function storyboard($proyectoId)
    {
        $proyectos = new \App\Models\RodajesProyectoModel();
        $escenasM  = new \App\Models\RodajesEscenaModel();
        $imgsM     = new \App\Models\RodajesEscenaImagenModel();

        // Proyecto
        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) {
            return redirect()->to(site_url('rodajes'));
        }

        // 1) Escenas ordenadas
        $escenas = $escenasM->where('proyecto_id', $proyectoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // Si no hay escenas, renderiza vacío
        if (!$escenas) {
            return view('rodajes/escenas/storyboard', [
                'proyecto' => $proyecto,
                'groups'   => [],
            ]);
        }

        // 2) Portadas por escena: primera imagen por categoría
        $ids = array_column($escenas, 'id');
        $coversLugar = [];
        $coversInsp  = [];

        // Helper que obtiene primera imagen por categoría para todas las escenas
        $fetchFirstByCat = function (string $categoria) use ($imgsM, $ids) {
            if (empty($ids)) return [];
            // Subquery: MIN(id) por escena/categoría
            $rows = $imgsM->select('escena_id, MIN(id) AS mid')
                ->whereIn('escena_id', $ids)
                ->where('categoria', $categoria)
                ->groupBy('escena_id')
                ->findAll();

            if (!$rows) return [];
            $minIds = array_column($rows, 'mid');
            if (!$minIds) return [];

            // Traer los registros finales
            $files = $imgsM->whereIn('id', $minIds)->findAll();
            $map = [];
            foreach ($files as $f) {
                // Guardamos la ruta pública tal cual está en DB (p.ej. images/rodajes/{escenaId}/file.jpg)
                $map[$f['escena_id']] = $f['ruta'];
            }
            return $map;
        };

        $coversLugar = $fetchFirstByCat('lugar_objetos');
        $coversInsp  = $fetchFirstByCat('inspiracion');

        // 3) Helpers de normalizado y detección de transición
        $unaccent = function (string $s): string {
            $s = trim($s);
            $s = strtr($s, [
                'Á' => 'A',
                'À' => 'A',
                'Â' => 'A',
                'Ä' => 'A',
                'Ã' => 'A',
                'Å' => 'A',
                'É' => 'E',
                'È' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Í' => 'I',
                'Ì' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ó' => 'O',
                'Ò' => 'O',
                'Ô' => 'O',
                'Ö' => 'O',
                'Õ' => 'O',
                'Ú' => 'U',
                'Ù' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'á' => 'a',
                'à' => 'a',
                'â' => 'a',
                'ä' => 'a',
                'ã' => 'a',
                'å' => 'a',
                'é' => 'e',
                'è' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'í' => 'i',
                'ì' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ó' => 'o',
                'ò' => 'o',
                'ô' => 'o',
                'ö' => 'o',
                'õ' => 'o',
                'ú' => 'u',
                'ù' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ñ' => 'n',
                'Ñ' => 'N'
            ]);
            return $s;
        };
        $isTransition = function (string $raw) use ($unaccent): bool {
            $n = strtoupper($unaccent($raw));
            return $n !== '' && (strpos($n, 'TRANS') === 0 || strpos($n, 'TRANSICION') !== false);
        };

        // 4) Agrupar: bloques normales por nombre; transiciones como bloque independiente por escena
        $groups = [];
        $blockFirstOrder = []; // guardamos el primer orden donde aparece cada bloque

        foreach ($escenas as $e) {
            $raw = (string)($e['escena_bloque'] ?? '');
            $ord = (int)($e['orden'] ?? 0);

            // Datos de imágenes por escena
            $item = [
                'escena'       => $e,
                'cover_lugar'  => $coversLugar[$e['id']] ?? null,
                'cover_insp'   => $coversInsp[$e['id']] ?? null,
            ];

            if ($isTransition($raw)) {
                // Transición: su propio bloque
                $key = 'TRANS#' . $e['id'];
                $groups[$key] = [
                    '_title' => ($raw !== '' ? $raw : 'Transición'),
                    '_first' => $ord,
                    'items'  => [$item],
                ];
                continue;
            }

            // Bloques normales
            $norm = strtoupper($unaccent($raw));
            if ($norm === '') $norm = 'OTROS';
            $key = 'BLOCK:' . $norm;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    '_title' => ($raw !== '' ? $raw : 'Otros'),
                    '_first' => $ord,
                    'items'  => [],
                ];
                // registramos primer orden
                $blockFirstOrder[$key] = $ord;
            }

            $groups[$key]['items'][] = $item;

            // actualizamos el menor orden si aparece antes
            if ($ord < $groups[$key]['_first']) {
                $groups[$key]['_first'] = $ord;
            }
        }

        // 5) Ordenar los grupos por el orden de aparición de su primera escena
        uasort($groups, function ($a, $b) {
            return ($a['_first'] <=> $b['_first']);
        });

        // 6) Dentro de cada grupo, mantener orden de escena
        foreach ($groups as &$g) {
            usort($g['items'], function ($a, $b) {
                return ($a['escena']['orden'] <=> $b['escena']['orden'])
                    ?: ($a['escena']['id'] <=> $b['escena']['id']);
            });
        }
        unset($g);

        return view('rodajes/escenas/storyboard', [
            'proyecto' => $proyecto,
            'groups'   => $groups,
        ]);
    }

    public function storyboardPorClasificacion($proyectoId)
    {
        $proyectos = new \App\Models\RodajesProyectoModel();
        $escenasM  = new \App\Models\RodajesEscenaModel();
        $imgsM     = new \App\Models\RodajesEscenaImagenModel();

        $proyecto = $proyectos->find($proyectoId);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));

        $escenas = $escenasM->where('proyecto_id', $proyectoId)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();

        if (!$escenas) {
            return view('rodajes/escenas/storyboard_por_clasificacion', [
                'proyecto' => $proyecto,
                'groups' => [],
                'clasificaciones' => [],
                'q' => []
            ]);
        }

        // Portadas (igual que ya tenías)
        $ids = array_column($escenas, 'id');
        $fetchFirstByCat = function (string $categoria) use ($imgsM, $ids) {
            if (empty($ids)) return [];
            $rows = $imgsM->select('escena_id, MIN(id) AS mid')
                ->whereIn('escena_id', $ids)->where('categoria', $categoria)
                ->groupBy('escena_id')->findAll();
            if (!$rows) return [];
            $minIds = array_column($rows, 'mid');
            $files = $imgsM->whereIn('id', $minIds)->findAll();
            $map = [];
            foreach ($files as $f) $map[$f['escena_id']] = $f['ruta'];
            return $map;
        };
        $coversLugar = $fetchFirstByCat('lugar_objetos');
        $coversInsp  = $fetchFirstByCat('inspiracion');

        // Normalizador
        $unaccent = function (string $s): string {
            $s = trim($s);
            $s = strtr($s, [
                'Á' => 'A',
                'À' => 'A',
                'Â' => 'A',
                'Ä' => 'A',
                'Ã' => 'A',
                'Å' => 'A',
                'É' => 'E',
                'È' => 'E',
                'Ê' => 'E',
                'Ë' => 'E',
                'Í' => 'I',
                'Ì' => 'I',
                'Î' => 'I',
                'Ï' => 'I',
                'Ó' => 'O',
                'Ò' => 'O',
                'Ô' => 'O',
                'Ö' => 'O',
                'Õ' => 'O',
                'Ú' => 'U',
                'Ù' => 'U',
                'Û' => 'U',
                'Ü' => 'U',
                'á' => 'a',
                'à' => 'a',
                'â' => 'a',
                'ä' => 'a',
                'ã' => 'a',
                'å' => 'a',
                'é' => 'e',
                'è' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'í' => 'i',
                'ì' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ó' => 'o',
                'ò' => 'o',
                'ô' => 'o',
                'ö' => 'o',
                'õ' => 'o',
                'ú' => 'u',
                'ù' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ñ' => 'n',
                'Ñ' => 'N'
            ]);
            return $s;
        };

        // --- Filtro GET ---
        $q = $this->request->getGet('q');
        // adm: ?q=valor o ?q[]=v1&q[]=v2
        $q = is_array($q) ? array_filter($q, fn($v) => trim((string)$v) !== '') : (trim((string)$q) !== '' ? [$q] : []);
        // normaliza claves del filtro
        $qNorm = array_map(function ($v) use ($unaccent) {
            return mb_strtoupper($unaccent((string)$v));
        }, $q);
        $qNorm = array_values(array_unique($qNorm)); // sin duplicados

        // Construir grupos y listado de clasificaciones únicas
        $groups = [];
        $clasificaciones = []; // para el <select>
        foreach ($escenas as $e) {
            $raw = trim((string)($e['plano_hora_dia'] ?? ''));
            $title = ($raw !== '') ? $raw : '(Sin clasificación)';
            $norm  = ($raw !== '') ? mb_strtoupper($unaccent($raw)) : '__SIN__';

            // contar para el selector
            if (!isset($clasificaciones[$norm])) {
                $clasificaciones[$norm] = ['norm' => $norm, 'title' => $title, 'count' => 0];
            }
            $clasificaciones[$norm]['count']++;

            // Si hay filtro, y esta clasificación NO está incluida, salta
            if (!empty($qNorm) && !in_array($norm, $qNorm, true)) continue;

            $ord = (int)($e['orden'] ?? 0);
            $item = [
                'escena'      => $e,
                'cover_lugar' => $coversLugar[$e['id']] ?? null,
                'cover_insp'  => $coversInsp[$e['id']] ?? null,
            ];

            $key = 'CLF:' . $norm;
            if (!isset($groups[$key])) {
                $groups[$key] = ['_title' => $title, '_first' => $ord, 'items' => []];
            } else {
                if ($ord < $groups[$key]['_first']) $groups[$key]['_first'] = $ord;
            }
            $groups[$key]['items'][] = $item;
        }

        // Ordenar selector y grupos
        uasort($clasificaciones, fn($a, $b) => strnatcasecmp($a['title'], $b['title']));
        uasort($groups, function ($a, $b) {
            return ($a['_first'] <=> $b['_first']) ?: strnatcasecmp($a['_title'], $b['_title']);
        });

        return view('rodajes/escenas/storyboard_por_clasificacion', [
            'proyecto'        => $proyecto,
            'groups'          => $groups,
            'clasificaciones' => $clasificaciones,
            'q'               => $q, // títulos originales tal cual vinieron en GET
        ]);
    }
}
