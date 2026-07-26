<?php namespace App\Models;

use CodeIgniter\Model;

class ComidasPesoModel extends Model
{
    protected $table      = 'comida_pesos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'fecha', 'peso',
        'imc', 'grasa_corporal_pct', 'grasa_visceral', 'masa_muscular_kg',
        'masa_osea_kg', 'metabolismo_basal_kcal', 'edad_metabolica',
        'agua_corporal_pct', 'valoracion_fisica',
    ];

    // timestamps DB (created_at/updated_at) los maneja MySQL
    protected $useTimestamps = false;

    protected $validationRules = [
        'fecha' => 'required|valid_date',
        'peso'  => 'required|decimal'
    ];

    protected $validationMessages = [
        'fecha' => [
            'required'   => 'La fecha es obligatoria.',
            'valid_date' => 'La fecha no es válida.'
        ],
        'peso' => [
            'required' => 'El peso es obligatorio.',
            'decimal'  => 'El peso debe ser un número (usa punto como decimal).'
        ]
    ];
}
