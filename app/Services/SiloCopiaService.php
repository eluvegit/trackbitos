<?php

namespace App\Services;

use App\Models\SiloEventoModel;
use App\Models\SiloFicheroModel;
use App\Models\SiloTareaModel;
use App\Models\SiloUbicacionModel;
use App\Models\SiloUnidadBucketModel;
use App\Models\SiloUnidadModel;

/**
 * Propagación física (Fase 3, docs/silo-ingesta-propagacion.md): qué tiene
 * que haber en cada unidad de copia y qué se hizo de verdad. El agente
 * (`silo --copiar` / `silo --renombrar`) pide el plan de un USB, lo
 * reconcilia contra lo que encuentra en el disco **por ID de negocio** —
 * nunca por el nombre completo, que es justo lo que puede haber cambiado en
 * el Maestro — y devuelve el resultado aquí.
 *
 * Dos clases de destino:
 *   · Copia 2/3 (USB de nivel 2 por año, nivel 3 por categoría): lo que
 *     dicen sus filas de `silo_ubicaciones`, con el nombre
 *     `{cubo}/{fecha … [id]}` (SiloService::nombreEnCopia()).
 *   · Espejo de un Maestro (`silo_unidades.espejo_de`): todas las carpetas
 *     de Copia 1 de ese Maestro, con el mismo nombre exacto y en la raíz.
 *     No tiene filas propias en `silo_ubicaciones`; está al día si su
 *     `hash_origen` es el `hash_indice` actual del Maestro.
 */
class SiloCopiaService
{
    private SiloUnidadModel $unidadModel;
    private SiloUbicacionModel $ubicacionModel;
    private SiloFicheroModel $ficheroModel;
    private SiloTareaModel $tareaModel;
    private SiloEventoModel $eventoModel;
    private SiloUnidadBucketModel $bucketModel;
    private SiloService $silo;

    public function __construct()
    {
        helper('silo');
        $this->unidadModel    = new SiloUnidadModel();
        $this->ubicacionModel = new SiloUbicacionModel();
        $this->ficheroModel   = new SiloFicheroModel();
        $this->tareaModel     = new SiloTareaModel();
        $this->eventoModel    = new SiloEventoModel();
        $this->bucketModel    = new SiloUnidadBucketModel();
        $this->silo           = new SiloService();
    }

    public function esDestino(array $unidad): bool
    {
        return (int) $unidad['nivel'] !== 1 || $unidad['espejo_de'] !== null;
    }

    /**
     * Unidades que reciben copias, en el orden en que el agente las irá
     * pidiendo (espejos, luego nivel 2, luego nivel 3), con lo que les falta.
     */
    public function destinos(): array
    {
        $unidades = $this->unidadModel
            ->groupStart()->where('nivel !=', 1)->orWhere('espejo_de IS NOT NULL')->groupEnd()
            ->orderBy('nivel', 'ASC')->orderBy('espejo_de IS NULL', 'ASC', false)->orderBy('numero', 'ASC')
            ->findAll();

        return array_map(fn (array $u) => $this->resumenUnidad($u) + ['pendiente' => $this->pendiente($u)], $unidades);
    }

    /**
     * Lo que le falta a una unidad destino: carpetas por copiar (bytes) y
     * renombrados/movimientos encolados por cambios de nombre en el Maestro.
     *
     * @return array{al_dia: bool, piezas: int, bytes: int, renombrar: int}
     */
    public function pendiente(array $unidad): array
    {
        if ($unidad['espejo_de'] !== null) {
            $maestro = $this->unidadModel->find((int) $unidad['espejo_de']);
            $alDia   = $maestro && $maestro['hash_indice'] !== null && $unidad['hash_origen'] === $maestro['hash_indice'];

            return ['al_dia' => $alDia, 'piezas' => 0, 'bytes' => 0, 'renombrar' => 0];
        }

        $faltan    = $this->ubicacionModel->pendienteDeCopiarEnUnidad((int) $unidad['id']);
        $renombrar = count($this->reubicacionesQueTocan((int) $unidad['id']));

        return ['al_dia' => $faltan['piezas'] === 0 && $renombrar === 0] + $faltan + ['renombrar' => $renombrar];
    }

