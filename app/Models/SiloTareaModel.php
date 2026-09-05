<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Cola del agente `.py` (ver docs/silo-ingesta-propagacion.md). Dos vías de
 * alta: el propio agente al auto-reportar un escaneo (Silo\Agente::escaneo,
 * sin tarea previa) y la web al pedir un escaneo bajo demanda
 * (Web::solicitarEscaneo, tipo `escaneo_maestro`, auto-aprobada porque la
 * pide el dueño de la unidad). Aprobación humana para tareas más sensibles
 * (mover piezas entre unidades, propagación física) sigue siendo un hito
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

    /** Ya hay una tarea de este tipo esperando (o en curso) para esta unidad, para no duplicar la petición desde la web. */
    public function pendienteDeUnidad(int $unidadId, string $tipo): ?array
    {
        return $this->where('unidad_id', $unidadId)
            ->where('tipo', $tipo)
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->orderBy('id', 'DESC')
            ->first();
    }

    /** Última tarea (de cualquier estado) de una unidad, para pintar su estado en /silo/unidades. */
    public function ultimaDeUnidad(int $unidadId, string $tipo): ?array
    {
        return $this->where('unidad_id', $unidadId)
            ->where('tipo', $tipo)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /** Encolada por la web (botón "Solicitar escaneo"): se auto-aprueba, la pide el propio dueño de la unidad. */
    public function crear(int $unidadId, string $tipo): array
    {
        $id = $this->insert([
            'unidad_id' => $unidadId,
            'tipo'      => $tipo,
            'estado'    => 'pendiente',
            'aprobada'  => 1,
        ], true);

        return $this->find($id);
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
