<?php

namespace App\Controllers\Silo;

use App\Controllers\BaseController;
use App\Models\SiloEventoModel;
use App\Models\SiloFicheroModel;
use App\Models\SiloPiezaModel;
use App\Models\SiloProxyModel;
use App\Models\SiloTareaModel;
use App\Models\SiloUbicacionModel;
use App\Models\SiloUnidadModel;
use App\Services\SiloIngestaService;
use App\Services\SiloService;

/**
 * API que habla el agente `.py` (ver silo-agente/agente.py y
 * docs/silo-ingesta-propagacion.md): el agente toca disco y reporta, esta
 * clase decide y guarda. Auth por token Bearer (filtro `siloApi`, no
 * Myth\Auth: aquí no hay sesión de navegador). Primer esbozo — cubre
 * handshake + escaneo del primer nivel del Maestro; no hay todavía cola de
 * aprobación humana, manifiesto por-fichero (N1-N3), ni propagación física.
 */
class Agente extends BaseController
{
    protected SiloUnidadModel $unidadModel;
    protected SiloTareaModel $tareaModel;
    protected SiloEventoModel $eventoModel;
    protected SiloPiezaModel $piezaModel;
    protected SiloUbicacionModel $ubicacionModel;
    protected SiloProxyModel $proxyModel;
    protected SiloFicheroModel $ficheroModel;
    protected SiloService $silo;
    protected SiloIngestaService $ingesta;

    public function __construct()
    {
        $this->unidadModel    = new SiloUnidadModel();
        $this->tareaModel     = new SiloTareaModel();
        $this->eventoModel    = new SiloEventoModel();
        $this->piezaModel     = new SiloPiezaModel();
        $this->ubicacionModel = new SiloUbicacionModel();
        $this->proxyModel     = new SiloProxyModel();
        $this->ficheroModel   = new SiloFicheroModel();
        $this->silo           = new SiloService();
        $this->ingesta        = new SiloIngestaService();
    }

    /**
     * El agente anuncia qué unidades tiene montadas ahora mismo (por
     * `unidad_id` si ya lo conoce, o por `ruta_montaje` para que la web lo
     * resuelva/recuerde). Devuelve la unidad resuelta + sus tareas
     * pendientes; las rutas que no casan con ninguna unidad de la BD
     * vuelven en `desconocidas` en vez de fallar en silencio.
     */
    public function handshake()
    {
        $body     = $this->cuerpoJson();
        $unidades = (array) ($body['unidades'] ?? []);

        $resueltas   = [];
        $desconocidas = [];

        foreach ($unidades as $u) {
            $unidad = null;

            if (!empty($u['unidad_id'])) {
                $unidad = $this->unidadModel->find((int) $u['unidad_id']);
            }
            if (!$unidad && !empty($u['ruta_montaje'])) {
                $unidad = $this->unidadModel->porRutaMontaje((string) $u['ruta_montaje']);

                // Primera vez que se ve esta ruta en esta unidad: la
                // recordamos, así el próximo handshake ya la resuelve por
                // unidad_id sin depender de que la ruta no cambie.
                if ($unidad && empty($unidad['ruta_montaje'])) {
                    $this->unidadModel->update($unidad['id'], ['ruta_montaje' => $u['ruta_montaje']]);
                    $unidad['ruta_montaje'] = $u['ruta_montaje'];
                }
            }

            if (!$unidad) {
                $desconocidas[] = $u;
                continue;
            }

            $resueltas[] = [
                'unidad_id'    => (int) $unidad['id'],
                'nivel'        => (int) $unidad['nivel'],
                'numero'       => (int) $unidad['numero'],
                'etiqueta'     => $unidad['etiqueta'],
                'ruta_montaje' => $unidad['ruta_montaje'],
                'tareas'       => $this->tareaModel->pendientesDeUnidad((int) $unidad['id']),
            ];
        }

        return $this->response->setJSON([
            'unidades'     => $resueltas,
            'desconocidas' => $desconocidas,
        ]);
    }

