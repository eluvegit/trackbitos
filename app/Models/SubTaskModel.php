<?php

namespace App\Models;

use CodeIgniter\Model;

class SubtaskModel extends Model
{
    protected $table = 'subtasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['task_id', 'title', 'color', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
 