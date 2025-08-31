<?
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
        /** @var BaseConnection $db */
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
        // (aplicamos en este orden de prioridad y rematamos con posicion ASC)
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
        $videosModel   = clone $this->videos;
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

    public function importarTexto(string $slug)
    {
        $lista = $this->listas->findBySlug($slug);
        if (!$lista) return redirect()->to(site_url('youtube'));

        if ($this->request->getMethod() === 'POST') {
            $texto = $this->request->getPost('texto');
            $urls  = $this->parseLinesToUrls($texto);
            $this->bulkInsert($lista['id'], $urls);
            return redirect()->to(site_url('youtube/' . $slug));
        }
        return view('youtube/importar_texto', ['lista' => $lista]);
    }

    public function importarHTML(string $slug)
    {
        $lista = $this->listas->findBySlug($slug);
        if (!$lista) return redirect()->to(site_url('youtube'));

        if ($this->request->getMethod() === 'POST') {
            $html = $this->request->getPost('html');
            $urls = $this->extractYoutubeUrlsFromHtml($html);
            $this->bulkInsert($lista['id'], $urls);
            return redirect()->to(site_url('youtube/' . $slug));
        }
        return view('youtube/importar_html', ['lista' => $lista]);
    }

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


    /**
     * Extrae el playlistId desde un ID directo o una URL con ?list=...
     * Devuelve "" si no consigue uno válido.
     */
    private function extractPlaylistId(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        // Si parece URL, intentamos capturar ?list=XXXX
        if (stripos($raw, 'http://') === 0 || stripos($raw, 'https://') === 0) {
            // Primero intenta querystring
            $parts = parse_url($raw);
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $q);
                if (!empty($q['list'])) return $q['list'];
            }
            // Algunas URL estilo /playlist/PLxxxx (poco común)
            if (!empty($parts['path'])) {
                if (preg_match('~(?:/playlist|/watch)\?list=([A-Za-z0-9_-]+)~i', $raw, $m)) {
                    return $m[1];
                }
            }
            // No encontrado en URL
            return '';
        }

        // Si no es URL, asumimos que ya es un ID
        return $raw;
    }

    /**
     * Formatea mensaje de error a partir del JSON devuelto por YouTube en 4xx/5xx.
     */
    private function formatYouTubeError(int $http, ?string $body, string $curlErr, string $url): string
    {
        $msg = "Error API YouTube (HTTP {$http}). ";
        if ($curlErr) $msg .= "cURL: {$curlErr}. ";

        if ($body) {
            $j = json_decode($body, true);
            if (is_array($j) && isset($j['error'])) {
                $em = $j['error']['message'] ?? null;
                $reasons = [];
                foreach ($j['error']['errors'] ?? [] as $e) {
                    $reasons[] = $e['reason'] ?? '';
                }
                $msg .= 'Mensaje: ' . ($em ?: 'sin detalle') . '.';
                if ($reasons) $msg .= ' Razón: ' . implode(', ', array_filter($reasons)) . '.';
            } else {
                // No era JSON o estructura distinta
                $msg .= 'Respuesta: ' . mb_substr($body, 0, 400) . '…';
            }
        }

        // Útil para depurar: muestra qué URL construimos
        $msg .= ' [debug URL: ' . $url . ']';

        return $msg;
    }


    // --- Helpers ---

    private function parseLinesToUrls(string $texto): array
    {
        $urls = [];
        foreach (preg_split('/\r\n|\r|\n/', $texto) as $line) {
            $u = trim($line);
            if ($u !== '' && $this->isYoutubeUrl($u)) $urls[] = $u;
        }
        return array_values(array_unique($urls));
    }

    private function extractYoutubeUrlsFromHtml(string $html): array
    {
        $urls = [];
        // 1) buscar href="...youtube..." o "youtu.be..."
        if (preg_match_all('#href=["\']([^"\']+)(youtube\.com/watch\?v=[^"\']+|youtu\.be/[^"\']+)#i', $html, $m)) {
            foreach ($m[1] as $raw) if ($this->isYoutubeUrl($raw)) $urls[] = $raw;
        }
        // 2) buscar data-* con URLs embebidas (backup)
        if (preg_match_all('#(https?://(?:www\.)?(?:youtube\.com/watch\?v=[\w\-]+|youtu\.be/[\w\-]+)[^"\'\s<]*)#i', $html, $m2)) {
            foreach ($m2[1] as $raw) if ($this->isYoutubeUrl($raw)) $urls[] = $raw;
        }
        return array_values(array_unique($urls));
    }

    private function isYoutubeUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\.)?(youtube\.com/watch\?v=|youtu\.be/)#i', $url);
    }

    private function extractVideoId(string $url): ?string
    {
        // watch?v=ID
        if (preg_match('#watch\?v=([\w\-]{6,})#', $url, $m)) return $m[1];
        // youtu.be/ID
        if (preg_match('#youtu\.be/([\w\-]{6,})#', $url, $m)) return $m[1];
        return null;
    }

    private function bulkInsert(int $listaId, array $urls): void
    {
        // averiguar última posición
        $ultimo = $this->videos->where('lista_id', $listaId)->selectMax('posicion')->first();
        $pos = (int) ($ultimo['posicion'] ?? 0);

        $rows = [];
        foreach ($urls as $u) {
            $rows[] = [
                'lista_id'  => $listaId,
                'posicion'  => ++$pos,
                'url'       => $u,
                'video_id'  => $this->extractVideoId($u),
                'visto'     => 0,
                'relevante' => 0,
            ];
        }
        if (!empty($rows)) $this->videos->insertBatch($rows);
    }
}
