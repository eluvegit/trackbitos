<?php

namespace App\Controllers;

use App\Models\TaskLogModel;
use App\Models\TaskModel;
use App\Models\JournalCategoryModel;

class Journal extends BaseController
{
    protected TaskLogModel $taskLogModel;
    protected TaskModel $taskModel;
    protected JournalCategoryModel $categoryModel;

    public function __construct()
    {
        $this->taskLogModel = new TaskLogModel();
        $this->taskModel    = new TaskModel();
        $this->categoryModel = new JournalCategoryModel();
    }

    /**
     * Mostrar Journal con todas las categorías y sus tareas, con filtros opcionales
     */
    public function index()
    {
        // 1. Traer todas las categorías
        $categories = $this->categoryModel->getAll();

        // 2. Leer parámetros de la URL; si no vienen (recarga o entrada directa),
        // se recuperan de la cookie del último filtro elegido.
        $viewMode = $this->stickyFilter('view', 'listado');
        $filterFocus = $this->stickyFilter('filterFocus', 'focus');
        $filterPriority = $this->stickyFilter('priority', '1');
        $filterHechos = $this->stickyFilter('hechos', 'mostrar'); // 'mostrar' | 'ocultar'

        // 3. Traer todas las tareas agrupadas por categoría
        $allTasksByCategory = $this->taskModel->getAllGroupedByCategory();

        // 4. CALCULAR TIEMPO TOTAL POR CATEGORÍA (todas las tareas, sin filtrar)
        $totalTimeByCategory = [];
        foreach ($categories as $cat) {
            $catName = $cat['name'];
            $tasks = $allTasksByCategory[$catName] ?? [];
            $totalTimeByCategory[$catName] = array_sum(array_map(fn($t) => (int)($t['time_spent'] ?? 0), $tasks));
        }

        // 5. CREAR CÓPIA PARA LA VISTA Y APLICAR FILTROS
        $tasksByCategory = $allTasksByCategory;

        // Filtrar por focus
        if ($filterFocus === 'focus') {
            foreach ($tasksByCategory as $cat => &$tasks) {
                $tasks = array_filter($tasks, fn($t) => !empty($t['is_current']));
            }
        }

        // Ocultar tareas ya hechas (con end_time)
        if ($filterHechos === 'ocultar') {
            foreach ($tasksByCategory as $cat => &$tasks) {
                $tasks = array_filter($tasks, function ($t) {
                    $isDone = !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00';
                    return !$isDone;
                });
            }
        }

        // Ordenar tareas dentro de cada categoría por prioridad
        if (!empty($filterPriority)) {
            foreach ($tasksByCategory as $cat => &$tasks) {
                usort($tasks, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
            }
        }

        $taskLogModel = new TaskLogModel();

        $lastTaskActivity = $taskLogModel->getLastActivityPerTask();
        $lastCategoryActivity = $taskLogModel->getLastActivityPerCategory();



        // 6. Ordenar categorías por tiempo total si prioridad activada
        if (!empty($filterPriority)) {
            usort($categories, function ($a, $b) use ($totalTimeByCategory) {
                $timeA = $totalTimeByCategory[$a['name']] ?? 0;
                $timeB = $totalTimeByCategory[$b['name']] ?? 0;
                return $timeB <=> $timeA; // descendente
            });
        }

        $progressByCategory = [];

        foreach ($categories as $category) {
            $catName = $category['name'];
            $catTasks = $allTasksByCategory[$catName] ?? [];

            $total = count($catTasks);
            $current = count(array_filter($catTasks, fn($t) => !empty($t['is_current'])));
            $completed = count(array_filter($catTasks, fn($t) => !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00'));

            $totalSafe = max($total, 1); // evitar división por 0

            $progressByCategory[$catName] = [
                'total' => $total,
                'current' => $current,
                'completed' => $completed,
                'currentPerc' => round(($current / $totalSafe) * 100),
                'completedPerc' => round(($completed / $totalSafe) * 100),
                'remainingPerc' => round(100 - (($current / $totalSafe) * 100) - (($completed / $totalSafe) * 100))
            ];
        }



        // 7. Enviar todo a la vista
        return view('journal/index', [
            'view_mode'           => $viewMode,
            'filterFocus'         => $filterFocus,
            'filterPriority'      => $filterPriority,
            'filterHechos'        => $filterHechos,
            'categories'          => $categories,
            'tasksByCategory'     => $tasksByCategory,
            'totalTimeByCategory' => $totalTimeByCategory, // array con tiempo total
            'lastTaskActivity'    => $lastTaskActivity,
            'lastCategoryActivity' => $lastCategoryActivity,
            'progressByCategory'   => $progressByCategory // <--- aquí lo pasamos
        ]);
    }

    /**
     * Lee un filtro de la URL y, si viene, lo recuerda en una cookie de largo
     * plazo; si no viene (recarga o entrada directa a /journal), recupera el
     * último valor guardado para que el filtro no se resetee solo.
     */
    private function stickyFilter(string $key, string $default): string
    {
        $value = $this->request->getGet($key);
        if ($value !== null) {
            $this->response->setCookie([
                'name'   => 'journal_' . $key,
                'value'  => (string) $value,
                'expire' => 31536000, // 1 año
            ]);
            return (string) $value;
        }

        return $this->request->getCookie('journal_' . $key) ?? $default;
    }

    /**
     * "¿Qué hago ahora?": sortea (ponderado) 3-4 categorías candidatas para
     * hacer algo, combinando cuánto hace que no se toca la categoría, el
     * peso que le ha dado el usuario (0 = excluida del reparto) y cuántas
     * horas tiene ya acumuladas (para compensar las que apenas se invierten,
     * no solo las que llevan tiempo sin tocarse).
     */
    public function queHacer()
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
            // Cuantas más horas ya acumuladas tiene la categoría, más se
            // amortigua su puntuación (de forma suave, con logaritmo, para
            // no anular de golpe categorías con mucho tiempo invertido).
            $factorHoras = 1 / (1 + log(1 + $horas));

            $candidatos[] = [
                'categoria' => $cat,
                'tareas'    => $tareas,
                'dias'      => $dias,
                'horas'     => round($horas, 1),
                'score'     => max(1, $dias) * $peso * $factorHoras,
            ];
        }

        $sugeridos = $this->sorteoPonderado($candidatos, 4);

        // Para cada sugerida, elegir unas pocas tareas a mostrar: primero las
        // que tienen estrella, luego el resto por orden reciente.
        foreach ($sugeridos as &$s) {
            $tareas = $s['tareas'];
            usort($tareas, function ($a, $b) {
                $aCur = !empty($a['is_current']);
                $bCur = !empty($b['is_current']);
                return $aCur === $bCur ? 0 : ($aCur ? -1 : 1);
            });
            $s['tareas_muestra'] = array_slice($tareas, 0, 4);
            $s['tareas_total'] = count($tareas);
        }
        unset($s);

        // Horas totales de TODAS las categorías (no solo las sugeridas), para
        // mostrarlas también en el panel de ajuste de pesos.
        $horasPorCategoria = [];
        foreach ($allTasksByCategory as $catName => $tareas) {
            $horasPorCategoria[$catName] = round(array_sum(array_column($tareas, 'time_spent')) / 60, 1);
        }

        return view('journal/que_hacer', [
            'sugeridos'         => $sugeridos,
            'categorias'        => $categories,
            'horasPorCategoria' => $horasPorCategoria,
        ]);
    }

    /**
     * Reparto ponderado sin reemplazo: cada candidato tiene tantas
     * "papeletas" como su score, se sortea uno, se saca del bombo y se repite.
     */
    private function sorteoPonderado(array $candidatos, int $n): array
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
     * Ajusta el peso de una categoría en el reparto de "¿Qué hago ahora?"
     * (0 = excluida, 1-5 = cuánto peso tiene).
     */
    public function actualizarPeso(int $categoryId)
    {
        $categoria = $this->categoryModel->find($categoryId);
        if (!$categoria) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $peso = (int) $this->request->getPost('peso');
        $peso = max(0, min(5, $peso));

        $this->categoryModel->skipValidation(true)->update($categoryId, ['peso' => $peso]);

        return $this->response->setJSON(['success' => true, 'peso' => $peso]);
    }

    /**
     * Crear nueva tarea
     */
    public function create()
    {
        $input = $this->request->getJSON();
        $title = trim($input->title ?? '');
        $categoryId = (int)($input->category_id ?? 0);

        if (!$title || !$categoryId) {
            return $this->response->setJSON(['success' => false]);
        }

        $category = $this->categoryModel->find($categoryId);
        if (!$category) {
            return $this->response->setJSON(['success' => false]);
        }

        $taskId = $this->taskModel->insert([
            'title'      => $title,
            'category'   => $category['name'],
            'color'      => $category['color'] ?? '#000000',
            'created_at' => date('Y-m-d H:i:s')
        ], true);

        return $this->response->setJSON([
            'success' => true,
            'task' => [
                'id'         => $taskId,
                'title'      => $title,
                'color'      => $category['color'] ?? '#000000',
                'time_spent' => 0,
                'is_current' => 0
            ]
        ]);
    }


    /**
     * Editar tarea
     */
    public function edit(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $data = [
                'title'      => $this->request->getPost('title'),
                'start_time' => $this->request->getPost('start_time'),
                'end_time'   => $this->request->getPost('end_time'),
                'time_spent' => $this->request->getPost('time_spent'),
                'amplitude'  => $this->request->getPost('amplitude'),
                'completed'  => $this->request->getPost('completed'),
                'note'       => $this->request->getPost('note'),
                'is_current' => $this->request->getPost('is_current') ? 1 : 0,
            ];

            $file = $this->request->getFile('image');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                $uploadPath = FCPATH . 'upload/images/journal/';
                if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

                // Eliminar imagen anterior
                if (!empty($task['image']) && is_file(FCPATH . $task['image'])) {
                    unlink(FCPATH . $task['image']);
                }

                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $data['image'] = 'upload/images/journal/' . $newName;
            }

            $this->taskModel->update($taskId, $data);

            return redirect()->to('/journal/edit/' . $taskId)->with('success', 'Registro actualizado correctamente.');
        }

