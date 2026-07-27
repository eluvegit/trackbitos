<?php

namespace App\Models;

use CodeIgniter\Model;

class ModelReleaseModel extends Model
{
    protected $table         = 'model_releases';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sesion_id',
        'nombre_modelo',
        'ruta_archivo',
        'fecha',
    ];

    protected $validationRules = [
        'sesion_id'     => 'required|is_natural_no_zero',
        'nombre_modelo' => 'required|max_length[150]',
        'ruta_archivo'  => 'required|max_length[255]',
    ];
}
