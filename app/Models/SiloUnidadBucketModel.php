<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Qué años (Nivel 2) o categorías (Nivel 3) vive en una unidad física —
 * relación 1:N desde que Nivel 2 puede agrupar varios años consecutivos por
 * unidad (docs/silo-ingesta-propagacion.md, planificación por capacidad de
 * USB). Ver SiloPropagacionService::calcularPlanNivel2()/aplicarPlanNivel2().
 */
class SiloUnidadBucketModel extends Model
{
    protected $table         = 'silo_unidad_buckets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['unidad_id', 'bucket'];

    /** Buckets (años/categorías) de una unidad, ordenados. */
    public function bucketsDe(int $unidadId): array
    {
        return array_column(
            $this->where('unidad_id', $unidadId)->orderBy('bucket', 'ASC')->findAll(),
            'bucket'
        );
    }

    /** Da de alta el bucket en la unidad si no lo tenía ya (idempotente). */
    public function asignarBucket(int $unidadId, string $bucket): void
    {
        $existe = $this->where('unidad_id', $unidadId)->where('bucket', $bucket)->first();
        if (!$existe) {
            $this->insert(['unidad_id' => $unidadId, 'bucket' => $bucket]);
        }
    }
}
