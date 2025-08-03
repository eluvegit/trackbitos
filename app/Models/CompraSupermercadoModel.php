<?php

namespace App\Models;

use CodeIgniter\Model;

class CompraSupermercadoModel extends Model
{
    protected $table            = 'compra_supermercados';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['nombre', 'descripcion', 'created_at', 'updated_at'];
    protected $useTimestamps    = true;

    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[100]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre del supermercado es obligatorio.',
            'min_length' => 'Debe tener al menos 2 caracteres.',
            'max_length' => 'No puede superar los 100 caracteres.'
        ]
    ];
}
