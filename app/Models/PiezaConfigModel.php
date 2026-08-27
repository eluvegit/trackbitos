<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ajustes del módulo Piezas que no son datos del catálogo en sí: qué tarea
 * de Journal está enlazada como fuente de "Pendientes de crear" (spec:
 * journal es el punto de entrada, esta tabla solo recuerda la referencia) y
 * las pautas de promoción (checklist recordatorio antes de promocionar).
 * Fila única (id=1) — no hace falta un modelo de ajustes más genérico para
 * dos valores.
 */
class PiezaConfigModel extends Model
{
    protected $table = 'piezas_config';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id', 'tarea_journal_id', 'pautas_promocion', 'actualizado_en'];

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

    /**
     * Una línea de texto por pauta, en el orden en que se escribieron.
     * Líneas en blanco descartadas: son las que quedan al editar el
     * textarea, no una pauta real.
     */
    public function pautasPromocion(): array
    {
        $fila = $this->find(self::FILA);
        if (!$fila || $fila['pautas_promocion'] === null) {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n/', $fila['pautas_promocion'])
        ), static fn($linea) => $linea !== ''));
    }

    /** Mismo motivo que enlazarTarea(): save() no crea la fila si aún no existe. */
    public function guardarPautas(string $texto): void
    {
        $datos = ['pautas_promocion' => trim($texto) !== '' ? $texto : null, 'actualizado_en' => date('Y-m-d H:i:s')];

        if ($this->find(self::FILA)) {
            $this->update(self::FILA, $datos);
        } else {
            $this->insert(['id' => self::FILA] + $datos);
        }
    }
}
