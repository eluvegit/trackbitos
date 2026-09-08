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
        $this->asignarACopia($pieza, 2, $bucketAnio, "{$bucketAnio}/{$pieza['nombre_carpeta']}");

        $categoriaTexto = $this->categoriaBucket($pieza);
        $this->asignarACopia($pieza, 3, $categoriaTexto, "{$categoriaTexto}/{$pieza['nombre_carpeta']}");
    }

    /** Slug de la categoría de la pieza = bucket de Copia 3 ("sin_clasificar" si no tiene). */
    private function categoriaBucket(array $pieza): string
    {
        if ($pieza['categoria_id']) {
            $cat = $this->vocabularioModel->find($pieza['categoria_id']);
            if ($cat) {
                return $this->silo->slugify($cat['nombre']);
            }
        }

        return 'sin_clasificar';
    }

    /**
     * Da de alta la ubicación en la copia indicada si no existe ya y hay
     * una unidad de ese nivel con sitio. El reparto **ya no crea unidades**
     * (pisaba etiquetas/rutas puestas a mano y generaba una unidad por cada
     * slug de categoría): si no cabe en ninguna existente, la pieza se queda
     * *pendiente de almacenar* — la tarjeta de resumen de `/silo/unidades`
     * la cuenta y `repartirCopia3()` / `aplicarPlanNivel2()` la recolocan
     * cuando se dé de alta una unidad donde quepa.
     *
     * No re-sincroniza: si la pieza cambia de categoría después, esta pasada
     * no mueve la ubicación de Copia 3 ya creada.
     */
    private function asignarACopia(array $pieza, int $copia, string $bucket, string $rutaRelativa): void
    {
        $existente = $this->ubicacionModel->where('pieza_id', $pieza['id'])->where('copia', $copia)->first();
        if ($existente) {
            return;
        }

        $unidad = $this->unidadDestino($copia, $bucket, (int) ($pieza['tamano_bytes'] ?? 0));
        if ($unidad === null) {
            return; // sin unidad con sitio: queda pendiente hasta que se dé de alta una y se reparta.
        }

        $this->ubicacionModel->insert([
            'pieza_id'      => $pieza['id'],
            'unidad_id'     => $unidad['id'],
            'copia'         => $copia,
            'ruta_relativa' => $rutaRelativa,
        ]);
    }

    /**
     * Recoloca la Copia 3 (por categoría) de las piezas que quedaron
     * pendientes por no haber unidad de Nivel 3 con sitio — a llamar cuando
     * se den de alta más unidades y se pulse "Recalcular reparto". Solo usa
     * unidades de Nivel 3 **ya existentes** cuyo bucket case con la
     * categoría de la pieza; nunca crea ninguna.
     *
     * @return array{colocadas: int, pendientes: int}
     */
    public function repartirCopia3(): array
    {
        $pendientes = $this->piezaModel
            ->where('EXISTS (SELECT 1 FROM silo_ubicaciones uc1 WHERE uc1.pieza_id = silo_piezas.id AND uc1.copia = 1)', null, false)
            ->where('NOT EXISTS (SELECT 1 FROM silo_ubicaciones uc3 WHERE uc3.pieza_id = silo_piezas.id AND uc3.copia = 3)', null, false)
            ->findAll();

        $colocadas = 0;
        foreach ($pendientes as $pieza) {
            $bucket = $this->categoriaBucket($pieza);
            $unidad = $this->unidadDestino(3, $bucket, (int) ($pieza['tamano_bytes'] ?? 0));
            if ($unidad === null) {
                continue;
            }

            $this->ubicacionModel->insert([
                'pieza_id'      => $pieza['id'],
                'unidad_id'     => $unidad['id'],
                'copia'         => 3,
                'ruta_relativa' => "{$bucket}/{$pieza['nombre_carpeta']}",
            ]);
            $colocadas++;
        }

        return ['colocadas' => $colocadas, 'pendientes' => count($pendientes) - $colocadas];
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

    /**
     * Unidad ya destinada a este bucket (año/categoría) con hueco libre
     * para la pieza, o `null` si no hay ninguna — el reparto ya no crea
     * unidades, así que en ese caso la pieza queda pendiente de almacenar.
     */
    private function unidadDestino(int $nivel, string $bucket, int $tamanoPieza): ?array
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

        return null;
    }
}
