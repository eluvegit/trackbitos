<?php

namespace App\Services;

use App\Models\PiezaParteModel;
use App\Models\PiezaParteMovimientoModel;

class PiezaPartes
{
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
     * Solo devuelve las variantes que tienen partes.
     *
     * @param list<int>       $varianteIds vacío = todas
     * @param array<int,int>  $stock       varianteId => unidades
     */
    public function resumenes(array $varianteIds, array $stock): array
    {
        $q = $this->partes->orderBy('orden')->orderBy('id');
        if ($varianteIds !== []) {
            $q->whereIn('variante_id', $varianteIds);
        }
        $lista = $q->findAll();
        if ($lista === []) {
            return [];
        }

        $saldos = $this->saldos(array_map(static fn ($p) => (int) $p['id'], $lista));
        $porVariante = [];
        foreach ($lista as $p) {
            $porVariante[(int) $p['variante_id']][] = $p;
        }

        $res = [];
        foreach ($porVariante as $vid => $partes) {
            $res[$vid] = $this->calcular($partes, $saldos, max(0, (int) ($stock[$vid] ?? 0)));
        }

        return $res;
    }

    public function resumenDe(int $varianteId, int $stock): ?array
    {
        return $this->resumenes([$varianteId], [$varianteId => $stock])[$varianteId] ?? null;
    }

    private function calcular(array $partes, array $saldos, int $unidades): array
    {
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

    /** Monta solas las unidades que salen de las partes sueltas. Devuelve cuántas. */
    public function autoMontar(array $varianteIds): int
    {
        $varianteIds = array_values(array_unique(array_map('intval', $varianteIds)));
        if ($varianteIds === []) {
            return 0;
        }
        $inventario = new PiezaInventario();
        $stock = $inventario->stockPorVariante($varianteIds);
        $total = 0;
        foreach ($this->resumenes($varianteIds, $stock) as $vid => $r) {
            $n = (int) $r['montables'];
            if ($n < 1) {
                continue;
            }
            $inventario->movimientoManual($vid, $n, 'alta_manual', 'Montada con partes sueltas', $inventario->huecoDeVariante($vid), null);
            foreach ($r['partes'] as $p) {
                $this->movimiento((int) $p['id'], -$n * max(1, (int) $p['por_unidad']), 'ajuste', 'Montada en ' . $n . ' unidad(es)');
            }
            $total += $n;
        }

        return $total;
    }

    public function borrarMovimiento(int $id): ?int
    {
        $m = $this->movimientos->where('placa_version_id', null)->find($id);
        if (!$m) {
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
