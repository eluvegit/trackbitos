<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Cada subida individual dentro de una sesión (histórico). La sesión
 * (PiezaSesionModel) solo guarda el fichero de la última subida — este
 * modelo guarda las de en medio, que antes se perdían al pisarse.
 */
class PiezaSubidaModel extends Model
{
    protected $table         = 'piezas_subidas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sesion_id', 'numero', 'ruta_blend', 'hash_blend',
        'tamano_bytes', 'hash_padre', 'log', 'subida_en', 'purgada',
    ];

    public function siguienteNumero(int $sesionId): int
    {
        $fila = $this->where('sesion_id', $sesionId)->selectMax('numero')->first();

        return ((int) ($fila['numero'] ?? 0)) + 1;
    }

    public function deSesion(int $sesionId): array
    {
        return $this->where('sesion_id', $sesionId)->orderBy('numero', 'ASC')->findAll();
    }
}
