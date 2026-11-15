<?php

namespace App\Models;

use CodeIgniter\Model;

class RecordatorioModel extends Model
{
    protected $table         = 'recordatorios';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'titulo', 'categoria', 'icono', 'fecha_evento', 'periodo_meses', 'periodo_dias', 'notas',
        // Lista común con Coche y Lentillas (ver App\Libraries\RecordatorioServicio)
        'persistente', 'avisar', 'intervalo_km', 'ultima_vez', 'elemento',
    ];
    protected $useTimestamps = true;

    protected $validationRules = [
        'titulo'       => 'required|min_length[2]|max_length[150]',
        'fecha_evento' => 'permit_empty|valid_date',   // NULL = sin plazo en días o aún sin hacer
    ];

    protected $validationMessages = [
        'titulo' => [
            'required'   => 'El título es obligatorio.',
            'min_length' => 'Debe tener al menos 2 caracteres.',
            'max_length' => 'No puede superar los 150 caracteres.',
        ],
        'fecha_evento' => [
            'valid_date' => 'Introduce una fecha válida.',
        ],
    ];

}