    /**
     * Recibe el escaneo del primer nivel del root de una unidad Maestro:
     * `{ unidad_id, lista_negra?: string[], entradas: [{ nombre, es_carpeta,
     * ficheros?: [{nombre, tamano_bytes?, hash?}] }], tarea_id? }`.
     *
     * Clasifica cada entrada (SiloService::clasificarEntradaRoot), ingesta
     * las candidatas (SiloIngestaService::ingestarCarpeta — get-or-create
     * por id_negocio) y deja rastro en silo_eventos de todo lo saltado y de
     * cualquier ID de negocio repetido dentro del propio escaneo. Nunca
     * bloquea el resto del lote por un error puntual en una entrada.
     *
     * El agente manda SIEMPRE el primer nivel completo del root (nunca un
     * delta), así que también sirve para detectar borrados: cualquier pieza
     * de Copia 1 de esta unidad cuyo `id_negocio` no aparezca entre las
     * candidatas de esta pasada ya no está en el Maestro y se borra del
     * catálogo (cascada a ficheros/atributos/proxies/ubicaciones vía FK) —
     * si no, se quedaba huérfana para siempre (p.ej. seguía marcando "sin
     * lugar" en /silo/datos-faltan una carpeta que ya no existe).
     *
     * Cada `ingestadas[]` trae `necesita_proxies`: si es true, el agente
     * genera (ffmpeg) y sube por separado (subirProxy()) hasta 10 fotos + 10
     * fotogramas de vídeo de previsualización para esa pieza — ver
     * docs/silo-ingesta-propagacion.md § "Proxies / capturas".
     */
    public function escaneo()
    {
        $body = $this->cuerpoJson();

        $unidadId = (int) ($body['unidad_id'] ?? 0);
        $unidad   = $unidadId ? $this->unidadModel->find($unidadId) : null;
        if (!$unidad) {
            return $this->response->setJSON(['error' => 'unidad_id no encontrado.'])->setStatusCode(404);
        }

        $listaNegra = (array) ($body['lista_negra'] ?? []);
        $entradas   = (array) ($body['entradas'] ?? []);

        $ingestadas = [];
        $saltadas   = [];
        $errores    = [];
        $idsVistos  = []; // id_negocio => nombre de carpeta de la primera vez que se vio en este escaneo

        foreach ($entradas as $entrada) {
            $nombre    = trim((string) ($entrada['nombre'] ?? ''));
            $esCarpeta = (bool) ($entrada['es_carpeta'] ?? false);

            if ($nombre === '') {
                continue;
            }

            $clasificacion = $this->silo->clasificarEntradaRoot($nombre, $esCarpeta, $listaNegra);

            if ($clasificacion['estado'] === 'saltada') {
                $saltadas[] = ['nombre' => $nombre, 'motivo' => $clasificacion['motivo']];
                $this->eventoModel->registrar('carpeta_saltada', [
                    'unidad_id'  => $unidad['id'],
                    'referencia' => $nombre,
                    'motivo'     => $clasificacion['motivo'],
                ]);
                continue;
            }

            $idNegocio = $this->silo->parsearNombreCarpeta($nombre)['id_negocio'];
            if (isset($idsVistos[$idNegocio])) {
                $this->eventoModel->registrar('id_duplicado', [
                    'unidad_id'  => $unidad['id'],
                    'referencia' => $nombre,
                    'detalle'    => "Mismo id_negocio que «{$idsVistos[$idNegocio]}» en este escaneo.",
                ]);
            } else {
                $idsVistos[$idNegocio] = $nombre;
            }

            try {
                $pieza = $this->ingesta->ingestarCarpeta($unidad['id'], $nombre, (array) ($entrada['ficheros'] ?? []));
                $ingestadas[] = [
                    'nombre'           => $nombre,
                    'pieza_id'         => $pieza['id'],
                    'id_negocio'       => $pieza['id_negocio'],
                    // El agente genera proxies reales (ffmpeg) solo mientras
                    // la pieza no tenga ya alguno — ver
                    // SiloProxyModel::tieneProxiesReales(). Sin esto, cada
                    // pasada normal (que reingesta TODAS las candidatas, no
                    // solo las nuevas) volvería a generar proxies para todo
                    // el Maestro cada vez.
                    'necesita_proxies' => !$this->proxyModel->tieneProxiesReales((int) $pieza['id']),
                ];
            } catch (\Throwable $e) {
                $errores[] = ['nombre' => $nombre, 'error' => $e->getMessage()];
                $this->eventoModel->registrar('error_ingesta', [
                    'unidad_id'  => $unidad['id'],
                    'referencia' => $nombre,
                    'detalle'    => $e->getMessage(),
                ]);
            }
        }

        $desaparecidas = [];
        foreach ($this->ubicacionModel->deCopia1EnUnidad($unidad['id']) as $registrada) {
            if (isset($idsVistos[$registrada['id_negocio']])) {
                continue;
            }

            $desaparecidas[] = ['nombre' => $registrada['nombre_carpeta'], 'id_negocio' => $registrada['id_negocio']];
            $this->eventoModel->registrar('carpeta_desaparecida', [
                'unidad_id'  => $unidad['id'],
                'pieza_id'   => $registrada['pieza_id'],
                'referencia' => $registrada['nombre_carpeta'],
                'detalle'    => "id_negocio {$registrada['id_negocio']}: ya no está en el primer nivel del Maestro, pieza borrada del catálogo.",
            ]);
            $this->piezaModel->delete($registrada['pieza_id']);
        }

        $resumen = [
            'unidad_id'     => $unidad['id'],
            'ingestadas'    => $ingestadas,
            'saltadas'      => $saltadas,
            'errores'       => $errores,
            'desaparecidas' => $desaparecidas,
        ];

        $this->eventoModel->registrar('escaneo', [
            'unidad_id' => $unidad['id'],
            'detalle'   => count($ingestadas) . ' ingestada(s), ' . count($saltadas) . ' saltada(s), '
                . count($desaparecidas) . ' desaparecida(s), ' . count($errores) . ' error(es).',
        ]);

        $tareaId = (int) ($body['tarea_id'] ?? 0);
        if ($tareaId) {
            $this->tareaModel->marcarResultado($tareaId, $resumen, $errores !== [] ? 'Ver errores en el resumen.' : null);
        }

        return $this->response->setJSON($resumen);
    }

