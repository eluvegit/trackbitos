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
        // El placeholder {id} de la regla de arriba exige que 'id' tenga
        // también su propia regla aquí — si no, CI4 lanza LogicException
        // ("No validation rules for the placeholder") en vez de solo
        // ignorar el placeholder cuando no llega. Solo hace falta al
        // editar (update() manda 'id' en los datos para poder excluir la
        // propia fila); al crear no llega y no se aplica.
        'id' => 'permit_empty|is_natural_no_zero',
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
