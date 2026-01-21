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
        'amplitude',    // <-- nuevo
        'completed',    // <-- nuevo
        'note',
        'image',
        'is_current' // <-- nuevo
    ];


    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAllGroupedByCategory(): array
    {
        $tasks = $this->orderBy('id', 'DESC')->findAll();
        $grouped = [];
        foreach ($tasks as $task) {
            $grouped[$task['category']][] = $task;
        }
        return $grouped;
    }
}
