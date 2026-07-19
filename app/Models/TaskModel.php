<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';

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
        'is_current'
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
}
