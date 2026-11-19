<?php

namespace App\Models;

use CodeIgniter\Model;

class RodajesSetupModel extends Model
{
    protected $table         = 'rodajes_setups';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['proyecto_id', 'nombre', 'tipo', 'optica', 'soporte', 'apertura', 'velocidad', 'iso', 'fps', 'nd', 'luz', 'notas', 'imagen', 'orden'];
}
