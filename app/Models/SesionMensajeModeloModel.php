<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionMensajeModeloModel extends Model
{
    protected $table         = 'sesion_mensajes_modelo';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'sesion_id',
        'model_release_id',
        'nombre_modelo',
        'mensaje',
    ];

    protected $validationRules = [
        'sesion_id'     => 'required|is_natural_no_zero',
        'nombre_modelo' => 'required|max_length[150]',
        'mensaje'       => 'required',
    ];
}
