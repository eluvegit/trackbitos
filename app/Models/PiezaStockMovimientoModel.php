<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Movimientos de existencias de piezas fisicas, contados por variante. El
 * stock de una variante es la suma de sus deltas. Ver App\Services\PiezaInventario
 * para las dos clases de movimiento (manual vs. reflejo de placa).
 */
class PiezaStockMovimientoModel extends Model
{
    protected $table         = 'piezas_stock_movimientos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'variante_id', 'version_id', 'origen', 'delta', 'motivo', 'nota',
        'placa_id', 'placa_version_id', 'creado_en', 'actualizado_en',
    ];

    /** Stock actual de una variante: la suma de todos sus movimientos. */
    public function stockDe(int $varianteId): int
    {
        $fila = $this->selectSum('delta')->where('variante_id', $varianteId)->get()->getRowArray();

        return (int) ($fila['delta'] ?? 0);
    }

    /**
     * Stock por variante en una sola consulta.
     *
     * @param list<int> $varianteIds  vacio = todas las que tengan movimientos
     * @return array<int,int>  varianteId => stock
     */
    public function stockPorVariante(array $varianteIds = []): array
    {
        $q = $this->select('variante_id, SUM(delta) AS total')->groupBy('variante_id');
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', $varianteIds);
        }

        $mapa = [];
        foreach ($q->findAll() as $fila) {
            $mapa[(int) $fila['variante_id']] = (int) $fila['total'];
        }

        return $mapa;
    }
}
