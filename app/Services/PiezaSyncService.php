<?php

namespace App\Services;

use App\Models\PiezaDescargaModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaMaquinaModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use RuntimeException;
use Throwable;

/**
 * El círculo descarga -> subida (spec 4.4, invariante 8). Aquí vive la
 * única regla que los tres hashes locales no pueden cubrir: una copia
 * descargada en la otra máquina con cambios que nunca se subieron.
 *
 * Se trata cada descarga como un asiento contable que debe cuadrarse. Toda
 * subida declara de qué hash partió; si no coincide con el hash entregado
 * en una descarga abierta de esa misma máquina, se rechaza. Aceptarla
 * silenciosamente perdería el trabajo intermedio, que es exactamente el
 * fallo que este módulo existe para impedir.
 *
 * Convención de errores: el `code` de la RuntimeException es el estado HTTP
 * que debe devolver la API (404 no encontrado, 403 máquina equivocada, 409
 * el asiento no cuadra, 422 el fichero no es el que dice ser). Sin código,
 * la API lo trata como 409: un conflicto de dominio, no un fallo del
 * servidor.
 */
class PiezaSyncService
{
    private PiezaVarianteModel $varianteModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;
    private PiezaDescargaModel $descargaModel;
    private PiezaMaquinaModel $maquinaModel;
    private PiezaAlmacen $almacen;

    public function __construct(?PiezaAlmacen $almacen = null)
    {
        $this->varianteModel = new PiezaVarianteModel();
        $this->ramaModel     = new PiezaRamaModel();
        $this->sesionModel   = new PiezaSesionModel();
        $this->descargaModel = new PiezaDescargaModel();
        $this->maquinaModel  = new PiezaMaquinaModel();
        $this->almacen       = $almacen ?? new PiezaAlmacen();
    }

    /**
     * Foto del estado de sincronización de una variante: dónde está el
     * trabajo en curso y de qué fichero se parte. Lo consumen la API (para
     * el veredicto del CLI) y la ficha web, que deben contar lo mismo — si
     * cada una lo calculase por su cuenta, acabarían discrepando.
     *
     * @return array{rama: ?array, ultima_subida: ?array, version_origen: ?array, origen_descarga: ?array, hash_nube: ?string, sesion_abierta: ?array, descargas_pendientes: array}
     */
    public function estadoDeSincronizacion(int $varianteId): array
    {
        $rama          = $this->ramaModel->abiertaDe($varianteId);
        $ultimaSubida  = $rama ? $this->sesionModel->ultimaSubida((int) $rama['id']) : null;
        $sesionAbierta = $rama
            ? $this->sesionModel->where('rama_id', $rama['id'])->where('cerrada_en', null)->first()
            : null;

        // Recién promocionada, la rama nueva no tiene ninguna sesión subida y
        // el punto de partida es el .blend de la versión que la abrió: sin
        // esto, el ciclo normal (promocionar -> seguir trabajando) parecería
        // empezar de cero.
        $versionOrigen = $rama && !$ultimaSubida && !empty($rama['desde_version_id'])
            ? (new PiezaVersionModel())->find($rama['desde_version_id'])
            : null;

        $origenDescarga = null;
        if ($ultimaSubida) {
            $origenDescarga = ['tipo' => 'sesion', 'id' => (int) $ultimaSubida['id'], 'numero' => (int) $ultimaSubida['numero']];
        } elseif ($versionOrigen) {
            $origenDescarga = ['tipo' => 'version', 'id' => (int) $versionOrigen['id'], 'numero' => (int) $versionOrigen['numero']];
        }

        return [
            'rama'                 => $rama,
            'ultima_subida'        => $ultimaSubida,
            'version_origen'       => $versionOrigen,
            'origen_descarga'      => $origenDescarga,
            'hash_nube'            => $ultimaSubida['hash_blend'] ?? $versionOrigen['hash_blend'] ?? null,
            'sesion_abierta'       => $sesionAbierta,
            'descargas_pendientes' => $this->descargaModel->abiertasParaVariante($varianteId),
        ];
    }

