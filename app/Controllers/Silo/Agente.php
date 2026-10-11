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
use App\Services\SiloCatalogoService;
use App\Services\SiloCopiaService;
use App\Services\SiloIngestaService;
use App\Services\SiloService;

/**
 * API que habla el agente `.py` (ver silo-agente/agente.py y
 * docs/silo-ingesta-propagacion.md): el agente toca disco y reporta, esta
 * clase decide y guarda. Auth por token Bearer (filtro `siloApi`, no
 * Myth\Auth: aquí no hay sesión de navegador). Cubre handshake, escaneo
 * del primer nivel del Maestro con detección de cambios (el agente lleva el
 * manifiesto N1–N3 en el disco y solo manda las carpetas que cambiaron),
 * proxies (subida y copia en el Maestro) y la réplica del catálogo que el
 * agente deja en cada unidad, y la propagación física a las unidades de
 * copia (destinos/plan-copia/resultado-copia, ver SiloCopiaService).
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

            // La réplica del catálogo que hay en ese disco es MÁS NUEVA que
            // la BD viva: la BD perdió datos o se revirtió. Aviso fuerte en
            // el panel (una vez por réplica, el daemon hace handshake cada
            // pocos segundos) con cómo restaurar.
            $metaDisco = (array) ($u['catalogo_meta'] ?? []);
            if ($metaDisco && (new SiloCatalogoService())->esMasNuevaQueLaViva($metaDisco)) {
                $referencia = 'catalogo ' . ($metaDisco['generado_en'] ?? '?');
                $yaAvisado  = $this->eventoModel->where('tipo', 'catalogo_mas_nuevo')
                    ->where('unidad_id', $unidad['id'])->where('referencia', $referencia)->countAllResults() > 0;
                if (!$yaAvisado) {
                    $this->eventoModel->registrar('catalogo_mas_nuevo', [
                        'unidad_id'  => $unidad['id'],
                        'referencia' => $referencia,
                        'detalle'    => 'La réplica del catálogo de este disco llega hasta el evento #' . (int) ($metaDisco['ultimo_evento_id'] ?? 0)
                            . ' y la BD viva no: se perdieron o revirtieron datos. Para recuperarla: silo --restaurar-catalogo '
                            . (int) $unidad['id'] . ' (desde el PC con el disco) o php spark silo:restaurar <.catalogo.sql.gz> en el servidor.',
                    ]);
                }
            }

            $resueltas[] = [
                'unidad_id'    => (int) $unidad['id'],
                'nivel'        => (int) $unidad['nivel'],
                'espejo_de'    => $unidad['espejo_de'] !== null ? (int) $unidad['espejo_de'] : null,
                'numero'       => (int) $unidad['numero'],
                'etiqueta'     => $unidad['etiqueta'],
                'ruta_montaje' => $unidad['ruta_montaje'],
                // N0: el agente lo compara con el de su `.silo_unit.json`;
                // si no casan, manda todas las carpetas completas.
                'hash_indice'  => $unidad['hash_indice'] ?? null,
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
        // Solo el Maestro es fuente: escanear un espejo o un USB de copia
        // como si lo fuera borraría del catálogo todo lo que no tenga.
        if ((int) $unidad['nivel'] !== 1 || $unidad['espejo_de'] !== null) {
            return $this->response->setJSON(['error' => 'Esta unidad es una copia (espejo o nivel 2/3): no se escanea, se rellena con silo --copiar.'])->setStatusCode(422);
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
                // Sin `ficheros`: el agente la da por sin cambios (manifiesto).
                $ficheros = array_key_exists('ficheros', $entrada) ? (array) $entrada['ficheros'] : null;
                $pieza    = $this->ingesta->ingestarCarpeta($unidad['id'], $nombre, $ficheros);

                $cambios = $pieza['_cambios'];
                if (!$pieza['_nueva'] && ($cambios['nuevos'] || $cambios['borrados'] || $cambios['modificados'])) {
                    $this->eventoModel->registrar('ficheros_cambiados', [
                        'unidad_id'  => $unidad['id'],
                        'pieza_id'   => $pieza['id'],
                        'referencia' => $nombre,
                        'detalle'    => $this->resumenCambios($cambios),
                    ]);
                }

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
                    // ...o cuando cambiaron sus fotos/vídeos: los proxies de
                    // antes ya no representan la carpeta.
                    'necesita_proxies' => $pieza['_multimedia_cambiada'] || !$this->proxyModel->tieneProxiesReales((int) $pieza['id']),
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

        // N2/N3: ficheros con el mismo tamaño y fecha que el manifiesto pero
        // otro contenido (hash) — corrupción silenciosa o alguien tocó el
        // disco por fuera conservando la fecha.
        foreach ((array) ($body['corruptos'] ?? []) as $c) {
            $this->eventoModel->registrar('hash_distinto', [
                'unidad_id'  => $unidad['id'],
                'referencia' => (string) ($c['carpeta'] ?? '') . '/' . (string) ($c['fichero'] ?? ''),
                'detalle'    => 'Mismo tamaño y fecha que en la última sincronización, pero el contenido (hash) es otro. Revisa el fichero contra otra copia.',
            ]);
        }
        if (!empty($body['verificacion'])) {
            $this->unidadModel->update($unidad['id'], ['ultima_verificacion' => date('Y-m-d H:i:s')]);
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

    /**
     * El agente terminó una pasada sobre la unidad y dejó en su raíz el
     * manifiesto nuevo: guarda su rollup (`hash_indice`, N0 del próximo
     * handshake) y lo refleja en el `.silo_unit.json` descargable.
     */
    public function unidadSincronizada(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            return $this->response->setJSON(['error' => 'Unidad no encontrada.'])->setStatusCode(404);
        }

        $hash = (string) ($this->cuerpoJson()['hash_indice'] ?? '');
        if (!preg_match('/^[0-9a-f]{64}$/', $hash)) {
            return $this->response->setJSON(['error' => 'hash_indice no válido.'])->setStatusCode(422);
        }

        $ahora   = date('Y-m-d H:i:s');
        $control = json_decode((string) $unidad['fichero_control'], true) ?: [];
        $control['hash_indice']           = $hash;
        $control['ultima_sincronizacion'] = date('c');

        $this->unidadModel->update($id, [
            'hash_indice'           => $hash,
            'ultima_sincronizacion' => $ahora,
            'fichero_control'       => json_encode($control, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $this->response->setJSON(['ok' => true, 'fichero_control' => $control]);
    }

    /**
     * Réplica del catálogo para la raíz de una unidad: `.catalogo.sql.gz`
     * (en base64 dentro del JSON, pesa poco) + su `.catalogo.meta.json`.
     */
    public function catalogo()
    {
        $unidadId = (int) ($this->cuerpoJson()['unidad_id'] ?? 0) ?: null;
        $volcado  = (new SiloCatalogoService())->volcar($unidadId);

        return $this->response->setJSON([
            'meta'         => $volcado['meta'],
            'contenido_b64' => base64_encode($volcado['gz']),
        ]);
    }

    /**
     * Restaura el catálogo desde la réplica que sube el agente (`silo
     * --restaurar-catalogo`, para cuando la BD vive en un servidor sin
     * acceso a consola). Exige `confirmar: "RESTAURAR"` en el cuerpo; guarda
     * antes el estado actual en writable/silo/ (ver SiloCatalogoService).
     */
    public function restaurarCatalogo()
    {
        $body = $this->cuerpoJson();
        if (($body['confirmar'] ?? '') !== 'RESTAURAR') {
            return $this->response->setJSON(['error' => 'Falta confirmar: "RESTAURAR".'])->setStatusCode(422);
        }

        $gz = base64_decode((string) ($body['contenido_b64'] ?? ''), true);
        if ($gz === false || $gz === '') {
            return $this->response->setJSON(['error' => 'Falta la réplica (contenido_b64).'])->setStatusCode(422);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'silo_catalogo_');
        file_put_contents($tmp, $gz);
        try {
            $resultado = (new SiloCatalogoService())->restaurar($tmp);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['error' => $e->getMessage()])->setStatusCode(500);
        } finally {
            @unlink($tmp);
        }

        // Tras restaurar, silo_eventos es el de la réplica: este evento ya
        // queda en la BD restaurada.
        $this->eventoModel->registrar('catalogo_restaurado', [
            'unidad_id'  => isset($body['unidad_id']) ? (int) $body['unidad_id'] : null,
            'referencia' => 'catalogo ' . (string) ($body['meta']['generado_en'] ?? '?'),
            'detalle'    => "{$resultado['sentencias']} sentencia(s). Estado anterior guardado en {$resultado['copia_previa']}.",
        ]);

        return $this->response->setJSON(['ok' => true] + $resultado);
    }

    /**
     * Proxies que la web tiene de una pieza (URL absoluta), para que el
     * agente guarde su copia en el Maestro (`.silo_proxies/`) cuando falte.
     */
    public function listarProxies(int $id)
    {
        helper('silo');

        $proxies = array_map(function (array $p) {
            $fichero = $p['fichero_id'] ? $this->ficheroModel->find($p['fichero_id']) : null;

            return [
                'tipo'           => $p['tipo'],
                'orden'          => (int) $p['orden'],
                'url'            => silo_proxy_url($p['url']),
                'fichero_nombre' => $fichero['nombre'] ?? null,
            ];
        }, $this->proxyModel->deLaPieza($id));

        return $this->response->setJSON(['proxies' => $proxies]);
    }

    /**
     * Unidades que reciben copias (USB de nivel 2/3 y espejos del Maestro)
     * con lo que les falta — `silo --copiar` / `--renombrar` las va pidiendo
     * una a una a partir de esta lista.
     */
    public function destinos()
    {
        return $this->response->setJSON(['destinos' => (new SiloCopiaService())->destinos()]);
    }

    /** Lo que tiene que quedar en el disco de esa unidad (ver SiloCopiaService::plan()). */
    public function planCopia(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        $copia  = new SiloCopiaService();
        if (!$unidad || !$copia->esDestino($unidad)) {
            return $this->response->setJSON(['error' => 'No es una unidad de copia (nivel 2/3 o espejo).'])->setStatusCode(404);
        }

        return $this->response->setJSON($copia->plan($unidad));
    }

    /** El agente terminó con esa unidad: qué quedó completo, qué no y qué sobra. */
    public function resultadoCopia(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        $copia  = new SiloCopiaService();
        if (!$unidad || !$copia->esDestino($unidad)) {
            return $this->response->setJSON(['error' => 'No es una unidad de copia (nivel 2/3 o espejo).'])->setStatusCode(404);
        }

        return $this->response->setJSON($copia->registrarResultado($unidad, $this->cuerpoJson()));
    }

    /** "+2 nuevos · −1 borrado · ~3 modificados", con los nombres (recortado). */
    private function resumenCambios(array $cambios): string
    {
        $partes = [];
        foreach (['nuevos' => '+', 'borrados' => '−', 'modificados' => '~'] as $clave => $signo) {
            if ($cambios[$clave]) {
                $nombres  = array_slice($cambios[$clave], 0, 5);
                $mas      = count($cambios[$clave]) > 5 ? ' y ' . (count($cambios[$clave]) - 5) . ' más' : '';
                $partes[] = $signo . count($cambios[$clave]) . ' ' . $clave . ': ' . implode(', ', $nombres) . $mas;
            }
        }

        return implode(' · ', $partes);
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
