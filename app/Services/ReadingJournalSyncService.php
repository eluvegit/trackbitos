<?php

namespace App\Services;

use App\Models\BookModel;
use App\Models\JournalCategoryModel;
use App\Models\TaskModel;

/**
 * Sincroniza el módulo Lectura con la categoría "Lectura" de Journal:
 * Journal sigue siendo la puerta de entrada (crear/editar la task no
 * cambia), y este módulo completa el resto de datos del mismo libro
 * (autor, portada, sesiones, constancia...). El enlace es books.task_id.
 */
class ReadingJournalSyncService
{
    public const CATEGORIA = 'Lectura';

    /**
     * Alta desde el módulo Lectura: crea la task en Journal (lo que a su
     * vez, vía TaskModel::afterInsert, crea el book mínimo vinculado) y
     * completa ese book con el resto de campos del formulario.
     */
    public function crearLibroConTask(array $bookData): ?int
    {
        $title = trim((string) ($bookData['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        $taskModel = new TaskModel();
        $bookModel = new BookModel();
        $catModel  = new JournalCategoryModel();

        $categoria = $catModel->where('name', self::CATEGORIA)->first();

        $taskId = $taskModel->insert([
            'title'      => $title,
            'category'   => self::CATEGORIA,
            'color'      => $categoria['color'] ?? '#4A9367',
            'start_time' => $bookData['started_at'] ?? null,
        ], true);

        if (!$taskId) {
            return null;
        }

        $libro = $bookModel->where('task_id', $taskId)->first();
        if (!$libro) {
            // No debería pasar (el afterInsert de TaskModel lo crea), pero
            // sin book vinculado no hay nada que completar.
            return null;
        }

        $bookModel->update($libro['id'], $bookData);
        $libro = $bookModel->find($libro['id']);

        $this->pushBookSettingsToTask((int) $taskId, $libro);

        return (int) $libro['id'];
    }

    /**
     * Llamado desde TaskModel::afterInsert cuando se crea una task en la
     * categoría Lectura (alta directa en Journal, o paso intermedio de
     * crearLibroConTask). Crea el book mínimo vinculado si no existe ya.
     * No copia amplitude/completed: son valores por defecto genéricos de
     * Journal::create() (100/1), no páginas reales de un libro.
     */
    public function createBookForTask(int $taskId, array $taskData): void
    {
        $bookModel = new BookModel();

        if ($bookModel->where('task_id', $taskId)->first()) {
            return; // ya vinculado (viene de crearLibroConTask)
        }

        $title = trim((string) ($taskData['title'] ?? ''));
        if ($title === '') {
            return;
        }

        $bookModel->insert([
            'task_id' => $taskId,
            'title'   => $title,
            'status'  => 'quiero_leer',
        ]);
    }

    /**
     * Tras registrar una sesión de lectura, empuja el progreso a la task
     * vinculada: minutos acumulados, página actual y, si es la primera
     * sesión del libro, la fecha de inicio.
     */
    public function pushSessionToTask(int $taskId, int $minutes, ?int $pageReached, bool $justStarted): void
    {
        $taskModel = new TaskModel();
        $task = $taskModel->find($taskId);
        if (!$task) {
            return;
        }

        $data = [];

        if ($minutes > 0) {
            $data['time_spent'] = ((int) ($task['time_spent'] ?? 0)) + $minutes;
        }
        if ($pageReached !== null) {
            $data['completed'] = $pageReached;
        }
        if ($justStarted && (empty($task['start_time']) || $task['start_time'] === '0000-00-00 00:00:00')) {
            $data['start_time'] = date('Y-m-d');
        }

        if (!empty($data)) {
            $taskModel->update($taskId, $data);
        }
    }

    /**
     * Tras editar los ajustes de un libro (título, páginas totales, estado,
     * página actual...) desde Reading, refleja lo relevante en la task
     * vinculada. En esta dirección el libro es la fuente de la verdad:
     *
     *   estado        estrella   hecho (end_time)      progreso (completed)
     *   ----------------------------------------------------------------------
     *   leyendo       sí         no                    página actual
     *   quiero_leer   no         no                    página actual
     *   pausado       no         no                    página actual
     *   abandonado    no         sí (finished/hoy)     página actual (parcial)
     *   terminado     no         sí (finished/hoy)     todas (= amplitude)
     */
    public function pushBookSettingsToTask(int $taskId, array $libro): void
    {
        $taskModel = new TaskModel();
        if (!$taskModel->find($taskId)) {
            return;
        }

        $status      = $libro['status'] ?? 'quiero_leer';
        $totalPages  = (int) ($libro['total_pages'] ?? 0);
        $currentPage = max(0, (int) ($libro['current_page'] ?? 0));
        if ($totalPages > 0) {
            $currentPage = min($currentPage, $totalPages);
        }

        $data = [
            'title'      => $libro['title'],
            'amplitude'  => $totalPages,
            'is_current' => $status === 'leyendo' ? 1 : 0,
        ];

        // "Hecho" en Journal = libro terminado o dejado.
        if (in_array($status, ['terminado', 'abandonado'], true)) {
            $data['end_time'] = !empty($libro['finished_at']) ? $libro['finished_at'] : date('Y-m-d');
        } else {
            $data['end_time'] = null; // por leer / leyendo / pausado: no cuenta como hecha
        }

        // "Leído" marca todas las páginas; el resto refleja por dónde vas.
        $data['completed'] = ($status === 'terminado' && $totalPages > 0) ? $totalPages : $currentPage;

        $taskModel->update($taskId, $data);
    }

    /**
     * Dirección inversa: tras tocar una task de la categoría Lectura desde
     * Journal (estrella, "hecho", progreso), refleja lo relevante en el libro
     * vinculado. Segura de llamar para cualquier task: si no es de Lectura o
     * no tiene libro, no hace nada. Solo escribe en `books`, nunca vuelve a
     * disparar la sincronización en el otro sentido.
     *
     * $syncPages: solo cuando el cambio viene del formulario de edición
     * completo (Journal::edit), donde el usuario ha puesto amplitude/completed
     * a mano. Desde el toggle de estrella o el "completar" rápido NO se tocan
     * las páginas del libro (evita colar el amplitude genérico 100 de
     * Journal::create en libros sin total real).
     */
    public function pushTaskToBook(int $taskId, bool $syncPages = false): void
    {
        $taskModel = new TaskModel();
        $bookModel = new BookModel();

        $task = $taskModel->find($taskId);
        if (!$task || ($task['category'] ?? null) !== self::CATEGORIA) {
            return;
        }

        $libro = $bookModel->where('task_id', $taskId)->first();
        if (!$libro) {
            return;
        }

        $status     = $libro['status'];
        $totalPages = (int) ($libro['total_pages'] ?? 0);
        $isDone     = !empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00';
        $isCurrent  = !empty($task['is_current']);

        $data = [];

        // 1) El estado "hecho" manda sobre el status del libro.
        if ($isDone && !in_array($status, ['terminado', 'abandonado'], true)) {
            $data['status']      = $status = 'terminado';
            $data['finished_at'] = substr((string) $task['end_time'], 0, 10) ?: date('Y-m-d');
            if ($totalPages > 0) {
                $data['current_page'] = $totalPages; // "leído" => todas las páginas
            }
            if ($isCurrent) {
                $taskModel->update($taskId, ['is_current' => 0]); // terminado => sin estrella
            }
        } elseif (!$isDone && in_array($status, ['terminado', 'abandonado'], true)) {
            $data['status']      = $status = 'leyendo';
            $data['finished_at'] = null;
            if (empty($libro['started_at'])) {
                $data['started_at'] = date('Y-m-d');
            }
            if (!$isCurrent) {
                $taskModel->update($taskId, ['is_current' => 1]); // reabrir = retomar
            }
        }

        // 2) Estrella <-> "leyendo". Solo si el paso 1 no cambió el estado y la
        //    tarea NO está hecha (con "hecho" manda siempre el paso 1).
        if (!isset($data['status']) && !$isDone) {
            if ($isCurrent && $status !== 'leyendo') {
                $data['status'] = $status = 'leyendo';
                if (empty($libro['started_at'])) {
                    $data['started_at'] = date('Y-m-d');
                }
            } elseif (!$isCurrent && $status === 'leyendo') {
                $data['status'] = $status = 'pausado';
            }
        }

        // 3) Páginas (solo desde el formulario de edición completo).
        if ($syncPages) {
            $amplitude = (int) ($task['amplitude'] ?? 0);
            if ($amplitude > 0 && $amplitude !== $totalPages) {
                $data['total_pages'] = $totalPages = $amplitude;
            }
            if (!isset($data['current_page'])) {
                $completed = max(0, (int) ($task['completed'] ?? 0));
                if ($totalPages > 0) {
                    $completed = min($completed, $totalPages);
                }
                if ($completed !== (int) ($libro['current_page'] ?? 0)) {
                    $data['current_page'] = $completed;
                }
            }
        }

        if (!empty($data)) {
            $bookModel->update($libro['id'], $data);
        }
    }
}
