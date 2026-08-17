<?php

namespace App\Services;

use App\Models\PiezaFamiliaModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Los verbos del dominio de Piezas (spec sección 3). Cada método es la
 * transacción atómica completa de una acción de usuario; los invariantes
 * 1-4 ya viven en los modelos (ver Pieza*Model), aquí se añade la parte
 * de "verbo" propiamente dicha: qué pasos concretos ejecuta cada acción
 * y qué transición de estado exige antes de dejarla pasar.
 *
 * Sin interfaz todavía (fase 6): se prueba por consola/seeder.
 */
class PiezaService
{
    private PiezaFamiliaModel $familiaModel;
    private PiezaVarianteModel $varianteModel;
    private PiezaVersionModel $versionModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;

    public function __construct()
    {
        $this->familiaModel  = new PiezaFamiliaModel();
        $this->varianteModel = new PiezaVarianteModel();
        $this->versionModel  = new PiezaVersionModel();
        $this->ramaModel     = new PiezaRamaModel();
        $this->sesionModel   = new PiezaSesionModel();
    }

    public function crearFamilia(string $nombre, ?string $notas = null): array
    {
        $id = $this->insertarOFallar($this->familiaModel, ['nombre' => $nombre, 'notas' => $notas]);

        return $this->familiaModel->find($id);
    }

    /**
     * Crea la variante y le abre de una vez su rama inicial (desde_version_id
     * NULL): sin rama abierta no habría dónde abrir la primera sesión.
     */
    public function crearVariante(int $familiaId, string $nombre, ?string $notas = null, ?string $sku = null): array
    {
        if (!$this->familiaModel->find($familiaId)) {
            throw new RuntimeException("Familia {$familiaId} no encontrada.");
        }
        $sku = $this->normalizarSkuOFallar($sku);

        $varianteId = $this->transaccion('crear la variante', function () use ($familiaId, $nombre, $notas, $sku) {
            $varianteId = $this->insertarOFallar($this->varianteModel, [
                'familia_id' => $familiaId,
                'nombre'     => $nombre,
                'notas'      => $notas,
                'sku'        => $sku,
            ]);

            $this->ramaModel->abrir($varianteId);

            return $varianteId;
        });

        return $this->varianteModel->find($varianteId);
    }

    /**
     * El SKU es lo único de la variante que se puede editar libremente
     * después de crearla (nombre y familia no tienen verbo de edición
     * todavía: no hacía falta hasta ahora). Es una referencia manual —
     * Trackbitos no sincroniza con la tienda, solo guarda el mismo código.
     */
    public function actualizarSku(int $varianteId, ?string $sku): array
    {
        if (!$this->varianteModel->find($varianteId)) {
            throw new RuntimeException("Variante {$varianteId} no encontrada.");
        }

        $sku = $this->normalizarSkuOFallar($sku, $varianteId);
        $this->varianteModel->update($varianteId, ['sku' => $sku]);

        return $this->varianteModel->find($varianteId);
    }

