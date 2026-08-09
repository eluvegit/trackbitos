<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskFileModel extends Model
{
    protected $table         = 'journal_task_files';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = '';

    protected $allowedFields = ['task_id', 'ruta_archivo', 'nombre_original', 'tamano'];

    public function getForTask(int $taskId): array
    {
        return $this->where('task_id', $taskId)->orderBy('id', 'DESC')->findAll();
    }
}
