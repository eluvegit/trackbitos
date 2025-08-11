<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioEntrenamientoEjerciciosModel extends Model
{
    protected $table            = 'gimnasio_entrenamiento_ejercicios';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['entrenamiento_id', 'ejercicio_id', 'orden'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
