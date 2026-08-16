<?php

namespace App\Database\Seeds;

use App\Models\BookModel;
use App\Models\ReadingSessionModel;
use App\Models\TaskLogModel;
use App\Models\TaskModel;
use CodeIgniter\Database\Seeder;

/**
 * Backfill único: crea un book vinculado (task_id) por cada task existente
 * en la categoría "Lectura" de Journal, y migra su historial de
 * journal_task_logs a reading_sessions. Idempotente: si una task ya tiene
 * book vinculado, se salta.
 *
 * Ejecutar con: php spark db:seed BackfillReadingFromJournalSeeder
 */
class BackfillReadingFromJournalSeeder extends Seeder
{
    public function run()
    {
        $taskModel    = new TaskModel();
        $logModel     = new TaskLogModel();
        $bookModel    = new BookModel();
        $sessionModel = new ReadingSessionModel();

        $tasks = $taskModel->where('category', 'Lectura')->findAll();
        $creados = 0;

        foreach ($tasks as $task) {
            if ($bookModel->where('task_id', $task['id'])->first()) {
                continue;
            }

            $startedAt = $this->validDate($task['start_time'] ?? null);
            $finishedAt = $this->validDate($task['end_time'] ?? null);
            $completed = (int) ($task['completed'] ?? 0);
            $amplitude = (int) ($task['amplitude'] ?? 0);

            if ($finishedAt) {
                $status = 'terminado';
            } elseif ($startedAt || $completed > 0) {
                $status = 'leyendo';
            } else {
                $status = 'quiero_leer';
            }

            $bookId = $bookModel->insert([
                'task_id'      => $task['id'],
                'title'        => $task['title'],
                'total_pages'  => $amplitude > 0 ? $amplitude : null,
                'current_page' => $completed,
                'status'       => $status,
                'started_at'   => $startedAt,
                'finished_at'  => $finishedAt,
            ], true);

            foreach ($logModel->where('task_id', $task['id'])->findAll() as $log) {
                $sessionModel->insert([
                    'book_id'      => $bookId,
                    'session_date' => $log['log_date'],
                    'minutes'      => (int) $log['minutes'] > 0 ? (int) $log['minutes'] : null,
                    'skipped'      => 0,
                ]);
            }

            $creados++;
        }

        echo "Libros creados desde Journal: {$creados}\n";
    }

    private function validDate($value): ?string
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        return date('Y-m-d', strtotime($value));
    }
}
