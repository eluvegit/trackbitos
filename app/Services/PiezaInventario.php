<?php

namespace App\Services;

use App\Models\PiezaPlacaModel;
use App\Models\PiezaPlacaVersionModel;
use App\Models\PiezaStockMovimientoModel;
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

    public function __construct()
    {
        $this->movimientos    = new PiezaStockMovimientoModel();
        $this->placas         = new PiezaPlacaModel();
        $this->placaVersiones = new PiezaPlacaVersionModel();
        $this->versiones      = new PiezaVersionModel();
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
                $this->movimientos->insert([
                    'variante_id'      => $varianteId,
                    'version_id'       => (int) $version['id'],
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
    public function movimientoManual(int $varianteId, int $delta, string $motivo, ?string $nota): int
    {
        return (int) $this->movimientos->insert([
            'variante_id' => $varianteId,
            'origen'      => 'manual',
            'delta'       => $delta,
            'motivo'      => $motivo,
            'nota'        => $nota,
            'creado_en'   => date('Y-m-d H:i:s'),
        ], true);
    }

    /** Historial completo de una variante, lo mas nuevo primero. */
    public function historialDeVariante(int $varianteId): array
    {
        return $this->movimientos
            ->where('variante_id', $varianteId)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
