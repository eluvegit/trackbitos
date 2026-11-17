<?php

namespace App\Models;

use CodeIgniter\Model;

class RodajesPuntoModel extends Model
{
    protected $table         = 'rodajes_puntos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['ubicacion_id', 'nombre', 'x', 'y', 'orden'];
}
