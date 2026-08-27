<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\ReadingSessionModel;
use App\Services\BookLookupService;
use App\Services\ReadingJournalSyncService;

class Reading extends BaseController
{
    /** Estado => etiqueta visible en las pestañas de la biblioteca. */
    public const ESTADOS = [
        'leyendo'     => 'Leyendo',
        'quiero_leer' => 'Quiero leer',
        'pausado'     => 'Pausados',
        'terminado'   => 'Terminados',
        'abandonado'  => 'Abandonados',
    ];

    protected BookModel $bookModel;
    protected ReadingSessionModel $sessionModel;
    protected ReadingJournalSyncService $syncService;

    public function __construct()
    {
        $this->bookModel = new BookModel();
        $this->sessionModel = new ReadingSessionModel();
        $this->syncService = new ReadingJournalSyncService();
    }

    /**
     * Biblioteca: una pestaña por estado. Cada tarjeta muestra portada,
     * título, autor y % de progreso si el libro tiene total_pages.
     */
    public function library()
    {
        $tab = $this->request->getGet('tab') ?: 'leyendo';
        if (!array_key_exists($tab, self::ESTADOS)) {
            $tab = 'leyendo';
        }

        $libros = $this->bookModel->getByStatus($tab);
        foreach ($libros as &$libro) {
            $libro['progreso'] = $this->bookModel->progreso($libro);
        }
        unset($libro);

        return view('reading/library', [
            'estados'    => self::ESTADOS,
            'tabActual'  => $tab,
            'libros'     => $libros,
        ]);
    }

    public function nuevoLibro()
    {
        return view('reading/form', ['libro' => null]);
    }

    /**
     * Búsqueda de portada/autor/ISBN/páginas contra Open Library (AJAX),
     * usada tanto al añadir un libro como para completar uno ya existente.
     */
    public function buscarLibro()
    {
        $query = trim((string) $this->request->getGet('q'));
        if (mb_strlen($query) < 3) {
            return $this->response->setJSON(['success' => true, 'resultados' => []]);
        }

        $resultados = (new BookLookupService())->buscar($query);

        return $this->response->setJSON(['success' => true, 'resultados' => $resultados]);
    }

    public function crearLibro()
    {
        $data = $this->datosDelFormulario();

        if ($data['status'] === 'leyendo') {
            $data['started_at'] = date('Y-m-d');
        }

        // Si se subió una imagen manualmente (libro no encontrado en la
        // API), tiene prioridad sobre cualquier URL de portada.
        $subida = $this->procesarPortadaSubida(null);
        if ($subida !== null) {
            $data['cover_url'] = $subida;
        }

        // Crea también la task en Journal (categoría Lectura): Journal sigue
        // siendo la puerta de entrada aunque el alta se haga desde aquí.
        $id = $this->syncService->crearLibroConTask($data);
        if (!$id) {
            return redirect()->back()->withInput()->with('error', 'Revisa el título del libro.');
        }

        return redirect()->to(site_url('reading/libro/' . $id))->with('success', 'Libro añadido.');
    }

    /**
     * Ficha de libro: check binario del día + formulario de sesión (Capa 2),
     * widget de constancia e historial reciente.
     */
    public function libro(int $id)
    {
        $libro = $this->bookModel->find($id);
        if (!$libro) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Libro no encontrado');
        }

        $libro['progreso'] = $this->bookModel->progreso($libro);

