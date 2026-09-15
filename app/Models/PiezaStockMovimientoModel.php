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
     * Desglose por hueco del stock de una variante. La clave 0 agrupa lo
     * que no tiene hueco asignado (ubicacion_id NULL).
     *
     * @return array<int,int>  huecoId (0 = sin asignar) => stock
     */
    public function stockPorHueco(int $varianteId): array
    {
        $mapa = [];
        foreach (
            $this->select('ubicacion_id, SUM(delta) AS total')
                ->where('variante_id', $varianteId)
                ->groupBy('ubicacion_id')
                ->findAll() as $fila
        ) {
            $mapa[(int) ($fila['ubicacion_id'] ?? 0)] = (int) $fila['total'];
        }

        return $mapa;
    }

    /**
     * Qué variantes hay en un hueco y cuánto de cada una.
     *
     * @return array<int,int>  varianteId => stock
     */
    public function stockDeHueco(int $huecoId): array
    {
        $mapa = [];
        foreach (
            $this->select('variante_id, SUM(delta) AS total')
                ->where('ubicacion_id', $huecoId)
                ->groupBy('variante_id')
                ->findAll() as $fila
        ) {
            $mapa[(int) $fila['variante_id']] = (int) $fila['total'];
        }

        return $mapa;
    }

    /**
     * Stock de todas las variantes desglosado por hueco, en una sola
     * consulta — para catálogos donde hace falta ver de un vistazo cuánto
     * hay de cada pieza y dónde está, sin una consulta por variante.
     *
     * @return array<int, array<int,int>>  varianteId => (huecoId (0 = sin asignar) => stock)
     */
    public function stockPorVarianteYHueco(): array
    {
        $mapa = [];
        foreach (
            $this->select('variante_id, ubicacion_id, SUM(delta) AS total')
                ->groupBy(['variante_id', 'ubicacion_id'])
                ->findAll() as $fila
        ) {
            $vid = (int) $fila['variante_id'];
            $hid = (int) ($fila['ubicacion_id'] ?? 0);
            $mapa[$vid][$hid] = (int) $fila['total'];
        }

        return $mapa;
    }

    /**
     * Si TODO el stock de una variante está en un único hueco, ese hueco —
     * si no o está repartida en varios, null. Fallback de dónde colocar lo
     * nuevo cuando la variante todavía no tiene hueco por defecto fijado
     * (App\Services\PiezaInventario::sincronizarPlaca()): si ya vive en un
     * sitio concreto, lo nuevo va ahí también; si está repartida o no tiene
     * nada, no hay nada que inferir.
     */
    public function huecoUnicoDeVariante(int $varianteId): ?int
    {
        $conStock = array_filter(
            $this->stockPorHueco($varianteId),
            static fn (int $n, int $huecoId) => $n > 0 && $huecoId > 0,
            ARRAY_FILTER_USE_BOTH
        );

        return count($conStock) === 1 ? array_key_first($conStock) : null;
    }

    /**
     * Coloca en un hueco todo lo que de una variante estuviera "sin
     * asignar" (ubicacion_id NULL) — para cuando se fija el hueco por
     * defecto de una pieza y ya había stock suelto esperando destino
     * (típicamente lo recién impreso de una placa). Devuelve cuántas
     * filas de movimiento tocó.
     */
    public function asignarSinAsignar(int $varianteId, int $huecoId): int
    {
        $n = $this->where('variante_id', $varianteId)->where('ubicacion_id', null)->countAllResults(false);
        if ($n > 0) {
            $this->where('variante_id', $varianteId)->where('ubicacion_id', null)->set('ubicacion_id', $huecoId)->update();
        }

        return $n;
    }

    /**
     * Reasigna todos los movimientos de un hueco a otro — para fusionar dos
     * huecos (mover y luego borrar el que queda vacío) o para corregir que
     * algo se apuntó en el hueco equivocado. No toca las cantidades, solo
     * la ubicación. Devuelve cuántas filas de movimiento tocó.
     */
    public function moverUbicacion(int $origenId, int $destinoId): int
    {
        $n = $this->where('ubicacion_id', $origenId)->countAllResults(false);
        if ($n > 0) {
            $this->where('ubicacion_id', $origenId)->set('ubicacion_id', $destinoId)->update();
        }

        return $n;
    }
}
