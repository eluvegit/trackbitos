<?php

namespace App\Models;

use CodeIgniter\Model;

class CarActionModel extends Model
{
    protected $table      = 'car_actions';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'reminder_id',       // antiguo enlace con car_reminders (ya no se usa)
        'recordatorio_id',   // recordatorio (tabla común) que renueva esta acción
        'title',
        'date',
        'kilometers',
        'notes',
        'created_at'
    ];

    protected $useTimestamps = false;
}
