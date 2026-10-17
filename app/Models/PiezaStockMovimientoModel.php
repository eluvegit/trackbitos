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
        'variante_id', 'version_id', 'ubicacion_id', 'origen', 'delta', 'motivo', 'nota',
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

    /**
     * Stock por hueco que cuadra con el total. Cada pieza vive en un único
     * hueco: las bajas (delta negativo, sin hueco) se restan de ese hueco.
     *
     * @return array<int, array<int,int>>  varianteId => (huecoId => stock)
     */
    private function reparto(array $varianteIds = []): array
    {
        $q = $this->select('variante_id, ubicacion_id, SUM(delta) AS total')
            ->where('NOT (delta < 0 AND ubicacion_id IS NULL)', null, false)
            ->groupBy(['variante_id', 'ubicacion_id']);
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', $varianteIds);
        }

        $mapa = [];
        foreach ($q->findAll() as $fila) {
            $mapa[(int) $fila['variante_id']][(int) ($fila['ubicacion_id'] ?? 0)] = (int) $fila['total'];
        }

        $q = $this->select('variante_id, SUM(-delta) AS total')
            ->where('delta <', 0)
            ->where('ubicacion_id', null)
            ->groupBy('variante_id');
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', $varianteIds);
        }

        foreach ($q->findAll() as $fila) {
            $vid   = (int) $fila['variante_id'];
            $hueco = max(array_keys($mapa[$vid] ?? [0 => 0]));
            $mapa[$vid][$hueco] = ($mapa[$vid][$hueco] ?? 0) - (int) $fila['total'];
        }

        foreach ($mapa as $vid => $porHueco) {
            $mapa[$vid] = array_filter($porHueco, static fn (int $n) => $n !== 0);
            if ($mapa[$vid] === []) {
                unset($mapa[$vid]);
            }
        }

        return $mapa;
    }

    /** @return array<int,int>  huecoId (0 = sin asignar) => stock */
    public function stockPorHueco(int $varianteId): array
    {
        return $this->reparto([$varianteId])[$varianteId] ?? [];
    }

    /** @return array<int, array<int,int>>  varianteId => (huecoId (0 = sin asignar) => stock) */
    public function stockPorVarianteYHueco(): array
    {
        return $this->reparto();
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

    /**
     * Pone todas las altas de una variante en un hueco (null = sin asignar).
     * Las bajas no se tocan: no tienen ubicación, se restan del hueco de la
     * pieza en reparto().
     */
    public function colocarVariante(int $varianteId, ?int $huecoId): int
    {
        $q = function () use ($varianteId, $huecoId) {
            $this->where('variante_id', $varianteId)->where('delta >', 0);

            return $huecoId === null
                ? $this->where('ubicacion_id IS NOT NULL', null, false)
                : $this->groupStart()->where('ubicacion_id', null)->orWhere('ubicacion_id !=', $huecoId)->groupEnd();
        };

        $n = $q()->countAllResults();
        if ($n > 0) {
            $q()->set('ubicacion_id', $huecoId)->update();
        }

        return $n;
    }
}
