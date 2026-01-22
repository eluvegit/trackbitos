<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskLogModel extends Model
{
    protected $table = 'journal_task_logs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'task_id',
        'log_date',
        'minutes',
    ];

      // 🔴 Desactivar timestamps
    protected $useTimestamps = false;

    /**
     * Obtener todos los logs con información de tarea
     */
    public function getAll()
    {
        return $this->select('
                journal_task_logs.*,
                tasks.title AS task_title,
                tasks.category,
                tasks.color
            ')
            ->join('tasks', 'journal_task_logs.task_id = tasks.id')
            ->orderBy('journal_task_logs.log_date', 'DESC')
            ->findAll();
    }

    /**
     * Obtener log por ID
     */
    public function getById(int $id)
    {
        return $this->where('id', $id)->first();
    }

    /**
     * Obtener logs por tarea
     */
    public function getByTaskId(int $taskId)
    {
        return $this->where('task_id', $taskId)
                    ->orderBy('log_date', 'DESC')
                    ->findAll();
    }
}
