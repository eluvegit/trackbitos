<?php

namespace App\Models;

use App\Services\ReadingJournalSyncService;
use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';

    // Si se crea una task en la categoría "Lectura" (desde Journal o desde
    // cualquier otro sitio), el módulo Lectura se entera y crea su book
    // vinculado mínimo. Ver ReadingJournalSyncService::createBookForTask.
    protected $afterInsert = ['syncReadingBookOnInsert'];

    // Al terminar cualquier tarea (se le pone end_time) se le quita la
    // estrella y sale del foco, sea cual sea su categoría.
    protected $beforeUpdate = ['clearStarWhenDone'];

    protected $allowedFields = [
        'category',
        'title',
        'color',
        'description',
        'start_time',
        'end_time',
        'time_spent',
        'amplitude',
        'completed',
        'note',
        'image',
        'is_current',
        'en_foco',
        'foco_orden'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Devuelve todas las tareas agrupadas por categoría,
     * asegurando que time_spent sea un entero
     */
    public function getAllGroupedByCategory(): array
    {
        $tasks = $this->orderBy('id', 'DESC')->findAll();
        $grouped = [];

        foreach ($tasks as $task) {
            // Convertimos time_spent a entero seguro
            $task['time_spent'] = isset($task['time_spent']) ? (int)$task['time_spent'] : 0;

            $grouped[$task['category']][] = $task;
        }

        return $grouped;
    }

    /**
     * Última vez que se tocó (editó/actualizó) alguna tarea de cada categoría.
     * Usa updated_at de las tareas, no el historial de fechas: refleja mucho
     * mejor la actividad real porque el historial apenas se usa en comparación
     * con editar la tarea directamente.
     *
     * @return array [category => last_updated]
     */
    public function getLastUpdatedPerCategory(): array
    {
        $rows = $this->select('category, MAX(updated_at) as last_updated')
            ->groupBy('category')
            ->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['category']] = $row['last_updated'];
        }

        return $result;
    }

    /**
     * Callback afterInsert: si la task nueva es de categoría "Lectura",
     * delega en el servicio de sincronización para crear su book
     * vinculado. Nunca debe romper la creación de la task en Journal.
     */
    protected function syncReadingBookOnInsert(array $eventData): array
    {
        $data = $eventData['data'] ?? [];

        if (($data['category'] ?? null) === 'Lectura') {
            try {
                (new ReadingJournalSyncService())->createBookForTask((int) $eventData['id'], $data);
            } catch (\Throwable $e) {
                log_message('error', 'ReadingJournalSyncService::createBookForTask failed: ' . $e->getMessage());
            }
        }

        return $eventData;
    }

    /**
     * Callback beforeUpdate: si el update pone end_time a una fecha real
     * (la tarea se marca como terminada), fuerza is_current=0 y en_foco=0
     * aunque el caller no los mande — "terminada" y "con estrella" son
     * mutuamente excluyentes para cualquier categoría, no solo Lectura
     * (que ya tenía esta misma regla, pero solo para sí misma, en
     * ReadingJournalSyncService::pushTaskToBook).
     */
    protected function clearStarWhenDone(array $eventData): array
    {
        $data = $eventData['data'] ?? [];

        if (array_key_exists('end_time', $data)) {
            $endTime = $data['end_time'];
            $isDone = !empty($endTime) && $endTime !== '0000-00-00 00:00:00';

            if ($isDone) {
                $eventData['data']['is_current'] = 0;
                $eventData['data']['en_foco'] = 0;
            }
        }

        return $eventData;
    }
}
