<?php

namespace App\Models;

use CodeIgniter\Model;

class CarActionModel extends Model
{
    protected $table      = 'car_actions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'reminder_id',
        'title',
        'date',
        'kilometers',
        'notes',
        'created_at'
    ];

    protected $useTimestamps = false;
}
