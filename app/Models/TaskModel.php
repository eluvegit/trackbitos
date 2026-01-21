<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'category', 'title', 'color', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
