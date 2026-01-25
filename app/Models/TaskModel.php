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
}
