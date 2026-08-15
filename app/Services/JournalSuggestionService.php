<?php

namespace App\Services;

use App\Models\JournalCategoryModel;
use App\Models\TaskModel;

/**
 * Lógica de sugerencia ponderada del Journal ("¿Qué hago ahora?"), compartida
 * entre la propia pantalla de Journal y el resumen del dashboard.
 */
class JournalSuggestionService
{
    private JournalCategoryModel $categoryModel;
    private TaskModel $taskModel;

    public function __construct()
    {
        $this->categoryModel = new JournalCategoryModel();
        $this->taskModel = new TaskModel();
    }

    /**
     * Candidatos ponderados: una entrada por categoría con tareas y peso > 0,
     * puntuada por cuánto hace que no se toca, el peso manual y las horas ya
     * invertidas (para compensar las que apenas se tocan).
     */
    public function candidatosPonderados(): array
    {
        $categories = $this->categoryModel->getAll();
        $lastUpdatedByCategory = $this->taskModel->getLastUpdatedPerCategory();
        $allTasksByCategory = $this->taskModel->getAllGroupedByCategory();

        $candidatos = [];
        foreach ($categories as $cat) {
            $peso = (int) ($cat['peso'] ?? 3);
            if ($peso <= 0) {
                continue; // excluida del reparto
            }

            $catName = $cat['name'];
            $tareas = $allTasksByCategory[$catName] ?? [];
            if (empty($tareas)) {
                continue; // sin tareas, no tiene sentido sugerirla
            }

            $ultima = $lastUpdatedByCategory[$catName] ?? null;
            $dias = $ultima ? (int) floor((time() - strtotime($ultima)) / 86400) : 365;

            $horas = array_sum(array_column($tareas, 'time_spent')) / 60;
            $factorHoras = 1 / (1 + log(1 + $horas));

            $candidatos[] = [
                'categoria' => $cat,
                'tareas'    => $tareas,
                'dias'      => $dias,
                'horas'     => round($horas, 1),
                'score'     => max(1, $dias) * $peso * $factorHoras,
            ];
        }

        return $candidatos;
    }

    /**
     * Sorteo ponderado sin reemplazo: cada candidato tiene tantas
     * "papeletas" como su score, se sortea uno, se saca del bombo y se repite.
     */
    public function sorteoPonderado(array $candidatos, int $n): array
    {
        $pool = array_values($candidatos);
        $elegidos = [];

        while (count($elegidos) < $n && !empty($pool)) {
            $total = array_sum(array_column($pool, 'score'));
            if ($total <= 0) {
                break;
            }

            $r = mt_rand(1, $total);
            $acumulado = 0;
            foreach ($pool as $i => $c) {
                $acumulado += $c['score'];
                if ($r <= $acumulado) {
                    $elegidos[] = $c;
                    unset($pool[$i]);
                    $pool = array_values($pool);
                    break;
                }
            }
        }

        return $elegidos;
    }

    /**
     * Sugerencia única (para el dashboard): sortea una categoría por peso y,
     * dentro de ella, una tarea concreta —priorizando las no terminadas y,
     * entre esas, las marcadas con estrella—.
     */
    public function sugerirUnaTarea(): ?array
    {
        $elegidos = $this->sorteoPonderado($this->candidatosPonderados(), 1);
        if (empty($elegidos)) {
            return null;
        }

        $tareas = $elegidos[0]['tareas'];

        $pendientes = array_values(array_filter($tareas, function ($t) {
            return empty($t['end_time']) || $t['end_time'] === '0000-00-00 00:00:00';
        }));
        $pool = !empty($pendientes) ? $pendientes : $tareas;

        $conEstrella = array_values(array_filter($pool, fn($t) => !empty($t['is_current'])));
        $mejores = !empty($conEstrella) ? $conEstrella : $pool;

        return [
            'categoria' => $elegidos[0]['categoria'],
            'tarea'     => $mejores[array_rand($mejores)],
        ];
    }
}
