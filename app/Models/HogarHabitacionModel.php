<?php

namespace App\Models;

use CodeIgniter\Model;

class HogarHabitacionModel extends Model
{
    protected $table         = 'hogar_habitaciones';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['nombre', 'icono', 'orden'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[100]',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre de la habitación es obligatorio.',
            'min_length' => 'Debe tener al menos 2 caracteres.',
            'max_length' => 'No puede superar los 100 caracteres.',
        ],
    ];

    public function siguienteOrden(): int
    {
        return (int) ($this->selectMax('orden')->first()['orden'] ?? 0) + 1;
    }
}
