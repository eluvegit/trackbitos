<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioSeriesModel extends Model
{
    protected $table            = 'gimnasio_series';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['entrenamiento_ejercicio_id','series','repeticiones','peso','orden','rpe','nota'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
