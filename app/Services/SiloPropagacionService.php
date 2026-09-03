<?php

namespace App\Services;

use App\Models\SiloPiezaModel;
use App\Models\SiloUbicacionModel;
use App\Models\SiloUnidadModel;
use App\Models\SiloVocabularioModel;

/**
 * Deriva automáticamente dónde debería vivir cada pieza de Nivel 1 en
 * Copia 2 (por año) y Copia 3 (por categoría) — plan Silo §2: las copias 2
 * y 3 "se generan y actualizan automáticamente desde la base de datos", no
 * se eligen a mano. Cada ingesta en Nivel 1 dispara su propia propagación
 * (SiloIngestaService la llama al final); `propagarTodo()` es el
 * backfill/recalculo completo (spark `silo:propagar`).
 *
 * Simplificación consciente de esta primera pasada: agrupa Nivel 3 solo
 * por categoría (una carpeta de nivel), no por categoría/persona/tema
 * anidado como describe el diseño completo.
 *
 * "Año abierto/cerrado" (§2) NO es un estado ni un filtro que programar
 * aquí — es solo una forma de razonar sobre cadencia de backup/verificación
 * (un año antiguo, al crecer poco, es más invariante y necesita menos
 * revisión que uno reciente). No condiciona si una pieza se propaga o no.
 */
class SiloPropagacionService
{
    private SiloPiezaModel $piezaModel;
    private SiloUbicacionModel $ubicacionModel;
    private SiloUnidadModel $unidadModel;
    private SiloVocabularioModel $vocabularioModel;
    private SiloService $silo;

    public function __construct()
    {
        $this->piezaModel       = new SiloPiezaModel();
        $this->ubicacionModel   = new SiloUbicacionModel();
        $this->unidadModel      = new SiloUnidadModel();
        $this->vocabularioModel = new SiloVocabularioModel();
        $this->silo             = new SiloService();
    }

    /** Propaga todas las piezas que ya viven en Nivel 1 (backfill/recálculo completo). */
    public function propagarTodo(): int
    {
        $piezas = $this->piezaModel
            ->select('silo_piezas.*')
            ->join('silo_ubicaciones', 'silo_ubicaciones.pieza_id = silo_piezas.id')
            ->join('silo_unidades', 'silo_unidades.id = silo_ubicaciones.unidad_id')
            ->where('silo_unidades.nivel', 1)
            ->groupBy('silo_piezas.id')
            ->findAll();

        foreach ($piezas as $pieza) {
            $this->propagarPieza((int) $pieza['id']);
        }

        return count($piezas);
    }

    public function propagarPieza(int $piezaId): void
    {
        $pieza = $this->piezaModel->find($piezaId);
        if (!$pieza) {
            return;
        }

        $bucketAnio = $pieza['fecha'] ? substr($pieza['fecha'], 0, 4) : 'sin_fecha';
        $this->asignarACopia($pieza, 2, $bucketAnio, "{$bucketAnio}/{$pieza['nombre_carpeta']}", "Año {$bucketAnio}");

        $categoriaTexto = 'sin_clasificar';
        if ($pieza['categoria_id']) {
            $cat = $this->vocabularioModel->find($pieza['categoria_id']);
            if ($cat) {
                $categoriaTexto = $this->silo->slugify($cat['nombre']);
            }
        }
        $this->asignarACopia($pieza, 3, $categoriaTexto, "{$categoriaTexto}/{$pieza['nombre_carpeta']}", "Categoría {$categoriaTexto}");
    }

    /**
     * Da de alta la ubicación en la copia indicada si no existe ya. No
     * re-sincroniza: si la pieza cambia de categoría después, esta pasada
     * no mueve la ubicación de Copia 3 ya creada (recalcular de verdad
     * pertenece a un comando de mantenimiento aparte, no a esta ruta).
     */
    private function asignarACopia(array $pieza, int $copia, string $bucket, string $rutaRelativa, string $etiquetaNueva): void
    {
        $existente = $this->ubicacionModel->where('pieza_id', $pieza['id'])->where('copia', $copia)->first();
        if ($existente) {
            return;
        }

        $unidad = $this->unidadDestino($copia, $bucket, (int) ($pieza['tamano_bytes'] ?? 0), $etiquetaNueva);

        $this->ubicacionModel->insert([
            'pieza_id'      => $pieza['id'],
            'unidad_id'     => $unidad['id'],
            'copia'         => $copia,
            'ruta_relativa' => $rutaRelativa,
        ]);
    }

    /** Reutiliza una unidad ya destinada a este bucket con hueco libre, o crea la siguiente si no cabe/no existe. */
    private function unidadDestino(int $nivel, string $bucket, int $tamanoPieza, string $etiquetaNueva): array
    {
        foreach ($this->unidadModel->buscarPorAgrupador($nivel, $bucket) as $u) {
            if ($u['capacidad_bytes'] === null) {
                return $u; // sin límite conocido: todo el cubo cabe aquí.
            }

            $usado = $this->ubicacionModel->sumaTamanoPorUnidad($u['id']);
            if ($usado + $tamanoPieza <= (int) $u['capacidad_bytes']) {
                return $u;
            }
        }

        $numeroDelBucket = count($this->unidadModel->buscarPorAgrupador($nivel, $bucket)) + 1;
        $etiqueta = $numeroDelBucket > 1 ? "{$etiquetaNueva} ({$numeroDelBucket})" : $etiquetaNueva;

        return $this->silo->crearUnidad($nivel, $etiqueta, $bucket);
    }
}
