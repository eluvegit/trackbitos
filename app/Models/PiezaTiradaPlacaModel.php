<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Placa de una tirada, en su orden de cola. Qué día y en qué máquina sale
 * no se guarda: lo calcula la vista simulando el plan con los ajustes de la
 * tirada. Lo que lleva va en sus líneas (PiezaTiradaLineaModel).
 */
class PiezaTiradaPlacaModel extends Model
{
    protected $table      = 'piezas_tiradas_placas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['tirada_id', 'codigo', 'motivo', 'orden', 'impresa_en'];

    public function deTirada(int $tiradaId): array
    {
        return $this->where('tirada_id', $tiradaId)->orderBy('orden')->orderBy('id')->findAll();
    }
}
