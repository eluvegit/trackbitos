<?php

namespace App\Models;

use CodeIgniter\Model;

class CarReminderModel extends Model
{
    protected $table      = 'car_reminders';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'title',
        'interval_days',
        'interval_km',
        'notes',
        'created_at'
    ];

    protected $useTimestamps = false;
}
