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
        $this->categoryModel  = new JournalCategoryModel();
    }

    // INDEX: mostrar Journal con todas las categorías y sus tareas, con filtro focus
    public function index()
    {
        $categories = $this->categoryModel->getAll();
        $viewMode = $this->request->getGet('view') ?? 'portadas';  // 'portadas' o 'texto'
        $filterFocus = $this->request->getGet('filterFocus') ?? 'todas'; // 'focus' o 'todas'

        $taskLogs = $this->taskLogModel->getAll(); // registros históricos
        $tasksByCategory = $this->taskModel->getAllGroupedByCategory(); // tareas activas por categoría

        // Aplicar filtro "focus" si se solicita
        if ($filterFocus === 'focus') {
            foreach ($tasksByCategory as $cat => &$tasks) {
                $tasks = array_filter($tasks, fn($t) => !empty($t['is_current']));
            }
        }

        return view('journal/index', [
            'view_mode'        => $viewMode,
            'filterFocus'      => $filterFocus,
            'task_logs'        => $taskLogs,
            'categories'       => $categories,
            'tasksByCategory'  => $tasksByCategory
        ]);
    }


    public function create()
    {
        $input = $this->request->getJSON();
        $title = trim($input->title ?? '');
        $categoryId = (int)($input->category_id ?? 0);

        if ($title && $categoryId) {
            // Buscar nombre y color de la categoría
            $category = $this->categoryModel->getById($categoryId);
            if (!$category) {
                return $this->response->setJSON(['success' => false]);
            }

            // Insertar tarea
            $taskId = $this->taskModel->insert([
                'title'       => $title,
                'category'    => $category['name'],
                'color'       => $category['color'] ?? '#000000',
                'created_at'  => date('Y-m-d H:i:s')
            ], true); // el segundo parámetro devuelve el ID insertado

            return $this->response->setJSON([
                'success' => true,
                'id'      => $taskId,
                'color'   => $category['color'] ?? '#000000'
            ]);
        }

        return $this->response->setJSON(['success' => false]);
    }




    // VER UN REGISTRO (si quieres mantener TaskLogModel)
    public function view(int $logId)
    {
        $log = $this->taskLogModel->getById($logId);

        if (!$log) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('journal/view', [
            'log' => $log
        ]);
    }

    public function edit(int $taskId)
    {
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $data = [
                'title'       => $this->request->getPost('title'),
                'start_time'  => $this->request->getPost('start_time'),
                'end_time'    => $this->request->getPost('end_time'),
                'time_spent'  => $this->request->getPost('time_spent'),
                'amplitude'   => $this->request->getPost('amplitude'),
                'completed'   => $this->request->getPost('completed'),
                'note'        => $this->request->getPost('note'),
                'is_current'  => $this->request->getPost('is_current') ? 1 : 0,
            ];

            // Subida de imagen opcional (reemplaza la anterior)
            $file = $this->request->getFile('image');
            if ($file && $file->isValid() && !$file->hasMoved()) {

                $uploadPath = FCPATH . 'upload/images/journal/';

                // Crear carpeta si no existe
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // --- Eliminar imagen anterior si existe ---
                if (!empty($task['image'])) {
                    $oldImagePath = FCPATH . $task['image'];
                    if (is_file($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                // --- Guardar nueva imagen ---
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);

                // Guardamos la ruta relativa en BD
                $data['image'] = 'upload/images/journal/' . $newName;
            }



            $this->taskModel->update($taskId, $data);

            return redirect()->to('/journal/edit/' . $taskId)->with('success', 'Registro actualizado correctamente.');
        }


        return view('journal/edit', [
            'task' => $task
        ]);
    }

    public function delete(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) return $this->response->setJSON(['success' => false]);

        $this->taskModel->delete($taskId);
        return $this->response->setJSON(['success' => true]);
    }

    public function deleteImage(int $taskId)
    {
        $task = $this->taskModel->find($taskId);

        if (!$task) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (!empty($task['image'])) {
            $imagePath = FCPATH . $task['image'];

            if (is_file($imagePath)) {
                unlink($imagePath);
            }

            // Limpiar campo en base de datos
            $this->taskModel->update($taskId, [
                'image' => null
            ]);
        }

        return redirect()
            ->to('/journal/edit/' . $taskId)
            ->with('success', 'Imagen eliminada correctamente.');
    }

    public function toggleCurrent($id)
    {
        $taskModel = new \App\Models\TaskModel();
        $task = $taskModel->find($id);

        if (!$task) {
            return $this->response->setJSON(['success' => false, 'error' => 'Tarea no encontrada']);
        }

        $task['is_current'] = $task['is_current'] ? 0 : 1;
        $taskModel->update($id, ['is_current' => $task['is_current']]);

        return $this->response->setJSON([
            'success' => true,
            'is_current' => $task['is_current']
        ]);
    }

    public function addTime(int $id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $minutes = (int) ($this->request->getJSON()->minutes ?? 0);
        if ($minutes <= 0) {
            return $this->response->setJSON(['success' => false]);
        }

        $task = $this->taskModel->find($id);
        if (!$task) {
            return $this->response->setJSON(['success' => false]);
        }

        $newTime = ($task['time_spent'] ?? 0) + $minutes;

        $this->taskModel->update($id, [
            'time_spent' => $newTime
        ]);

        return $this->response->setJSON([
            'success' => true,
            'minutes' => $newTime,
            'hours'   => number_format($newTime / 60, 2)
        ]);
    }
}