    /**
     * Lo que tiene que quedar en la unidad: una entrada por carpeta con su
     * ruta final, de dónde se copia (unidad Maestro + carpeta) y la lista
     * de ficheros esperada (nombre, tamaño, mtime) para comparar sin abrir
     * nada.
     */
    public function plan(array $unidad): array
    {
        $unidadId = (int) $unidad['id'];
        $items    = [];

        if ($unidad['espejo_de'] !== null) {
            $maestroId = (int) $unidad['espejo_de'];
            foreach ($this->ubicacionModel->deCopia1EnUnidad($maestroId) as $c1) {
                $items[] = [
                    'pieza_id'     => (int) $c1['pieza_id'],
                    'ubicacion_id' => null,
                    'id_negocio'   => $c1['id_negocio'],
                    'ruta'         => $c1['nombre_carpeta'],
                ];
            }
            $maestro    = $this->unidadModel->find($maestroId);
            $hashOrigen = $maestro['hash_indice'] ?? null;
        } else {
            $hashOrigen = null;
            $tareas     = $this->reubicacionesQueTocan($unidadId);

            foreach ($this->ubicacionModel->select('silo_ubicaciones.*, silo_piezas.id_negocio')
                ->join('silo_piezas', 'silo_piezas.id = silo_ubicaciones.pieza_id')
                ->where('silo_ubicaciones.unidad_id', $unidadId)->findAll() as $u) {
                $tarea = $tareas[(int) $u['id']] ?? null;
                unset($tareas[(int) $u['id']]);
                if ($tarea && ($tarea['datos']['unidad_destino_id'] ?? null) === null) {
                    $tarea = null; // cambió de cubo pero aún no hay USB con sitio: se queda como está
                }
                if ($tarea && (int) $tarea['datos']['unidad_destino_id'] !== $unidadId) {
                    continue; // se va a otro USB: aquí pasa a ser sobrante
                }
                $items[] = [
                    'pieza_id'     => (int) $u['pieza_id'],
                    'ubicacion_id' => (int) $u['id'],
                    'id_negocio'   => $u['id_negocio'],
                    'ruta'         => $tarea ? $tarea['datos']['hasta'] : $u['ruta_relativa'],
                ];
            }

            // Lo que llega de otro USB (cambió de cubo): aquí se copia de cero.
            foreach ($tareas as $ubicacionId => $tarea) {
                $pieza   = $this->ubicacionModel->select('silo_piezas.id_negocio')
                    ->join('silo_piezas', 'silo_piezas.id = silo_ubicaciones.pieza_id')
                    ->where('silo_ubicaciones.id', $ubicacionId)->first();
                $items[] = [
                    'pieza_id'     => (int) $tarea['datos']['pieza_id'],
                    'ubicacion_id' => $ubicacionId,
                    'id_negocio'   => $pieza['id_negocio'] ?? null,
                    'ruta'         => $tarea['datos']['hasta'],
                ];
            }
        }

        // Origen (Copia 1) y ficheros de todas las piezas de una vez.
        $piezaIds = array_column($items, 'pieza_id');
        $origenes = [];
        $ficheros = [];
        if ($piezaIds !== []) {
            foreach ($this->ubicacionModel->whereIn('pieza_id', $piezaIds)->where('copia', 1)->findAll() as $c1) {
                $origenes[(int) $c1['pieza_id']] = ['unidad_id' => (int) $c1['unidad_id'], 'ruta' => $c1['ruta_relativa']];
            }
            foreach ($this->ficheroModel->select('pieza_id, nombre, tamano_bytes, mtime')->whereIn('pieza_id', $piezaIds)->findAll() as $f) {
                $ficheros[(int) $f['pieza_id']][] = [
                    'nombre'       => $f['nombre'],
                    'tamano_bytes' => $f['tamano_bytes'] !== null ? (int) $f['tamano_bytes'] : null,
                    'mtime'        => $f['mtime'] !== null ? (int) $f['mtime'] : null,
                ];
            }
        }

        $maestros = [];
        foreach ($items as &$item) {
            $origen          = $origenes[$item['pieza_id']] ?? null;
            $item['origen']  = $origen;
            $item['ficheros'] = $ficheros[$item['pieza_id']] ?? [];
            if ($origen) {
                $maestros[$origen['unidad_id']] = true;
            }
        }
        unset($item);

        return [
            'unidad'      => $this->resumenUnidad($unidad) + ['fichero_control' => json_decode((string) $unidad['fichero_control'], true) ?: []],
            'maestros'    => array_map(
                fn (array $m) => $this->resumenUnidad($m),
                $maestros ? $this->unidadModel->whereIn('id', array_keys($maestros))->findAll() : []
            ),
            'hash_origen' => $hashOrigen,
            'items'       => $items,
        ];
    }

