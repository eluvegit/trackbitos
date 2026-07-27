<?php

namespace App\Models;

use CodeIgniter\Model;

class SesionModel extends Model
{
    protected $table         = 'sesiones';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'creada_at';
    protected $updatedField  = 'actualizada_at';

    protected $allowedFields = [
        'titulo',
        'estado_foto',
        'estado_video',
        'pausada',
        'entrega_modelos',
        'mensaje_modelos',
        'fecha_sesion',
        'notas',
        'briefing',
    ];

    protected $validationRules = [
        'titulo'          => 'required|max_length[150]',
        'fecha_sesion'    => 'permit_empty|valid_date',
        'estado_foto'     => 'permit_empty|in_list[planificacion,edicion,subiendo,completado]',
        'estado_video'    => 'permit_empty|in_list[planificacion,edicion,subiendo,completado]',
        'entrega_modelos' => 'permit_empty|in_list[no_aplica,pendiente,entregado]',
    ];

    public const ESTADOS          = ['planificacion', 'edicion', 'subiendo', 'completado'];
    public const PARTES           = ['foto', 'video'];
    public const ENTREGA_MODELOS  = ['no_aplica', 'pendiente', 'entregado'];

    /**
     * Cambia el estado de una parte (foto o vídeo) y deja constancia en el
     * historial. Devuelve false si la parte/estado no son válidos, si la
     * sesión no existe, o si esa parte no aplica a la sesión (columna a
     * NULL), sin dejar el historial a medias (transacción).
     */
    public function cambiarEstado(int $id, string $parte, string $nuevoEstado): bool
    {
        if (!in_array($parte, self::PARTES, true) || !in_array($nuevoEstado, self::ESTADOS, true)) {
            return false;
        }

        $sesion = $this->find($id);
        $columna = 'estado_' . $parte;

        if (!$sesion || $sesion[$columna] === null) {
            return false;
        }

        $this->db->transStart();

        $this->update($id, [$columna => $nuevoEstado]);

        (new SesionHistorialEstadoModel())->insert([
            'sesion_id' => $id,
            'parte'     => $parte,
            'estado'    => $nuevoEstado,
        ]);

        $this->db->transComplete();

        return $this->db->transStatus() !== false;
    }

    /**
     * Toggle simple de `pausada` (play/pausa), sin historial. Devuelve el
     * nuevo valor (bool) o null si la sesión no existe.
     */
    public function togglePausada(int $id): ?bool
    {
        $sesion = $this->find($id);
        if (!$sesion) {
            return null;
        }

        $nuevo = !((bool) $sesion['pausada']);
        $this->update($id, ['pausada' => $nuevo]);

        return $nuevo;
    }

    /**
     * Cambia `entrega_modelos` a uno de los 3 valores de ENTREGA_MODELOS.
     * Devuelve false si el valor no es válido o la sesión no existe.
     */
    public function cambiarEntregaModelos(int $id, string $valor): bool
    {
        if (!in_array($valor, self::ENTREGA_MODELOS, true) || !$this->find($id)) {
            return false;
        }

        return (bool) $this->update($id, ['entrega_modelos' => $valor]);
    }

    /**
     * Sesión completa + situaciones + moodboard (agrupado por situación,
     * 'general' para los items sin situacion_id) + equipo + model releases,
     * en una sola llamada para la vista de detalle.
     */
    public function detalleCompleto(int $id): ?array
    {
        $sesion = $this->find($id);
        if (!$sesion) {
            return null;
        }

        $situaciones = (new SituacionModel())
            ->where('sesion_id', $id)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $moodboard = (new MoodboardItemModel())
            ->where('sesion_id', $id)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $moodboardPorSituacion = [];
        foreach ($moodboard as $item) {
            $clave = $item['situacion_id'] ?? 'general';
            $moodboardPorSituacion[$clave][] = $item;
        }

        $equipo = (new SesionEquipoModel())
            ->where('sesion_id', $id)
            ->orderBy('orden', 'ASC')
            ->findAll();

        $modelReleases = (new ModelReleaseModel())
            ->where('sesion_id', $id)
            ->orderBy('id', 'ASC')
            ->findAll();

        return [
            'sesion'                  => $sesion,
            'situaciones'             => $situaciones,
            'moodboard_por_situacion' => $moodboardPorSituacion,
            'equipo'                  => $equipo,
            'model_releases'          => $modelReleases,
        ];
    }
}
