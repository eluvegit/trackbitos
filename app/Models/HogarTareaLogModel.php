<?php

namespace App\Models;

use CodeIgniter\Model;

class HogarTareaLogModel extends Model
{
    protected $table         = 'hogar_tareas_logs';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['tarea_id', 'completada_at'];
    protected $useTimestamps = false;
}