    private function normalizarSkuOFallar(?string $sku, ?int $excluirVarianteId = null): ?string
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return null;
        }

        $query = $this->varianteModel->where('sku', $sku);
        if ($excluirVarianteId !== null) {
            $query->where('id !=', $excluirVarianteId);
        }
        $existente = $query->first();
        if ($existente) {
            throw new RuntimeException("El SKU '{$sku}' ya lo tiene \"{$existente['nombre']}\".");
        }

        return $sku;
    }

    /**
     * "Derivar variante": nueva línea de diseño a partir de una versión ya
     * existente (de la misma familia o de otra). No copia ficheros ni
     * referencias — numeración de versiones propia desde v001.
     */
    public function derivarVariante(int $origenVersionId, string $nombre, ?string $notas = null): array
    {
        $origen = $this->versionModel->find($origenVersionId);
        if (!$origen) {
            throw new RuntimeException("Versión de origen {$origenVersionId} no encontrada.");
        }
        $varianteOrigen = $this->varianteModel->find($origen['variante_id']);

        $varianteId = $this->transaccion('derivar la variante', function () use ($origenVersionId, $nombre, $notas, $varianteOrigen) {
            $varianteId = $this->insertarOFallar($this->varianteModel, [
                'familia_id'        => $varianteOrigen['familia_id'],
                'nombre'            => $nombre,
                'origen_version_id' => $origenVersionId,
                'notas'             => $notas,
            ]);

            $this->ramaModel->abrir($varianteId, $origenVersionId);

            return $varianteId;
        });

        return $this->varianteModel->find($varianteId);
    }

    /**
     * "Abrir sesión": reclama la máquina. Requiere que la variante tenga
     * una rama abierta (la crea crearVariante/promocionar/devolverATrabajo,
     * nunca este método). Falla si ya hay una sesión sin cerrar
     * (invariante 3, aplicado dentro de PiezaSesionModel::abrir).
     */
    public function abrirSesion(int $varianteId, int $maquinaId): array
    {
        $rama = $this->ramaModel->abiertaDe($varianteId);
        if (!$rama) {
            throw new RuntimeException(
                "La variante {$varianteId} no tiene ninguna rama de trabajo abierta. Esto no debería pasar: "
                . 'toda variante nace con una, y promocionar/devolver a trabajo siempre dejan una abierta.'
            );
        }

        return $this->sesionModel->abrir($rama['id'], $maquinaId);
    }

    /**
     * "Subir sesión": guarda el .blend de una sesión ya abierta. El cálculo
     * del hash, el guardado físico del fichero y el cuadre del asiento de
     * descarga son responsabilidad de PiezaSyncService, que llama aquí —
     * este método solo persiste los datos ya verificados.
     */
    public function subirSesion(int $sesionId, string $rutaBlend, string $hashBlend, int $tamanoBytes, ?string $log = null, ?string $hashPadre = null): array
    {
        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.");
        }

        $datos = [
            'ruta_blend'   => $rutaBlend,
            'hash_blend'   => $hashBlend,
            'tamano_bytes' => $tamanoBytes,
            'hash_padre'   => $hashPadre,
            'subida_en'    => date('Y-m-d H:i:s'),
        ];
        // Una segunda subida sin nota no debe borrar la nota de la primera.
        if ($log !== null) {
            $datos['log'] = $log;
        }

        $this->sesionModel->update($sesionId, $datos);

        return $this->sesionModel->find($sesionId);
    }

    /**
     * "Cerrar sesión": libera el bloqueo de máquina. No exige que se haya
     * subido nada — cerrar sin subir es legítimo (p.ej. sesión de consulta).
     */
    public function cerrarSesion(int $sesionId): array
    {
        if (!$this->sesionModel->find($sesionId)) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.");
        }

        return $this->sesionModel->cerrar($sesionId);
    }

    /**
     * "Promocionar": crea la version con el .blend de la última sesión
     * subida de la rama abierta, la cierra, y abre la rama siguiente
     * ("desde-vNNN"). Exige `cambio` no vacío (lo valida PiezaVersionModel).
     */
    public function promocionar(int $varianteId, string $cambio, ?string $medidas = null): array
    {
        $rama = $this->ramaModel->abiertaDe($varianteId);
        if (!$rama) {
            throw new RuntimeException("La variante {$varianteId} no tiene ninguna rama abierta que promocionar.");
        }

        // Promocionar con una sesión viva dejaría el bloqueo colgando de una
        // rama ya cerrada, y la rama nueva nacería inutilizable (invariante 3
        // se comprueba por variante, no por rama). Se niega y explica.
        if ($this->sesionModel->hayAbiertaParaVariante($varianteId)) {
            throw new RuntimeException(
                'Hay una sesión de trabajo sin cerrar en esta variante. Súbela y ciérrala antes de promocionar: '
                . 'lo que quede sin subir no entraría en la versión.'
            );
        }

        $ultimaSubida = $this->sesionModel->ultimaSubida((int) $rama['id']);
        if (!$ultimaSubida) {
            throw new RuntimeException(
                'No hay ninguna sesión subida en esta rama todavía. Sube el .blend antes de promocionar '
                . '— promocionar sin fichero dejaría una versión sin contenido real.'
            );
        }

        // La versión se lleva su propia copia del fichero, no la ruta de la
        // sesión: las sesiones se purgan al validar (invariante 5) y esa purga
        // se llevaría por delante justo el fichero que nunca debe perderse.
        $numero      = $this->versionModel->siguienteNumero($varianteId);
        $almacen     = new PiezaAlmacen();
        $rutaVersion = $almacen->rutaVersion($varianteId, $numero);

        if (!$almacen->existe($ultimaSubida['ruta_blend'])) {
            throw new RuntimeException(
                "El .blend de la sesión {$ultimaSubida['numero']} no está en el almacén "
                . "({$ultimaSubida['ruta_blend']}). No se promociona una versión sin fichero real detrás."
            );
        }
        $almacen->copiar($ultimaSubida['ruta_blend'], $rutaVersion);

        try {
            $versionId = $this->transaccion('promocionar', function () use ($varianteId, $numero, $cambio, $medidas, $rama, $ultimaSubida, $rutaVersion) {
                $versionId = $this->insertarOFallar($this->versionModel, [
                    'variante_id'     => $varianteId,
                    'numero'          => $numero,
                    'estado'          => 'borrador',
                    'promocionada_en' => date('Y-m-d H:i:s'),
                    'ruta_blend'      => $rutaVersion,
                    'hash_blend'      => $ultimaSubida['hash_blend'],
                    'cambio'          => $cambio,
                    'medidas'         => $medidas,
                ]);

                $this->ramaModel->cerrar($rama['id'], $versionId);
                $this->ramaModel->abrir($varianteId, $versionId);

                return $versionId;
            });
        } catch (Throwable $e) {
            $almacen->descartarEscritura($rutaVersion);

            throw $e;
        }

        return $this->versionModel->find($versionId);
    }

    /**
     * "Devolver a trabajo": abre una rama nueva partiendo de una versión ya
     * existente, sin tocarla (las versiones son inmutables). Típicamente
     * para retomar una versión superada/descartada, o iterar sobre la
     * validada actual.
     *
     * Siempre hay una rama abierta (promocionar cierra una y abre otra), así
     * que retomar una versión antigua implica necesariamente abandonar la
     * línea en curso. Eso es destructivo y ambiguo, así que por defecto se
     * niega y explica cuánto trabajo dejaría atrás; con $abandonarRama el
     * usuario ya sabe lo que hace. Las sesiones no se pierden: quedan
     * colgando de la rama cerrada, con su historial intacto.
     */
    public function devolverATrabajo(int $versionId, bool $abandonarRama = false): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        $varianteId = (int) $version['variante_id'];

        if ($this->sesionModel->hayAbiertaParaVariante($varianteId)) {
            throw new RuntimeException(
                'Hay una sesión de trabajo sin cerrar en esta variante. Ciérrala antes de cambiar de línea de trabajo.'
            );
        }

        $ramaAbierta = $this->ramaModel->abiertaDe($varianteId);

        if ($ramaAbierta && !$abandonarRama) {
            $subidas = count($this->sesionModel
                ->where('rama_id', $ramaAbierta['id'])
                ->where('subida_en IS NOT NULL')
                ->findAll());

            throw new RuntimeException(sprintf(
                'La rama "%s" sigue abierta con %d sesión(es) subida(s) sin promocionar. Volver a la v%03d '
                . 'la cerraría sin convertirla en versión: ese trabajo quedaría solo en el historial.',
                $this->ramaModel->nombre($ramaAbierta),
                $subidas,
                (int) $version['numero']
            ));
        }

        return $this->transaccion('devolver a trabajo', function () use ($ramaAbierta, $varianteId, $versionId) {
            if ($ramaAbierta) {
                // Sin versión que la cierre: la rama se abandona, no se promociona.
                $this->ramaModel->cerrar((int) $ramaAbierta['id']);
            }

            return $this->ramaModel->abrir($varianteId, $versionId);
        });
    }

    /**
     * Adjunta el STL de una versión para poder imprimirla. Aparte de
     * promocionar: el usuario lo exporta desde Blender cuando le hace
     * falta, no siempre en el mismo momento en que sube el .blend. Una vez
     * puesto es inmutable (PiezaVersionModel::CAMPOS_INMUTABLES) — si hace
     * falta otro STL, es porque el modelo cambió, y eso es una versión
     * nueva, no un reemplazo silencioso del que ya se imprimió con este.
     */
    public function adjuntarStl(int $versionId, string $rutaRelativa, string $hash): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }
        if (!empty($version['ruta_stl'])) {
            throw new RuntimeException(
                "La versión {$versionId} ya tiene un STL adjunto. Es inmutable, como el .blend: "
                . 'si el modelo cambió, promociona una versión nueva.'
            );
        }

        $this->versionModel->update($versionId, [
            'ruta_stl' => $rutaRelativa,
            'hash_stl' => $hash,
        ]);

        return $this->versionModel->find($versionId);
    }

    /**
     * "Marcar impresa": borrador -> impresa, con los parámetros usados.
     */
    public function marcarImpresa(int $versionId, ?string $paramsImpresion = null): array
    {
        $version = $this->exigirEstado($versionId, ['borrador'], 'marcar como impresa');

        $this->versionModel->update($versionId, [
            'estado'           => 'impresa',
            'params_impresion' => $paramsImpresion ?? $version['params_impresion'],
        ]);

        return $this->versionModel->find($versionId);
    }

    /**
     * "Validar": impresa -> validada. Degrada la anterior validada de la
     * misma variante a superada (invariante 1, PiezaVersionModel::marcarValidada)
     * y habilita la purga de las sesiones que llevaron hasta ella.
     */
    public function validar(int $versionId, ?string $resultado = null): array
    {
        $this->exigirEstado($versionId, ['impresa'], 'validar');

        $version = $this->versionModel->marcarValidada($versionId, $resultado);

        // Invariante 5: las sesiones se purgan al VALIDAR, no al promocionar.
        // Si la impresión sale mal, los .blend intermedios aún hacen falta
        // para entender qué se probó; una vez la pieza física funciona, ya
        // no. Va fuera de la transacción de marcarValidada a propósito: mover
        // ficheros no es reversible con un rollback, y un fallo purgando no
        // debe deshacer una validación que es correcta.
        $this->purgarSesionesDe($versionId);

        return $version;
    }

    /**
     * Aparta los .blend de las sesiones de la rama que cerró esta versión.
     * Las filas NO se borran: se marcan `purgada` y conservan número, hashes,
     * máquina y log. Lo que ocupa sitio es el fichero; lo que da valor al
     * historial es el registro, y eso se queda.
     *
     * @return int cuántas sesiones se purgaron
     */
    public function purgarSesionesDe(int $versionId): int
    {
        $rama = $this->ramaModel->where('cerrada_por_version_id', $versionId)->first();
        if (!$rama) {
            return 0;
        }

        $almacen  = new PiezaAlmacen();
        $purgadas = 0;

        foreach ($this->sesionModel->where('rama_id', $rama['id'])->where('purgada', 0)->findAll() as $sesion) {
            $datos = ['purgada' => 1];

            if (!empty($sesion['ruta_blend'])) {
                $enPapelera = $almacen->aPapelera($sesion['ruta_blend']);
                if ($enPapelera !== null) {
                    $datos['ruta_blend'] = $enPapelera;
                }
            }

            $this->sesionModel->update($sesion['id'], $datos);
            $purgadas++;
        }

        return $purgadas;
    }

    /**
     * "Descartar": no sirve, pero no se borra — se conserva con el motivo.
     * Solo desde borrador/impresa: una versión ya validada/superada/
     * descartada tiene su propio historial, no se tapa con un descarte.
     */
    public function descartar(int $versionId, string $resultado): array
    {
        if (trim($resultado) === '') {
            throw new RuntimeException('Descartar exige un motivo en "resultado": no se borra nada sin dejar constancia de por qué.');
        }

        $this->exigirEstado($versionId, ['borrador', 'impresa'], 'descartar');

        $this->versionModel->update($versionId, ['estado' => 'descartada', 'resultado' => $resultado]);

        return $this->versionModel->find($versionId);
    }

    /**
     * Comprueba que la versión está en uno de los estados de partida
     * permitidos para la acción; si no, se niega y explica en qué estado
     * está realmente, en vez de dejar pasar una transición inválida.
     */
    private function exigirEstado(int $versionId, array $permitidos, string $accion): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        if (!in_array($version['estado'], $permitidos, true)) {
            throw new RuntimeException(
                "No se puede {$accion} la versión {$versionId}: está en estado '{$version['estado']}', "
                . 'y esta acción solo es válida desde ' . implode('/', $permitidos) . '.'
            );
        }

        return $version;
    }

    /**
     * insert() de CodeIgniter no lanza excepción si falla la validación:
     * devuelve false en silencio. Aquí se convierte en un fallo explícito
     * con el motivo, para no arrastrar un id falso (false/0) a los pasos
     * siguientes de un verbo — que es exactamente lo que rompía una
     * transacción a medias antes de este helper.
     */
    private function insertarOFallar(Model $model, array $datos): int
    {
        $id = $model->insert($datos, true);
        if (!$id) {
            $errores = $model->errors();
            throw new RuntimeException(
                'No se pudo guardar: ' . ($errores ? implode(' ', $errores) : 'motivo desconocido.')
            );
        }

        return (int) $id;
    }

    /**
     * Ejecuta $pasos dentro de una transacción; si algo lanza excepción a
     * mitad de camino, hace rollback explícito antes de relanzarla, para
     * que la conexión quede limpia y la siguiente operación no herede una
     * transacción a medias.
     */
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
