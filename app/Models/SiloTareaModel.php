<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Cola del agente `.py` (ver docs/silo-ingesta-propagacion.md). Por ahora
 * solo la usa Silo\Agente::escaneo() para dejar rastro de cada pasada
 * auto-reportada; la aprobación humana / disparo desde la web es un hito
 * posterior.
 */
class SiloTareaModel extends Model
{
    protected $table         = 'silo_tareas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creado_en';
    protected $updatedField  = 'actualizado_en';

    protected $allowedFields = ['unidad_id', 'tipo', 'payload', 'estado', 'aprobada', 'resultado', 'error'];

    /** Tareas todavía sin resolver de una unidad (o globales, unidad_id nulo), para el handshake. */
    public function pendientesDeUnidad(int $unidadId): array
    {
        return $this->groupStart()
                ->where('unidad_id', $unidadId)
                ->orWhere('unidad_id', null)
            ->groupEnd()
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function marcarResultado(int $id, array $resultado, ?string $error = null): void
    {
        $this->update($id, [
            'estado'    => $error !== null ? 'error' : 'hecha',
            'resultado' => json_encode($resultado, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'error'     => $error,
        ]);
    }
}
