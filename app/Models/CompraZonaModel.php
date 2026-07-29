<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraZonaModel extends Model
{
    protected $table            = 'compra_zonas';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['supermercado_id', 'nombre', 'orden'];
    protected $useTimestamps    = true;

    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[100]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre de la zona es obligatorio.',
            'min_length' => 'Debe tener al menos 2 caracteres.',
            'max_length' => 'No puede superar los 100 caracteres.',
        ],
    ];
}
