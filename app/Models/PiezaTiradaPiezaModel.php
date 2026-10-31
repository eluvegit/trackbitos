<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pieza que compone una tirada, con sus condiciones: resina, necesarias,
 * margen (%), cuántas caben por placa, prioridad de pintura (más alta, antes)
 * y si espera a la confirmación.
 */
class PiezaTiradaPiezaModel extends Model
{
    protected $table      = 'piezas_tiradas_piezas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = ['tirada_id', 'nombre', 'resina', 'necesarias', 'margen', 'por_placa', 'prioridad', 'espera_confirmacion', 'color', 'orden'];

    public function deTirada(int $tiradaId): array
    {
        return $this->where('tirada_id', $tiradaId)->orderBy('orden')->orderBy('id')->findAll();
    }
}
