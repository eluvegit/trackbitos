<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Asiento de sincronización descarga->subida (invariante 8, spec 4.4).
 * Append-only: nunca se borra, solo se cierra. La lógica de cuadre
 * (hash_padre contra hash_entregado) llega en la fase de API; por ahora
 * es CRUD simple para poder probar el resto del modelo.
 */
class PiezaDescargaModel extends Model
{
    protected $table         = 'piezas_descargas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'sesion_id', 'variante_id', 'rama_id', 'maquina_id',
        'motivo', 'descargado_en', 'hash_entregado',
        'cerrada', 'cerrada_en', 'cerrada_por', 'cerrada_sesion_id', 'motivo_forzado',
    ];

    public function abiertasParaVariante(int $varianteId): array
    {
        return $this->where('variante_id', $varianteId)->where('cerrada', 0)->findAll();
    }
}
