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

        // 2. Leer parámetros de la URL
        $viewMode = $this->request->getGet('view') ?? 'listado';
        $filterFocus = $this->request->getGet('filterFocus') ?? 'focus';
        $filterPriority = $this->request->getGet('priority') ?? 1;

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

        // Ordenar tareas dentro de cada categoría por prioridad
        if (!empty($filterPriority)) {
            foreach ($tasksByCategory as $cat => &$tasks) {
                usort($tasks, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
            }
        }

        // 6. Ordenar categorías por tiempo total si prioridad activada
        if (!empty($filterPriority)) {
            usort($categories, function ($a, $b) use ($totalTimeByCategory) {
                $timeA = $totalTimeByCategory[$a['name']] ?? 0;
                $timeB = $totalTimeByCategory[$b['name']] ?? 0;
                return $timeB <=> $timeA; // descendente
            });
        }

        // 7. Enviar todo a la vista
        return view('journal/index', [
            'view_mode'           => $viewMode,
            'filterFocus'         => $filterFocus,
            'filterPriority'      => $filterPriority,
            'categories'          => $categories,
            'tasksByCategory'     => $tasksByCategory,
            'totalTimeByCategory' => $totalTimeByCategory, // array con tiempo total
        ]);
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
