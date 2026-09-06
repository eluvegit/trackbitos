<?php

namespace App\Controllers;

use App\Models\TaskLogModel;
use App\Models\TaskModel;
use App\Models\JournalCategoryModel;
use App\Models\SubtaskModel;
use App\Models\TaskFileModel;
use App\Models\TaskLinkModel;
use App\Services\ClaudeService;
use App\Services\JournalSuggestionService;
use App\Services\ReadingJournalSyncService;

class Journal extends BaseController
{
    protected TaskLogModel $taskLogModel;
    protected TaskModel $taskModel;
    protected JournalCategoryModel $categoryModel;
    protected SubtaskModel $subtaskModel;
    protected TaskFileModel $taskFileModel;
    protected TaskLinkModel $taskLinkModel;
    protected ReadingJournalSyncService $readingSync;

    public function __construct()
    {
        $this->taskLogModel = new TaskLogModel();
        $this->taskModel    = new TaskModel();
        $this->categoryModel = new JournalCategoryModel();
        $this->subtaskModel = new SubtaskModel();
        $this->taskFileModel = new TaskFileModel();
        $this->taskLinkModel = new TaskLinkModel();
        $this->readingSync   = new ReadingJournalSyncService();
    }

    /**
     * Mostrar Journal con todas las categorías y sus tareas, con filtros opcionales
     */
    public function index()
    {
        // Leer parámetros de la URL; si no vienen (recarga o entrada directa),
        // se recuperan de la cookie del último filtro elegido.
        $viewMode = $this->stickyFilter('view', 'listado');
        $filterFocus = $this->stickyFilter('filterFocus', 'focus');
        $filterPriority = $this->stickyFilter('priority', '1');
        $filterHechos = $this->stickyFilter('hechos', 'mostrar'); // 'mostrar' | 'ocultar'

        $data = $this->buildGridData($viewMode, $filterFocus, $filterPriority, $filterHechos);

        return view('journal/index', array_merge($data, [
            'filterFocus'    => $filterFocus,
            'filterPriority' => $filterPriority,
            'filterHechos'   => $filterHechos,
        ]));
    }

    /**
     * Igual que index(), pero devuelve en JSON el HTML de la rejilla de
     * categorías y el de la barra de filtros (sin layout). Lo usan los
     * botones de filtro/vista del Journal para refrescar el listado por
     * AJAX sin recargar la página; se manda también la barra de filtros
     * para que su estado activo (qué botón está en azul, qué icono toca)
     * salga siempre del mismo render PHP y no se tenga que duplicar esa
     * lógica en JS.
     */
    public function grid()
    {
        $viewMode = $this->stickyFilter('view', 'listado');
        $filterFocus = $this->stickyFilter('filterFocus', 'focus');
        $filterPriority = $this->stickyFilter('priority', '1');
        $filterHechos = $this->stickyFilter('hechos', 'mostrar');

        $data = $this->buildGridData($viewMode, $filterFocus, $filterPriority, $filterHechos);

        return $this->response->setJSON([
            'success' => true,
            'html'    => view('journal/_grid', $data),
            'toolbar' => view('journal/_toolbar_filters', [
                'filterPriority' => $filterPriority,
                'filterFocus'    => $filterFocus,
                'filterHechos'   => $filterHechos,
                'view_mode'      => $viewMode,
            ]),
        ]);
    }

