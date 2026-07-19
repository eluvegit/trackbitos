<?php

namespace App\Models;

use CodeIgniter\Model;

class HogarTareaModel extends Model
{
    protected $table         = 'hogar_tareas';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['habitacion_id', 'nombre', 'orden', 'frecuencia_dias', 'estado', 'ultima_vez'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'nombre'        => 'required|min_length[2]|max_length[150]',
        'habitacion_id' => 'required|is_natural_no_zero',
    ];

    protected $validationMessages = [
        'nombre' => [
            'required'   => 'El nombre de la tarea es obligatorio.',
            'min_length' => 'Debe tener al menos 2 caracteres.',
            'max_length' => 'No puede superar los 150 caracteres.',
        ],
    ];

    public function porHabitacion(int $habitacionId): array
    {
        return $this->where('habitacion_id', $habitacionId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function siguienteOrden(int $habitacionId): int
    {
        return (int) ($this->where('habitacion_id', $habitacionId)->selectMax('orden')->first()['orden'] ?? 0) + 1;
    }
}
