<?php

namespace App\Models;

use CodeIgniter\Model;

class RodajesVarianteModel extends Model
{
    protected $table         = 'rodajes_variantes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['escena_id', 'texto', 'prioridad', 'moodboard_item_id', 'toma_estado', 'toma_estado_at', 'toma_estado_foto', 'toma_estado_foto_at', 'orden'];
}
