<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskLogModel extends Model
{
    protected $table = 'task_logs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'task_id',
        'subtask_id',
        'date',
        'time_spent',
        'progress',
        'note',
        'image'
    ];

    protected $useTimestamps = true;

    public function getAll()
    {
        return $this->select('
                task_logs.*, 
                tasks.title as task_title,
                tasks.category,
                tasks.color,
                subtasks.title as subtask_title
            ')
            ->join('tasks', 'task_logs.task_id = tasks.id')
            ->join('subtasks', 'task_logs.subtask_id = subtasks.id', 'left')
            ->orderBy('task_logs.date', 'DESC')
            ->findAll();
    }

    public function getById(int $id)
    {
        return $this->where('id', $id)->first();
    }
}
 