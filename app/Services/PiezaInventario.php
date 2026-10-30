<?php

namespace App\Services;

use App\Models\PiezaPlacaModel;
use App\Models\PiezaPlacaVersionModel;
use App\Models\PiezaSalidaDestinoModel;
use App\Models\PiezaSalidaLineaModel;
use App\Models\PiezaSalidaModel;
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
    public function sincronizarPlaca(int $placaId, string $caja = 'impresas'): array
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
                    'caja'             => $caja,
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

    /** @return array<string, array<int,int>>  caja => (varianteId => unidades) */
    public function stockEnCajas(array $varianteIds = []): array
    {
        return $this->movimientos->stockEnCajas($varianteIds);
    }

    /**
     * Stock guardado (fuera de las cajas) por variante.
     *
     * @param list<int> $varianteIds
     * @return array<int,int>
     */
    public function stockEnHuecos(array $varianteIds = []): array
    {
        $stock = $this->stockPorVariante($varianteIds);
        foreach ($this->stockEnCajas($varianteIds) as $porVariante) {
            foreach ($porVariante as $vid => $n) {
                $stock[$vid] = ($stock[$vid] ?? 0) - $n;
            }
        }

        return $stock;
    }

    /**
     * Pasa n piezas de una caja a su hueco: par -n caja / +n hueco. Si la
     * pieza no tiene hueco, se le asigna $huecoId.
     */
    public function guardarDeCaja(int $varianteId, string $caja, int $cantidad, ?int $huecoId = null): void
    {
        $actual = $this->huecoDeVariante($varianteId);
        if ($actual === null && $huecoId !== null) {
            $this->colocarEnHueco($varianteId, $huecoId);
            $actual = $huecoId;
        }

        $ahora = date('Y-m-d H:i:s');
        $this->movimientos->insertBatch([
            ['variante_id' => $varianteId, 'ubicacion_id' => null, 'caja' => $caja, 'origen' => 'manual', 'delta' => -$cantidad,
                'motivo' => 'guardado', 'nota' => 'A su hueco', 'creado_en' => $ahora],
            ['variante_id' => $varianteId, 'ubicacion_id' => $actual, 'caja' => null, 'origen' => 'manual', 'delta' => $cantidad,
                'motivo' => 'guardado', 'nota' => 'Desde caja ' . $caja, 'creado_en' => $ahora],
        ]);
    }

    /** Lo contrario de guardar: n piezas de su hueco (o sin ubicación) a una caja. */
    public function meterEnCaja(int $varianteId, string $caja, int $cantidad): void
    {
        $ahora = date('Y-m-d H:i:s');
        $this->movimientos->insertBatch([
            ['variante_id' => $varianteId, 'ubicacion_id' => $this->huecoDeVariante($varianteId), 'caja' => null, 'origen' => 'manual',
                'delta' => -$cantidad, 'motivo' => 'cambio_caja', 'nota' => 'A caja ' . $caja, 'creado_en' => $ahora],
            ['variante_id' => $varianteId, 'ubicacion_id' => null, 'caja' => $caja, 'origen' => 'manual',
                'delta' => $cantidad, 'motivo' => 'cambio_caja', 'nota' => 'Desde su hueco', 'creado_en' => $ahora],
        ]);
    }

    /** Pasa n piezas de una caja a la otra: par -n / +n con motivo 'cambio_caja'. */
    public function cambiarDeCaja(int $varianteId, string $de, string $a, int $cantidad): void
    {
        $ahora = date('Y-m-d H:i:s');
        $this->movimientos->insertBatch([
            ['variante_id' => $varianteId, 'caja' => $de, 'origen' => 'manual', 'delta' => -$cantidad,
                'motivo' => 'cambio_caja', 'nota' => 'A caja ' . $a, 'creado_en' => $ahora],
            ['variante_id' => $varianteId, 'caja' => $a, 'origen' => 'manual', 'delta' => $cantidad,
                'motivo' => 'cambio_caja', 'nota' => 'Desde caja ' . $de, 'creado_en' => $ahora],
        ]);
    }

    /**
     * Alguien se lleva n piezas de una caja: albarán sin foto, ya
     * confirmado, con una línea y su movimiento 'salida'. Devuelve su id.
     */
    public function salidaDeCaja(int $varianteId, string $caja, int $cantidad, ?int $destinoId, ?string $nota): int
    {
        $ahora   = date('Y-m-d H:i:s');
        $destino = $destinoId !== null ? (new PiezaSalidaDestinoModel())->find($destinoId) : null;

        $salidaId = (int) (new PiezaSalidaModel())->insert([
            'fecha' => date('Y-m-d'), 'destino_id' => $destino['id'] ?? null, 'estado' => 'confirmada', 'nota' => $nota,
            'confirmada_en' => $ahora, 'creado_en' => $ahora, 'actualizado_en' => $ahora,
        ], true);
        (new PiezaSalidaLineaModel())->insert([
            'salida_id' => $salidaId, 'variante_id' => $varianteId, 'cantidad' => $cantidad, 'origen' => $caja,
        ]);
        $this->movimientos->insert([
            'variante_id' => $varianteId, 'caja' => $caja, 'origen' => 'manual', 'delta' => -$cantidad, 'motivo' => 'salida',
            'nota' => trim('Se la lleva ' . ($destino['nombre'] ?? 'alguien') . '. ' . (string) $nota), 'salida_id' => $salidaId,
            'creado_en' => $ahora,
        ]);

        return $salidaId;
    }

    /** @return array{hueco:int, impresas:int, pedido:int}  dónde hay unidades de una pieza */
    public function dondeHay(int $varianteId): array
    {
        $cajas = $this->stockEnCajas([$varianteId]);

        return [
            'hueco'    => $this->stockEnHuecos([$varianteId])[$varianteId] ?? 0,
            'impresas' => $cajas['impresas'][$varianteId] ?? 0,
            'pedido'   => $cajas['pedido'][$varianteId] ?? 0,
        ];
    }

    /**
     * Confirma un albarán en borrador: un movimiento 'salida' por línea,
     * restando de su origen. Devuelve un error o null.
     */
    public function confirmarSalida(int $salidaId): ?string
    {
        $salidas = new PiezaSalidaModel();
        $salida  = $salidas->find($salidaId);
        if (!$salida || $salida['estado'] !== 'borrador') {
            return 'Ese albarán no está en borrador.';
        }
        $lineas = (new PiezaSalidaLineaModel())->where('salida_id', $salidaId)->findAll();
        if ($lineas === []) {
            return 'El albarán no tiene líneas.';
        }
        foreach ($lineas as $l) {
            if (empty($l['variante_id'])) {
                return 'Hay líneas sin pieza asignada («' . ($l['descripcion'] ?? '') . '»).';
            }
        }

        $destino = !empty($salida['destino_id']) ? (new PiezaSalidaDestinoModel())->find($salida['destino_id']) : null;
        $ahora   = date('Y-m-d H:i:s');
        $filas   = [];
        foreach ($lineas as $l) {
            $vid     = (int) $l['variante_id'];
            $filas[] = [
                'variante_id'  => $vid,
                'ubicacion_id' => $l['origen'] === 'hueco' ? $this->huecoDeVariante($vid) : null,
                'caja'         => $l['origen'] === 'hueco' ? null : $l['origen'],
                'origen'       => 'manual',
                'delta'        => -(int) $l['cantidad'],
                'motivo'       => 'salida',
                'nota'         => 'Albarán #' . $salidaId . ($destino ? ' · ' . $destino['nombre'] : ''),
                'salida_id'    => $salidaId,
                'creado_en'    => $ahora,
            ];
        }
        $this->movimientos->insertBatch($filas);
        $salidas->update($salidaId, ['estado' => 'confirmada', 'confirmada_en' => $ahora, 'actualizado_en' => $ahora]);

        return null;
    }

    /** Vuelve a borrador: borra los movimientos del albarán, las piezas vuelven a su origen. */
    public function reabrirSalida(int $salidaId): void
    {
        $this->movimientos->where('salida_id', $salidaId)->delete();
        (new PiezaSalidaModel())->update($salidaId, ['estado' => 'borrador', 'confirmada_en' => null, 'actualizado_en' => date('Y-m-d H:i:s')]);
    }

    /** Quita del inventario todo lo que aportaba una placa. */
    public function desvincularPlaca(int $placaId): int
    {
        $n = $this->movimientos->where('placa_id', $placaId)->countAllResults(false);
        $this->movimientos->where('placa_id', $placaId)->delete();
        $this->placas->update($placaId, ['inventario_sincronizado_en' => null]);

        return $n;
    }

    /**
     * El hueco de la pieza (piezas_variantes.hueco_predeterminado_id). Es un
     * dato de la pieza, no sale del stock: sigue siendo suyo aunque esté a 0.
     */
    public function huecoDeVariante(int $varianteId): ?int
    {
        $variante = $this->variantes->find($varianteId);

        return isset($variante['hueco_predeterminado_id']) ? (int) $variante['hueco_predeterminado_id'] : null;
    }

    /** @return list<array> variantes (no borradas) cuyo hueco es este */
    public function variantesDeHueco(int $huecoId): array
    {
        return $this->variantes->where('hueco_predeterminado_id', $huecoId)->where('borrado_en', null)->findAll();
    }

    /** @return array<int,true> ids de huecos con alguna pieza (no borrada) */
    public function huecosOcupados(): array
    {
        $filas = $this->variantes->select('hueco_predeterminado_id')->distinct()
            ->where('hueco_predeterminado_id IS NOT NULL')->where('borrado_en', null)->findAll();

        return array_fill_keys(array_map(static fn (array $f) => (int) $f['hueco_predeterminado_id'], $filas), true);
    }

    /**
     * Mete la pieza en un hueco, o la saca (null), con o sin stock. Lleva
     * sus altas al mismo sitio. Devuelve cuántas filas de movimiento tocó.
     */
    public function colocarEnHueco(int $varianteId, ?int $huecoId): int
    {
        $this->variantes->update($varianteId, ['hueco_predeterminado_id' => $huecoId]);

        return $this->movimientos->colocarVariante($varianteId, $huecoId);
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
     * Stock de todas las variantes desglosado por hueco, en una sola
     * consulta (0 = sin asignar).
     *
     * @return array<int, array<int,int>>
     */
    public function stockPorVarianteYHueco(): array
    {
        return $this->movimientos->stockPorVarianteYHueco();
    }

    /** Mueve todas las piezas de un hueco a otro. Devuelve cuántas piezas movió. */
    public function moverHueco(int $origenId, int $destinoId): int
    {
        $n = count($this->variantesDeHueco($origenId));
        $this->variantes->where('hueco_predeterminado_id', $origenId)->set('hueco_predeterminado_id', $destinoId)->update();
        $this->movimientos->moverUbicacion($origenId, $destinoId);

        return $n;
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
        if (!$movimiento || $movimiento['origen'] !== 'manual' || in_array($movimiento['motivo'], ['guardado', 'cambio_caja', 'salida'], true)) {
            return false;
        }

        return (bool) $this->movimientos->delete($movimientoId);
    }
}
