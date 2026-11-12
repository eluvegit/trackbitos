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
        'variante_id', 'version_id', 'ubicacion_id', 'caja', 'origen', 'delta', 'motivo', 'nota',
        'placa_id', 'placa_version_id', 'salida_id', 'creado_en', 'actualizado_en',
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

    /**
     * @param list<int> $varianteIds
     * @return array<int, array{total:int, hueco:int, impresas:int, pedido:int}>
     */
    public function desglose(array $varianteIds = []): array
    {
        $q = $this->select('variante_id, caja, SUM(delta) AS total')->groupBy(['variante_id', 'caja']);
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', $varianteIds);
        }

        $mapa = [];
        foreach ($q->findAll() as $fila) {
            $vid = (int) $fila['variante_id'];
            $mapa[$vid] ??= ['total' => 0, 'hueco' => 0, 'impresas' => 0, 'pedido' => 0];
            $mapa[$vid][$fila['caja'] ?? 'hueco'] = (int) $fila['total'];
            $mapa[$vid]['total'] += (int) $fila['total'];
        }

        return $mapa;
    }

    /**
     * @param list<int> $varianteIds
     * @return array<string, array<int,int>>  caja => (varianteId => unidades), sin ceros
     */
    public function stockEnCajas(array $varianteIds = []): array
    {
        $q = $this->select('caja, variante_id, SUM(delta) AS total')
            ->where('caja IS NOT NULL', null, false)
            ->groupBy(['caja', 'variante_id'])
            ->having('total !=', 0);
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', $varianteIds);
        }

        $mapa = [];
        foreach ($q->findAll() as $fila) {
            $mapa[$fila['caja']][(int) $fila['variante_id']] = (int) $fila['total'];
        }

        return $mapa;
    }

    /**
     * Reasigna todos los movimientos de un hueco a otro — para fusionar dos
     * huecos (mover y luego borrar el que queda vacío). No toca las
     * cantidades, solo la ubicación. Devuelve cuántas filas tocó.
     */
    public function moverUbicacion(int $origenId, int $destinoId): int
    {
        $n = $this->where('ubicacion_id', $origenId)->countAllResults(false);
        if ($n > 0) {
            $this->where('ubicacion_id', $origenId)->set('ubicacion_id', $destinoId)->update();
        }

        return $n;
    }

    /** Lleva al hueco (null = sin hueco) todo lo de la variante que está fuera de las cajas, salvo las bajas sin hueco. */
    public function colocarVariante(int $varianteId, ?int $huecoId): int
    {
        $q = function () use ($varianteId, $huecoId) {
            return $this->where('variante_id', $varianteId)->where('caja', null)
                ->groupStart()->where('delta >', 0)->orWhere('ubicacion_id IS NOT NULL', null, false)->groupEnd()
                ->where('NOT (ubicacion_id <=> ' . ($huecoId === null ? 'NULL' : (int) $huecoId) . ')', null, false);
        };

        $n = $q()->countAllResults();
        if ($n > 0) {
            $q()->set('ubicacion_id', $huecoId)->update();
        }

        return $n;
    }
}
