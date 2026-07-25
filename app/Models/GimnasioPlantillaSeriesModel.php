<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioPlantillaSeriesModel extends Model
{
    protected $table            = 'gimnasio_plantilla_series';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['plantilla_ejercicio_id', 'series', 'repeticiones', 'peso', 'rpe', 'nota', 'orden'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
