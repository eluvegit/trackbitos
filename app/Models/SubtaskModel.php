<?php

namespace App\Models;

use CodeIgniter\Model;

class SubtaskModel extends Model
{
    protected $table = 'subtasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['task_id', 'title', 'color', 'is_done', 'orden'];
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
}
