<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EnlacesModel;               // -> debe mapear a enlaces_items
use App\Models\EnlacesCategoriasModel;     // -> enlaces_categorias
use App\Models\EnlacesEtiquetasModel;      // -> enlaces_etiquetas
use App\Models\EnlaceCategoriasModel;      // -> enlaces_item_categorias
use App\Models\EnlaceEtiquetasModel;       // -> enlaces_item_etiquetas
use App\Models\EnlacesItemBloquesModel;

use DOMDocument;
use DOMXPath;

class Enlaces extends BaseController
{
    protected $enlaces;
    protected $categorias;
    protected $etiquetas;
    protected $enlaceCategorias;
    protected $enlaceEtiquetas;

    public function __construct()
    {
        $this->enlaces          = new EnlacesModel();
        $this->categorias       = new EnlacesCategoriasModel();
        $this->etiquetas        = new EnlacesEtiquetasModel();
        $this->enlaceCategorias = new EnlaceCategoriasModel();
        $this->enlaceEtiquetas  = new EnlaceEtiquetasModel();
    }

    public function index()
    {
        $req   = service('request');
        $db    = \Config\Database::connect();

        // --- Parámetros de filtro ---
        $q      = trim((string) $req->getGet('q'));
        $visto  = $req->getGet('visto'); // '', '0', '1'
        $match  = strtolower((string) $req->getGet('match')) === 'all' ? 'all' : 'any';

        // cats puede venir como array (cats[]) o como string "1,2,3"
        $catsRaw = $req->getGet('cats');
        $cats = [];
        if (is_array($catsRaw)) {
            $cats = array_values(array_unique(array_map('intval', $catsRaw)));
        } elseif (is_string($catsRaw) && $catsRaw !== '') {
            $cats = array_values(array_unique(array_map('intval', explode(',', $catsRaw))));
        }

        // tags (texto) puede venir como array o como string "ia,dev"
        $tagsRaw = $req->getGet('tags');
        $tags = [];
        if (is_array($tagsRaw)) {
            $tags = array_values(array_unique(array_filter(array_map('trim', $tagsRaw))));
        } elseif (is_string($tagsRaw) && $tagsRaw !== '') {
            $tags = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsRaw)))));
        }

        // Resolver tags (nombres/slug) -> ids (solo para los venidos por texto)
        $tagIds = [];
        if (!empty($tags)) {
            $tagIds = $this->etiquetas
                ->select('id')
                ->groupStart()
                ->whereIn('slug', array_map(fn($t) => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $t)), $tags))
                ->orWhereIn('nombre', $tags)
                ->groupEnd()
                ->findColumn('id') ?? [];
        }

        // NUEVO: tags por checkboxes -> tag_ids[]
        $tagIdsFromCb = $req->getGet('tag_ids');
        if (is_array($tagIdsFromCb)) {
            $tagIds = array_values(array_unique(array_merge($tagIds, array_map('intval', $tagIdsFromCb))));
        }

        // --- Query base ---
        $builder = $db->table('enlaces_items e')->select('e.*');

        // Texto libre
        if ($q !== '') {
            $builder->groupStart()
                ->like('e.titulo', $q)
                ->orLike('e.url', $q)
                ->orLike('e.extra', $q)
                ->groupEnd();
        }

        // Visto
        if ($visto === '0' || $visto === '1') {
            $builder->where('e.visto', (int)$visto);
        }

        // --- Filtro CATEGORÍAS ---
        if (!empty($cats)) {
            $builder->join('enlaces_item_categorias eic', 'eic.item_id = e.id', 'inner');

            if ($match === 'any') {
                $builder->whereIn('eic.categoria_id', $cats);
                $builder->groupBy('e.id');
            } else {
                $builder->whereIn('eic.categoria_id', $cats)
                    ->groupBy('e.id')
                    ->having('COUNT(DISTINCT eic.categoria_id) >=', count($cats));
            }
        }

        // --- Filtro ETIQUETAS ---
        if (!empty($tagIds)) {
            $builder->join('enlaces_item_etiquetas eie', 'eie.item_id = e.id', 'inner');

            if ($match === 'any') {
                $builder->whereIn('eie.etiqueta_id', $tagIds);
                $builder->groupBy('e.id');
            } else {
                $builder->whereIn('eie.etiqueta_id', $tagIds)
                    ->groupBy('e.id')
                    ->having('COUNT(DISTINCT eie.etiqueta_id) >=', count($tagIds));
            }
        }

        // Si no se pidió ALL en ningún filtro, aún necesitamos un groupBy al tener joins múltiples
        if (empty($builder->QBGroupBy)) {
            $builder->groupBy('e.id');
        }

        $builder->orderBy('e.fecha', 'DESC')->orderBy('e.id', 'DESC');

        $enlaces = $builder->get()->getResultArray();

        $pendientesRevision = (int) $db->table('enlaces_items')
            ->selectCount('id', 'c')->where('(titulo IS NULL OR titulo = "")', null, false)
            ->get()->getRow('c');

        // Carga para filtros y chips
        $categorias = $this->categorias->orderBy('nombre', 'ASC')->findAll();
        $etiquetas  = $this->etiquetas->orderBy('nombre', 'ASC')->findAll();

        // Hidratar relaciones para pintar chips (usando nombres de las maestras)
        $ids = array_column($enlaces, 'id');
        $catsPorEnlace = $tagsPorEnlace = [];
        if ($ids) {
            // Categorías por item
            $rows = $db->table('enlaces_item_categorias eic')
                ->select('eic.item_id, c.id, c.nombre, c.slug')
                ->join('enlaces_categorias c', 'c.id = eic.categoria_id')
                ->whereIn('eic.item_id', $ids)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $catsPorEnlace[$r['item_id']][] = ['id' => $r['id'], 'nombre' => $r['nombre'], 'slug' => $r['slug']];
            }

            // Etiquetas por item
            $rows = $db->table('enlaces_item_etiquetas eie')
                ->select('eie.item_id, t.id, t.nombre, t.slug')
                ->join('enlaces_etiquetas t', 't.id = eie.etiqueta_id')
                ->whereIn('eie.item_id', $ids)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $tagsPorEnlace[$r['item_id']][] = ['id' => $r['id'], 'nombre' => $r['nombre'], 'slug' => $r['slug']];
            }
        }

        // Conteo de enlaces por categoría (global; rápido y simple)
        $conteos = $db->table('enlaces_item_categorias ec')
            ->select('ec.categoria_id, COUNT(*) AS total')
            ->groupBy('ec.categoria_id')
            ->get()->getResultArray();

        $catCount = [];
        foreach ($conteos as $r) {
            $catCount[(int)$r['categoria_id']] = (int)$r['total'];
        }

        // Ordenar categorías: más usadas primero; empate por nombre
        usort($categorias, function ($a, $b) use ($catCount) {
            $ta = $catCount[$a['id']] ?? 0;
            $tb = $catCount[$b['id']] ?? 0;
            if ($ta === $tb) return strcasecmp($a['nombre'], $b['nombre']);
            return $tb <=> $ta;
        });

        // NUEVO: Etiquetas disponibles en los resultados mostrados + conteo
        $tagsDisp = [];
        if ($ids) {
            $rows = $db->table('enlaces_item_etiquetas ee')
                ->select('ee.etiqueta_id, t.nombre, t.slug, COUNT(*) AS total')
                ->join('enlaces_etiquetas t', 't.id = ee.etiqueta_id')
                ->whereIn('ee.item_id', $ids)
                ->groupBy('ee.etiqueta_id, t.nombre, t.slug')
                ->orderBy('total', 'DESC')
                ->orderBy('t.nombre', 'ASC')
                ->get()->getResultArray();
            $tagsDisp = $rows;
        }

        // Selección actual de tags por IDs (de texto + checkboxes)
        $tagIdsSel = $tagIds;

        // --- Chips de filtros activos + contador (para el botón "Filtros" y el panel) ---
        $catById = array_column($categorias, 'nombre', 'id');
        $tagById = array_column($etiquetas, 'nombre', 'id');

        $baseParams = array_filter([
            'q'     => $q,
            'visto' => $visto,
            'match' => $match,
        ], fn($v) => $v !== '' && $v !== null);

        $chipsActivos = [];
        foreach ($cats as $cid) {
            if (!isset($catById[$cid])) continue;
            $chipsActivos[] = [
                'texto' => $catById[$cid],
                'url'   => site_url('enlaces') . '?' . http_build_query($baseParams + [
                    'cats'     => array_values(array_diff($cats, [$cid])),
                    'tag_ids'  => $tagIdsSel,
                ]),
            ];
        }
        foreach ($tagIdsSel as $tid) {
            if (!isset($tagById[$tid])) continue;
            $chipsActivos[] = [
                'texto' => $tagById[$tid],
                'url'   => site_url('enlaces') . '?' . http_build_query($baseParams + [
                    'cats'     => $cats,
                    'tag_ids'  => array_values(array_diff($tagIdsSel, [$tid])),
                ]),
            ];
        }
        if ($visto === '0' || $visto === '1') {
            $chipsActivos[] = [
                'texto' => $visto === '0' ? 'No vistos' : 'Vistos',
                'url'   => site_url('enlaces') . '?' . http_build_query(array_filter([
                    'q' => $q, 'match' => $match,
                ], fn($v) => $v !== '' && $v !== null) + ['cats' => $cats, 'tag_ids' => $tagIdsSel]),
            ];
        }
        if ($q !== '') {
            $chipsActivos[] = [
                'texto' => '"' . $q . '"',
                'url'   => site_url('enlaces') . '?' . http_build_query(array_filter([
                    'visto' => $visto, 'match' => $match,
                ], fn($v) => $v !== '' && $v !== null) + ['cats' => $cats, 'tag_ids' => $tagIdsSel]),
            ];
        }

        // Cuenta solo lo que vive dentro del panel colapsable (para el badge y el auto-expandir)
        $panelActiveCount = count($cats) + count($tagIdsSel) + ($visto === '0' || $visto === '1' ? 1 : 0);

        return view('enlaces/index', [
            'enlaces'          => $enlaces,
            'categorias'       => $categorias,
            'catCount'         => $catCount,
            'etiquetas'        => $etiquetas,
            'catsPorEnlace'    => $catsPorEnlace,
            'tagsPorEnlace'    => $tagsPorEnlace,
            'cats'             => $cats,
            'tags'             => $tags,       // si sigues mostrando el input de texto
            'tagIdsSel'        => $tagIdsSel,  // seleccion actual (IDs)
            'tagsDisp'         => $tagsDisp,   // etiquetas disponibles en estos resultados
            'q'                => $q,
            'visto'            => $visto,
            'match'            => $match,
            'chipsActivos'      => $chipsActivos,
            'panelActiveCount'  => $panelActiveCount,
            'pendientesRevision' => $pendientesRevision,
        ]);
    }

    /**
     * AJAX: etiquetas disponibles según las categorías (y búsqueda) seleccionadas
     * en vivo en el formulario, sin haber aplicado aún los filtros. Se usa para
     * refrescar el selector de etiquetas al tocar una categoría.
     */
    public function etiquetasDisponibles()
    {
        $req = service('request');
        $db  = \Config\Database::connect();

        $catsRaw = $req->getGet('cats');
        $cats = [];
        if (is_array($catsRaw)) {
            $cats = array_values(array_unique(array_map('intval', $catsRaw)));
        } elseif (is_string($catsRaw) && $catsRaw !== '') {
            $cats = array_values(array_unique(array_map('intval', explode(',', $catsRaw))));
        }

        $q = trim((string) $req->getGet('q'));

        $builder = $db->table('enlaces_items e')->select('e.id');

        if ($q !== '') {
            $builder->groupStart()
                ->like('e.titulo', $q)
                ->orLike('e.url', $q)
                ->orLike('e.extra', $q)
                ->groupEnd();
        }

        if (!empty($cats)) {
            $builder->join('enlaces_item_categorias eic', 'eic.item_id = e.id', 'inner')
                ->whereIn('eic.categoria_id', $cats)
                ->groupBy('e.id');
        }

        $ids = array_column($builder->get()->getResultArray(), 'id');

        $tags = [];
        if ($ids) {
            $tags = $db->table('enlaces_item_etiquetas ee')
                ->select('ee.etiqueta_id AS id, t.nombre, COUNT(*) AS total')
                ->join('enlaces_etiquetas t', 't.id = ee.etiqueta_id')
                ->whereIn('ee.item_id', $ids)
                ->groupBy('ee.etiqueta_id, t.nombre')
                ->orderBy('total', 'DESC')
                ->orderBy('t.nombre', 'ASC')
                ->get()->getResultArray();
        }

        return $this->response->setJSON(['tags' => $tags]);
    }


    public function crear()
    {
        $categorias = $this->categorias->orderBy('nombre', 'ASC')->findAll();
        $etiquetas  = $this->etiquetas->orderBy('nombre', 'ASC')->findAll();
        return view('enlaces/crear', compact('categorias', 'etiquetas'));
    }



    public function editar($id)
    {
        $enlace = $this->enlaces->find($id);
        if (!$enlace) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $categorias = $this->categorias->orderBy('nombre', 'ASC')->findAll();
        $etiquetas  = $this->etiquetas->orderBy('nombre', 'ASC')->findAll();

        $selCats    = $this->enlaceCategorias->where('item_id', $id)->findColumn('categoria_id') ?? [];
        $selTagsIds = $this->enlaceEtiquetas->where('item_id', $id)->findColumn('etiqueta_id') ?? [];
        $selTagNames = [];
        if ($selTagsIds) {
            $rows = $this->etiquetas->whereIn('id', $selTagsIds)->findAll();
            foreach ($rows as $t) $selTagNames[] = $t['nombre'];
        }

        return view('enlaces/editar', compact('enlace', 'categorias', 'etiquetas', 'selCats', 'selTagNames'));
    }

    public function guardar()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $data = [
            'titulo'     => trim((string)$this->request->getPost('titulo')),
            'url'        => trim((string)$this->request->getPost('url')),
            'visto'      => $this->request->getPost('visto') ? 1 : 0,
            'relevancia' => (int)$this->request->getPost('relevancia'),
            'fecha'      => $this->request->getPost('fecha') ?: date('Y-m-d'),
            'extra'      => $this->request->getPost('extra') ?: null,
        ];

        // Inserta con validación del Model
        $id = $this->enlaces->insert($data);
        if ($id === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar: ' . json_encode($this->enlaces->errors()));
        }

        // Categorías (ids)
        $categorias = $this->request->getPost('categorias') ?? [];
        foreach ($categorias as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                $this->enlaceCategorias->insert(['item_id' => $id, 'categoria_id' => $cid]);
            }
        }

        // Etiquetas (por nombre, coma-separado)
        // Etiquetas (por nombre, coma-separado)
        $tagsString = trim((string)$this->request->getPost('etiquetas'));
        if ($tagsString !== '') {
            $nombres = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsString)))));
            foreach ($nombres as $nombre) {
                if ($nombre === '') continue;

                $tid = $this->getOrCreateEtiquetaId($nombre);
                if ($tid > 0) {
                    // evita duplicado (por si llega repetida)
                    $ya = $this->enlaceEtiquetas
                        ->where(['item_id' => $id, 'etiqueta_id' => $tid])
                        ->first();
                    if (!$ya) {
                        $this->enlaceEtiquetas->insert(['item_id' => $id, 'etiqueta_id' => $tid]);
                    }
                }
            }
        }


        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Fallo de transacción al guardar.');
        }

        return redirect()->to(site_url('enlaces'))->with('mensaje', 'Enlace agregado');
    }

    public function actualizar($id)
    {
        $enlace = $this->enlaces->find($id);
        if (!$enlace) {
            return redirect()->to(site_url('enlaces'))->with('error', 'Enlace no encontrado');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $data = [
            'titulo'     => trim((string)$this->request->getPost('titulo')),
            'url'        => trim((string)$this->request->getPost('url')),
            'visto'      => $this->request->getPost('visto') ? 1 : 0,
            'relevancia' => (int)$this->request->getPost('relevancia'),
            'fecha'      => $this->request->getPost('fecha') ?: date('Y-m-d'),
            'extra'      => $this->request->getPost('extra') ?: null,
        ];

        if (!$this->enlaces->update($id, $data)) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar: ' . json_encode($this->enlaces->errors()));
        }

        // Sync categorías
        $this->enlaceCategorias->where('item_id', $id)->delete();
        $categorias = $this->request->getPost('categorias') ?? [];
        foreach ($categorias as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                $this->enlaceCategorias->insert(['item_id' => $id, 'categoria_id' => $cid]);
            }
        }

        // Sync etiquetas
        // Sync etiquetas (por nombre, coma-separado)
        $this->enlaceEtiquetas->where('item_id', $id)->delete();

        $tagsString = trim((string)$this->request->getPost('etiquetas'));
        if ($tagsString !== '') {
            $nombres = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsString)))));
            foreach ($nombres as $nombre) {
                if ($nombre === '') continue;

                $tid = $this->getOrCreateEtiquetaId($nombre);
                if ($tid > 0) {
                    $ya = $this->enlaceEtiquetas
                        ->where(['item_id' => $id, 'etiqueta_id' => $tid])
                        ->first();
                    if (!$ya) {
                        $this->enlaceEtiquetas->insert(['item_id' => $id, 'etiqueta_id' => $tid]);
                    }
                }
            }
        }


        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Fallo de transacción al actualizar.');
        }

        return redirect()->to(site_url('enlaces'))->with('mensaje', 'Enlace actualizado');
    }


    public function borrar($id)
    {
        $this->enlaces->delete($id);
        return redirect()->to(site_url('enlaces'))->with('mensaje', 'Enlace eliminado');
    }

    // CRUD simple de categorías/etiquetas
    public function categorias()
    {
        $categorias = $this->categorias->orderBy('nombre', 'ASC')->findAll();

        // Contar enlaces por categoría
        $db = \Config\Database::connect();
        $conteos = $db->table('enlaces_item_categorias')
            ->select('categoria_id, COUNT(*) as total')
            ->groupBy('categoria_id')
            ->get()->getResultArray();

        $conteoPorCategoria = [];
        foreach ($conteos as $c) {
            $conteoPorCategoria[$c['categoria_id']] = (int)$c['total'];
        }

        return view('enlaces/categorias', [
            'categorias' => $categorias,
            'conteoPorCategoria' => $conteoPorCategoria,
        ]);
    }


    public function guardarCategoria()
    {
        $nombre = trim((string)$this->request->getPost('nombre'));
        if ($nombre !== '') {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $nombre));
            if (!$this->categorias->where('slug', $slug)->first()) {
                $this->categorias->insert(['nombre' => $nombre, 'slug' => $slug]);
            }
        }
        return redirect()->back();
    }

    public function borrarCategoria($id)
    {
        $this->categorias->delete($id);
        return redirect()->back();
    }

    public function etiquetas()
    {
        $etiquetas = $this->etiquetas->orderBy('nombre', 'ASC')->findAll();
        return view('enlaces/etiquetas', compact('etiquetas'));
    }

    public function guardarEtiqueta()
    {
        $nombre = trim((string)$this->request->getPost('nombre'));
        if ($nombre !== '') {
            // reutiliza helper robusto para no chocar con índice único en 'nombre' o 'slug'
            $this->getOrCreateEtiquetaId($nombre);
        }
        return redirect()->back();
    }


    public function borrarEtiqueta($id)
    {
        $this->etiquetas->delete($id);
        return redirect()->back();
    }

    // API pequeña para marcar visto toggle
    public function toggleVisto($id)
    {
        $enlace = $this->enlaces->find($id);
        if (!$enlace) return $this->response->setStatusCode(404);
        $this->enlaces->update($id, ['visto' => $enlace['visto'] ? 0 : 1]);
        return $this->response->setJSON(['ok' => true, 'visto' => !$enlace['visto']]);
    }




    public function pagina($itemId)
    {
        $item = $this->enlaces->find($itemId);
        if (!$item) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $bloquesModel = new EnlacesItemBloquesModel();
        $bloques = $bloquesModel->where('item_id', $itemId)
            ->orderBy('orden', 'ASC')->findAll();

        // categorías/etiquetas para cabecera (chips)
        $db = \Config\Database::connect();
        $cats = $db->table('enlaces_item_categorias eic')
            ->select('c.id, c.nombre, c.slug')
            ->join('enlaces_categorias c', 'c.id=eic.categoria_id')
            ->where('eic.item_id', $itemId)->get()->getResultArray();
        $tags = $db->table('enlaces_item_etiquetas eie')
            ->select('t.id, t.nombre, t.slug')
            ->join('enlaces_etiquetas t', 't.id=eie.etiqueta_id')
            ->where('eie.item_id', $itemId)->get()->getResultArray();

        return view('enlaces/pagina', compact('item', 'bloques', 'cats', 'tags'));
    }

    public function guardarDoc($itemId)
    {
        $item = $this->enlaces->find($itemId);
        if (!$item) return redirect()->back()->with('error', 'Enlace no encontrado');

        // HTML del editor (permitimos HTML tal cual)
        $html = $this->request->getPost('contenido_html') ?? '';

        // Guardamos en el campo 'extra' del item
        if (!$this->enlaces->update($itemId, ['extra' => $html])) {
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar el documento');
        }
        return redirect()->back()->with('mensaje', 'Página guardada');
    }

    /**
     * Endpoint de subida de imágenes para el editor (TinyMCE/CKEditor).
     * Acepta 'file' (TinyMCE) o 'upload' (CKEditor). Devuelve JSON.
     */
    public function editorUpload($itemId)
    {
        // Validación mínima del item
        $item = $this->enlaces->find($itemId);
        if (!$item) {
            return $this->response->setJSON(['error' => 'Item no encontrado'])->setStatusCode(404);
        }

        // Archivo puede venir como 'file' (TinyMCE) o 'upload' (CKEditor)
        $file = $this->request->getFile('file') ?? $this->request->getFile('upload');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['error' => 'Archivo inválido'])->setStatusCode(400);
        }

        $mime = $file->getMimeType() ?? '';
        $size = $file->getSize();
        $max  = 8 * 1024 * 1024; // 8MB

        if (!str_starts_with($mime, 'image/')) {
            return $this->response->setJSON(['error' => 'Solo imágenes'])->setStatusCode(415);
        }
        if ($size > $max) {
            return $this->response->setJSON(['error' => 'Imagen > 8MB'])->setStatusCode(413);
        }

        // Guardamos en public/uploads/enlaces
        $publicDir = FCPATH . 'uploads/enlaces';
        if (!is_dir($publicDir)) @mkdir($publicDir, 0775, true);

        $newName = $file->getRandomName();
        if (!$file->move($publicDir, $newName)) {
            return $this->response->setJSON(['error' => 'No se pudo mover el archivo'])->setStatusCode(500);
        }
        $absUrl = base_url('uploads/enlaces/' . $newName);

        // TinyMCE espera { location: 'url' }
        // CKEditor5 (simple upload) acepta { url: '...' }
        return $this->response->setJSON([
            'location' => $absUrl, // TinyMCE
            'url'      => $absUrl  // CKEditor
        ]);
    }

    public function importarForm()
    {
        return view('enlaces/importar');
    }

    public function importarUpload()
    {
        $file   = $this->request->getFile('html_file');
        $dryRun = (bool) $this->request->getPost('dry_run');

        $errors = [];
        if (!$file || !$file->isValid()) {
            $errors[] = 'Archivo no válido.';
        } elseif (!in_array($file->getClientExtension(), ['html', 'htm'])) {
            $errors[] = 'Debe ser un archivo .html o .htm';
        }
        if ($errors) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // Cargar HTML
        $html = file_get_contents($file->getTempName());
        if ($html === false) {
            return redirect()->back()->with('errors', ['No se pudo leer el archivo'])->withInput();
        }
        // Quitar BOM si existiera
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        $xp = new \DOMXPath($dom);

        // Contadores
        $found       = 0;
        $inserted    = 0;
        $duplicates  = 0;
        $errorsCount = 0;
        $imagesSkipped = 0;
        $collected   = [];

        // DB
        $db           = db_connect();
        $enlacesTable = $db->table('enlaces_items'); // tabla correcta

        // Fecha fija del día de importación
        $fechaImport = date('Y-m-d');

        // Recorremos solo mensajes normales
        $history = $xp->query('//div[contains(@class,"history")]/*');
        foreach ($history as $node) {
            $class = $node->attributes->getNamedItem('class')?->nodeValue ?? '';
            if (strpos($class, 'message default') === false) {
                continue;
            }

            // 1) Selector principal (enlaces dentro de .text)
            $aNodes = $xp->query('.//div[contains(@class,"text")]//a[@href]', $node);

            // 2) Fallback si el export no tiene .text
            if (!$aNodes || $aNodes->length === 0) {
                $aNodes = $xp->query('.//a[@href]', $node);
            }

            foreach ($aNodes as $a) {
                if (!($a instanceof \DOMElement) || !$a->hasAttribute('href')) continue;

                $url = trim($a->getAttribute('href'));
                if ($url === '') continue;

                $url = $this->limpiarUrl($url);
                $fecha = $fechaImport;

                // ⛔️ Saltar si es imagen por extensión
                if ($this->esImagenUrl($url)) {
                    $imagesSkipped++;
                    $collected[] = ['url' => $url, 'estado' => 'omitido (imagen)', 'fecha' => $fecha];
                    continue;
                }

                $found++;

                // Duplicados
                $exists = $enlacesTable->select('id')->where('url', $url)->get(1)->getRowArray();
                if ($exists) {
                    $duplicates++;
                    $collected[] = ['url' => $url, 'estado' => 'duplicado', 'fecha' => $fecha];
                    continue;
                }

                if ($dryRun) {
                    $collected[] = ['url' => $url, 'estado' => 'simulado', 'fecha' => $fecha];
                    continue;
                }

                // ⚠️ Inserción mínima para evitar errores por columnas NOT NULL que desconozcamos
                $data = [
                    'url'   => $url,
                    'fecha' => $fecha,
                ];

                $ok = $enlacesTable->insert($data);

                if ($ok === false) {
                    // Capturar error real de la DB
                    $dbErr = $db->error(); // ['code'=>..., 'message'=>...]
                    $errorsCount++;
                    $collected[] = [
                        'url'    => $url,
                        'estado' => 'error DB: ' . ($dbErr['code'] ?? '-') . ' ' . ($dbErr['message'] ?? 'desconocido'),
                        'fecha'  => $fecha
                    ];
                    // Log para ver en writable/logs
                    log_message('error', 'Import enlaces_items falló: {url} :: {code} {msg}', [
                        'url'  => $url,
                        'code' => $dbErr['code'] ?? '-',
                        'msg'  => $dbErr['message'] ?? 'desconocido',
                    ]);
                    continue;
                }

                $inserted++;
                $collected[] = ['url' => $url, 'estado' => 'insertado', 'fecha' => $fecha];
            }
        }

        // Mensaje + preview (sin perder el flashdata)
        $msg = 'Detectados: ' . $found .
            ' · Insertados: ' . $inserted .
            ' · Duplicados: ' . $duplicates .
            ' · Imágenes omitidas: ' . $imagesSkipped .
            ' · Errores: ' . $errorsCount .
            ($dryRun ? ' · (Simulación: no se guardó nada)' : '');

        $preview     = array_slice($collected, 0, 50);
        $htmlPreview = '';
        if ($preview) {
            $htmlPreview .= '<div class="mt-3"><div class="table-responsive"><table class="table table-sm table-striped">';
            $htmlPreview .= '<thead><tr><th>URL</th><th>Fecha</th><th>Estado</th></tr></thead><tbody>';
            foreach ($preview as $row) {
                $htmlPreview .= '<tr>';
                $htmlPreview .= '<td class="text-break"><a href="' . esc($row['url']) . '" target="_blank">' . esc($row['url']) . '</a></td>';
                $htmlPreview .= '<td>' . esc($row['fecha']) . '</td>';
                $htmlPreview .= '<td>' . esc($row['estado']) . '</td>';
                $htmlPreview .= '</tr>';
            }
            $htmlPreview .= '</tbody></table></div>';
            if (count($collected) > 50) {
                $htmlPreview .= '<div class="text-muted small">Mostrando 50 de ' . count($collected) . ' filas…</div>';
            }
            $htmlPreview .= '</div>';
        }

        // Seteamos una sola vez (evita getFlashdata() intermedio que la borre)
        session()->setFlashdata('msg', $msg . $htmlPreview);

        return redirect()->to(site_url('enlaces/importar'));
    }

    /**
     * Limpia parámetros de tracking comunes y normaliza la URL
     */
    private function limpiarUrl(string $url): string
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $blacklist = [
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'gclid',
                'fbclid',
                'mc_cid',
                'mc_eid',
                'igshid',
                'si',
                'mibextid'
            ];
            foreach ($blacklist as $k) unset($query[$k]);
        }

        $rebuilt = $parts['scheme'] . '://' . $parts['host']
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '/')
            . ($query ? ('?' . http_build_query($query)) : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

        return $rebuilt;
    }

    // --- REVISIÓN DE ENLACES SIN TÍTULO ---

    /**
     * Dashboard rápido: cuántos pendientes y botón para empezar/continuar.
     */
    public function revision()
    {
        $db = \Config\Database::connect();
        $pendientes = $db->table('enlaces_items')
            ->selectCount('id', 'c')->where('(titulo IS NULL OR titulo = "")', null, false)
            ->get()->getRow('c');

        return view('enlaces/revision', [
            'pendientes' => (int)$pendientes,
        ]);
    }

    /**
     * Carga un item a revisar. Si no se pasa ID, coge el primer pendiente.
     */
    public function revisionItem($id = null)
    {
        $db = \Config\Database::connect();
        if ($id === null) {
            $row = $db->table('enlaces_items')
                ->select('*')
                ->where('(titulo IS NULL OR titulo = "")', null, false)
                ->orderBy('id', 'ASC')
                ->get(1)->getRowArray();
            if (!$row) {
                return redirect()->to(site_url('enlaces/revision'))
                    ->with('msg', 'No hay enlaces pendientes 🎉');
            }
            $id = $row['id'];
        }

        $item = $this->enlaces->find($id);
        if (!$item) {
            return redirect()->to(site_url('enlaces/revision'))
                ->with('msg', 'Enlace no encontrado');
        }

        // Calcular siguiente pendiente (por si el usuario guarda/borrra/salta)
        $nextId = $this->revisionSiguienteId($id);

        // Cargar listas para rellenar rápido si quieres (opcionales)
        $categorias = $this->categorias->orderBy('nombre', 'ASC')->findAll();
        $etiquetas  = $this->etiquetas->orderBy('nombre', 'ASC')->findAll();

        return view('enlaces/revision_item', compact('item', 'nextId', 'categorias', 'etiquetas'));
    }

    /**
     * Guardar cambios mínimos y pasar al siguiente.
     */
    public function revisionGuardar($id)
    {
        $item = $this->enlaces->find($id);
        if (!$item) {
            return redirect()->to(site_url('enlaces/revision'))->with('error', 'Item no encontrado');
        }

        // Datos mínimos: título (requerido en esta revisión), relevancia, visto, fecha, extra
        $data = [
            'titulo'     => trim((string)$this->request->getPost('titulo')),
            'relevancia' => (int)$this->request->getPost('relevancia') ?: 3,
            'visto'      => $this->request->getPost('visto') ? 1 : 0,
            'fecha'      => $this->request->getPost('fecha') ?: $item['fecha'],
            'extra'      => $this->request->getPost('extra') ?: null,
        ];

        if ($data['titulo'] === '') {
            return redirect()->back()->withInput()->with('error', 'El título es obligatorio para cerrar este enlace');
        }

        // Guardar
        if (!$this->enlaces->update($id, $data)) {
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar: ' . json_encode($this->enlaces->errors()));
        }

        // Sincronizar categorías (opcional rápido)
        $this->enlaceCategorias->where('item_id', $id)->delete();
        $categorias = $this->request->getPost('categorias') ?? [];
        foreach ($categorias as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) $this->enlaceCategorias->insert(['item_id' => $id, 'categoria_id' => $cid]);
        }

        // Sincronizar etiquetas por texto coma-separado (rápido)
        // --- SINCRONIZAR ETIQUETAS (por texto coma-separado) ---
        $this->enlaceEtiquetas->where('item_id', $id)->delete();

        $tagsString = trim((string)$this->request->getPost('etiquetas'));
        if ($tagsString !== '') {
            $nombres = array_values(array_unique(array_filter(array_map('trim', explode(',', $tagsString)))));
            foreach ($nombres as $nombre) {
                if ($nombre === '') continue;

                $tid = $this->getOrCreateEtiquetaId($nombre);
                if ($tid > 0) {
                    // evita doble inserción por si el form se reenvía
                    $ya = $this->enlaceEtiquetas
                        ->where(['item_id' => $id, 'etiqueta_id' => $tid])
                        ->first();

                    if (!$ya) {
                        $this->enlaceEtiquetas->insert(['item_id' => $id, 'etiqueta_id' => $tid]);
                    }
                }
            }
        }

        // Ir al siguiente pendiente si lo hay
        $nextId = $this->revisionSiguienteId($id);
        if ($nextId) {
            return redirect()->to(site_url('enlaces/revision/item/' . $nextId))->with('mensaje', 'Guardado');
        }
        return redirect()->to(site_url('enlaces/revision'))->with('mensaje', 'Guardado. No quedan pendientes 🎉');
    }

    /**
     * Borrar y pasar al siguiente.
     */
    public function revisionBorrar($id)
    {
        // Limpia relaciones por si tienes FK suaves
        $this->enlaceCategorias->where('item_id', $id)->delete();
        $this->enlaceEtiquetas->where('item_id', $id)->delete();
        $this->enlaces->delete($id);

        $nextId = $this->revisionSiguienteId($id);
        if ($nextId) {
            return redirect()->to(site_url('enlaces/revision/item/' . $nextId))->with('mensaje', 'Eliminado');
        }
        return redirect()->to(site_url('enlaces/revision'))->with('mensaje', 'Eliminado. No quedan pendientes 🎉');
    }

    /**
     * Saltar y pasar al siguiente (no toca el actual).
     */
    public function revisionSaltar($id)
    {
        $nextId = $this->revisionSiguienteId($id);
        if ($nextId) {
            return redirect()->to(site_url('enlaces/revision/item/' . $nextId));
        }
        return redirect()->to(site_url('enlaces/revision'))->with('msg', 'No quedan pendientes 🎉');
    }

    /**
     * ID del siguiente enlace sin título, posterior al actual.
     */
    private function revisionSiguienteId($currentId)
    {
        $db = \Config\Database::connect();
        $row = $db->table('enlaces_items')
            ->select('id')
            ->where('(titulo IS NULL OR titulo = "")', null, false)
            ->where('id >', (int)$currentId)
            ->orderBy('id', 'ASC')
            ->get(1)->getRowArray();

        if ($row) return (int)$row['id'];

        // Si no hay posterior, intenta el primero pendiente (cierre de bucle)
        $row = $db->table('enlaces_items')
            ->select('id')
            ->where('(titulo IS NULL OR titulo = "")', null, false)
            ->orderBy('id', 'ASC')
            ->get(1)->getRowArray();

        return $row ? (int)$row['id'] : null;
    }

    /**
     * Slug consistente (quita acentos y normaliza)
     */
    private function slugify(string $text): string
    {
        $text = trim(mb_strtolower($text, 'UTF-8'));

        // quitar acentos (fallback simple para ES)
        $text = strtr($text, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
            'Ü' => 'u',
            'Ñ' => 'n',
        ]);

        // todo lo no [a-z0-9] -> guión
        $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = trim($text, '-');

        return $text !== '' ? $text : 'tag';
    }

    /**
     * Devuelve el id de la etiqueta; si no existe la crea.
     * Evita duplicados por slug/nombre y maneja colisión #1062.
     */
    private function getOrCreateEtiquetaId(string $nombre): int
    {
        $nombre = trim($nombre);
        if ($nombre === '') return 0;

        $slug = $this->slugify($nombre);

        // 1) Buscar por slug o por nombre
        $exist = $this->etiquetas
            ->groupStart()
            ->where('slug', $slug)
            ->orWhere('nombre', $nombre)
            ->groupEnd()
            ->first();

        if ($exist) return (int)$exist['id'];

        // 2) Intentar crear
        try {
            $id = $this->etiquetas->insert(['nombre' => $nombre, 'slug' => $slug]);
            return (int)$id;
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // 1062 duplicate key -> alguien la creó en paralelo o ya existía con otra variante
            if (strpos($e->getMessage(), '1062') !== false) {
                $exist = $this->etiquetas
                    ->groupStart()
                    ->where('slug', $slug)
                    ->orWhere('nombre', $nombre)
                    ->groupEnd()
                    ->first();
                if ($exist) return (int)$exist['id'];
            }
            throw $e; // si es otro error, lo re-lanzamos
        }
    }

    private function esImagenUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts) return false;

        $path = $parts['path'] ?? '';
        if ($path === '') return false;

        // por si acaso viene con barra final
        $path = rtrim($path, '/');

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // extensiones típicas de imagen
        $imgExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'tif', 'tiff', 'heic', 'heif', 'avif', 'ico'];

        return in_array($ext, $imgExts, true);
    }
}