    /**
     * Construye todos los datos de la rejilla de categorías/tareas del
     * Journal (compartido entre index() y grid()) a partir de los filtros ya
     * resueltos.
     */
    private function buildGridData(string $viewMode, string $filterFocus, string $filterPriority, string $filterHechos): array
    {
        $categories = $this->categoryModel->getAll();

        // Traer todas las tareas agrupadas por categoría
        $allTasksByCategory = $this->taskModel->getAllGroupedByCategory();

        // Tiempo total por categoría (todas las tareas, sin filtrar)
        $totalTimeByCategory = [];
        foreach ($categories as $cat) {
            $catName = $cat['name'];
            $tasks = $allTasksByCategory[$catName] ?? [];
            $totalTimeByCategory[$catName] = array_sum(array_map(fn($t) => (int)($t['time_spent'] ?? 0), $tasks));
        }

        // Copia para la vista y aplicar filtros
        $tasksByCategory = $allTasksByCategory;

        // Filtrar por focus
        if ($filterFocus === 'focus') {
            foreach ($tasksByCategory as $cat => &$tasks) {
                $tasks = array_filter($tasks, fn($t) => !empty($t['is_current']));
            }
            unset($tasks);
        }

        // Ocultar tareas ya hechas (con end_time)
        if ($filterHechos === 'ocultar') {
            foreach ($tasksByCategory as $cat => &$tasks) {
                $tasks = array_filter($tasks, function ($t) {
                    $isDone = !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00';
                    return !$isDone;
                });
            }
            unset($tasks);
        }

        // Ordenar tareas dentro de cada categoría por prioridad
        if (!empty($filterPriority)) {
            foreach ($tasksByCategory as $cat => &$tasks) {
                usort($tasks, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
            }
            unset($tasks);
        }

        $lastTaskActivity = $this->taskLogModel->getLastActivityPerTask();
        $lastCategoryActivity = $this->taskLogModel->getLastActivityPerCategory();

        // Ordenar categorías por tiempo total si prioridad activada
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

        // Subtareas de todas las tareas visibles, agrupadas por task_id
        // (para poder desplegarlas inline en el listado sin ir tarea a tarea)
        $visibleTaskIds = [];
        foreach ($tasksByCategory as $tasks) {
            foreach ($tasks as $t) {
                $visibleTaskIds[] = $t['id'];
            }
        }
        $subtasksByTask = $this->subtaskModel->getGroupedByTaskIds($visibleTaskIds);

        return [
            'view_mode'            => $viewMode,
            'categories'           => $categories,
            'tasksByCategory'      => $tasksByCategory,
            'totalTimeByCategory'  => $totalTimeByCategory,
            'lastTaskActivity'     => $lastTaskActivity,
            'lastCategoryActivity' => $lastCategoryActivity,
            'progressByCategory'   => $progressByCategory,
            'subtasksByTask'       => $subtasksByTask,
        ];
    }

    /**
     * Resumen (horas totales, contadores actuales/completadas/total y sus
     * porcentajes para la barra) de una categoría, identificada por su
     * nombre (las tasks no guardan category_id, solo el nombre). Se manda
     * en la respuesta JSON de cualquier acción que pueda cambiar esos
     * números (crear tarea, sumar tiempo, completar, marcar estrella...)
     * para que el JS pueda repintar la cabecera de la categoría sin recargar.
     */
    private function categorySummary(string $categoryName): ?array
    {
        $category = $this->categoryModel->where('name', $categoryName)->first();
        if (!$category) {
            return null;
        }

        $tasks = $this->taskModel->where('category', $categoryName)->findAll();

        $total = count($tasks);
        $current = count(array_filter($tasks, fn($t) => !empty($t['is_current'])));
        $completed = count(array_filter($tasks, fn($t) => !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00'));
        $totalMinutes = array_sum(array_map(fn($t) => (int)($t['time_spent'] ?? 0), $tasks));

        $totalSafe = max($total, 1);
        $currentPerc = round(($current / $totalSafe) * 100);
        $completedPerc = round(($completed / $totalSafe) * 100);

        return [
            'cat_id'        => (int) $category['id'],
            'total'         => $total,
            'current'       => $current,
            'completed'     => $completed,
            'currentPerc'   => $currentPerc,
            'completedPerc' => $completedPerc,
            'remainingPerc' => round(100 - $currentPerc - $completedPerc),
            'totalHours'    => number_format($totalMinutes / 60, 2),
        ];
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

        // Request::getCookie() no aplica el prefijo global de Config\Cookie
        // automáticamente (a diferencia de Response::setCookie(), que sí lo
        // añade al escribir), así que hay que leerlo con el mismo prefijo o
        // nunca se encuentra la cookie que se acaba de guardar.
        $prefix = config('Cookie')->prefix;

        return $this->request->getCookie($prefix . 'journal_' . $key) ?? $default;
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
        $allTasksByCategory = $this->taskModel->getAllGroupedByCategory();

        $suggestionService = new JournalSuggestionService();
        $candidatos = $suggestionService->candidatosPonderados();
        $sugeridos = $suggestionService->sorteoPonderado($candidatos, 4);

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
     * "Focalizar": la selección de la selección. Muestra todas las tareas con
     * estrella (is_current) en una lista minimalista "Categoría - Tarea", una
     * por línea y en el mismo orden que el Journal, con un checkbox para
     * marcar las que se quedan en el foco de los próximos días/semanas.
     *
     * El index del Journal es la fuente de la verdad: si allí se quita la
     * estrella de una tarea, deja de aparecer aquí y su marca de foco se
     * limpia (ver toggleCurrent y edit).
     */
    public function focalizar()
    {
        $categories = $this->categoryModel->getAll();
        $tasksByCategory = $this->taskModel->getAllGroupedByCategory();

        // Solo tareas con estrella, ordenadas dentro de cada categoría por
        // prioridad descendente igual que el listado del Journal.
        $starredByCategory = [];
        foreach ($categories as $cat) {
            $catName = $cat['name'];
            // Solo tareas con estrella (is_current) y que no estén terminadas
            // (mismo criterio de "hecha" que el listado del Journal).
            $tasks = array_values(array_filter(
                $tasksByCategory[$catName] ?? [],
                function ($t) {
                    $hecha = !empty($t['end_time']) && $t['end_time'] !== '0000-00-00 00:00:00';
                    return !empty($t['is_current']) && !$hecha;
                }
            ));
            if (empty($tasks)) {
                continue;
            }
            usort($tasks, fn($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));
            $starredByCategory[$catName] = $tasks;
        }

        // Lista de "en foco" en orden manual (foco_orden): las tareas sin
        // orden asignado todavía (NULL) van al final, en el mismo orden por
        // categoría/prioridad de arriba (sort estable desde PHP 8).
        $enFocoOrdered = [];
        foreach ($starredByCategory as $catName => $tasks) {
            foreach ($tasks as $t) {
                if (!empty($t['en_foco'])) {
                    $t['cat'] = $catName;
                    $enFocoOrdered[] = $t;
                }
            }
        }
        usort($enFocoOrdered, function ($a, $b) {
            $ordenA = $a['foco_orden'] ?? null;
            $ordenB = $b['foco_orden'] ?? null;
            if ($ordenA === null && $ordenB === null) {
                return 0;
            }
            if ($ordenA === null) {
                return 1;
            }
            if ($ordenB === null) {
                return -1;
            }
            return $ordenA <=> $ordenB;
        });

        return view('journal/focalizar', [
            'starredByCategory' => $starredByCategory,
            'enFocoOrdered'     => $enFocoOrdered,
        ]);
    }

    /**
     * Alterna la marca "en foco" de una tarea (AJAX). Solo se permite sobre
     * tareas que tengan estrella; si no la tienen, no deberían estar en la
     * lista de focalizar.
     */
    public function toggleFocalizar(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        if (empty($task['is_current'])) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'La tarea no tiene estrella.',
            ]);
        }

        $enFoco = empty($task['en_foco']) ? 1 : 0;
        $update = ['en_foco' => $enFoco];

        if ($enFoco === 1) {
            // Nueva en el foco: va al final del orden manual.
            $maxOrden = $this->taskModel->where('en_foco', 1)->selectMax('foco_orden')->first();
            $update['foco_orden'] = (int) ($maxOrden['foco_orden'] ?? 0) + 1;
        } else {
            $update['foco_orden'] = null;
        }

        $this->taskModel->update($taskId, $update);

        return $this->response->setJSON(['success' => true, 'en_foco' => $enFoco]);
    }

    /**
     * Guarda el orden manual (drag & drop) de la lista "en foco" (AJAX).
     * Recibe {order: [taskId, ...]} y asigna foco_orden = posición en el
     * array, ignorando cualquier id que no esté realmente en foco.
     */
    public function ordenarFocalizar()
    {
        $order = $this->request->getJSON(true)['order'] ?? null;
        if (!is_array($order) || empty($order)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }

        foreach (array_values($order) as $i => $taskId) {
            $taskId = (int) $taskId;
            $task = $this->taskModel->find($taskId);
            if ($task && !empty($task['en_foco'])) {
                $this->taskModel->update($taskId, ['foco_orden' => $i]);
            }
        }

        return $this->response->setJSON(['success' => true]);
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
            'amplitude'  => 100,
            'completed'  => 1,
            'created_at' => date('Y-m-d H:i:s')
        ], true);

