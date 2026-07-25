<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioPlantillaEjerciciosModel extends Model
{
    protected $table            = 'gimnasio_plantilla_ejercicios';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['plantilla_id', 'ejercicio_id', 'orden'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
