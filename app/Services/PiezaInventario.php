<?php

namespace App\Services;

use App\Models\PiezaPlacaModel;
use App\Models\PiezaPlacaVersionModel;
use App\Models\PiezaStockMovimientoModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;

/**
 * Inventario de piezas fisicas producidas (fase 60). El stock se cuenta por
 * variante: una copia fisica es la misma pieza imprima la version que la
 * imprima.
 *
 * Dos clases de movimiento en piezas_stock_movimientos:
 *  - origen='manual': alta/baja/ajuste a mano. Append-only, con su motivo;
 *    nunca se tocan.
 *  - origen='placa': reflejo de una linea de bitacora (cantidad - fallidas).
 *    UNA fila por placa_version_id. sincronizarPlaca() la crea, la reajusta
 *    o la borra segun cambien las cantidades de la placa, sin apilar
 *    movimientos nuevos en el registro.
 */
class PiezaInventario
{
    private PiezaStockMovimientoModel $movimientos;
    private PiezaPlacaModel $placas;
    private PiezaPlacaVersionModel $placaVersiones;
    private PiezaVersionModel $versiones;
    private PiezaVarianteModel $variantes;

    public function __construct()
    {
        $this->movimientos    = new PiezaStockMovimientoModel();
        $this->placas         = new PiezaPlacaModel();
        $this->placaVersiones = new PiezaPlacaVersionModel();
        $this->versiones      = new PiezaVersionModel();
        $this->variantes      = new PiezaVarianteModel();
    }

    public function stockDeVariante(int $varianteId): int
    {
        return $this->movimientos->stockDe($varianteId);
    }

    /**
     * @param list<int> $varianteIds
     * @return array<int,int>
     */
    public function stockPorVariante(array $varianteIds = []): array
    {
        return $this->movimientos->stockPorVariante($varianteIds);
    }

    /**
     * Da de alta / recuadra en existencias todo lo servible de una placa.
     * Re-ejecutable: no anade movimientos, reajusta los que ya hay.
     *
     * @return array{altas:int, ajustes:int, retiradas:int, unidades:int}
     */
    public function sincronizarPlaca(int $placaId): array
    {
        $ahora = date('Y-m-d H:i:s');
        $res = ['altas' => 0, 'ajustes' => 0, 'retiradas' => 0, 'unidades' => 0];

        foreach ($this->placaVersiones->where('placa_id', $placaId)->findAll() as $linea) {
            $servibles = max(0, (int) $linea['cantidad'] - (int) $linea['fallidas']);
            $existente = $this->movimientos->where('placa_version_id', $linea['id'])->first();

            if ($servibles === 0) {
                if ($existente) {
                    $this->movimientos->delete($existente['id']);
                    $res['retiradas']++;
                }
                continue;
            }

            $version = $this->versiones->find($linea['version_id']);
            if (!$version) {
                continue;   // la version ya no existe: nada que contar
            }
            $varianteId = (int) $version['variante_id'];

            if ($existente) {
                if ((int) $existente['delta'] !== $servibles || (int) $existente['variante_id'] !== $varianteId) {
                    $this->movimientos->update($existente['id'], [
                        'variante_id'    => $varianteId,
                        'version_id'     => (int) $version['id'],
                        'delta'          => $servibles,
                        'actualizado_en' => $ahora,
                    ]);
                    $res['ajustes']++;
                }
            } else {
                // Dónde colocarlo, en dos pasos: el hueco por defecto que se
                // haya fijado a mano en la pieza; si no hay ninguno, y todo
                // su stock actual vive en un único hueco, ese mismo (una
                // pieza nueva-nueva, sin stock en ningún sitio, se queda sin
                // asignar — no hay nada de qué inferir).
                $variante = $this->variantes->find($varianteId);
                $huecoId  = $variante['hueco_predeterminado_id'] ?? null;
                if ($huecoId === null) {
                    $huecoId = $this->movimientos->huecoUnicoDeVariante($varianteId);
                }

                $this->movimientos->insert([
                    'variante_id'      => $varianteId,
                    'version_id'       => (int) $version['id'],
                    'ubicacion_id'     => $huecoId,
                    'origen'           => 'placa',
                    'delta'            => $servibles,
                    'motivo'           => 'impresion',
                    'placa_id'         => $placaId,
                    'placa_version_id' => (int) $linea['id'],
                    'creado_en'        => $ahora,
                    'actualizado_en'   => $ahora,
                ]);
                $res['altas']++;
            }

            $res['unidades'] += $servibles;
        }

        $this->placas->update($placaId, ['inventario_sincronizado_en' => $ahora]);

        return $res;
    }

