<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskLinkModel extends Model
{
    protected $table         = 'journal_task_links';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = '';

    protected $allowedFields = ['task_id', 'subtask_id', 'url', 'titulo', 'descripcion'];

    public function getForTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)->orderBy('id', 'DESC')->findAll();
    }
}