    /**
     * Lo que hizo el agente en el disco:
     * `{ completas: [{pieza_id, ubicacion_id, ruta}], faltan: [{pieza_id, ruta, motivo}],
     *    sobrantes: [ruta], errores: [{ruta, error}], renombradas: int,
     *    copiados_bytes: int, hash_origen? }`.
     * Cada carpeta completa queda con su ruta real y `copiado_en`, y cierra
     * el renombrado/movimiento que tuviera pendiente.
     */
    public function registrarResultado(array $unidad, array $body): array
    {
        $unidadId = (int) $unidad['id'];
        $ahora    = date('Y-m-d H:i:s');
        $hechas   = 0;

        foreach ((array) ($body['completas'] ?? []) as $c) {
            $ubicacionId = (int) ($c['ubicacion_id'] ?? 0);
            if (!$ubicacionId) {
                continue; // espejo: sin filas propias
            }
            $ubicacion = $this->ubicacionModel->find($ubicacionId);
            if (!$ubicacion) {
                continue;
            }
            $this->ubicacionModel->update($ubicacionId, [
                'unidad_id'     => $unidadId,
                'ruta_relativa' => (string) $c['ruta'],
                'copiado_en'    => $ahora,
            ]);
            $tarea = $this->tareaModel->reubicacionPendienteDeUbicacion($ubicacionId);
            if ($tarea) {
                $this->tareaModel->marcarResultado((int) $tarea['id'], ['hecha_por' => 'agente', 'ruta' => (string) $c['ruta']]);
            }
            $hechas++;
        }

        $faltan    = (array) ($body['faltan'] ?? []);
        $errores   = (array) ($body['errores'] ?? []);
        $sobrantes = (array) ($body['sobrantes'] ?? []);

        foreach ($errores as $e) {
            $this->eventoModel->registrar('copia_error', [
                'unidad_id'  => $unidadId,
                'referencia' => (string) ($e['ruta'] ?? ''),
                'detalle'    => (string) ($e['error'] ?? ''),
            ]);
        }
        foreach ($sobrantes as $ruta) {
            $this->eventoModel->registrar('copia_sobrante', [
                'unidad_id'  => $unidadId,
                'referencia' => (string) $ruta,
                'detalle'    => 'Carpeta en el disco que ya no le toca a esta unidad (borrada o renombrada a otro cubo en el Maestro). Se borra con silo --copiar --purgar.',
            ]);
        }

        $espejoAlDia = false;
        if ($unidad['espejo_de'] !== null && $faltan === [] && $errores === [] && !empty($body['hash_origen'])) {
            $this->unidadModel->update($unidadId, ['hash_origen' => (string) $body['hash_origen']]);
            $espejoAlDia = true;
        }

        $resumen = sprintf(
            '%d carpeta(s) al día, %d renombrada(s), %s copiado(s), %d sin completar, %d sobrante(s), %d error(es).',
            $unidad['espejo_de'] !== null ? count((array) ($body['completas'] ?? [])) : $hechas,
            (int) ($body['renombradas'] ?? 0),
            ($body['copiados_bytes'] ?? 0) ? silo_formatear_tamano((int) $body['copiados_bytes']) : '0 B',
            count($faltan),
            count($sobrantes),
            count($errores)
        );
        $this->eventoModel->registrar('copia', ['unidad_id' => $unidadId, 'detalle' => $resumen]);

        return ['ok' => true, 'resumen' => $resumen, 'espejo_al_dia' => $espejoAlDia, 'pendiente' => $this->pendiente($this->unidadModel->find($unidadId))];
    }

    /**
     * Renombrados/movimientos pendientes que afectan a esta unidad (la
     * carpeta está aquí, o tiene que llegar aquí), por ubicacion_id.
     */
    private function reubicacionesQueTocan(int $unidadId): array
    {
        $out = [];
        foreach ($this->tareaModel->whereIn('tipo', SiloTareaModel::TIPOS_REUBICACION)
            ->whereIn('estado', ['pendiente', 'en_curso'])->findAll() as $t) {
            $t['datos'] = json_decode((string) $t['payload'], true) ?: [];
            if ((int) $t['unidad_id'] === $unidadId || (int) ($t['datos']['unidad_destino_id'] ?? 0) === $unidadId) {
                $out[(int) ($t['datos']['ubicacion_id'] ?? 0)] = $t;
            }
        }

        return $out;
    }

    private function resumenUnidad(array $u): array
    {
        return [
            'unidad_id'       => (int) $u['id'],
            'nivel'           => (int) $u['nivel'],
            'numero'          => (int) $u['numero'],
            'etiqueta'        => $u['etiqueta'],
            'espejo_de'       => $u['espejo_de'] !== null ? (int) $u['espejo_de'] : null,
            'capacidad_bytes' => $u['capacidad_bytes'] !== null ? (int) $u['capacidad_bytes'] : null,
            'ruta_montaje'    => $u['ruta_montaje'],
            'buckets'         => $this->silo->comprimirAnios($this->bucketModel->bucketsDe((int) $u['id'])),
        ];
    }
}
