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

    /**
     * En orden de código, natural (E2 antes que E10) en vez de alfabético
     * puro — ver PiezaHuecoModel::deEstuche() para el mismo motivo.
     */
    public function ordenados(): array
    {
        $estuches = $this->findAll();
        usort($estuches, static fn (array $a, array $b) => strnatcasecmp($a['codigo'], $b['codigo']));

        return $estuches;
    }
}
