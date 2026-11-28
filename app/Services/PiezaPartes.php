<?php

namespace App\Services;

use App\Models\PiezaParteModel;
use App\Models\PiezaParteMovimientoModel;

class PiezaPartes
{
    /**
     * Un montaje automático son dos apuntes que van juntos: +n a la pieza y
     * −n de cada parte. Ninguno de los dos se borra suelto: sin el +n, las
     * partes en negativo descuentan otra unidad (se pierde dos veces); sin
     * el −n, autoMontar vuelve a montar y la unidad sale dos veces.
     */
    public const NOTA_MONTAJE       = 'Montada con partes sueltas';
    public const NOTA_MONTAJE_PARTE = 'Montada en ';

    public static function esMontaje(?string $nota): bool
    {
        return $nota === self::NOTA_MONTAJE || str_starts_with((string) $nota, self::NOTA_MONTAJE_PARTE);
    }

    private PiezaParteModel $partes;
    private PiezaParteMovimientoModel $movimientos;

    public function __construct()
    {
        $this->partes      = new PiezaParteModel();
        $this->movimientos = new PiezaParteMovimientoModel();
    }

    public function partesDe(int $varianteId): array
    {
        return $this->partes->where('variante_id', $varianteId)->orderBy('orden')->orderBy('id')->findAll();
    }

    /**
     * Solo devuelve las variantes que tienen partes. El stock lo saca de los
     * movimientos en bruto: el descuento de unidades sin partes se hace aquí.
     *
     * @param list<int> $varianteIds vacío = todas
     */
    public function resumenes(array $varianteIds): array
    {
        [$porVariante, $saldos] = $this->cargar($varianteIds);
        if ($porVariante === []) {
            return [];
        }
        $bruto = (new PiezaInventario())->stockBrutoPorVariante(array_keys($porVariante));

        $res = [];
        foreach ($porVariante as $vid => $partes) {
            $res[$vid] = $this->calcular($partes, $saldos, (int) ($bruto[$vid] ?? 0));
        }

        return $res;
    }

    public function resumenDe(int $varianteId): ?array
    {
        return $this->resumenes([$varianteId])[$varianteId] ?? null;
    }

    /**
     * Unidades que no existen: si falta una de CADA parte, esa unidad no
     * está, aunque los movimientos de stock la sigan contando. Es solo
     * cálculo, no se apunta nada. Solo salen las variantes con algo que
     * descontar.
     *
     * @param array<int,int> $bruto varianteId => stock de los movimientos
     * @return array<int,int> varianteId => unidades a descontar
     */
    public function descontadas(array $bruto): array
    {
        $ids = array_keys(array_filter($bruto, static fn ($n) => $n > 0));
        if ($ids === []) {
            return [];
        }
        [$porVariante, $saldos] = $this->cargar($ids);
        $res = [];
        foreach ($porVariante as $vid => $partes) {
            $n = $this->sinPartes($partes, $saldos, (int) $bruto[$vid]);
            if ($n > 0) {
                $res[$vid] = $n;
            }
        }

        return $res;
    }

    /** @return array{0: array<int, list<array>>, 1: array<int,int>} partes por variante y saldo por parte */
    private function cargar(array $varianteIds): array
    {
        $q = $this->partes->orderBy('orden')->orderBy('id');
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', array_map('intval', $varianteIds));
        }
        $lista = $q->findAll();
        if ($lista === []) {
            return [[], []];
        }
        $porVariante = [];
        foreach ($lista as $p) {
            $porVariante[(int) $p['variante_id']][] = $p;
        }

