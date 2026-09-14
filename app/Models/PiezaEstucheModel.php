<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Caja física que agrupa huecos (App\Models\PiezaHuecoModel). Por sí solo
 * no aloja piezas — es el contenedor, no la ubicación de guardado.
 */
class PiezaEstucheModel extends Model
{
    protected $table         = 'piezas_estuches';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = '';

    protected $allowedFields = ['codigo', 'zona', 'notas'];

    protected $validationRules = [
        'codigo' => 'required|max_length[20]|is_unique[piezas_estuches.codigo,id,{id}]',
    ];

    public function ordenados(): array
    {
        return $this->orderBy('codigo', 'ASC')->findAll();
    }
}
