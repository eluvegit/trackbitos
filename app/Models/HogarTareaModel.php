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

    /**
     * Desmarca (estado 1 -> 0) las tareas que estaban hechas pero cuyo plazo
     * ya ha vencido: pasó más tiempo del de su frecuencia desde "ultima_vez".
     * Vuelven a quedar pendientes y a mostrar el aviso de "Toca hacerla".
     * Las tareas sin frecuencia no caducan nunca.
     *
     * @return int Número de tareas desmarcadas.
     */
    public function desmarcarVencidas(?int $habitacionId = null): int
    {
        helper('hogar');

        $query = $this->where('estado', 1)->where('frecuencia_dias IS NOT NULL');
        if ($habitacionId !== null) {
            $query->where('habitacion_id', $habitacionId);
        }

        $desmarcadas = 0;
        foreach ($query->findAll() as $t) {
            if (hogar_esta_atrasada((int) $t['frecuencia_dias'], $t['ultima_vez'])) {
                $this->skipValidation(true)->update($t['id'], ['estado' => 0]);
                $desmarcadas++;
            }
        }

        return $desmarcadas;
    }
}