        return [$porVariante, $this->saldos(array_map(static fn ($p) => (int) $p['id'], $lista))];
    }

    /** Cuántas de $bruto unidades no tienen ninguna de sus partes. */
    private function sinPartes(array $partes, array $saldos, int $bruto): int
    {
        if ($partes === [] || $bruto < 1) {
            return 0;
        }
        $n = $bruto;
        foreach ($partes as $p) {
            $n = min($n, intdiv(max(0, -($saldos[(int) $p['id']] ?? 0)), max(1, (int) $p['por_unidad'])));
        }

        return max(0, $n);
    }

    private function calcular(array $partes, array $saldos, int $bruto): array
    {
        // Las unidades sin ninguna parte no cuentan, y sus faltas tampoco.
        $descontadas = $this->sinPartes($partes, $saldos, $bruto);
        foreach ($partes as $p) {
            $saldos[(int) $p['id']] = ($saldos[(int) $p['id']] ?? 0) + $descontadas * max(1, (int) $p['por_unidad']);
        }
        $unidades = max(0, $bruto - $descontadas);
        $completas = $unidades;
        $montables = PHP_INT_MAX;
        $filas = [];
        $faltan = [];
        $sueltas = [];
        foreach ($partes as $p) {
            $k     = max(1, (int) $p['por_unidad']);
            $saldo = $saldos[(int) $p['id']] ?? 0;
            $hay   = max(0, $unidades * $k + $saldo);
            $falta = min(max(0, -$saldo), $unidades * $k);
            $completas = min($completas, intdiv($hay, $k));
            $filas[] = $p + ['saldo' => $saldo, 'hay' => $hay, 'faltan' => $falta, 'sobran' => max(0, $saldo)];
            if ($falta > 0) {
                $faltan[] = $falta . ' ' . $p['nombre'];
            }
            if ($saldo > 0) {
                $sueltas[] = $saldo . ' ' . $p['nombre'];
            }
            $montables = min($montables, intdiv(max(0, $saldo), $k));
        }

        return [
            'partes'      => $filas,
            'unidades'    => $unidades,
            'descontadas' => $descontadas,
            'completas'   => $completas,
            'incompletas' => $unidades - $completas,
            'imprimir'    => $faltan,
            'sueltas'     => $sueltas,
            'montables'   => $partes === [] ? 0 : $montables,
        ];
    }

    /** Saldo por parte: lo manual siempre; lo de bitácora solo si la placa está dada de alta. */
    private function saldos(array $parteIds): array
    {
        if ($parteIds === []) {
            return [];
        }
        $filas = $this->movimientos->db->table('piezas_partes_movimientos m')
            ->select('m.parte_id, SUM(m.delta) AS total')
            ->join('piezas_placas_versiones pv', 'pv.id = m.placa_version_id', 'left')
            ->join('piezas_placas p', 'p.id = pv.placa_id', 'left')
            ->whereIn('m.parte_id', $parteIds)
            ->groupStart()->where('m.placa_version_id', null)->orWhere('p.inventario_sincronizado_en IS NOT NULL', null, false)->groupEnd()
            ->groupBy('m.parte_id')
            ->get()->getResultArray();

        $mapa = [];
        foreach ($filas as $f) {
            $mapa[(int) $f['parte_id']] = (int) $f['total'];
        }

        return $mapa;
    }

    public function historialDe(int $varianteId): array
    {
        return $this->movimientos->db->table('piezas_partes_movimientos m')
            ->select('m.*, pa.nombre AS parte, pv.placa_id, p.inventario_sincronizado_en')
            ->join('piezas_partes pa', 'pa.id = m.parte_id')
            ->join('piezas_placas_versiones pv', 'pv.id = m.placa_version_id', 'left')
            ->join('piezas_placas p', 'p.id = pv.placa_id', 'left')
            ->where('pa.variante_id', $varianteId)
            ->orderBy('m.creado_en', 'DESC')->orderBy('m.id', 'DESC')
            ->get()->getResultArray();
    }

    public function crear(int $varianteId, string $nombre, int $porUnidad): void
    {
        $orden = (int) ($this->partes->selectMax('orden')->where('variante_id', $varianteId)->first()['orden'] ?? 0);
        $this->partes->insert([
            'variante_id' => $varianteId,
            'nombre'      => mb_substr($nombre, 0, 100),
            'por_unidad'  => max(1, min(20, $porUnidad)),
            'orden'       => $orden + 1,
            'creado_en'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function actualizar(int $parteId, string $nombre, int $porUnidad): void
    {
        $this->partes->update($parteId, ['nombre' => mb_substr($nombre, 0, 100), 'por_unidad' => max(1, min(20, $porUnidad))]);
    }

    public function borrar(int $parteId): void
    {
        $this->partes->delete($parteId);
    }

    public function parte(int $parteId): ?array
    {
        return $this->partes->find($parteId);
    }

    public function movimiento(int $parteId, int $delta, string $motivo, ?string $nota): void
    {
        $this->movimientos->insert([
            'parte_id'  => $parteId,
            'delta'     => $delta,
            'motivo'    => in_array($motivo, ['rota', 'suelta', 'ajuste'], true) ? $motivo : 'ajuste',
            'nota'      => $nota,
            'creado_en' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Monta solas las unidades que salen de las partes sueltas. Devuelve
     * cuántas. Con $caja (montaje al dar de alta una placa) la unidad va a
     * esa caja; sin ella, a su hueco.
     */
    public function autoMontar(array $varianteIds, ?string $caja = null): int
    {
        $varianteIds = array_values(array_unique(array_map('intval', $varianteIds)));
        if ($varianteIds === []) {
            return 0;
        }
        $inventario = new PiezaInventario();
        $total = 0;
        foreach ($this->resumenes($varianteIds) as $vid => $r) {
            $n = (int) $r['montables'];
            if ($n < 1) {
                continue;
            }
            $inventario->movimientoManual($vid, $n, 'alta_manual', self::NOTA_MONTAJE, $caja === null ? $inventario->huecoDeVariante($vid) : null, $caja);
            foreach ($r['partes'] as $p) {
                $this->movimiento((int) $p['id'], -$n * max(1, (int) $p['por_unidad']), 'ajuste', self::NOTA_MONTAJE_PARTE . $n . ' unidad(es)');
            }
            $total += $n;
        }

        return $total;
    }

    public function borrarMovimiento(int $id): ?int
    {
        $m = $this->movimientos->where('placa_version_id', null)->find($id);
        if (!$m || self::esMontaje($m['nota'])) {
            return null;
        }
        $parte = $this->partes->find($m['parte_id']);
        $this->movimientos->delete($id);

        return $parte ? (int) $parte['variante_id'] : null;
    }

    /** @return array<int, array<int,int>> filaId => (parteId => delta) */
    public function deFilas(array $filaIds): array
    {
        if ($filaIds === []) {
            return [];
        }
        $mapa = [];
        foreach ($this->movimientos->whereIn('placa_version_id', $filaIds)->findAll() as $m) {
            $mapa[(int) $m['placa_version_id']][(int) $m['parte_id']] = (int) $m['delta'];
        }

        return $mapa;
    }

    public function guardarFila(int $filaId, int $varianteId, array $deltas): void
    {
        $validas = array_map(static fn ($p) => (int) $p['id'], $this->partesDe($varianteId));
        foreach ($validas as $parteId) {
            if (!array_key_exists($parteId, $deltas) && !array_key_exists((string) $parteId, $deltas)) {
                continue;
            }
            $delta = (int) ($deltas[$parteId] ?? 0);
            $fila  = $this->movimientos->where('parte_id', $parteId)->where('placa_version_id', $filaId)->first();
            if ($delta === 0) {
                if ($fila) {
                    $this->movimientos->delete($fila['id']);
                }
            } elseif ($fila) {
                $this->movimientos->update($fila['id'], ['delta' => $delta]);
            } else {
                $this->movimientos->insert([
                    'parte_id' => $parteId, 'delta' => $delta, 'motivo' => 'placa',
                    'placa_version_id' => $filaId, 'creado_en' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
