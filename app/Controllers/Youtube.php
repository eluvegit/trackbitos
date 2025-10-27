<?php
// app/Controllers/Youtube.php
namespace App\Controllers;

use App\Models\YoutubeListasModel;
use App\Models\YoutubeVideosModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Controller;
use Config\Database;

class Youtube extends Controller
{
    protected $listas;
    protected $videos;

    public function __construct()
    {
        $this->listas = new YoutubeListasModel();
        $this->videos = new YoutubeVideosModel();
    }

    // app/Controllers/Youtube.php
    public function index()
    {
        $listas = $this->listas
            ->select('youtube_listas.*, 
                  COUNT(v.id) as total,
                  SUM(v.visto=1) as vistos,
                  SUM(v.relevante=1) as relevantes')
            ->join('youtube_videos v', 'v.lista_id = youtube_listas.id', 'left')
            ->groupBy('youtube_listas.id')
            ->findAll();

        return view('youtube/index', [
            'listas' => $listas
        ]);
    }

    public function lista(string $slug)
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = \Config\Database::connect();

        // 1) Obtener la lista
        $lista = $db->table('youtube_listas')->where('slug', $slug)->get()->getRowArray();
        if (!$lista) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Lista no encontrada');
        }

        // 2) Stats rápidos
        $listaId = (int)$lista['id'];
        $stats = [
            'total'      => (int)$db->table('youtube_videos')->where('lista_id', $listaId)->countAllResults(),
            'vistos'     => (int)$db->table('youtube_videos')->where('lista_id', $listaId)->where('visto', 1)->countAllResults(),
            'relevantes' => (int)$db->table('youtube_videos')->where('lista_id', $listaId)->where('relevante', 1)->countAllResults(),
        ];
        $stats['pct_vistos'] = $stats['total'] ? number_format(($stats['vistos'] / $stats['total']) * 100, 0) : 0;
        $stats['pct_relev']  = $stats['total'] ? number_format(($stats['relevantes'] / $stats['total']) * 100, 0) : 0;

        // 3) Filtros y orden
        $req = service('request');
        $sv  = (string)$req->getGet('sort_vistos');        // 'no_vistos_primero' | 'vistos_primero' | ''
        $sr  = (string)$req->getGet('sort_relevantes');    // 'primero' | ''
        $nv  = $req->getGet('no_vistos') ? 1 : 0;          // 1 | 0
        $rel = $req->getGet('relevantes') ? 1 : 0;         // 1 | 0

        $b = $db->table('youtube_videos')->where('lista_id', $listaId);

        // Filtros
        if ($nv) {
            $b->where('visto', 0);
        }
        if ($rel) {
            $b->where('relevante', 1);
        }

        // Orden compuesto
        if ($sr === 'primero') {
            $b->orderBy('relevante', 'DESC'); // relevantes primero
        }
        if ($sv === 'no_vistos_primero') {
            $b->orderBy('visto', 'ASC');      // 0 (no visto) antes que 1
        } elseif ($sv === 'vistos_primero') {
            $b->orderBy('visto', 'DESC');     // 1 antes que 0
        }

        // Desempate / orden natural
        $b->orderBy('posicion', 'ASC');

        $videos = $b->get()->getResultArray();

