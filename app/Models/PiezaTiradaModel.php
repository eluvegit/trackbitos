<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tirada: una producción grande planificada en placas por días y máquinas
 * (ver App\Controllers\Piezas\TiradasController). Sus tipos de pieza y sus
 * placas van en PiezaTiradaTipoModel y PiezaTiradaPlacaModel.
 */
class PiezaTiradaModel extends Model
{
    protected $table         = 'piezas_tiradas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = 'actualizado_en';

    protected $allowedFields = [
        'nombre', 'notas', 'fecha_inicio', 'plazo_dias', 'objetivo_h', 'objetivo_m',
        'maquinas', 'placas_dia', 'placas_correo', 'dia_confirmacion', 'cambio_resina', 'dias_fuera',
    ];

    /**
     * "A: blanco\nB: gris" → [['nombre' => 'A', 'resina' => 'blanco'], ...].
     * Una máquina por línea; sin dos puntos, la línea entera es el nombre y
     * la resina queda vacía.
     */
    public static function maquinas(string $texto): array
    {
        $maquinas = [];
        foreach (preg_split('/\R/', $texto) as $linea) {
            $linea = trim($linea);
            if ($linea === '') {
                continue;
            }
            [$nombre, $resina] = array_pad(array_map('trim', explode(':', $linea, 2)), 2, '');
            $maquinas[] = ['nombre' => $nombre, 'resina' => mb_strtolower($resina)];
        }

        return $maquinas;
    }

    /** Día de la tirada que es hoy (el de fecha_inicio es el 1). */
    public static function diaDeHoy(array $tirada): int
    {
        if (empty($tirada['fecha_inicio'])) {
            return 1;
        }
        $inicio = new \DateTimeImmutable($tirada['fecha_inicio']);
        $hoy    = new \DateTimeImmutable('today');

        return max(1, (int) $inicio->diff($hoy)->format('%r%a') + 1);
    }
}
