<?php

namespace App\Services;

use App\Models\SiloPiezaModel;
use App\Models\SiloUbicacionModel;
use App\Models\SiloUnidadBucketModel;
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
    private SiloUnidadBucketModel $unidadBucketModel;
    private SiloVocabularioModel $vocabularioModel;
    private SiloService $silo;

    public function __construct()
    {
        $this->piezaModel        = new SiloPiezaModel();
        $this->ubicacionModel    = new SiloUbicacionModel();
        $this->unidadModel       = new SiloUnidadModel();
        $this->unidadBucketModel = new SiloUnidadBucketModel();
        $this->vocabularioModel  = new SiloVocabularioModel();
        $this->silo              = new SiloService();
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

    /**
     * Reparto de Nivel 2 usando **únicamente las unidades ya dadas de alta**
     * con capacidad conocida (petición 2026-09-05: nada de inventar una
     * capacidad uniforme — cada USB real tiene la suya). Se recorren en el
     * orden en que se dieron de alta (`numero` ASC) y se van llenando con
     * años **consecutivos** mientras quepan; en cuanto uno no cabe, esa
     * unidad se cierra (aunque le sobre sitio) y se pasa a la siguiente —
     * un año **nunca se fragmenta** entre dos unidades. Posibles estados
     * por tramo: `ok` (cupo bien), `excede` (una unidad real ya asignada,
     * pero el año no cabe entero en ella: hace falta una de más capacidad
     * solo para eso) o `sin_unidad` (no quedan más unidades dadas de alta:
     * hace falta registrar más). "sin fecha" entra en el mismo recorrido
     * (ordena primero, como el año 0) sin trato especial. Puro cálculo, no
     * toca la BD — ver aplicarPlanNivel2() para materializarlo de verdad.
     *
     * @return array<int, array{unidad_id: ?int, anios: string[], bytes: int, estado: string}>
     */
    public function calcularPlanNivel2(): array
    {
        $unidades = $this->unidadModel->where('nivel', 2)
            ->where('capacidad_bytes IS NOT NULL')
            ->orderBy('numero', 'ASC')
            ->findAll();

        $filas = $this->piezaModel
            ->select("COALESCE(YEAR(fecha), 0) AS anio, SUM(tamano_bytes) AS bytes")
            ->groupBy('anio')
            ->orderBy('anio', 'ASC')
            ->findAll();

        $planes = [];
        $indice = 0;
        $run    = $this->abrirRunNivel2($unidades, $indice);

        foreach ($filas as $fila) {
            $bytes = (int) $fila['bytes'];
            $clave = ((int) $fila['anio']) === 0 ? 'sin_fecha' : (string) ((int) $fila['anio']);

            while ($run['unidad_id'] !== null && $run['bytes'] + $bytes > $run['capacidad']) {
                if ($run['anios'] !== []) {
                    // Se acabó el sitio de esta unidad con contenido ya
                    // dentro: se cierra normal y se prueba con la siguiente.
                    $planes[] = ['unidad_id' => $run['unidad_id'], 'anios' => $run['anios'], 'bytes' => $run['bytes'], 'estado' => 'ok'];
                    $run = $this->abrirRunNivel2($unidades, ++$indice);

                    continue;
                }

                // Unidad recién abierta (vacía) y el año YA no cabe él
                // solo: se marca excedida y se pasa a la siguiente unidad
                // para lo que venga después — este año queda resuelto.
                $planes[] = ['unidad_id' => $run['unidad_id'], 'anios' => [$clave], 'bytes' => $bytes, 'estado' => 'excede'];
                $run      = $this->abrirRunNivel2($unidades, ++$indice);

                continue 2;
            }

            if ($run['unidad_id'] === null) {
                // No quedan unidades de Nivel 2 registradas: falta dar de
                // alta más para este año y los que vengan después.
                $planes[] = ['unidad_id' => null, 'anios' => [$clave], 'bytes' => $bytes, 'estado' => 'sin_unidad'];

                continue;
            }

            $run['anios'][] = $clave;
            $run['bytes']  += $bytes;
        }

        if ($run['unidad_id'] !== null && $run['anios'] !== []) {
            $planes[] = ['unidad_id' => $run['unidad_id'], 'anios' => $run['anios'], 'bytes' => $run['bytes'], 'estado' => 'ok'];
        }

        return $planes;
    }

    /** @return array{unidad_id: ?int, capacidad: int, anios: string[], bytes: int} */
    private function abrirRunNivel2(array $unidades, int $indice): array
    {
        return isset($unidades[$indice])
            ? ['unidad_id' => (int) $unidades[$indice]['id'], 'capacidad' => (int) $unidades[$indice]['capacidad_bytes'], 'anios' => [], 'bytes' => 0]
            : ['unidad_id' => null, 'capacidad' => 0, 'anios' => [], 'bytes' => 0];
    }

    /**
     * Materializa calcularPlanNivel2() sobre las unidades **ya existentes**
     * — a diferencia de la primera versión, no borra ni crea unidades (eso
     * pisaba cualquier identificación física/ruta de montaje/etiqueta que
     * el usuario les hubiera puesto a mano); solo reconstruye qué buckets
     * tiene cada una y a qué unidad apunta la ubicación de copia 2 de cada
     * pieza. Los años en estado `sin_unidad` se quedan sin ubicación de
     * copia 2 hasta que se dé de alta una unidad donde quepan.
     *
     * @return array<int, array{unidad_id: ?int, anios: string[], bytes: int, estado: string}>
     */
    public function aplicarPlanNivel2(): array
    {
        $plan = $this->calcularPlanNivel2();

        $this->ubicacionModel->where('copia', 2)->delete();
        foreach ($this->unidadModel->where('nivel', 2)->findAll() as $u) {
            $this->unidadBucketModel->where('unidad_id', $u['id'])->delete();
        }

        foreach ($plan as $run) {
            if ($run['unidad_id'] === null) {
                continue; // sin_unidad: nada que asignar todavía.
            }

            foreach ($run['anios'] as $anio) {
                $this->unidadBucketModel->asignarBucket($run['unidad_id'], $anio);

                $piezas = $anio === 'sin_fecha'
                    ? $this->piezaModel->where('fecha', null)->findAll()
                    : $this->piezaModel->where('YEAR(fecha) = ' . (int) $anio)->findAll();

                foreach ($piezas as $pieza) {
                    $this->ubicacionModel->insert([
                        'pieza_id'      => $pieza['id'],
                        'unidad_id'     => $run['unidad_id'],
                        'copia'         => 2,
                        'ruta_relativa' => "{$anio}/{$pieza['nombre_carpeta']}",
                    ]);
                }
            }
        }

        return $plan;
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
