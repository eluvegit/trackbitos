<?php

namespace App\Models;

use CodeIgniter\Model;

class SituacionModel extends Model
{
    protected $table         = 'situaciones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sesion_id',
        'nombre',
        'orden',
    ];

    protected $validationRules = [
        'sesion_id' => 'required|is_natural_no_zero',
        'nombre'    => 'required|max_length[150]',
    ];
}