    /**
     * Recibe UN proxy de previsualización ya generado por el agente
     * (multipart: `tipo` foto|video, `orden` 0-9, `fichero_nombre` opcional
     * para enlazarlo con su `silo_ficheros.id`, `reemplazar` — "1" en la
     * primera subida del lote de esta pieza para borrar antes cualquier
     * proxy previo (simulado o de una generación anterior), campo de
     * fichero `archivo`). Se guarda como `public/assets/silo/proxies/{id}/
     * {tipo}-{orden}.webp` — nombre determinista, así una regeneración
     * futura simplemente sobreescribe el fichero y la URL en BD no cambia.
     */
    public function subirProxy(int $id)
    {
        $pieza = $this->piezaModel->find($id);
        if (!$pieza) {
            return $this->response->setJSON(['error' => 'Pieza no encontrada.'])->setStatusCode(404);
        }

        $tipo = (string) $this->request->getPost('tipo');
        if (!in_array($tipo, ['foto', 'video'], true)) {
            return $this->response->setJSON(['error' => 'tipo debe ser "foto" o "video".'])->setStatusCode(422);
        }

        $orden = (int) $this->request->getPost('orden');
        if ($orden < 0 || $orden > 9) {
            return $this->response->setJSON(['error' => 'orden debe estar entre 0 y 9.'])->setStatusCode(422);
        }

        $archivo = $this->request->getFile('archivo');
        if (!$archivo || !$archivo->isValid() || $archivo->hasMoved()) {
            return $this->response->setJSON(['error' => 'Falta el fichero "archivo" o no es válido.'])->setStatusCode(422);
        }

        if ((bool) $this->request->getPost('reemplazar')) {
            $this->proxyModel->where('pieza_id', $id)->delete();
        }

        $directorio = FCPATH . 'assets/silo/proxies/' . $id . '/';
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            return $this->response->setJSON(['error' => 'No se pudo crear el directorio de proxies.'])->setStatusCode(500);
        }

        $nombreFichero = $tipo . '-' . $orden . '.webp';
        try {
            $archivo->move($directorio, $nombreFichero, true);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['error' => 'No se pudo guardar el fichero: ' . $e->getMessage()])->setStatusCode(500);
        }

        $ficheroId       = null;
        $nombreOriginal  = trim((string) $this->request->getPost('fichero_nombre'));
        if ($nombreOriginal !== '') {
            $fichero   = $this->ficheroModel->where('pieza_id', $id)->where('nombre', $nombreOriginal)->first();
            $ficheroId = $fichero['id'] ?? null;
        }

        // Upsert manual por (pieza_id, tipo, orden): sustituye lo que hubiera
        // en ese hueco en vez de acumular filas si se sube dos veces seguidas.
        $this->proxyModel->where('pieza_id', $id)->where('tipo', $tipo)->where('orden', $orden)->delete();
        $this->proxyModel->insert([
            'pieza_id'   => $id,
            'fichero_id' => $ficheroId,
            'tipo'       => $tipo,
            'url'        => 'assets/silo/proxies/' . $id . '/' . $nombreFichero,
            'orden'      => $orden,
        ]);

        return $this->response->setJSON(['ok' => true]);
    }

    /** Reporte suelto de una tarea de la cola que no sea un escaneo (resultado genérico). */
    public function tareaResultado(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            return $this->response->setJSON(['error' => 'Tarea no encontrada.'])->setStatusCode(404);
        }

        $body = $this->cuerpoJson();
        $this->tareaModel->marcarResultado($id, (array) ($body['resultado'] ?? []), $body['error'] ?? null);

        return $this->response->setJSON(['ok' => true]);
    }

    private function cuerpoJson(): array
    {
        $decodificado = json_decode($this->request->getBody(), true);

        return is_array($decodificado) ? $decodificado : [];
    }
}
