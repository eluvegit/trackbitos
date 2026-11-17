<?php

namespace App\Models;

use CodeIgniter\Model;

class RodajesUbicacionModel extends Model
{
    protected $table         = 'rodajes_ubicaciones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['proyecto_id', 'nombre', 'notas', 'imagen', 'orden'];
}
