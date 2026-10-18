<?php

namespace App\Services;

use App\Models\PiezaGuiaModel;
use App\Models\PiezaGuiaOpcionModel;
use App\Models\PiezaGuiaPasoModel;
use App\Models\PiezaGuiaResultadoModel;

/**
 * Guías de elección del TPV de inventario: pasos ordenados con opciones, y
 * cada combinación completa (una opción por paso) lleva a una variante. La
 * combinación se identifica por los ids de sus opciones ordenados
 * ("3-7-12"): no depende del orden de los pasos.
 */
class PiezaGuias
{
    private PiezaGuiaModel $guias;
    private PiezaGuiaPasoModel $pasos;
    private PiezaGuiaOpcionModel $opciones;
    private PiezaGuiaResultadoModel $resultados;

    public function __construct()
    {
        $this->guias      = new PiezaGuiaModel();
        $this->pasos      = new PiezaGuiaPasoModel();
        $this->opciones   = new PiezaGuiaOpcionModel();
        $this->resultados = new PiezaGuiaResultadoModel();
    }

    public function todas(): array
    {
        return $this->guias->orderBy('orden')->orderBy('nombre')->findAll();
    }

    public function activas(): array
    {
        return $this->guias->where('activa', 1)->orderBy('orden')->orderBy('nombre')->findAll();
    }

    /**
     * La guía con sus pasos y opciones, en orden.
     *
     * @return array{guia: array, pasos: list<array>}|null  cada paso lleva 'opciones'
     */
    public function estructura(int $guiaId): ?array
    {
        $guia = $this->guias->find($guiaId);
        if (!$guia) {
            return null;
        }

        $pasos = $this->pasos->where('guia_id', $guiaId)->orderBy('orden')->orderBy('id')->findAll();
        $ids   = array_column($pasos, 'id');

        $porPaso = [];
        if ($ids !== []) {
            foreach ($this->opciones->whereIn('paso_id', $ids)->orderBy('orden')->orderBy('id')->findAll() as $o) {
                $porPaso[(int) $o['paso_id']][] = $o;
            }
        }
        foreach ($pasos as &$p) {
            $p['opciones'] = $porPaso[(int) $p['id']] ?? [];
        }
        unset($p);

        return ['guia' => $guia, 'pasos' => $pasos];
    }

    public static function clave(array $opcionIds): string
    {
        $ids = array_map('intval', $opcionIds);
        sort($ids);

        return implode('-', $ids);
    }

    /** @return array<string,int> clave => varianteId */
    public function resultados(int $guiaId): array
    {
        $mapa = [];
        foreach ($this->resultados->where('guia_id', $guiaId)->findAll() as $r) {
            $mapa[$r['combinacion']] = (int) $r['variante_id'];
        }

        return $mapa;
    }

    /**
     * Todas las combinaciones posibles (producto cartesiano de las opciones
     * de cada paso), para la cuadrícula de asignación.
     *
     * @return list<list<array>> cada combinación = una opción por paso, en orden
     */
    public function combinaciones(array $estructura): array
    {
        $combos = [[]];
        foreach ($estructura['pasos'] as $paso) {
            if ($paso['opciones'] === []) {
                return [];
            }
            $nuevas = [];
            foreach ($combos as $c) {
                foreach ($paso['opciones'] as $o) {
                    $nuevas[] = [...$c, $o];
                }
            }
            $combos = $nuevas;
        }

        return $estructura['pasos'] === [] ? [] : $combos;
    }

    /** @param array<string,int> $asignaciones clave => varianteId: la lista completa de la guía */
    public function reemplazarResultados(int $guiaId, array $asignaciones): void
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $this->resultados->where('guia_id', $guiaId)->delete();
        foreach ($asignaciones as $clave => $varianteId) {
            $this->resultados->insert(['guia_id' => $guiaId, 'combinacion' => $clave, 'variante_id' => (int) $varianteId]);
        }
        $db->transComplete();
    }

    /**
     * Al añadir un paso, las asignaciones que ya había pasan a su opción
     * por defecto en vez de perderse. Devuelve cuántas movió.
     */
    public function anadirOpcionATodas(int $guiaId, int $opcionId): int
    {
        $filas = $this->resultados->where('guia_id', $guiaId)->findAll();
        foreach ($filas as $r) {
            $ids = array_map('intval', explode('-', $r['combinacion']));
            $this->resultados->update($r['id'], ['combinacion' => self::clave([...$ids, $opcionId])]);
        }

        return count($filas);
    }

    /**
     * Borra las asignaciones que ya no encajan (se añadió o quitó un paso u
     * opción). Devuelve cuántas borró.
     */
    public function limpiar(int $guiaId): int
    {
        $validas = [];
        foreach ($this->combinaciones($this->estructura($guiaId)) as $c) {
            $validas[self::clave(array_column($c, 'id'))] = true;
        }

        $n = 0;
        foreach ($this->resultados->where('guia_id', $guiaId)->findAll() as $r) {
            if (!isset($validas[$r['combinacion']])) {
                $this->resultados->delete($r['id']);
                $n++;
            }
        }

        return $n;
    }

    public function cuantasAsignadas(int $guiaId): int
    {
        return $this->resultados->where('guia_id', $guiaId)->countAllResults();
    }
}