    /** Quita del inventario todo lo que aportaba una placa. */
    public function desvincularPlaca(int $placaId): int
    {
        $n = $this->movimientos->where('placa_id', $placaId)->countAllResults(false);
        $this->movimientos->where('placa_id', $placaId)->delete();
        $this->placas->update($placaId, ['inventario_sincronizado_en' => null]);

        return $n;
    }

    /** Un alta/baja/ajuste a mano. $delta con signo. Devuelve el id insertado. */
    public function movimientoManual(int $varianteId, int $delta, string $motivo, ?string $nota, ?int $huecoId = null): int
    {
        return (int) $this->movimientos->insert([
            'variante_id'  => $varianteId,
            'ubicacion_id' => $huecoId,
            'origen'       => 'manual',
            'delta'        => $delta,
            'motivo'       => $motivo,
            'nota'         => $nota,
            'creado_en'    => date('Y-m-d H:i:s'),
        ], true);
    }

    /**
     * Desglose por hueco del stock de una variante (0 = sin asignar).
     *
     * @return array<int,int>
     */
    public function stockPorHueco(int $varianteId): array
    {
        return $this->movimientos->stockPorHueco($varianteId);
    }

    /**
     * Qué variantes hay en un hueco y cuánto de cada una.
     *
     * @return array<int,int>  varianteId => stock
     */
    public function stockDeHueco(int $huecoId): array
    {
        return $this->movimientos->stockDeHueco($huecoId);
    }

    /**
     * Stock de todas las variantes desglosado por hueco, en una sola
     * consulta (0 = sin asignar).
     *
     * @return array<int, array<int,int>>
     */
    public function stockPorVarianteYHueco(): array
    {
        return $this->movimientos->stockPorVarianteYHueco();
    }

    /** Mueve todo el stock (todas las variantes) de un hueco a otro. Devuelve cuántos movimientos tocó. */
    public function moverStockDeHueco(int $origenId, int $destinoId): int
    {
        return $this->movimientos->moverUbicacion($origenId, $destinoId);
    }

    /**
     * Si todo el stock de una variante vive en un único hueco, ese hueco;
     * si está repartida en varios (o no tiene nada), null.
     */
    public function huecoUnicoDeVariante(int $varianteId): ?int
    {
        return $this->movimientos->huecoUnicoDeVariante($varianteId);
    }

    /**
     * Coloca en un hueco todo lo que de una variante estuviera "sin
     * asignar". Devuelve cuántas filas de movimiento tocó.
     */
    public function asignarSinAsignar(int $varianteId, int $huecoId): int
    {
        return $this->movimientos->asignarSinAsignar($varianteId, $huecoId);
    }

    /**
     * Fija (o quita, con $huecoId null) el hueco por defecto de una
     * variante y, si se fija uno, coloca ahí lo que tuviera "sin asignar"
     * (ver PiezaStockMovimientoModel::asignarSinAsignar()). Devuelve cuántas
     * filas de movimiento tocó esa colocación.
     */
    public function fijarHuecoPredeterminado(int $varianteId, ?int $huecoId): int
    {
        $this->variantes->update($varianteId, ['hueco_predeterminado_id' => $huecoId]);

        return $huecoId !== null ? $this->movimientos->asignarSinAsignar($varianteId, $huecoId) : 0;
    }

    /** Historial completo de una variante, lo mas nuevo primero. */
    public function historialDeVariante(int $varianteId): array
    {
        return $this->movimientos
            ->where('variante_id', $varianteId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    /**
     * Borra un movimiento manual (alta/baja a mano) por error, p. ej. un
     * alta duplicada. Solo admite origen='manual': las de origen='placa'
     * son un reflejo vivo de la bitácora (PiezaInventario::sincronizarPlaca)
     * y borrarlas aquí no las quita de verdad, solo desincroniza — para esas
     * hay que corregir la bitácora o desvincular la placa.
     */
    public function borrarMovimientoManual(int $movimientoId): bool
    {
        $movimiento = $this->movimientos->find($movimientoId);
        if (!$movimiento || $movimiento['origen'] !== 'manual') {
            return false;
        }

        return (bool) $this->movimientos->delete($movimientoId);
    }
}
