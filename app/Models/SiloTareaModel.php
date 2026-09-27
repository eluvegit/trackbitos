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

    /**
     * Tipos de tarea que corrigen una Copia 2/3 que se quedó con el nombre
     * o el cubo viejo tras renombrar la carpeta en el Maestro (ver
     * SiloPropagacionService::sincronizarCopia). Su payload empieza SIEMPRE
     * por `ubicacion_id` y luego `pieza_id` — así se buscan con un LIKE
     * sobre el JSON sin tipo JSON nativo.
     */
    public const TIPOS_REUBICACION = ['renombrar_copia', 'mover_copia'];

    /** La corrección pendiente de UNA ubicación (a lo sumo hay una). */
    public function reubicacionPendienteDeUbicacion(int $ubicacionId): ?array
    {
        return $this->whereIn('tipo', self::TIPOS_REUBICACION)
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->like('payload', '{"ubicacion_id":' . $ubicacionId . ',', 'after')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Correcciones pendientes de las copias de una pieza, indexadas por
     * ubicacion_id y con el payload ya decodificado, para pintarlas en la
     * ficha junto a cada ubicación.
     */
    public function reubicacionesPendientesDePieza(int $piezaId): array
    {
        $filas = $this->whereIn('tipo', self::TIPOS_REUBICACION)
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->like('payload', ',"pieza_id":' . $piezaId . ',', 'both')
            ->findAll();

        $porUbicacion = [];
        foreach ($filas as $t) {
            $t['datos'] = json_decode((string) $t['payload'], true) ?: [];
            $porUbicacion[(int) ($t['datos']['ubicacion_id'] ?? 0)] = $t;
        }

        return $porUbicacion;
    }

    /** Cuántas tareas quedan por resolver (todas las unidades), para el botón del índice. */
    public function contarPendientes(): int
    {
        return $this->whereIn('estado', ['pendiente', 'en_curso'])->countAllResults();
    }

    /**
     * Para /silo/tareas: las pendientes (todas) o las ya cerradas (las
     * `$limite` más recientes), con el payload decodificado en `datos` y la
     * unidad de la tarea resuelta para pintar directo.
     */
    public function paraListado(bool $pendientes, int $limite = 100): array
    {
        $builder = $this->select('silo_tareas.*, silo_unidades.nivel, silo_unidades.numero, silo_unidades.etiqueta AS unidad_etiqueta')
            ->join('silo_unidades', 'silo_unidades.id = silo_tareas.unidad_id', 'left');

        $pendientes
            ? $builder->whereIn('silo_tareas.estado', ['pendiente', 'en_curso'])->orderBy('silo_tareas.id', 'ASC')
            : $builder->whereNotIn('silo_tareas.estado', ['pendiente', 'en_curso'])->orderBy('silo_tareas.actualizado_en', 'DESC')->limit($limite);

        $filas = $builder->findAll();
        foreach ($filas as &$t) {
            $t['datos'] = json_decode((string) $t['payload'], true) ?: [];
        }

        return $filas;
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
