<?php

namespace App\Models;

use CodeIgniter\Model;

class GimnasioEjerciciosModel extends Model
{
    protected $table            = 'gimnasio_ejercicios';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['nombre', 'grupo_muscular'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
