<?php

namespace App\Models;

use CodeIgniter\Model;

class SubtaskModel extends Model
{
    protected $table = 'subtasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['task_id', 'title', 'color', 'is_done', 'orden', 'time_spent'];
    protected $useTimestamps = true;

    public function getForTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function siguienteOrden(int $taskId): int
    {
        $max = $this->where('task_id', $taskId)->selectMax('orden')->first();
        return ((int) ($max['orden'] ?? 0)) + 1;
    }

    /**
     * Trae las subtareas de varias tareas de golpe, agrupadas por task_id
     * (para no hacer una consulta por tarea al listar el journal entero).
     */
    public function getGroupedByTaskIds(array $taskIds): array
    {
        if (empty($taskIds)) {
            return [];
        }

        $rows = $this->whereIn('task_id', $taskIds)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['task_id']][] = $row;
        }

        return $grouped;
    }
}
