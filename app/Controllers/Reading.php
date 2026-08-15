<?php

namespace App\Controllers;

use App\Models\BookModel;
use App\Models\ReadingSessionModel;

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

    public function __construct()
    {
        $this->bookModel = new BookModel();
        $this->sessionModel = new ReadingSessionModel();
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

    public function crearLibro()
    {
        $data = $this->datosDelFormulario();

        if ($data['status'] === 'leyendo') {
            $data['started_at'] = date('Y-m-d');
        }

        $id = $this->bookModel->insert($data, true);
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

        // Transiciones de fecha: solo se rellenan la primera vez, nunca se
        // pisan ni se usan para comparar "deberías haber terminado ya".
        if ($data['status'] === 'leyendo' && empty($libro['started_at'])) {
            $data['started_at'] = date('Y-m-d');
        }
        if ($data['status'] === 'terminado' && empty($libro['finished_at'])) {
            $data['finished_at'] = date('Y-m-d');
        }
        if ($data['status'] !== 'terminado') {
            $data['rating'] = null; // la valoración solo tiene sentido al terminar
        }

        $this->bookModel->update($id, $data);

        return redirect()->to(site_url('reading/libro/' . $id))->with('success', 'Libro actualizado.');
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
        $bookUpdate = [];
        if ($libro['status'] === 'quiero_leer') {
            $bookUpdate['status'] = 'leyendo';
            $bookUpdate['started_at'] = $libro['started_at'] ?: date('Y-m-d');
        }
        if ($pageReached !== '') {
            $bookUpdate['current_page'] = (int) $pageReached;
        }
        if (!empty($bookUpdate)) {
            $this->bookModel->update($id, $bookUpdate);
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