        return view('youtube/lista', [
            'lista'  => $lista,
            'stats'  => $stats,
            'videos' => $videos,
        ]);
    }

    public function crearLista()
    {
        if ($this->request->getMethod() === 'POST') {
            $nombre = trim($this->request->getPost('nombre'));
            $slug   = url_title($nombre, '-', true);

            $this->listas->insert(['nombre' => $nombre, 'slug' => $slug]);
            return redirect()->to(site_url('youtube'));
        }
        return view('youtube/crear_lista');
    }

    public function ver(string $slug)
    {
        $lista = $this->listas->findBySlug($slug);
        if (!$lista) {
            return redirect()->to(site_url('youtube'));
        }

        // Filtros y orden
        $filters = [
            'solo_no_vistos'  => (bool) $this->request->getGet('no_vistos'),
            'solo_relevantes' => (bool) $this->request->getGet('relevantes'),
        ];
        $sort = [
            'vistos'     => $this->request->getGet('sort_vistos'),      // no_vistos_primero | vistos_primero | null
            'relevantes' => $this->request->getGet('sort_relevantes'),  // primero | null
        ];

        // Listado: usa un CLONE del modelo para no compartir estado del builder
        $videosModel    = clone $this->videos;
        $data['videos'] = $videosModel->baseQuery($lista['id'], $filters, $sort)->findAll();

        // Estadísticas: conexión limpia con db_connect() (no $this->db)
        $db = db_connect();
        $stats = $db->table('youtube_videos')
            ->select("
            COUNT(*) AS total,
            SUM(CASE WHEN visto = 1 THEN 1 ELSE 0 END)       AS vistos,
            SUM(CASE WHEN relevante = 1 THEN 1 ELSE 0 END)   AS relevantes
        ", false)
            ->where('lista_id', $lista['id'])
            ->get()
            ->getRowArray();

        $total  = (int) ($stats['total'] ?? 0);
        $vistos = (int) ($stats['vistos'] ?? 0);
        $relev  = (int) ($stats['relevantes'] ?? 0);

        $data['lista'] = $lista;
        $data['stats'] = [
            'total'       => $total,
            'vistos'      => $vistos,
            'relevantes'  => $relev,
            'pct_vistos'  => $total ? round($vistos * 100 / $total, 1) : 0,
            'pct_relev'   => $total ? round($relev  * 100 / $total, 1) : 0,
        ];

        return view('youtube/ver', $data);
    }

    /*** ====== IMPORTADOR POR LISTA (slug en URL) ====== ***/

    /** Muestra el formulario con el textarea, ligado a una lista por slug */
    public function importarForm(string $slug)
    {
        $lista = $this->listas->findBySlug($slug);
        if (!$lista) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Lista no encontrada');
        }

        return view('youtube/importar', [
            'lista'   => $lista,
            'results' => null,
            'errors'  => [],
            'oldJson' => '',
        ]);
    }

    /** Procesa el JSON pegado en el textarea para la lista indicada por slug */
    public function importarProcesar(string $slug): ResponseInterface
    {
        $lista = $this->listas->findBySlug($slug);
        $raw   = (string) $this->request->getPost('json');

        if (!$lista) {
            return $this->response->setStatusCode(404)->setBody(
                view('youtube/importar', [
                    'lista'   => null,
                    'results' => null,
                    'errors'  => ['No existe la lista con slug: ' . esc($slug)],
                    'oldJson' => $raw,
                ])
            );
        }

        // Normalizamos entrada: permitir array o objetos sueltos separados por comas
        $jsonText = trim($raw);
        if ($jsonText === '') {
            return $this->response->setStatusCode(422)->setBody(
                view('youtube/importar', [
                    'lista'   => $lista,
                    'results' => null,
                    'errors'  => ['El campo JSON está vacío. Pega objetos con "titulo" y "url".'],
                    'oldJson' => $raw,
                ])
            );
        }
        if ($jsonText[0] !== '[') {
            $jsonText = preg_replace('~,\s*$~u', '', $jsonText); // quitar coma final si la hay
            $jsonText = '[' . $jsonText . ']';
        }

        $items = json_decode($jsonText, true);
        if (!is_array($items)) {
            return $this->response->setStatusCode(422)->setBody(
                view('youtube/importar', [
                    'lista'   => $lista,
                    'results' => null,
                    'errors'  => ['No se pudo interpretar el JSON. Revisa comas y llaves.'],
                    'oldJson' => $raw,
                ])
            );
        }

        // Próxima posición
        $db = db_connect();
        $maxPos = (int) ($db->table('youtube_videos')
            ->select('COALESCE(MAX(posicion),0) AS maxpos', false)
            ->where('lista_id', $lista['id'])
            ->get()->getRow('maxpos') ?? 0);
        $nextPos = $maxPos + 1;

        $results = [];
        foreach ($items as $idx => $obj) {
            $row = [
                'index'     => $idx,
                'titulo'    => null,
                'url'       => null,
                'youtubeId' => null,   // solo para mostrar en el resultado
                'ok'        => false,
                'error'     => null,
                'duplicado' => false,
                'insert_id' => null,
            ];

            if (!is_array($obj)) {
                $row['error'] = 'Entrada no es un objeto.';
                $results[] = $row;
                continue;
            }

            $titulo = trim((string)($obj['titulo'] ?? ''));
            $url    = trim((string)($obj['url'] ?? ''));

            $row['titulo'] = $titulo;
            // Omitir vídeos borrados/privados por título
            if ($this->shouldSkipTitle($titulo)) {
                $row['error'] = 'Omitido: vídeo eliminado/privado';
                // No marcamos ok ni duplicado; simplemente se informa como omitido
                $results[] = $row;
                continue;
            }

            $row['url']    = $url;

            if ($titulo === '') {
                $row['error'] = 'Falta "titulo".';
                $results[] = $row;
                continue;
            }
            if ($url === '') {
                $row['error'] = 'Falta "url".';
                $results[] = $row;
                continue;
            }

            // Extraer ID
            $videoId = $this->extractYouTubeId($url);
            if (!$videoId) {
                $row['error'] = 'URL de YouTube no reconocida.';
                $results[] = $row;
                continue;
            }
            $row['youtubeId'] = $videoId;

            // URL canónica
            $canonicalUrl = 'https://www.youtube.com/watch?v=' . $videoId;

            // Duplicados por (lista_id, url canónica)
            $exists = (clone $this->videos)
                ->where('lista_id', $lista['id'])
                ->where('url', $canonicalUrl)
                ->first();

            if ($exists) {
                $row['ok']        = true;
                $row['duplicado'] = true;
                $row['insert_id'] = $exists['id'] ?? null;
                $results[] = $row;
                continue;
            }

            // Inserción
            $payload = [
                'lista_id'  => $lista['id'],
                'posicion'  => $nextPos++,
                'url'       => $canonicalUrl,
                'titulo'    => $titulo,
                'visto'     => 0,
                'relevante' => 0,
            ];

            try {
                $insertId = $this->videos->insert($payload, true);
                $row['ok'] = true;
                $row['insert_id'] = $insertId;
            } catch (\Throwable $e) {
                $row['error'] = 'Error al guardar: ' . $e->getMessage();
            }

            $results[] = $row;
        }

        return $this->response->setBody(
            view('youtube/importar', [
                'lista'   => $lista,
                'results' => $results,
                'errors'  => [],
                'oldJson' => $raw,
            ])
        );
    }

    public function editarLista(string $slug)
    {
        $lista = $this->listas->findBySlug($slug);
        if (!$lista) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Lista no encontrada');
        }

        return view('youtube/editar_lista', [
            'lista'  => $lista,
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function actualizarLista(string $slug)
    {
        $lista = $this->listas->findBySlug($slug);
        if (!$lista) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Lista no encontrada');
        }

        $nombre = trim($this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->back()->withInput()->with('errors', ['El nombre no puede estar vacío.']);
        }

        $newSlug = url_title($nombre, '-', true);

        $this->listas->update($lista['id'], [
            'nombre' => $nombre,
            'slug'   => $newSlug,
        ]);

        return redirect()->to(site_url('youtube/' . $newSlug));
    }


    /**
     * Extrae el ID de YouTube desde múltiples formatos (watch, youtu.be, shorts, embed).
     */
    private function extractYouTubeId(string $url): ?string
    {
        $url = trim($url);

        // Permitir ID directo
        if (preg_match('~^[a-zA-Z0-9_-]{11}$~', $url)) {
            return $url;
        }

        $parts = @parse_url($url);
        if (!$parts || !isset($parts['host'])) {
            return null;
        }
        $host  = strtolower($parts['host']);
        $path  = $parts['path'] ?? '';
        $query = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        // youtu.be/<id>
        if ($host === 'youtu.be') {
            $segments = array_values(array_filter(explode('/', $path)));
            if (!empty($segments[0]) && preg_match('~^[a-zA-Z0-9_-]{11}$~', $segments[0])) {
                return $segments[0];
            }
            return null;
        }

        // *.youtube.com
        if (strpos($host, 'youtube.com') !== false) {
            // watch?v=<id>
            if (!empty($query['v']) && preg_match('~^[a-zA-Z0-9_-]{11}$~', $query['v'])) {
                return $query['v'];
            }
            // shorts/<id>
            if (preg_match('~^/shorts/([a-zA-Z0-9_-]{11})~', $path, $m)) {
                return $m[1];
            }
            // embed/<id>
            if (preg_match('~^/embed/([a-zA-Z0-9_-]{11})~', $path, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    // HELPERS
    public function toggleVisto(int $id): ResponseInterface
    {
        $video = $this->videos->find($id);
        if (!$video) return $this->response->setStatusCode(404);
        $this->videos->update($id, ['visto' => $video['visto'] ? 0 : 1]);
        return $this->response->setJSON(['ok' => true, 'visto' => !$video['visto']]);
    }

    public function toggleRelevante(int $id): ResponseInterface
    {
        $video = $this->videos->find($id);
        if (!$video) return $this->response->setStatusCode(404);
        $this->videos->update($id, ['relevante' => $video['relevante'] ? 0 : 1]);
        return $this->response->setJSON(['ok' => true, 'relevante' => !$video['relevante']]);
    }

    public function toggleLargo($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $model = new \App\Models\YoutubeVideosModel();
        $video = $model->find($id);
        if (!$video) {
            return $this->response->setStatusCode(404);
        }

        $nuevoEstado = $video['largo'] ? 0 : 1;
        $model->update($id, ['largo' => $nuevoEstado]);

        return $this->response->setStatusCode(200);
    }



    /** Devuelve true si el título indica vídeo borrado/privado (ES/EN, con o sin corchetes). */
    private function shouldSkipTitle(string $title): bool
    {
        $t = trim(mb_strtolower($title));

        // Quitar posibles corchetes y espacios
        $t = trim($t, "[]() \t\n\r\0\x0B");

        // Coincidencias exactas más comunes
        $exact = [
            'deleted video',
            'private video',
            'vídeo eliminado',
            'video eliminado',
            'vídeo borrado',
            'video borrado',
            'vídeo privado',
            'video privado',
        ];
        if (in_array($t, $exact, true)) {
            return true;
        }

        // Por si vienen adornados: "[Deleted video - something]" (más raro)
        if (str_contains($t, 'deleted video') || str_contains($t, 'private video')) {
            return true;
        }
        if (
            str_contains($t, 'video eliminado') || str_contains($t, 'vídeo eliminado')
            || str_contains($t, 'video borrado') || str_contains($t, 'vídeo borrado')
            || str_contains($t, 'video privado') || str_contains($t, 'vídeo privado')
        ) {
            return true;
        }

        return false;
    }
}
