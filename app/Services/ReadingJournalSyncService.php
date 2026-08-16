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
     * Tras editar los ajustes de un libro (título, páginas totales,
     * estado...) desde Reading, refleja lo relevante en la task vinculada.
     */
    public function pushBookSettingsToTask(int $taskId, array $libro): void
    {
        $taskModel = new TaskModel();
        if (!$taskModel->find($taskId)) {
            return;
        }

        $data = [
            'title'     => $libro['title'],
            'amplitude' => $libro['total_pages'] ?? 0,
        ];

        if ($libro['status'] === 'terminado' && !empty($libro['finished_at'])) {
            $data['end_time'] = $libro['finished_at'];
        } elseif ($libro['status'] !== 'terminado') {
            $data['end_time'] = null; // reabierto: ya no cuenta como hecha en Journal
        }

        $taskModel->update($taskId, $data);
    }
}
