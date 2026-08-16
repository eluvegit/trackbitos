<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Línea de trabajo abierta partiendo de una versión. Nunca se edita una
 * versión existente; se apila encima en una rama nueva. Nombre visible
 * derivado ("desde-v002"), no se guarda como texto.
 */
class PiezaRamaModel extends Model
{
    protected $table         = 'piezas_ramas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'variante_id', 'desde_version_id', 'abierta',
        'cerrada_por_version_id', 'abierta_en', 'cerrada_en',
    ];

    public function hayAbierta(int $varianteId): bool
    {
        return (bool) $this->where('variante_id', $varianteId)->where('abierta', 1)->first();
    }

    public function abiertaDe(int $varianteId): ?array
    {
        return $this->where('variante_id', $varianteId)->where('abierta', 1)->first();
    }

    /**
     * Nombre visible ("desde-v002"), derivado del número de versión de
     * origen — nunca se guarda como texto (sección 1.1 de la spec).
     */
    public function nombre(array $rama): string
    {
        if (empty($rama['desde_version_id'])) {
            return 'inicial';
        }

        $version = (new PiezaVersionModel())->find($rama['desde_version_id']);

        return $version ? sprintf('desde-v%03d', $version['numero']) : 'inicial';
    }

    /**
     * Invariante 2: como mucho una rama abierta por variante. Se niega y
     * explica si ya hay una, en vez de dejar abrir una segunda en silencio.
     */
    public function abrir(int $varianteId, ?int $desdeVersionId = null): array
    {
        $db = $this->db;
        $db->transStart();
        $db->query('SELECT id FROM piezas_ramas WHERE variante_id = ? FOR UPDATE', [$varianteId]);

        if ($this->hayAbierta($varianteId)) {
            $db->transComplete();

            throw new RuntimeException(
                "La variante {$varianteId} ya tiene una rama de trabajo abierta. "
                . 'Ciérrala (promocionando, o descartando el trabajo) antes de abrir otra.'
            );
        }

        $id = $this->insert([
            'variante_id'      => $varianteId,
            'desde_version_id' => $desdeVersionId,
            'abierta'          => 1,
            'abierta_en'       => date('Y-m-d H:i:s'),
        ], true);

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new RuntimeException('No se pudo abrir la rama: fallo de transacción.');
        }

        return $this->find($id);
    }

    public function cerrar(int $ramaId, ?int $cerradaPorVersionId = null): array
    {
        $this->update($ramaId, [
            'abierta'                => 0,
            'cerrada_por_version_id' => $cerradaPorVersionId,
            'cerrada_en'             => date('Y-m-d H:i:s'),
        ]);

        return $this->find($ramaId);
    }
}
