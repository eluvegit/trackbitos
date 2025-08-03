<?php

namespace App\Models;

use CodeIgniter\Model;

class CarFaultModel extends Model
{
    protected $table      = 'car_faults';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'date',
        'kilometers',
        'notes',
        'created_at'
    ];

    protected $useTimestamps = false;
}
