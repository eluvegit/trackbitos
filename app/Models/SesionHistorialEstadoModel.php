<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionHistorialEstadoModel extends Model
{
    protected $table         = 'sesion_historial_estados';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'cambiado_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'sesion_id',
        'parte',
        'estado',
    ];

    protected $validationRules = [
        'sesion_id' => 'required|is_natural_no_zero',
        'parte'     => 'required|in_list[foto,video]',
        'estado'    => 'required|in_list[planificacion,edicion,subiendo,completado]',
    ];
}