    public function nombreDeMaquina(int $maquinaId): ?string
    {
        return $this->maquinaModel->find($maquinaId)['nombre'] ?? null;
    }

    /**
     * Entrega el .blend de una sesión ya subida.
     */
    public function entregarSesion(int $sesionOrigenId, int $maquinaId, string $motivo, bool $ignorarPendiente = false): array
    {
        $sesion = $this->sesionModel->find($sesionOrigenId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionOrigenId} no encontrada.", 404);
        }
        if (empty($sesion['subida_en']) || empty($sesion['ruta_blend'])) {
            throw new RuntimeException(
                "La sesión {$sesionOrigenId} no tiene ningún fichero subido: no hay nada que descargar.",
                409
            );
        }
        if (!empty($sesion['purgada'])) {
            // Invariante 5: su rama terminó en una versión que se validó, así
            // que el fichero bueno es el de la versión. La sesión conserva su
            // registro (hashes, log), pero ya no es un sitio del que partir.
            throw new RuntimeException(
                "La sesión {$sesionOrigenId} está purgada: su rama acabó en una versión validada. "
                . 'Parte de la versión, que es la que conserva el fichero bueno.',
                409
            );
        }

        $rama = $this->ramaModel->find($sesion['rama_id']);

        return $this->entregar([
            'tipo'        => 'sesion',
            'id'          => (int) $sesion['id'],
            'numero'      => (int) $sesion['numero'],
            'variante_id' => (int) $rama['variante_id'],
            'ruta_blend'  => $sesion['ruta_blend'],
            'rama'        => $rama,
        ], $maquinaId, $motivo, $ignorarPendiente);
    }

    /**
     * Entrega el .blend de una versión promocionada. Es el punto de partida
     * de toda rama recién abierta: al promocionar, la rama nueva nace sin
     * ninguna sesión subida, y sin esto no habría de dónde bajar el fichero
     * para seguir trabajando — que es el ciclo normal, no un caso raro.
     */
    public function entregarVersion(int $versionId, int $maquinaId, string $motivo, bool $ignorarPendiente = false): array
    {
        $version = (new PiezaVersionModel())->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.", 404);
        }

        return $this->entregar([
            'tipo'        => 'version',
            'id'          => (int) $version['id'],
            'numero'      => (int) $version['numero'],
            'variante_id' => (int) $version['variante_id'],
            'ruta_blend'  => $version['ruta_blend'],
            'rama'        => null,
        ], $maquinaId, $motivo, $ignorarPendiente);
    }

    /**
     * Abre el asiento de descarga y devuelve qué fichero servir. Con motivo
     * 'trabajo' abre además la sesión de trabajo (y consume número); con
     * 'consulta' no, porque mirar una cota no debe dejar sesiones vacías en
     * el historial — pero el asiento se abre igual, porque el fichero acaba
     * en un disco y el sistema tiene que saberlo.
     *
     * @return array{descarga: array, sesion_trabajo: ?array, origen: array, rama: array, variante: array, ruta_absoluta: string, hash: string, nombre_fichero: string}
     */
    private function entregar(array $origen, int $maquinaId, string $motivo, bool $ignorarPendiente): array
    {
        if (!in_array($motivo, ['trabajo', 'consulta'], true)) {
            throw new RuntimeException("Motivo de descarga no válido: '{$motivo}'. Debe ser 'trabajo' o 'consulta'.", 422);
        }

        $this->exigirMaquina($maquinaId);

        if (!$this->almacen->existe($origen['ruta_blend'])) {
            throw new RuntimeException(
                "El fichero de origen no está en el almacén ({$origen['ruta_blend']}). "
                . 'No se entrega nada hasta saber qué pasó con él.',
                409
            );
        }

        $varianteId = (int) $origen['variante_id'];
        $variante   = $this->varianteModel->find($varianteId);

        $pendientes = $this->descargaModel->abiertasDeOtrasMaquinas($varianteId, $maquinaId);
        if ($pendientes && !$ignorarPendiente) {
            throw new RuntimeException($this->mensajePendientes($pendientes), 409);
        }

        // El trabajo nuevo va siempre a la rama abierta, que puede no ser la
        // de la sesión de origen (al promocionar se cierra una y se abre otra:
        // se parte del último fichero, pero se trabaja en la rama nueva).
        $ramaDestino = $this->ramaModel->abiertaDe($varianteId) ?? $origen['rama'];
        if (!$ramaDestino) {
            throw new RuntimeException(
                "La variante {$varianteId} no tiene ninguna rama de trabajo abierta donde registrar la descarga.",
                409
            );
        }

        $hash = $this->almacen->hash($origen['ruta_blend']);

        $resultado = $this->transaccion('registrar la descarga', function () use ($motivo, $varianteId, $ramaDestino, $maquinaId, $hash) {
            $sesionTrabajo = null;
            if ($motivo === 'trabajo') {
                // Invariante 3 (una sesión sin cerrar por variante) lo aplica
                // el propio modelo: si otra máquina la tiene abierta, revienta
                // aquí y no llega a crearse el asiento.
                $sesionTrabajo = $this->sesionModel->abrir((int) $ramaDestino['id'], $maquinaId);
            }

            $descargaId = $this->descargaModel->insert([
                'sesion_id'      => $sesionTrabajo['id'] ?? null,
                'variante_id'    => $varianteId,
                'rama_id'        => (int) $ramaDestino['id'],
                'maquina_id'     => $maquinaId,
                'motivo'         => $motivo,
                'descargado_en'  => date('Y-m-d H:i:s'),
                'hash_entregado' => $hash,
                'cerrada'        => 0,
            ], true);

            if (!$descargaId) {
                throw new RuntimeException('No se pudo abrir el asiento de descarga.');
            }

            return [
                'descarga'       => $this->descargaModel->find($descargaId),
                'sesion_trabajo' => $sesionTrabajo,
            ];
        });

        return [
            'descarga'       => $resultado['descarga'],
            'sesion_trabajo' => $resultado['sesion_trabajo'],
            'origen'         => ['tipo' => $origen['tipo'], 'id' => $origen['id'], 'numero' => $origen['numero']],
            'rama'           => $ramaDestino,
            'variante'       => $variante,
            'ruta_absoluta'  => $this->almacen->absoluta($origen['ruta_blend']),
            'hash'           => $hash,
            'nombre_fichero' => $this->nombreFichero($variante, $ramaDestino, $resultado['sesion_trabajo']),
        ];
    }

    /**
     * Recibe el .blend de una sesión de trabajo y cierra el asiento que la
     * originó. Dos comprobaciones independientes, y las dos son necesarias:
     * el hash recalculado (¿me ha llegado entero el fichero que dices?) y el
     * hash_padre (¿parte de la copia que yo te entregué, o de otra?).
     */
    public function recibir(int $sesionId, int $maquinaId, string $rutaTemporal, string $hashDeclarado, ?string $hashPadre, ?string $log = null): array
    {
        $this->exigirMaquina($maquinaId);

        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.", 404);
        }
        if (!empty($sesion['cerrada_en'])) {
            throw new RuntimeException(
                "La sesión {$sesionId} ya está cerrada: no admite más subidas. Abre una sesión nueva.",
                409
            );
        }
        if ((int) $sesion['maquina_id'] !== $maquinaId) {
            throw new RuntimeException(
                "La sesión {$sesionId} la abrió otra máquina. Solo puede subir quien tiene el fichero.",
                403
            );
        }

        if (!is_file($rutaTemporal)) {
            throw new RuntimeException('No llegó ningún fichero en la petición.', 422);
        }

        // Spec 6: el servidor recalcula el hash de todo fichero recibido. Es
        // barato (350 KB) y es lo único que distingue "se subió" de "se subió
        // entero".
        $hashReal = hash_file('sha256', $rutaTemporal);
        if (!hash_equals(strtolower($hashDeclarado), $hashReal)) {
            throw new RuntimeException(
                "El fichero recibido no coincide con el hash declarado (dijiste {$hashDeclarado}, "
                . "he calculado {$hashReal}). Subida rechazada: puede haber llegado incompleto.",
                422
            );
        }

        $hashPadre = $hashPadre !== null && $hashPadre !== '' ? strtolower($hashPadre) : null;
        $descarga  = $this->descargaModel->abiertaDeMaquina($maquinaId, (int) $sesion['rama_id']);
        $this->exigirCuadre($sesion, $descarga, $hashPadre);

        $rutaRelativa = $this->almacen->rutaSesion(
            (int) $this->ramaModel->find($sesion['rama_id'])['variante_id'],
            (int) $sesion['rama_id'],
            $sesionId
        );
        $this->almacen->guardar($rutaTemporal, $rutaRelativa);

        try {
            return $this->transaccion('registrar la subida', function () use ($sesionId, $rutaRelativa, $hashReal, $hashPadre, $log, $descarga) {
                $sesion = (new PiezaService())->subirSesion(
                    $sesionId,
                    $rutaRelativa,
                    $hashReal,
                    (int) filesize($this->almacen->absoluta($rutaRelativa)),
                    $log,
                    $hashPadre
                );

                return [
                    'sesion'   => $sesion,
                    'descarga' => $descarga
                        ? $this->descargaModel->cerrar((int) $descarga['id'], 'subida', $sesionId)
                        : null,
                ];
            });
        } catch (Throwable $e) {
            // El fichero ya está escrito pero la base de datos no se enteró:
            // dejarlo ahí sería un huérfano que nadie sabría interpretar.
            $this->almacen->descartarEscritura($rutaRelativa);

            throw $e;
        }
    }

    /**
     * Bajaste y no tocaste nada: no tiene sentido subir un fichero idéntico.
     * La prueba es el hash del fichero en ESE disco, así que solo puede
     * ejecutarlo la máquina que tiene la copia — desde la otra o desde la
     * web no hay nada que comprobar, y para eso está el cierre forzado, que
     * se registra distinto precisamente porque no aporta prueba.
     */
    public function cerrarSinCambios(int $descargaId, int $maquinaId, string $hashLocal): array
    {
        $descarga = $this->exigirDescargaAbierta($descargaId);

        if ((int) $descarga['maquina_id'] !== $maquinaId) {
            throw new RuntimeException(
                'Esta descarga la hizo otra máquina. "Sin cambios" solo puede declararlo el equipo que tiene la copia, '
                . 'porque la prueba es el hash de ese fichero. Si esa máquina ya no existe, usa el cierre forzado desde la web.',
                403
            );
        }

        if (!hash_equals((string) $descarga['hash_entregado'], strtolower($hashLocal))) {
            throw new RuntimeException(
                'El fichero de tu disco ya no es el que se entregó, así que no se puede cerrar como "sin cambios". '
                . "Entregado: {$descarga['hash_entregado']}. Tuyo: " . strtolower($hashLocal) . '. Súbelo.',
                409
            );
        }

        return $this->transaccion('cerrar la descarga', function () use ($descarga) {
            // Si la descarga abrió sesión de trabajo, hay que cerrarla o la
            // variante queda bloqueada para siempre por una sesión vacía.
            $sesion = $this->cerrarSesionAsociada($descarga, 'Cerrada sin cambios: se bajó para trabajar pero el fichero no se tocó.');

            return [
                'descarga' => $this->descargaModel->cerrar((int) $descarga['id'], 'sin_cambios'),
                'sesion'   => $sesion,
            ];
        });
    }

    /**
     * La válvula de escape (spec 4.4). Si la máquina que tiene la copia no
     * vuelve nunca —disco formateado, portátil roto—, el asiento no puede
     * cuadrarse y un sistema estricto sin salida se convierte en una trampa.
     * Exige motivo escrito y queda marcado como 'forzado' para que dentro de
     * seis meses se sepa que ahí no hubo prueba, solo una decisión.
     */
    public function forzarCierre(int $descargaId, string $motivo): array
    {
        if (trim($motivo) === '') {
            throw new RuntimeException(
                'El cierre forzado exige un motivo escrito: es la única constancia de por qué se cerró sin prueba.',
                422
            );
        }

        $descarga = $this->exigirDescargaAbierta($descargaId);

        return $this->transaccion('forzar el cierre', function () use ($descarga, $motivo) {
            $sesion = $this->cerrarSesionAsociada($descarga, 'Cerrada por cierre forzado de la descarga: ' . trim($motivo));

            return [
                'descarga' => $this->descargaModel->cerrar((int) $descarga['id'], 'forzado', null, trim($motivo)),
                'sesion'   => $sesion,
            ];
        });
    }

    /**
     * ¿Puede esta subida encadenarse con lo que el servidor conoce?
     *
     * Caso normal: hay un asiento abierto de esta máquina y el hash_padre
     * declarado es el que se entregó. Los otros dos casos no son excepciones
     * cómodas, son cadenas igual de verificables: una rama estrenada (no se
     * pudo descargar nada porque no había nada) y una segunda subida dentro
     * de la misma sesión (el padre es tu propia subida anterior).
     */
    private function exigirCuadre(array $sesion, ?array $descarga, ?string $hashPadre): void
    {
        if ($descarga) {
            if ($hashPadre === null) {
                throw new RuntimeException(
                    'Tienes una descarga sin cerrar de esta rama, pero subes sin declarar de qué hash partes. '
                    . "Se esperaba hash_padre = {$descarga['hash_entregado']}.",
                    409
                );
            }
            if (!hash_equals((string) $descarga['hash_entregado'], $hashPadre)) {
                throw new RuntimeException(
                    'Este fichero no procede de la última descarga de esta máquina, así que aceptarlo perdería el '
                    . "trabajo intermedio. Se esperaba hash_padre = {$descarga['hash_entregado']}, has enviado {$hashPadre}.",
                    409
                );
            }

            return;
        }

        if ($hashPadre === null) {
            $ultimaSubida = $this->sesionModel->ultimaSubida((int) $sesion['rama_id']);
            if ($ultimaSubida) {
                throw new RuntimeException(
                    "Esta rama ya tiene la sesión {$ultimaSubida['numero']} subida, así que tu fichero tiene que "
                    . 'declarar de qué hash parte. Baja primero (trackbitos bajar) para abrir el asiento.',
                    409
                );
            }

            return;
        }

        if (!empty($sesion['hash_blend']) && hash_equals((string) $sesion['hash_blend'], $hashPadre)) {
            // Segunda subida de la misma sesión: el asiento anterior ya se
            // cerró con la primera, y la cadena la cierra la sesión consigo
            // misma. Nada que cuadrar.
            return;
        }

        // Rama recién abierta y el fichero parte de la versión que la abrió:
        // es el ciclo normal (promocionas y sigues trabajando con el mismo
        // .blend delante), y la cadena versión -> sesión es igual de
        // verificable. En cuanto la rama tiene una subida esta puerta se
        // cierra sola, así que no permite pisar trabajo de la otra máquina.
        $rama = $this->ramaModel->find($sesion['rama_id']);
        $ultimaSubidaRama = $this->sesionModel->ultimaSubida((int) $sesion['rama_id']);
        if (!$ultimaSubidaRama && !empty($rama['desde_version_id'])) {
            $version = (new PiezaVersionModel())->find($rama['desde_version_id']);
            if ($version && hash_equals((string) $version['hash_blend'], $hashPadre)) {
                return;
            }
        }

        // Se cerró una sesión anterior de esta misma rama sin promocionar y
        // se reabrió otra para seguir (p.ej. "subir" vuelve a abrir sola una
        // sesión cuando no hay ninguna viva en el directorio). Sin `bajar` de
        // por medio no hay descarga que cuadrar, pero la cadena es igual de
        // verificable: el hash_padre declarado coincide con la última subida
        // real de la rama, que es de lo único que se pudo partir. Si no
        // coincidiera sería la fila 4 de la tabla 4.3 (divergencia) y
        // seguiría rechazándose más abajo — esto no relaja esa comprobación,
        // solo reconoce una cadena que antes no tenía por dónde entrar.
        if ($ultimaSubidaRama && (int) $ultimaSubidaRama['id'] !== (int) $sesion['id']
            && hash_equals((string) $ultimaSubidaRama['hash_blend'], $hashPadre)) {
            return;
        }

        throw new RuntimeException(
            'No hay ninguna descarga abierta de esta máquina en esta rama con la que cuadrar tu subida. '
            . 'Si tienes ese fichero de otra descarga anterior, ciérrala o baja de nuevo antes de subir.',
            409
        );
    }

    /**
     * Cierre forzado de una sesión que nunca pasó por una descarga (spec
     * 4.4, extendido): "trackbitos abrir" en una variante estrenada, o el
     * reabrir-sola que hace "subir" cuando no hay sesión viva en el
     * directorio, no crean ningún asiento — así que si el disco que la
     * abrió desaparece (se borra la carpeta de trabajo sin subir ni
     * cerrar), `forzarCierre` no tiene ningún `descargaId` al que agarrarse
     * y el bloqueo (invariante 3) se quedaría para siempre sin válvula de
     * escape. Esto es esa válvula para ese caso: mismo criterio que el
     * cierre forzado de una descarga (motivo obligatorio, sin prueba de
     * que no se perdiera trabajo), pero sobre la sesión directamente.
     *
     * Se niega si la sesión sí tiene una descarga abierta: esa descarga
     * necesita su propio cierre (con su propio `cerrada_por`), y cerrar la
     * sesión por aquí la dejaría huérfana, abierta para siempre sin nada
     * que la referencie.
     */
    public function forzarCierreSesion(int $sesionId, string $motivo): array
    {
        if (trim($motivo) === '') {
            throw new RuntimeException(
                'El cierre forzado exige un motivo escrito: es la única constancia de por qué se cerró sin prueba.',
                422
            );
        }

        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.", 404);
        }
        if (!empty($sesion['cerrada_en'])) {
            throw new RuntimeException("La sesión {$sesionId} ya está cerrada.", 409);
        }

        $descargaAbierta = $this->descargaModel->where('sesion_id', $sesionId)->where('cerrada', 0)->first();
        if ($descargaAbierta) {
            throw new RuntimeException(
                "Esta sesión tiene la descarga {$descargaAbierta['id']} sin cerrar. Usa el cierre forzado de esa "
                . 'descarga: se lleva la sesión por delante y deja un único registro, no dos sueltos.',
                409
            );
        }

        return $this->transaccion('forzar el cierre de la sesión', function () use ($sesion, $motivo) {
            $this->sesionModel->update($sesion['id'], [
                'cerrada_en' => date('Y-m-d H:i:s'),
                'log'        => trim(($sesion['log'] ? $sesion['log'] . "\n" : '')
                    . 'Cerrada por cierre forzado (sin descarga asociada): ' . trim($motivo)),
            ]);

            return $this->sesionModel->find($sesion['id']);
        });
    }

    private function cerrarSesionAsociada(array $descarga, string $nota): ?array
    {
        if (empty($descarga['sesion_id'])) {
            return null;
        }

        $sesion = $this->sesionModel->find($descarga['sesion_id']);
        if (!$sesion || !empty($sesion['cerrada_en'])) {
            return $sesion ?: null;
        }

        $this->sesionModel->update($sesion['id'], [
            'cerrada_en' => date('Y-m-d H:i:s'),
            'log'        => trim(($sesion['log'] ? $sesion['log'] . "\n" : '') . $nota),
        ]);

        return $this->sesionModel->find($sesion['id']);
    }

    private function exigirDescargaAbierta(int $descargaId): array
    {
        $descarga = $this->descargaModel->find($descargaId);
        if (!$descarga) {
            throw new RuntimeException("Descarga {$descargaId} no encontrada.", 404);
        }
        if (!empty($descarga['cerrada'])) {
            throw new RuntimeException(
                "La descarga {$descargaId} ya está cerrada ({$descarga['cerrada_por']}, {$descarga['cerrada_en']}). "
                . 'Los asientos no se reabren.',
                409
            );
        }

        return $descarga;
    }

    private function exigirMaquina(int $maquinaId): array
    {
        $maquina = $this->maquinaModel->find($maquinaId);
        if (!$maquina) {
            throw new RuntimeException("Máquina {$maquinaId} no encontrada.", 404);
        }

        return $maquina;
    }

    private function mensajePendientes(array $pendientes): string
    {
        $lineas = array_map(function ($d) {
            $maquina = $this->maquinaModel->find($d['maquina_id']);
            $nombre  = $maquina['nombre'] ?? "máquina {$d['maquina_id']}";
            $fecha   = date('d/m H:i', strtotime($d['descargado_en']));

            return "{$nombre} — {$fecha}, motivo: {$d['motivo']}";
        }, $pendientes);

        return 'Hay una descarga sin cerrar en otra máquina (' . implode('; ', $lineas) . '). '
            . 'Esa copia nunca se cerró: si trabajaste ahí, entregarte otra ahora perdería ese trabajo. '
            . 'Revísala antes de continuar, o repite con ignorar_pendiente=1 si sabes que no la tocaste.';
    }

    /**
     * Nombre con el que el fichero aterriza en el disco del usuario. Legible
     * (lo va a ver en Blender), pero saneado: los nombres son texto libre y
     * pueden traer acentos, espacios o barras.
     *
     * Lleva la FAMILIA delante, no solo la variante: el nombre de la
     * variante suele ser una etiqueta genérica ("estandar") que se repite
     * entre piezas, así que por sí solo no distingue nada en cuanto tienes
     * dos .blend abiertos o dos carpetas al lado. El SKU va primero cuando
     * lo hay, para que ordene igual que las descargas de la web.
     */
    private function nombreFichero(array $variante, array $rama, ?array $sesion): string
    {
        $familia = (new PiezaFamiliaModel())->find($variante['familia_id']);

        $partes = array_filter([
            $this->paraNombreDeArchivo($variante['sku'] ?? null),
            $this->paraNombreDeArchivo($familia['nombre'] ?? null),
            $this->paraNombreDeArchivo($variante['nombre']) ?: 'variante',
        ]);
        $base = implode('-', $partes);

        return $sesion
            ? sprintf('%s-r%d-s%03d.blend', $base, (int) $rama['id'], (int) $sesion['numero'])
            : sprintf('%s-r%d-consulta.blend', $base, (int) $rama['id']);
    }

    private function paraNombreDeArchivo(?string $texto): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $texto), '-');
    }

    private function transaccion(string $accion, callable $pasos)
    {
        $db = db_connect();
        $db->transStart();

        try {
            $resultado = $pasos();
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException("No se pudo {$accion}: fallo de transacción.");
        }

        return $resultado;
    }
}
