<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Un estado congelado y consolidado de una variante. Creada al promocionar,
 * inmutable en sus campos de fichero (invariante 4). El ciclo de estados
 * vive en $table.estado: borrador -> impresa -> validada/descartada;
 * validada -> superada (automático al validar otra, invariante 1).
 */
class PiezaVersionModel extends Model
{
    protected $table         = 'piezas_versiones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    public const ESTADOS = ['borrador', 'impresa', 'validada', 'superada', 'descartada'];

    /**
     * Congelados desde la creación (invariante 4): identifican el fichero
     * exacto de esa versión. numero incluido porque reordenar versiones
     * rompería el historial.
     *
     * Los STL ya no están aquí: desde la fase 21 viven en su propia tabla
     * (`piezas_version_stls`), porque una versión puede tener varios. Su
     * inmutabilidad se aplica allí, en PiezaService::adjuntarStl().
     */
    private const CAMPOS_INMUTABLES = ['ruta_blend', 'hash_blend', 'numero'];

    protected $allowedFields = [
        'variante_id', 'numero', 'estado', 'promocionada_en',
        'ruta_blend', 'hash_blend',
        'cambio', 'medidas', 'params_impresion', 'resultado',
    ];

    protected $validationRules = [
        'variante_id' => 'required|integer',
        'numero'      => 'required|integer',
        'estado'      => 'permit_empty|in_list[borrador,impresa,validada,superada,descartada]',
        'ruta_blend'  => 'required|max_length[500]',
        'hash_blend'  => 'required|max_length[64]',
        // Invariante 7: el cambio es obligatorio, nunca vacío.
        'cambio'      => 'required',
    ];

    /**
     * Bloquea la modificación de los campos congelados de una versión ya
     * creada. Se niega y explica en vez de dejar pasar la escritura.
     *
     * Solo se bloquea sobreescribir un valor que ya estaba puesto: la
     * PRIMERA asignación de un campo vacío no cuenta como cambio.
     */
    public function update($id = null, $data = null): bool
    {
        if ($id !== null && is_array($data)) {
            $actual = $this->find($id);
            if ($actual) {
                foreach (self::CAMPOS_INMUTABLES as $campo) {
                    $valorActual = $actual[$campo] ?? null;
                    $yaEstabaPuesto = $valorActual !== null && $valorActual !== '';

                    if ($yaEstabaPuesto && array_key_exists($campo, $data) && (string) $data[$campo] !== (string) $valorActual) {
                        throw new RuntimeException(
                            "La versión {$id} es inmutable: no se puede cambiar '{$campo}' "
                            . "(de '{$actual[$campo]}' a '{$data[$campo]}'). Crea una versión nueva."
                        );
                    }
                }
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Invariante 1: como mucho una versión "validada" por variante. Degrada
     * la anterior a "superada" y sube la nueva, en la misma transacción.
     */
    public function marcarValidada(int $versionId, ?string $resultado = null): array
    {
        $version = $this->find($versionId);
        if (!$version) {
            throw new RuntimeException("Versión {$versionId} no encontrada.");
        }

        $db = $this->db;
        $db->transStart();

        // Bloquea todas las versiones de la variante para que dos validaciones
        // concurrentes no puedan dejar dos "validada" a la vez.
        $db->query('SELECT id FROM piezas_versiones WHERE variante_id = ? FOR UPDATE', [$version['variante_id']]);

        $anteriorValidada = $this->where('variante_id', $version['variante_id'])
            ->where('estado', 'validada')
            ->where('id !=', $versionId)
            ->first();

        if ($anteriorValidada) {
            $this->update($anteriorValidada['id'], ['estado' => 'superada']);
        }

        $datos = ['estado' => 'validada'];
        if ($resultado !== null) {
            $datos['resultado'] = $resultado;
        }
        $this->update($versionId, $datos);

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException('No se pudo validar la versión: fallo de transacción.');
        }

        return $this->find($versionId);
    }

    public function siguienteNumero(int $varianteId): int
    {
        $fila = $this->where('variante_id', $varianteId)->selectMax('numero')->first();

        return ((int) ($fila['numero'] ?? 0)) + 1;
    }
}
