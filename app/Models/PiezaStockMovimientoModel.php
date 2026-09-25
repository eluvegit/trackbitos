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

    /** Hueco donde vive la variante (con stock > 0), o null si no está en ninguno. */
    public function huecoDeVariante(int $varianteId): ?int
    {
        foreach ($this->stockPorHueco($varianteId) as $huecoId => $n) {
            if ($huecoId > 0 && $n > 0) {
                return $huecoId;
            }
        }

        return null;
    }

    /** @return array<int,int>  varianteId => stock en ese hueco */
    public function stockDeHueco(int $huecoId): array
    {
        $ids = array_map('intval', array_column(
            $this->select('variante_id')->distinct()->where('ubicacion_id', $huecoId)->findAll(),
            'variante_id'
        ));
        if ($ids === []) {
            return [];
        }

        $mapa = [];
        foreach ($this->reparto($ids) as $vid => $porHueco) {
            if (isset($porHueco[$huecoId])) {
                $mapa[$vid] = $porHueco[$huecoId];
            }
        }

        return $mapa;
    }

    /** @return array<int, array<int,int>>  varianteId => (huecoId (0 = sin asignar) => stock) */
    public function stockPorVarianteYHueco(): array
    {
        return $this->reparto();
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
     * filas de movimiento tocó. Las bajas (delta negativo) también quedan
     * sin hueco pero no son stock esperando destino — se dejan fuera.
     */
    public function asignarSinAsignar(int $varianteId, int $huecoId): int
    {
        $n = $this->where('variante_id', $varianteId)->where('ubicacion_id', null)->where('delta >', 0)->countAllResults(false);
        if ($n > 0) {
            $this->where('variante_id', $varianteId)->where('ubicacion_id', null)->where('delta >', 0)->set('ubicacion_id', $huecoId)->update();
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

    /**
     * Traslada TODO el stock de una variante concreta de un hueco a otro —
     * como moverUbicacion, pero filtrado también por variante, para separar
     * una pieza de un hueco que tiene varias sin tocar el resto.
     */
    public function moverVarianteDeHueco(int $origenId, int $destinoId, int $varianteId): int
    {
        $n = $this->where('ubicacion_id', $origenId)->where('variante_id', $varianteId)->countAllResults(false);
        if ($n > 0) {
            $this->where('ubicacion_id', $origenId)->where('variante_id', $varianteId)->set('ubicacion_id', $destinoId)->update();
        }

        return $n;
    }

    public function quitarVarianteDeHueco(int $huecoId, int $varianteId): int
    {
        $n = $this->where('ubicacion_id', $huecoId)->where('variante_id', $varianteId)->countAllResults(false);
        if ($n > 0) {
            $this->where('ubicacion_id', $huecoId)->where('variante_id', $varianteId)->set('ubicacion_id', null)->update();
        }

        return $n;
    }

    /**
     * Trae TODO el stock de una variante a un hueco, sea cual sea su
     * ubicación actual (otro hueco, o suelta) — el "tirar" complementario a
     * moverVarianteDeHueco() (que "empuja" desde un hueco concreto). Las
     * bajas a mano (delta negativo con ubicacion_id NULL) no se tocan: no
     * representan una ubicación real, ver asignarSinAsignar().
     */
    public function consolidarVarianteEnHueco(int $varianteId, int $huecoId): int
    {
        $n = $this->where('variante_id', $varianteId)
            ->groupStart()
                ->where('ubicacion_id !=', $huecoId)
                ->orWhere('ubicacion_id', null)
            ->groupEnd()
            ->where('delta >', 0)
            ->countAllResults(false);

        if ($n > 0) {
            $this->where('variante_id', $varianteId)
                ->groupStart()
                    ->where('ubicacion_id !=', $huecoId)
                    ->orWhere('ubicacion_id', null)
                ->groupEnd()
                ->where('delta >', 0)
                ->set('ubicacion_id', $huecoId)
                ->update();
        }

        return $n;
    }
}