        $task = $this->taskModel->find($taskId);

        // El HTML de la fila la genera el servidor con el mismo partial que
        // usa el listado, para que una tarea recién creada tenga desde el
        // primer momento estrella, botón de completar, subtareas y barra de
        // progreso funcionales (antes se montaba un <li> a mano en JS que se
        // quedaba corto).
        $html = view('journal/_task_item', [
            'task'         => $task,
            'subs'         => [],
            'lastActivity' => null,
        ]);

        return $this->response->setJSON([
            'success'          => true,
            'html'             => $html,
            'category_summary' => $this->categorySummary($category['name']),
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

            // Al quitar la estrella, la tarea sale también del foco (ver focalizar).
            if (!$data['is_current']) {
                $data['en_foco'] = 0;
            }

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
            $this->readingSync->pushTaskToBook($taskId, true);

            return redirect()->to('/journal/edit/' . $taskId)->with('success', 'Registro actualizado correctamente.');
        }

        // Obtener logs para calendario
        $logs = $this->taskLogModel->where('task_id', $taskId)->orderBy('log_date', 'DESC')->findAll();

        return view('journal/edit', [
            'task'      => $task,
            'logs'      => $logs,
            'subtasks'  => $this->subtaskModel->getForTask($taskId),
            'files'     => $this->taskFileModel->getForTask($taskId),
            'links'     => $this->taskLinkModel->getForTask($taskId),
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
     * Valida que un subtask_id recibido exista y pertenezca a la tarea dada;
     * si no, devuelve null (sin asociar) en vez de fallar la petición entera.
     */
    private function resolveSubtaskId(int $taskId, $rawSubtaskId): ?int
    {
        $subtaskId = (int) $rawSubtaskId;
        if ($subtaskId <= 0) {
            return null;
        }

        $subtask = $this->subtaskModel->find($subtaskId);
        if (!$subtask || (int) $subtask['task_id'] !== $taskId) {
            return null;
        }

        return $subtaskId;
    }

    /**
     * Sube uno o varios archivos como historial de materiales de una tarea
     * (fotos de referencia, PDFs, documentos...). No sustituye a la imagen
     * de portada, es un histórico aparte y admite cualquier tipo de archivo.
     */
    public function taskFileUpload(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $subtaskId = $this->resolveSubtaskId($taskId, $this->request->getPost('subtask_id'));
        $subtaskTitle = $subtaskId ? ($this->subtaskModel->find($subtaskId)['title'] ?? null) : null;

        $files = $this->request->getFileMultiple('archivo') ?? [];
        if (empty($files) && $this->request->getFile('archivo')) {
            $files = [$this->request->getFile('archivo')];
        }

        if (empty($files)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Ningún archivo recibido.']);
        }

        $targetDir = 'upload/journal-files/' . $taskId;
        $absDir = FCPATH . $targetDir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }

        $subidos = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $originalName = $file->getClientName();
            $newName      = $file->getRandomName();
            $size         = $file->getSize();
            $file->move($absDir, $newName);

            $rutaRelativa = $targetDir . '/' . $newName;
            $id = $this->taskFileModel->insert([
                'task_id'         => $taskId,
                'subtask_id'      => $subtaskId,
                'ruta_archivo'    => $rutaRelativa,
                'nombre_original' => $originalName,
                'tamano'          => $size,
            ], true);

            $row = $this->taskFileModel->find($id);
            $row['url'] = base_url($rutaRelativa);
            $row['subtask_title'] = $subtaskTitle;
            $subidos[] = $row;
        }

        if (empty($subidos)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Ningún archivo válido.']);
        }

        return $this->response->setJSON(['success' => true, 'files' => $subidos]);
    }

    /**
     * Edita el nombre mostrado y/o la descripción de un material, sin tocar
     * el archivo físico (para distinguirlos entre sí o poder buscarlos).
     */
    public function taskFileUpdate(int $fileId)
    {
        $file = $this->taskFileModel->find($fileId);
        if (!$file) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $nombre = trim($input['nombre_original'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');
        $subtaskId = $this->resolveSubtaskId((int) $file['task_id'], $input['subtask_id'] ?? null);

        if ($nombre === '') {
            return $this->response->setJSON(['success' => false, 'error' => 'El nombre es obligatorio.']);
        }

        $this->taskFileModel->update($fileId, [
            'nombre_original' => $nombre,
            'descripcion'     => $descripcion !== '' ? $descripcion : null,
            'subtask_id'      => $subtaskId,
        ]);

        $row = $this->taskFileModel->find($fileId);
        $row['url'] = base_url($row['ruta_archivo']);
        $row['subtask_title'] = $subtaskId ? ($this->subtaskModel->find($subtaskId)['title'] ?? null) : null;

        return $this->response->setJSON(['success' => true, 'file' => $row]);
    }

    /**
     * Elimina un material adjunto a una tarea (archivo físico + registro).
     */
    public function taskFileDelete(int $fileId)
    {
        $file = $this->taskFileModel->find($fileId);
        if (!$file) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $path = FCPATH . $file['ruta_archivo'];
        if (is_file($path)) {
            unlink($path);
        }

        $this->taskFileModel->delete($fileId);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Añade un enlace (URL + texto libre opcional) a una tarea, como
     * complemento a los materiales pero sin necesidad de subir un archivo.
     */
    public function taskLinkCreate(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $url = trim($input['url'] ?? '');
        $titulo = trim($input['titulo'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');
        $subtaskId = $this->resolveSubtaskId($taskId, $input['subtask_id'] ?? null);

        if ($url === '') {
            return $this->response->setJSON(['success' => false, 'error' => 'La URL es obligatoria.']);
        }

        $id = $this->taskLinkModel->insert([
            'task_id'     => $taskId,
            'subtask_id'  => $subtaskId,
            'url'         => $url,
            'titulo'      => $titulo !== '' ? $titulo : null,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
        ], true);

        $link = $this->taskLinkModel->find($id);
        $link['subtask_title'] = $subtaskId ? ($this->subtaskModel->find($subtaskId)['title'] ?? null) : null;

        return $this->response->setJSON(['success' => true, 'link' => $link]);
    }

    /**
     * Edita el título y/o la descripción de un enlace (la URL no se toca).
     */
    public function taskLinkUpdate(int $linkId)
    {
        $link = $this->taskLinkModel->find($linkId);
        if (!$link) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $titulo = trim($input['titulo'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');
        $subtaskId = $this->resolveSubtaskId((int) $link['task_id'], $input['subtask_id'] ?? null);

        $this->taskLinkModel->update($linkId, [
            'titulo'      => $titulo !== '' ? $titulo : null,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'subtask_id'  => $subtaskId,
        ]);

        $row = $this->taskLinkModel->find($linkId);
        $row['subtask_title'] = $subtaskId ? ($this->subtaskModel->find($subtaskId)['title'] ?? null) : null;

        return $this->response->setJSON(['success' => true, 'link' => $row]);
    }

    /**
     * Elimina un enlace adjunto a una tarea.
     */
    public function taskLinkDelete(int $linkId)
    {
        $link = $this->taskLinkModel->find($linkId);
        if (!$link) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $this->taskLinkModel->delete($linkId);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Alternar estado "current"
     */
    public function toggleCurrent(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) return $this->response->setJSON(['success' => false, 'error' => 'Tarea no encontrada']);

        $isCurrent = $task['is_current'] ? 0 : 1;
        $update = ['is_current' => $isCurrent];
        // Al quitar la estrella, la tarea sale también del foco (ver focalizar).
        if ($isCurrent === 0) {
            $update['en_foco'] = 0;
        }
        $this->taskModel->update($taskId, $update);
        $this->readingSync->pushTaskToBook($taskId);

        return $this->response->setJSON([
            'success'          => true,
            'is_current'       => $isCurrent,
            'category_summary' => $this->categorySummary($task['category']),
        ]);
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
            'success'          => true,
            'minutes'          => $newTime,
            'hours'            => number_format($newTime / 60, 2),
            'category_summary' => $this->categorySummary($task['category']),
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

    /**
     * Crear subtarea (checklist tachable dentro de una tarea)
     */
    public function subtaskCreate(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $title = trim($input['title'] ?? '');
        if ($title === '') {
            return $this->response->setJSON(['success' => false]);
        }

        $id = $this->subtaskModel->insert([
            'task_id'    => $taskId,
            'title'      => $title,
            'is_done'    => 0,
            'orden'      => $this->subtaskModel->siguienteOrden($taskId),
            'time_spent' => 0,
        ], true);

        $progress = $this->syncTaskProgressFromSubtasks($taskId);

        return $this->response->setJSON([
            'success'  => true,
            'subtask'  => ['id' => $id, 'title' => $title, 'is_done' => 0, 'time_spent' => 0],
            'progress' => $progress,
        ]);
    }

    /**
     * Mientras una tarea tenga subtareas, su progreso (amplitud/completados)
     * se deriva de ellas en vez de llevarse a mano; si se quedan a 0 no se
     * toca nada, para no pisar un progreso manual anterior sin subtareas.
     */
    private function syncTaskProgressFromSubtasks(int $taskId): array
    {
        $subtasks = $this->subtaskModel->getForTask($taskId);
        $total = count($subtasks);

        if ($total > 0) {
            $done = count(array_filter($subtasks, fn($s) => !empty($s['is_done'])));
            $this->taskModel->update($taskId, [
                'amplitude' => $total,
                'completed' => $done,
            ]);
        }

        $task = $this->taskModel->find($taskId);

        return [
            'amplitude' => (int) ($task['amplitude'] ?? 0),
            'completed' => (int) ($task['completed'] ?? 0),
        ];
    }

    /**
     * Renombrar una subtarea
     */
    public function subtaskUpdate(int $id)
    {
        $subtask = $this->subtaskModel->find($id);
        if (!$subtask) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $title = trim($input['title'] ?? '');
        if ($title === '') {
            return $this->response->setJSON(['success' => false]);
        }

        $this->subtaskModel->skipValidation(true)->update($id, ['title' => $title]);

        return $this->response->setJSON(['success' => true, 'title' => $title]);
    }

    /**
     * Alternar tachado de una subtarea
     */
    public function subtaskToggle(int $id)
    {
        $subtask = $this->subtaskModel->find($id);
        if (!$subtask) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $isDone = $subtask['is_done'] ? 0 : 1;
        $this->subtaskModel->skipValidation(true)->update($id, ['is_done' => $isDone]);

        $taskId = (int) $subtask['task_id'];
        $progress = $this->syncTaskProgressFromSubtasks($taskId);

        return $this->response->setJSON([
            'success'  => true,
            'is_done'  => $isDone,
            'task_id'  => $taskId,
            'progress' => $progress,
        ]);
    }

    /**
     * Eliminar subtarea
     */
    public function subtaskDelete(int $id)
    {
        $subtask = $this->subtaskModel->find($id);
        if (!$subtask) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $taskId = (int) $subtask['task_id'];
        $this->subtaskModel->delete($id);
        $progress = $this->syncTaskProgressFromSubtasks($taskId);

        return $this->response->setJSON([
            'success'  => true,
            'task_id'  => $taskId,
            'progress' => $progress,
        ]);
    }

    /**
     * Añadir tiempo a una subtarea; el mismo tiempo se suma también al
     * time_spent de la tarea padre, para poder controlar el tiempo por partes.
     */
    public function subtaskAddTime(int $id)
    {
        $subtask = $this->subtaskModel->find($id);
        if (!$subtask) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $minutes = (int)($input['minutes'] ?? 0);
        if ($minutes <= 0) {
            return $this->response->setJSON(['success' => false]);
        }

        $newSubtaskTime = ((int)($subtask['time_spent'] ?? 0)) + $minutes;
        $this->subtaskModel->skipValidation(true)->update($id, ['time_spent' => $newSubtaskTime]);

        $task = $this->taskModel->find($subtask['task_id']);
        $newTaskTime = ((int)($task['time_spent'] ?? 0)) + $minutes;
        $this->taskModel->update($subtask['task_id'], ['time_spent' => $newTaskTime]);

        return $this->response->setJSON([
            'success'          => true,
            'subtask_minutes'  => $newSubtaskTime,
            'task_minutes'     => $newTaskTime,
            'task_id'          => (int) $subtask['task_id'],
            'category_summary' => $this->categorySummary($task['category']),
        ]);
    }

    /**
     * Pide a Claude que sugiera subtareas concretas para una tarea (no las
     * crea directamente: el usuario elige cuáles añadir en el modal).
     */
    public function suggestSubtasks(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $existentes = array_column($this->subtaskModel->getForTask($taskId), 'title');
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $contextoExtra = trim($input['contexto'] ?? '');

        $claude = new ClaudeService();
        $subtareas = $claude->sugerirSubtareas(
            $task['title'],
            $task['category'] ?? null,
            $task['note'] ?? null,
            $existentes,
            $contextoExtra !== '' ? $contextoExtra : null
        );

        if ($subtareas === null) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'No se pudo generar sugerencias. Revisa la API key o inténtalo de nuevo.',
            ]);
        }

        return $this->response->setJSON(['success' => true, 'subtareas' => $subtareas]);
    }

    /**
     * Completa (o reabre) una tarea desde el resumen rápido del listado, sin
     * pasar por el formulario de edición completo. Permite ajustar inicio,
     * fin, tiempo invertido y nota a la vez que se marca como terminada.
     */
    public function completeTask(int $taskId)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();

        $data = [
            'start_time' => trim($input['start_time'] ?? '') ?: null,
            'time_spent' => max(0, (int) ($input['time_spent'] ?? 0)),
            'note'       => trim($input['note'] ?? ''),
        ];

        if (!empty($input['reopen'])) {
            $data['end_time'] = null;
        } else {
            $data['end_time'] = trim($input['end_time'] ?? '') ?: date('Y-m-d');
        }

        $this->taskModel->update($taskId, $data);
        $this->readingSync->pushTaskToBook($taskId);
        $task = $this->taskModel->find($taskId);

        return $this->response->setJSON([
            'success'          => true,
            'is_done'          => !empty($task['end_time']) && $task['end_time'] !== '0000-00-00 00:00:00',
            'is_current'       => (int) ($task['is_current'] ?? 0),
            'start_time'       => $task['start_time'],
            'end_time'         => $task['end_time'],
            'time_spent'       => (int) $task['time_spent'],
            'note'             => $task['note'],
            'category_summary' => $this->categorySummary($task['category']),
        ]);
    }

    /**
     * Reordenar subtareas (drag & drop)
     */
    public function subtaskReorder()
    {
        $ids = $this->request->getJSON(true)['orden'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false]);
        }

        foreach ($ids as $index => $id) {
            $this->subtaskModel->skipValidation(true)->update((int) $id, ['orden' => $index + 1]);
        }

        return $this->response->setJSON(['success' => true]);
    }
}
