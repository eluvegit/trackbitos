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
    public function crearVariante(int $familiaId, string $nombre, ?string $notas = null): array
    {
        if (!$this->familiaModel->find($familiaId)) {
            throw new RuntimeException("Familia {$familiaId} no encontrada.");
        }

        $varianteId = $this->transaccion('crear la variante', function () use ($familiaId, $nombre, $notas) {
            $varianteId = $this->insertarOFallar($this->varianteModel, [
                'familia_id' => $familiaId,
                'nombre'     => $nombre,
                'notas'      => $notas,
            ]);

            $this->ramaModel->abrir($varianteId);

            return $varianteId;
        });

        return $this->varianteModel->find($varianteId);
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
     * del hash y el guardado físico del fichero son responsabilidad del
     * llamador (API, fase 5) — aquí solo se persisten los datos ya conocidos.
     */
    public function subirSesion(int $sesionId, string $rutaBlend, string $hashBlend, int $tamanoBytes, ?string $log = null): array
    {
        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion) {
            throw new RuntimeException("Sesión {$sesionId} no encontrada.");
        }

        $datos = [
            'ruta_blend'   => $rutaBlend,
            'hash_blend'   => $hashBlend,
            'tamano_bytes' => $tamanoBytes,
            'subida_en'    => date('Y-m-d H:i:s'),
        ];
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

        $ultimaSubida = $this->sesionModel->ultimaSubida((int) $rama['id']);
        if (!$ultimaSubida) {
            throw new RuntimeException(
                'No hay ninguna sesión subida en esta rama todavía. Sube el .blend antes de promocionar '
                . '— promocionar sin fichero dejaría una versión sin contenido real.'
            );
        }

        $versionId = $this->transaccion('promocionar', function () use ($varianteId, $cambio, $medidas, $rama, $ultimaSubida) {
            $versionId = $this->insertarOFallar($this->versionModel, [
                'variante_id'     => $varianteId,
                'numero'          => $this->versionModel->siguienteNumero($varianteId),
                'estado'          => 'borrador',
                'promocionada_en' => date('Y-m-d H:i:s'),
                'ruta_blend'      => $ultimaSubida['ruta_blend'],
                'hash_blend'      => $ultimaSubida['hash_blend'],
                'cambio'          => $cambio,
                'medidas'         => $medidas,
            ]);

            $this->ramaModel->cerrar($rama['id'], $versionId);
            $this->ramaModel->abrir($varianteId, $versionId);

            return $versionId;
        });

        return $this->versionModel->find($versionId);
    }

    /**
     * "Devolver a trabajo": abre una rama nueva partiendo de una versión ya
     * existente, sin tocarla (las versiones son inmutables). Típicamente
     * para retomar una versión superada/descartada, o iterar sobre la
     * validada actual.
     */
    public function devolverATrabajo(int $versionId): array
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        return $this->ramaModel->abrir((int) $version['variante_id'], $versionId);
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
     * misma variante a superada (invariante 1, PiezaVersionModel::marcarValidada).
     */
    public function validar(int $versionId, ?string $resultado = null): array
    {
        $this->exigirEstado($versionId, ['impresa'], 'validar');

        return $this->versionModel->marcarValidada($versionId, $resultado);
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
