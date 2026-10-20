<?php

namespace App\Services;

use App\Models\SiloPiezaModel;
use App\Models\SiloTareaModel;
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
 * anidado como describe el diseño completo. Una unidad de Nivel 3 con
 * categoría puesta a mano solo recibe esa; las que no tienen categoría
 * reciben varias categorías enteras del reparto (calcularPlanNivel3()).
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
    private SiloTareaModel $tareaModel;
    private SiloService $silo;

    public function __construct()
    {
        $this->piezaModel        = new SiloPiezaModel();
        $this->ubicacionModel    = new SiloUbicacionModel();
        $this->unidadModel       = new SiloUnidadModel();
        $this->unidadBucketModel = new SiloUnidadBucketModel();
        $this->vocabularioModel  = new SiloVocabularioModel();
        $this->tareaModel        = new SiloTareaModel();
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
        $this->asignarACopia($pieza, 2, $bucketAnio, $this->rutaEnCopia($bucketAnio, $pieza));

        $categoriaTexto = $this->categoriaBucket($pieza);
        $this->asignarACopia($pieza, 3, $categoriaTexto, $this->rutaEnCopia($categoriaTexto, $pieza));
    }

    /** `{cubo}/{fecha … [id]}` — ver SiloService::nombreEnCopia(). */
    private function rutaEnCopia(string $bucket, array $pieza): string
    {
        return $bucket . '/' . $this->silo->nombreEnCopia((string) $pieza['nombre_carpeta']);
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
     * Si la copia YA existe no la toca: la ubicación refleja lo que hay en
     * disco. Si ya no casa con lo esperado (carpeta renombrada en el
     * Maestro, fecha o categoría corregidas) deja marcada la corrección —
     * ver sincronizarCopia().
     */
    private function asignarACopia(array $pieza, int $copia, string $bucket, string $rutaRelativa): void
    {
        $existente = $this->ubicacionModel->where('pieza_id', $pieza['id'])->where('copia', $copia)->first();
        if ($existente) {
            $this->sincronizarCopia($pieza, $existente, $bucket, $rutaRelativa);
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
     * Copia 2/3 ya creada cuya ruta no es la que tocaría ahora: se encola
     * (silo_tareas, sin aprobar — mover cosas en disco es sensible) una
     * corrección para hacerla en disco y confirmarla desde la ficha:
     *   · `renombrar_copia` — sigue en su cubo (mismo año/categoría), solo
     *     cambia el nombre de la carpeta; se queda en la misma unidad.
     *   · `mover_copia` — cambió de cubo (p. ej. de `sin_fecha/` a `2006/`):
     *     va a la unidad de ese cubo con sitio (`unidad_destino_id` null si
     *     no hay ninguna todavía).
     * Una sola corrección pendiente por ubicación: si se vuelve a renombrar
     * antes de hacerla, se reescribe; si la ruta vuelve a casar, se cancela.
     */
    private function sincronizarCopia(array $pieza, array $ubicacion, string $bucket, string $rutaEsperada): void
    {
        $pendiente = $this->tareaModel->reubicacionPendienteDeUbicacion((int) $ubicacion['id']);

        if ($ubicacion['ruta_relativa'] === $rutaEsperada) {
            if ($pendiente) {
                $this->tareaModel->update($pendiente['id'], ['estado' => 'cancelada']);
            }
            return;
        }

        $bucketActual = strstr((string) $ubicacion['ruta_relativa'], '/', true);

        // Aún no está físicamente en ningún USB (o hay que volver a
        // copiarla): no hay nada que renombrar en disco, la ubicación se
        // corrige directamente y el próximo `silo --copiar` ya la deja
        // con el nombre bueno.
        if ($ubicacion['copiado_en'] === null) {
            if ($pendiente) {
                $this->tareaModel->update($pendiente['id'], ['estado' => 'cancelada']);
            }
            if ($bucketActual === $bucket) {
                $this->ubicacionModel->update($ubicacion['id'], ['ruta_relativa' => $rutaEsperada]);

                return;
            }
            $destino = $this->unidadDestino((int) $ubicacion['copia'], $bucket, (int) ($pieza['tamano_bytes'] ?? 0));
            $destino
                ? $this->ubicacionModel->update($ubicacion['id'], ['unidad_id' => $destino['id'], 'ruta_relativa' => $rutaEsperada])
                : $this->ubicacionModel->delete($ubicacion['id']); // sin sitio en su cubo nuevo: pendiente de almacenar

            return;
        }

        if ($bucketActual === $bucket) {
            $tipo      = 'renombrar_copia';
            $destinoId = (int) $ubicacion['unidad_id'];
        } else {
            $tipo      = 'mover_copia';
            $destino   = $this->unidadDestino((int) $ubicacion['copia'], $bucket, (int) ($pieza['tamano_bytes'] ?? 0));
            $destinoId = $destino ? (int) $destino['id'] : null;
        }

        // Orden de claves fijo: SiloTareaModel busca por el prefijo del JSON.
        $payload = json_encode([
            'ubicacion_id'      => (int) $ubicacion['id'],
            'pieza_id'          => (int) $pieza['id'],
            'copia'             => (int) $ubicacion['copia'],
            'desde'             => $ubicacion['ruta_relativa'],
            'hasta'             => $rutaEsperada,
            'unidad_destino_id' => $destinoId,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($pendiente) {
            if ($pendiente['tipo'] !== $tipo || $pendiente['payload'] !== $payload) {
                $this->tareaModel->update($pendiente['id'], ['tipo' => $tipo, 'payload' => $payload]);
            }
            return;
        }

        $this->tareaModel->insert([
            'unidad_id' => (int) $ubicacion['unidad_id'],
            'tipo'      => $tipo,
            'payload'   => $payload,
            'estado'    => 'pendiente',
            'aprobada'  => 0,
        ]);
    }

    /**
     * Recoloca la Copia 3 (por categoría) — a llamar cuando se den de alta
     * más unidades y se pulse "Recalcular reparto". Primero reparte las
     * categorías entre las unidades automáticas (aplicarPlanNivel3()) y
     * después coloca lo pendiente en las unidades de Nivel 3 **ya
     * existentes** cuyo bucket case con la categoría de la pieza; nunca crea
     * ninguna.
     *
     * @return array{colocadas: int, pendientes: int}
     */
    public function repartirCopia3(): array
    {
        $colocadasPlan = $this->aplicarPlanNivel3();

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
                'ruta_relativa' => $this->rutaEnCopia($bucket, $pieza),
            ]);
            $colocadas++;
        }

        return ['colocadas' => $colocadasPlan + $colocadas, 'pendientes' => count($pendientes) - $colocadas];
    }

    /** Por encima de esto la búsqueda óptima de Nivel 3 (3^n estados) se dispara: se reparte de mayor a menor. */
    private const MAX_CATEGORIAS_OPTIMO = 12;

    /**
     * Unidades de Nivel 3 **automáticas**: sin categoría puesta a mano y con
     * capacidad conocida. El reparto les asigna categorías enteras
     * (calcularPlanNivel3()); las que tienen categoría puesta a mano se
     * respetan y solo reciben esa.
     */
    private function unidadesNivel3Auto(): array
    {
        return $this->unidadModel->where('nivel', 3)
            ->groupStart()->where('agrupador', null)->orWhere('agrupador', '')->groupEnd()
            ->where('capacidad_bytes IS NOT NULL')
            ->orderBy('numero', 'ASC')
            ->findAll();
    }

    /**
     * Piezas que viven en Nivel 1 agrupadas por su bucket de Copia 3,
     * quitando las categorías que ya tienen una unidad puesta a mano.
     *
     * @return array<string, array[]> bucket => piezas
     */
    private function piezasNivel1PorCategoriaAuto(): array
    {
        $manuales = [];
        foreach ($this->unidadModel->where('nivel', 3)->where('agrupador IS NOT NULL')->where("agrupador <> ''")->findAll() as $u) {
            $manuales[$u['agrupador']] = true;
        }

        $piezas = $this->piezaModel
            ->where('EXISTS (SELECT 1 FROM silo_ubicaciones uc1 WHERE uc1.pieza_id = silo_piezas.id AND uc1.copia = 1)', null, false)
            ->findAll();

        $porCategoria = [];
        foreach ($piezas as $pieza) {
            $bucket = $this->categoriaBucket($pieza);
            if (!isset($manuales[$bucket])) {
                $porCategoria[$bucket][] = $pieza;
            }
        }

        return $porCategoria;
    }

    /**
     * Reparto de Nivel 3 entre las unidades automáticas, como el de Nivel 2
     * pero sin orden: cada unidad recibe **varias categorías enteras** que
     * quepan en su capacidad, y una categoría **nunca se parte** entre dos
     * unidades. Búsqueda exacta (programación dinámica sobre unidad ×
     * categorías ya colocadas), mismos criterios por prioridad:
     *   1. Que quede guardado el máximo de bytes (lo mínimo pendiente).
     *   2. Que haya que recopiar lo mínimo: una categoría ya copiada en un
     *      USB prefiere seguir en ese USB.
     *   3. Que sobren los mínimos huecos (capacidad total usada mínima).
     * Con más de MAX_CATEGORIAS_OPTIMO categorías se reparte de mayor a
     * menor en la primera unidad donde quepa.
     *
     * Estados como en Nivel 2: `ok`, `excede` (la categoría sola no cabe en
     * ninguna unidad automática) o `sin_unidad`. Puro cálculo, no toca la BD.
     *
     * @return array<int, array{unidad_id: ?int, categorias: string[], bytes: int, estado: string}>
     */
    public function calcularPlanNivel3(): array
    {
        $unidades = $this->unidadesNivel3Auto();
        $porCat   = $this->piezasNivel1PorCategoriaAuto();

        $bytesCat = [];
        $catDe    = [];
        $tamDe    = [];
        foreach ($porCat as $bucket => $piezas) {
            $bytesCat[$bucket] = 0;
            foreach ($piezas as $p) {
                $bytesCat[$bucket]    += (int) ($p['tamano_bytes'] ?? 0);
                $catDe[(int) $p['id']] = $bucket;
                $tamDe[(int) $p['id']] = (int) ($p['tamano_bytes'] ?? 0);
            }
        }
        arsort($bytesCat);

        $claves = array_map('strval', array_keys($bytesCat));
        $bytes  = array_values($bytesCat);
        $n      = count($unidades);
        $c      = count($claves);
        $cap    = array_map(static fn ($u) => (int) $u['capacidad_bytes'], $unidades);
        $maxCap = $cap === [] ? 0 : max($cap);

        // recopia[u][k]: bytes de la categoría k ya copiados en OTRO USB que
        // habría que volver a copiar si la categoría k va a la unidad u.
        $indiceCat = array_flip($claves);
        $copiados  = [];
        $filas     = $this->ubicacionModel->select('pieza_id, unidad_id')
            ->where('copia', 3)->where('copiado_en IS NOT NULL')->findAll();
        foreach ($filas as $f) {
            $bucket = $catDe[(int) $f['pieza_id']] ?? null;
            if ($bucket !== null) {
                $k = $indiceCat[$bucket];
                $copiados[$k][(int) $f['unidad_id']] = ($copiados[$k][(int) $f['unidad_id']] ?? 0)
                    + $tamDe[(int) $f['pieza_id']];
            }
        }

        $recopia = [];
        foreach ($unidades as $u => $unidad) {
            for ($k = 0; $k < $c; $k++) {
                $deCat           = $copiados[$k] ?? [];
                $recopia[$u][$k] = array_sum($deCat) - ($deCat[(int) $unidad['id']] ?? 0);
            }
        }

        $asignacion = $c > self::MAX_CATEGORIAS_OPTIMO
            ? $this->planNivel3DeMayorAMenor($cap, $bytes)
            : $this->planNivel3Optimo($cap, $bytes, $recopia);

        $planes   = [];
        $colocada = [];
        foreach ($asignacion as $u => $ks) {
            if ($ks === []) {
                continue;
            }
            $planes[] = [
                'unidad_id'  => (int) $unidades[$u]['id'],
                'categorias' => array_map(static fn ($k) => $claves[$k], $ks),
                'bytes'      => array_sum(array_map(static fn ($k) => $bytes[$k], $ks)),
                'estado'     => 'ok',
            ];
            foreach ($ks as $k) {
                $colocada[$k] = true;
            }
        }
        for ($k = 0; $k < $c; $k++) {
            if (!isset($colocada[$k])) {
                $planes[] = [
                    'unidad_id'  => null,
                    'categorias' => [$claves[$k]],
                    'bytes'      => $bytes[$k],
                    'estado'     => $n > 0 && $bytes[$k] > $maxCap ? 'excede' : 'sin_unidad',
                ];
            }
        }

        return $planes;
    }

    /**
     * Búsqueda exacta de calcularPlanNivel3(): mejor resultado desde la
     * unidad u con las categorías de `mask` ya colocadas, probando cada
     * subconjunto de las libres que quepa en u (o dejar u sin usar).
     *
     * @param int[]   $cap     capacidad por unidad
     * @param int[]   $bytes   bytes por categoría
     * @param int[][] $recopia [unidad][categoría] => bytes a recopiar
     * @return array<int, int[]> unidad => índices de categoría
     */
    private function planNivel3Optimo(array $cap, array $bytes, array $recopia): array
    {
        $n     = count($cap);
        $c     = count($bytes);
        $total = 1 << $c;
        $lleno = $total - 1;

        // Bytes y recopia de cada subconjunto de categorías.
        $sumB = array_fill(0, $total, 0);
        $sumR = array_fill(0, $n, array_fill(0, $total, 0));
        for ($s = 1; $s < $total; $s++) {
            $k        = (int) log($s & -$s, 2);
            $resto    = $s & ($s - 1);
            $sumB[$s] = $sumB[$resto] + $bytes[$k];
            for ($u = 0; $u < $n; $u++) {
                $sumR[$u][$s] = $sumR[$u][$resto] + $recopia[$u][$k];
            }
        }

        $guardado = $recop = $capUsada = $el = [];
        for ($mask = 0; $mask < $total; $mask++) {
            $idx = $n * $total + $mask;
            $guardado[$idx] = $recop[$idx] = $capUsada[$idx] = $el[$idx] = 0;
        }

        for ($u = $n - 1; $u >= 0; $u--) {
            for ($mask = 0; $mask < $total; $mask++) {
                // Opción base: la unidad u se queda sin usar.
                $sig = ($u + 1) * $total + $mask;
                $bG  = $guardado[$sig];
                $bR  = $recop[$sig];
                $bC  = $capUsada[$sig];
                $bS  = 0;

                $libres = $lleno & ~$mask;
                for ($s = $libres; $s > 0; $s = ($s - 1) & $libres) {
                    if ($sumB[$s] > $cap[$u]) {
                        continue;
                    }
                    $sig = ($u + 1) * $total + ($mask | $s);
                    $g   = $guardado[$sig] + $sumB[$s];
                    $r   = $recop[$sig] + $sumR[$u][$s];
                    $cc  = $capUsada[$sig] + $cap[$u];

                    if ($g > $bG || ($g === $bG && ($r < $bR || ($r === $bR && $cc < $bC)))) {
                        [$bG, $bR, $bC, $bS] = [$g, $r, $cc, $s];
                    }
                }

                $idx            = $u * $total + $mask;
                $guardado[$idx] = $bG;
                $recop[$idx]    = $bR;
                $capUsada[$idx] = $bC;
                $el[$idx]       = $bS;
            }
        }

        $asignacion = [];
        for ($u = 0, $mask = 0; $u < $n; $u++) {
            $s              = $el[$u * $total + $mask];
            $asignacion[$u] = [];
            for ($k = 0; $k < $c; $k++) {
                if ($s & (1 << $k)) {
                    $asignacion[$u][] = $k;
                }
            }
            $mask |= $s;
        }

        return $asignacion;
    }

    /**
     * Respaldo de calcularPlanNivel3() con demasiadas categorías: de mayor a
     * menor, cada una en la primera unidad (orden de alta) donde quepa.
     *
     * @param int[] $cap
     * @param int[] $bytes ya ordenados de mayor a menor
     * @return array<int, int[]>
     */
    private function planNivel3DeMayorAMenor(array $cap, array $bytes): array
    {
        $libre      = $cap;
        $asignacion = array_fill(0, count($cap), []);
        foreach ($bytes as $k => $b) {
            foreach ($libre as $u => $l) {
                if ($b <= $l) {
                    $asignacion[$u][] = $k;
                    $libre[$u]       -= $b;
                    break;
                }
            }
        }

        return $asignacion;
    }

    /**
     * Materializa calcularPlanNivel3() sobre las unidades automáticas, como
     * aplicarPlanNivel2(): rehace sus buckets y las ubicaciones de Copia 3.
     * Lo ya copiado que sigue tocándole al mismo USB se conserva; lo que
     * cambia de USB se recrea pendiente (en el viejo queda como sobrante
     * para `silo --copiar --purgar`). Las unidades con categoría puesta a
     * mano no se tocan.
     *
     * @return int ubicaciones de Copia 3 nuevas
     */
    public function aplicarPlanNivel3(): int
    {
        $auto = array_map(static fn ($u) => (int) $u['id'], $this->unidadesNivel3Auto());
        $plan = $this->calcularPlanNivel3();

        $previas = [];
        if ($auto !== []) {
            foreach ($this->ubicacionModel->where('copia', 3)->whereIn('unidad_id', $auto)->findAll() as $u) {
                $previas[(int) $u['pieza_id']] = $u;
            }
            $this->unidadBucketModel->whereIn('unidad_id', $auto)->delete();
        }

        // Piezas con Copia 3 en una unidad que no es automática (p. ej. una
        // corrección mover_copia sin hacer): no se duplican.
        $fuera = [];
        foreach ($this->ubicacionModel->where('copia', 3)->findAll() as $u) {
            if (!in_array((int) $u['unidad_id'], $auto, true)) {
                $fuera[(int) $u['pieza_id']] = true;
            }
        }

        $porCat      = $this->piezasNivel1PorCategoriaAuto();
        $conservadas = [];
        $nuevas      = 0;

        foreach ($plan as $run) {
            if ($run['unidad_id'] === null) {
                continue; // sin_unidad / excede: queda pendiente.
            }

            foreach ($run['categorias'] as $categoria) {
                $this->unidadBucketModel->asignarBucket($run['unidad_id'], $categoria);

                foreach ($porCat[$categoria] ?? [] as $pieza) {
                    if (isset($fuera[(int) $pieza['id']])) {
                        continue;
                    }
                    $ruta   = $this->rutaEnCopia($categoria, $pieza);
                    $previa = $previas[(int) $pieza['id']] ?? null;
                    if ($previa && (int) $previa['unidad_id'] === (int) $run['unidad_id']) {
                        $conservadas[(int) $previa['id']] = true;
                        if ($previa['copiado_en'] === null && $previa['ruta_relativa'] !== $ruta) {
                            $this->ubicacionModel->update($previa['id'], ['ruta_relativa' => $ruta]);
                        }
                        continue;
                    }

                    $this->ubicacionModel->insert([
                        'pieza_id'      => $pieza['id'],
                        'unidad_id'     => $run['unidad_id'],
                        'copia'         => 3,
                        'ruta_relativa' => $ruta,
                    ]);
                    $nuevas++;
                }
            }
        }

        foreach ($previas as $previa) {
            if (!isset($conservadas[(int) $previa['id']])) {
                $pendiente = $this->tareaModel->reubicacionPendienteDeUbicacion((int) $previa['id']);
                if ($pendiente) {
                    $this->tareaModel->update($pendiente['id'], ['estado' => 'cancelada']);
                }
                $this->ubicacionModel->delete($previa['id']);
            }
        }

        return $nuevas;
    }

    /** Por encima de esto la búsqueda óptima (2^n estados) se dispara: se reparte en orden de alta. */
    private const MAX_UNIDADES_OPTIMO = 12;

    /**
     * Una unidad de Nivel 3 cambia de categoría al editarla: el reparto
     * busca por `silo_unidad_buckets`, no por la columna `agrupador`, así
     * que hay que rehacer su bucket. Sus Copias 3 de la categoría vieja
     * dejan de tocarle (las ya copiadas quedan en el USB como sobrantes que
     * `silo --copiar` avisa y `--purgar` borra), y repartirCopia3() coloca
     * lo pendiente — lo de la categoría nueva en esta unidad, y lo que ha
     * salido de aquí en otra de su categoría si la hay.
     *
     * @return array{colocadas: int, pendientes: int}
     */
    public function cambiarCategoriaNivel3(int $unidadId, ?string $categoria): array
    {
        $this->unidadBucketModel->where('unidad_id', $unidadId)->delete();
        if ($categoria !== null && $categoria !== '') {
            $this->unidadBucketModel->asignarBucket($unidadId, $categoria);
        }

        $ubicaciones = $this->ubicacionModel->where('unidad_id', $unidadId)->where('copia', 3)->findAll();
        foreach ($ubicaciones as $u) {
            $pendiente = $this->tareaModel->reubicacionPendienteDeUbicacion((int) $u['id']);
            if ($pendiente) {
                $this->tareaModel->update($pendiente['id'], ['estado' => 'cancelada']);
            }
            $this->ubicacionModel->delete($u['id']);
        }

        return $this->repartirCopia3();
    }

    /**
     * Reparto de Nivel 2 usando **únicamente las unidades ya dadas de alta**
     * con capacidad conocida (petición 2026-09-05: nada de inventar una
     * capacidad uniforme — cada USB real tiene la suya). Cada unidad recibe
     * un tramo de años **consecutivos** y un año **nunca se fragmenta**
     * entre dos unidades.
     *
     * Desde 2026-10-19 no se rellenan en orden de alta sino en el orden de
     * unidades que mejor encaja los tramos (búsqueda exacta, programación
     * dinámica sobre año × unidades ya usadas). Criterios, por prioridad:
     *   1. Que quede guardado el máximo de bytes (lo mínimo pendiente).
     *   2. Que haya que recopiar lo mínimo: un año ya copiado físicamente en
     *      un USB prefiere seguir en ese USB.
     *   3. Que sobren los mínimos huecos: la capacidad total de las unidades
     *      usadas es la mínima, así las que no hacen falta quedan enteras
     *      libres en vez de varias medio vacías.
     * Con más de MAX_UNIDADES_OPTIMO unidades se vuelve al relleno en orden
     * de alta (planNivel2EnOrdenDeAlta()).
     *
     * Estados por tramo: `ok`, `excede` (el año no cabe entero en ninguna
     * unidad: hace falta una de más capacidad solo para eso) o `sin_unidad`
     * (cabría, pero no queda ninguna unidad libre donde meterlo: dar de alta
     * más). "sin fecha" ordena primero, como el año 0. Puro cálculo, no toca
     * la BD — ver aplicarPlanNivel2() para materializarlo de verdad.
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

        if (count($unidades) > self::MAX_UNIDADES_OPTIMO) {
            return $this->planNivel2EnOrdenDeAlta($unidades, $filas);
        }

        $n      = count($unidades);
        $y      = count($filas);
        $total  = 1 << $n;
        $cap    = array_map(static fn ($u) => (int) $u['capacidad_bytes'], $unidades);
        $maxCap = $cap === [] ? 0 : max($cap);

        $claves = [];
        $bytes  = [];
        $pref   = [0];
        foreach ($filas as $k => $fila) {
            $claves[$k] = ((int) $fila['anio']) === 0 ? 'sin_fecha' : (string) ((int) $fila['anio']);
            $bytes[$k]  = (int) $fila['bytes'];
            $pref[]     = $pref[$k] + $bytes[$k];
        }

        // recopia[u][k]: bytes del año k ya copiados en OTRO USB que habría
        // que volver a copiar si el año k va a la unidad u.
        $copiados = $this->ubicacionModel->bytesCopiadosNivel2PorAnio();
        $recopia  = [];
        foreach ($unidades as $u => $unidad) {
            foreach ($filas as $k => $fila) {
                $delAnio         = $copiados[(int) $fila['anio']] ?? [];
                $recopia[$u][$k] =array_sum($delAnio) - ($delAnio[(int) $unidad['id']] ?? 0);
            }
        }

        // Mejor resultado desde el año i con las unidades de `mask` ya
        // usadas, en arrays planos (índice i*total+mask) para no gastar
        // memoria: guardado (máx), recopia (mín), capacidad usada (mín) y
        // la elección (unidad, último año del tramo; -1 = año pendiente).
        $guardado = $recop = $capUsada = $elU = $elJ = [];
        for ($mask = 0; $mask < $total; $mask++) {
            $idx = $y * $total + $mask;
            $guardado[$idx] = $recop[$idx] = $capUsada[$idx] = 0;
            $elU[$idx]      = $elJ[$idx] = -1;
        }

        for ($i = $y - 1; $i >= 0; $i--) {
            for ($mask = 0; $mask < $total; $mask++) {
                // Opción base: el año i se queda pendiente.
                $sig   = ($i + 1) * $total + $mask;
                $bG    = $guardado[$sig];
                $bR    = $recop[$sig];
                $bC    = $capUsada[$sig];
                $bU    = $bJ = -1;

                for ($u = 0; $u < $n; $u++) {
                    if ($mask & (1 << $u)) {
                        continue;
                    }
                    $re = 0;
                    for ($j = $i; $j < $y; $j++) {
                        $tramo = $pref[$j + 1] - $pref[$i];
                        if ($tramo > $cap[$u]) {
                            break;
                        }
                        $re  += $recopia[$u][$j];
                        $sig  = ($j + 1) * $total + ($mask | (1 << $u));
                        $g    = $guardado[$sig] + $tramo;
                        $r    = $recop[$sig] + $re;
                        $c    = $capUsada[$sig] + $cap[$u];

                        if ($g > $bG || ($g === $bG && ($r < $bR || ($r === $bR && $c < $bC)))) {
                            [$bG, $bR, $bC, $bU, $bJ] = [$g, $r, $c, $u, $j];
                        }
                    }
                }

                $idx            = $i * $total + $mask;
                $guardado[$idx] = $bG;
                $recop[$idx]    = $bR;
                $capUsada[$idx] = $bC;
                $elU[$idx]      = $bU;
                $elJ[$idx]      = $bJ;
            }
        }

        $planes = [];
        for ($i = 0, $mask = 0; $i < $y;) {
            $idx = $i * $total + $mask;
            if ($elU[$idx] === -1) {
                $planes[] = [
                    'unidad_id' => null,
                    'anios'     => [$claves[$i]],
                    'bytes'     => $bytes[$i],
                    'estado'    => $n > 0 && $bytes[$i] > $maxCap ? 'excede' : 'sin_unidad',
                ];
                $i++;

                continue;
            }

            [$u, $j]  = [$elU[$idx], $elJ[$idx]];
            $planes[] = [
                'unidad_id' => (int) $unidades[$u]['id'],
                'anios'     => array_slice($claves, $i, $j - $i + 1),
                'bytes'     => $pref[$j + 1] - $pref[$i],
                'estado'    => 'ok',
            ];
            $mask |= 1 << $u;
            $i     = $j + 1;
        }

        return $planes;
    }

    /**
     * Reparto antiguo (hasta 2026-10-19): recorre las unidades en orden de
     * alta llenándolas de años consecutivos mientras quepan; en cuanto uno
     * no cabe la cierra (aunque le sobre sitio) y pasa a la siguiente. Solo
     * se usa ya como respaldo cuando hay demasiadas unidades para la
     * búsqueda óptima de calcularPlanNivel2().
     */
    private function planNivel2EnOrdenDeAlta(array $unidades, array $filas): array
    {
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

        // Las copias que ya están físicamente en un USB y siguen tocándole
        // a ese mismo USB se conservan (con su `copiado_en`): rehacerlas
        // obligaría a copiarlas otra vez. Las que cambian de USB se
        // recrean pendientes; en el USB viejo quedan como sobrantes que
        // `silo --copiar` avisa (y borra con --purgar).
        $previas = [];
        foreach ($this->ubicacionModel->where('copia', 2)->findAll() as $u) {
            $previas[(int) $u['pieza_id']] = $u;
        }
        $conservadas = [];

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
                    $ruta   = $this->rutaEnCopia($anio, $pieza);
                    $previa = $previas[(int) $pieza['id']] ?? null;
                    if ($previa && (int) $previa['unidad_id'] === (int) $run['unidad_id']) {
                        $conservadas[(int) $previa['id']] = true;
                        if ($previa['copiado_en'] === null && $previa['ruta_relativa'] !== $ruta) {
                            $this->ubicacionModel->update($previa['id'], ['ruta_relativa' => $ruta]);
                        }
                        continue;
                    }

                    $this->ubicacionModel->insert([
                        'pieza_id'      => $pieza['id'],
                        'unidad_id'     => $run['unidad_id'],
                        'copia'         => 2,
                        'ruta_relativa' => $ruta,
                    ]);
                }
            }
        }

        foreach ($previas as $previa) {
            if (!isset($conservadas[(int) $previa['id']])) {
                $this->ubicacionModel->delete($previa['id']);
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
