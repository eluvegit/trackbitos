<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionEquipoModel extends Model
{
    protected $table         = 'sesion_equipo';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sesion_id',
        'item',
        'marcado',
        'orden',
    ];

    protected $validationRules = [
        'sesion_id' => 'required|is_natural_no_zero',
        'item'      => 'required|max_length[150]',
    ];
}