        // Obtener logs para calendario
        $logs = $this->taskLogModel->where('task_id', $taskId)->orderBy('log_date', 'DESC')->findAll();

        return view('journal/edit', [
            'task' => $task,
            'logs' => $logs
        ]);
    }

    /**
     * Eliminar tarea
     */
    public function delete(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return redirect()->to('/journal')->with('error', 'La tarea no existe.');
        }

        $this->taskModel->delete($taskId);
        return redirect()->to('/journal')->with('success', 'Tarea eliminada correctamente.');
    }

    /**
     * Eliminar imagen de tarea
     */
    public function deleteImage(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        if (!empty($task['image']) && is_file(FCPATH . $task['image'])) {
            unlink(FCPATH . $task['image']);
            $this->taskModel->update($taskId, ['image' => null]);
        }

        return redirect()->to('/journal/edit/' . $taskId)->with('success', 'Imagen eliminada correctamente.');
    }

    /**
     * Alternar estado "current"
     */
    public function toggleCurrent(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) return $this->response->setJSON(['success' => false, 'error' => 'Tarea no encontrada']);

        $isCurrent = $task['is_current'] ? 0 : 1;
        $this->taskModel->update($taskId, ['is_current' => $isCurrent]);

        return $this->response->setJSON(['success' => true, 'is_current' => $isCurrent]);
    }

    /**
     * Añadir tiempo a tarea (AJAX)
     */
    public function addTime(int $taskId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }

        $task = $this->taskModel->find($taskId);
        if (!$task) return $this->response->setJSON(['success' => false]);

        $data = $this->request->getJSON();
        $minutes = (int)($data->minutes ?? 0);
        if ($minutes <= 0) return $this->response->setJSON(['success' => false]);

        $newTime = ($task['time_spent'] ?? 0) + $minutes;
        $this->taskModel->update($taskId, ['time_spent' => $newTime]);

        return $this->response->setJSON([
            'success' => true,
            'minutes' => $newTime,
            'hours'   => number_format($newTime / 60, 2)
        ]);
    }


    /**
     * Añadir log de fecha a tarea (AJAX)
     */
    public function addLog(int $taskId)
    {
        // 1. Validar tarea
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'La tarea no existe'
            ]);
        }

        // 2. Leer input (JSON o POST)
        $input = $this->request->getJSON(true) ?: $this->request->getPost();

        $rawDate = trim($input['date'] ?? '');
        $minutes = (int)($input['minutes'] ?? 0);

        if ($rawDate === '') {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'La fecha es obligatoria'
            ]);
        }

        if ($minutes < 0) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Los minutos no pueden ser negativos'
            ]);
        }

        // 3. Normalizar fecha
        try {
            $dateObj = new \DateTime($rawDate);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Formato de fecha inválido'
            ]);
        }

        $logDate = $dateObj->format('Y-m-d');

        // 4. Insertar o acumular minutos
        try {
            $existing = $this->taskLogModel
                ->where('task_id', $taskId)
                ->where('log_date', $logDate)
                ->first();

            if ($existing) {
                // Acumular minutos
                $newMinutes = ((int)$existing['minutes']) + $minutes;

                $this->taskLogModel->update($existing['id'], [
                    'minutes' => $newMinutes
                ]);
            } else {
                // Crear nuevo registro
                $this->taskLogModel->insert([
                    'task_id'  => $taskId,
                    'log_date' => $logDate,
                    'minutes'  => $minutes
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'AddLog error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'error'   => 'No se pudo guardar el registro'
            ]);
        }

        // 5. Respuesta OK
        return $this->response->setJSON([
            'success'  => true,
            'date'     => $logDate,
            'minutes'  => $minutes
        ]);
    }


    /**
     * Obtener logs de una tarea (AJAX)
     */
    public function getLogs(int $taskId)
    {
        $logs = $this->taskLogModel
            ->where('task_id', $taskId)
            ->orderBy('log_date', 'DESC')
            ->findAll();

        return $this->response->setJSON($logs);
    }

    public function updateLog(int $logId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        $input = $this->request->getJSON(true);

        $minutes = (int)($input['minutes'] ?? -1);
        $date    = trim($input['log_date'] ?? '');

        if ($minutes < 0 || !$date) {
            return $this->response->setJSON(['success' => false]);
        }

        $this->taskLogModel->update($logId, [
            'minutes'  => $minutes,
            'log_date' => $date
        ]);

        return $this->response->setJSON(['success' => true]);
    }
}
