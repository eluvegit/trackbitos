<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ajustes del módulo Piezas que no son datos del catálogo en sí, de momento
 * uno solo: qué tarea de Journal está enlazada como fuente de "Pendientes de
 * crear" (spec: journal es el punto de entrada, esta tabla solo recuerda la
 * referencia). Fila única (id=1) — no hace falta un modelo de ajustes más
 * genérico para un solo valor.
 */
class PiezaConfigModel extends Model
{
    protected $table = 'piezas_config';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'tarea_journal_id', 'actualizado_en'];

    private const FILA = 1;

    public function tareaJournalId(): ?int
    {
        $fila = $this->find(self::FILA);

        return $fila && $fila['tarea_journal_id'] !== null ? (int) $fila['tarea_journal_id'] : null;
    }

    /**
     * `save()` no vale aquí: con la clave primaria puesta asume update() y,
     * si la fila todavía no existe (primer enlace), la actualización no
     * toca ninguna fila y el cambio se pierde en silencio.
     */
    public function enlazarTarea(?int $tareaId): void
    {
        $datos = ['tarea_journal_id' => $tareaId, 'actualizado_en' => date('Y-m-d H:i:s')];

        if ($this->find(self::FILA)) {
            $this->update(self::FILA, $datos);
        } else {
            $this->insert(['id' => self::FILA] + $datos);
        }
    }
}
