<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tareas de Journal fijadas para acceso rápido desde el sidebar del
 * dashboard. Aparte de los "enlaces rápidos" (lista fija en código,
 * Dashboard::enlacesRapidos): esto es lo que el usuario elige él mismo,
 * pudiendo ser varias.
 */
class DashboardTareaFijadaModel extends Model
{
    protected $table         = 'dashboard_tareas_fijadas';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['task_id', 'orden', 'creado_en'];

    /**
     * Tareas fijadas con su título y categoría, en el orden guardado. Si
     * alguna tarea ya no existe (no debería pasar, la FK es CASCADE, pero
     * mejor no reventar el sidebar por una fila huérfana) se omite.
     */
    public function fijadasConTarea(): array
    {
        $fijadas = $this->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        if ($fijadas === []) {
            return [];
        }

        $tareas = array_column(
            (new TaskModel())->whereIn('id', array_column($fijadas, 'task_id'))->findAll(),
            null,
            'id'
        );

        $resultado = [];
        foreach ($fijadas as $f) {
            if (isset($tareas[$f['task_id']])) {
                $resultado[] = $f + ['tarea' => $tareas[$f['task_id']]];
            }
        }

        return $resultado;
    }

    public function estaFijada(int $taskId): bool
    {
        return (bool) $this->where('task_id', $taskId)->first();
    }

    /** No hace nada si ya estaba fijada — fijar dos veces no es un error. */
    public function fijar(int $taskId): void
    {
        if ($this->estaFijada($taskId)) {
            return;
        }

        $siguiente = (int) ($this->selectMax('orden')->first()['orden'] ?? 0) + 1;

        $this->insert(['task_id' => $taskId, 'orden' => $siguiente, 'creado_en' => date('Y-m-d H:i:s')]);
    }

    public function desfijar(int $taskId): void
    {
        $this->where('task_id', $taskId)->delete();
    }
}