        return view('reading/book_detail', [
            'libro'      => $libro,
            'hoy'        => $this->sessionModel->getForToday($id),
            'constancia' => $this->sessionModel->constanciaVentana($id),
            'historial'  => array_slice($this->sessionModel->getForBook($id), 0, 10),
        ]);
    }

    public function actualizarLibro(int $id)
    {
        $libro = $this->bookModel->find($id);
        if (!$libro) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Libro no encontrado');
        }

        $data = $this->datosDelFormulario();

        $subida = $this->procesarPortadaSubida($libro['cover_url'] ?? null);
        if ($subida !== null) {
            $data['cover_url'] = $subida;
        }

        $data = $this->conTransicionesDeFecha($libro, $data);

        $this->bookModel->update($id, $data);
        $this->sincronizarConTask($id, $libro);

        return redirect()->to(site_url('reading/libro/' . $id))->with('success', 'Libro actualizado.');
    }

    /**
     * Cambio de estado desde el selector rápido (fuera de Ajustes): guarda
     * al toque, sin pasar por el resto del formulario.
     */
    public function actualizarEstado(int $id)
    {
        $libro = $this->bookModel->find($id);
        if (!$libro) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $status = $input['status'] ?? '';
        if (!array_key_exists($status, self::ESTADOS)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Estado no válido.']);
        }

        $data = $this->conTransicionesDeFecha($libro, ['status' => $status]);

        $this->bookModel->update($id, $data);
        $this->sincronizarConTask($id, $libro);

        return $this->response->setJSON(['success' => true, 'status' => $status]);
    }

    /**
     * Actualiza solo "por qué página voy" desde el editor inline de la ficha,
     * sin registrar una sesión ni pasar por Ajustes. Sincroniza el progreso
     * con la task de Journal al vuelo.
     */
    public function actualizarPagina(int $id)
    {
        $libro = $this->bookModel->find($id);
        if (!$libro) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $page  = max(0, (int) ($input['page'] ?? 0));

        $total = (int) ($libro['total_pages'] ?? 0);
        if ($total > 0) {
            $page = min($page, $total);
        }

        $this->bookModel->update($id, ['current_page' => $page]);

        if (!empty($libro['task_id'])) {
            $libro['current_page'] = $page;
            $this->syncService->pushBookSettingsToTask((int) $libro['task_id'], $libro);
        }

        return $this->response->setJSON([
            'success'      => true,
            'current_page' => $page,
            'progreso'     => $this->bookModel->progreso($this->bookModel->find($id)),
        ]);
    }

    /**
     * Transiciones de fecha: solo se rellenan la primera vez, nunca se
     * pisan ni se usan para comparar "deberías haber terminado ya". La
     * valoración solo tiene sentido si el libro está terminado.
     */
    private function conTransicionesDeFecha(array $libro, array $data): array
    {
        if ($data['status'] === 'leyendo' && empty($libro['started_at'])) {
            $data['started_at'] = date('Y-m-d');
        }
        if ($data['status'] === 'terminado' && empty($libro['finished_at'])) {
            $data['finished_at'] = date('Y-m-d');
        }
        if ($data['status'] !== 'terminado') {
            $data['rating'] = null;
        }

        // "Leído" => se dan todas las páginas por hechas. "Dejado" (abandonado)
        // deja la página actual como esté: en Journal saldrá hecho pero con el
        // progreso a medias.
        if ($data['status'] === 'terminado') {
            $total = (int) ($data['total_pages'] ?? $libro['total_pages'] ?? 0);
            if ($total > 0) {
                $data['current_page'] = $total;
            }
        }

        return $data;
    }

    private function sincronizarConTask(int $bookId, array $libroAntes): void
    {
        if (empty($libroAntes['task_id'])) {
            return;
        }

        $libroActualizado = $this->bookModel->find($bookId);
        $this->syncService->pushBookSettingsToTask((int) $libroAntes['task_id'], $libroActualizado);
    }

    public function borrarLibro(int $id)
    {
        $this->bookModel->delete($id);
        return redirect()->to(site_url('reading'))->with('success', 'Libro eliminado.');
    }

    /**
     * Registrar sesión ("Sí, toqué el libro hoy"). Todo opcional salvo la
     * fecha, que es automática. Incluye los datos de Capa 3 (perdí el hilo,
     * pensamiento aparcado) si el usuario los usó.
     */
    public function registrarSesion(int $id)
    {
        $libro = $this->bookModel->find($id);
        if (!$libro) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();

        $minutes = trim((string) ($input['minutes'] ?? ''));
        $pageReached = trim((string) ($input['page_reached'] ?? ''));
        $note = trim((string) ($input['note'] ?? ''));
        $parkedThought = trim((string) ($input['parked_thought'] ?? ''));
        $lostThreadCount = max(0, (int) ($input['lost_thread_count'] ?? 0));

        $this->sessionModel->insert([
            'book_id'            => $id,
            'session_date'       => date('Y-m-d'),
            'minutes'            => $minutes !== '' ? (int) $minutes : null,
            'page_reached'       => $pageReached !== '' ? (int) $pageReached : null,
            'note'               => $note !== '' ? $note : null,
            'lost_thread_count'  => $lostThreadCount,
            'parked_thought'     => $parkedThought !== '' ? $parkedThought : null,
            'skipped'            => 0,
        ]);

        // El primer toque saca al libro de "quiero leer" sin que haga falta
        // decidirlo aparte; y si se alcanzó página, se actualiza el progreso.
        $justStarted = $libro['status'] === 'quiero_leer';
        $bookUpdate = [];
        if ($justStarted) {
            $bookUpdate['status'] = 'leyendo';
            $bookUpdate['started_at'] = $libro['started_at'] ?: date('Y-m-d');
        }
        if ($pageReached !== '') {
            $bookUpdate['current_page'] = (int) $pageReached;
        }
        if (!empty($bookUpdate)) {
            $this->bookModel->update($id, $bookUpdate);
        }

        if (!empty($libro['task_id'])) {
            $this->syncService->pushSessionToTask(
                (int) $libro['task_id'],
                $minutes !== '' ? (int) $minutes : 0,
                $pageReached !== '' ? (int) $pageReached : null,
                $justStarted
            );
        }

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => 'Página registrada. Eso es lo que cuenta.',
        ]);
    }

    /**
     * "Hoy no toca": registra la decisión para que el calendario no muestre
     * un hueco interpretable como fallo.
     */
    public function registrarSkip(int $id)
    {
        $libro = $this->bookModel->find($id);
        if (!$libro) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $this->sessionModel->insert([
            'book_id'      => $id,
            'session_date' => date('Y-m-d'),
            'skipped'      => 1,
        ]);

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => 'Anotado. Mañana será otro día.',
        ]);
    }

    /**
     * Si el formulario trae un archivo de imagen ("cover_image"), lo sube y
     * devuelve su URL absoluta (para libros que la API no encuentra). Borra
     * la portada anterior solo si era una imagen subida por nosotros mismos
     * (las de la API externa no se tocan). Devuelve null si no hay archivo
     * válido, para no pisar el cover_url que ya traiga el formulario.
     */
    private function procesarPortadaSubida(?string $coverUrlActual): ?string
    {
        $file = $this->request->getFile('cover_image');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        if (strpos((string) $file->getClientMimeType(), 'image/') !== 0) {
            return null;
        }

        $uploadDir = FCPATH . 'upload/images/reading/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $prefijoLocal = base_url('upload/images/reading/');
        if ($coverUrlActual && str_starts_with($coverUrlActual, $prefijoLocal)) {
            $rutaAnterior = $uploadDir . basename($coverUrlActual);
            if (is_file($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        return $prefijoLocal . $newName;
    }

    private function datosDelFormulario(): array
    {
        $status = $this->request->getPost('status');
        if (!array_key_exists($status, self::ESTADOS)) {
            $status = 'quiero_leer';
        }

        $totalPages   = $this->request->getPost('total_pages');
        $minGoalPages = $this->request->getPost('min_goal_pages');
        $rating       = $this->request->getPost('rating');

        return [
            'title'          => trim((string) $this->request->getPost('title')),
            'author'         => trim((string) $this->request->getPost('author')) ?: null,
            'cover_url'      => trim((string) $this->request->getPost('cover_url')) ?: null,
            'isbn'           => trim((string) $this->request->getPost('isbn')) ?: null,
            'total_pages'    => $totalPages !== '' && $totalPages !== null ? (int) $totalPages : null,
            'status'         => $status,
            'min_goal_pages' => $minGoalPages !== '' && $minGoalPages !== null ? max(1, (int) $minGoalPages) : 1,
            'anchor_routine' => trim((string) $this->request->getPost('anchor_routine')) ?: null,
            'rating'         => $rating !== '' && $rating !== null ? (int) $rating : null,
        ];
    }
}
