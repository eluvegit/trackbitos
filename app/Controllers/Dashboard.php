<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\BookModel;
use App\Models\DashboardTareaFijadaModel;
use App\Models\LentillasSustitucionesModel;
use App\Models\RecordatorioModel;
use App\Models\TaskModel;

class Dashboard extends BaseController
{
    public function index()
    {
        helper('recordatorio');

        $model = new LentillasSustitucionesModel();
        $ultima = $model->whereIn('elemento', ['lentilla izquierda', 'lentilla derecha', 'lentillas'])
                        ->orderBy('fecha', 'DESC')
                        ->first();

        $dias = 0;
        if ($ultima) {
            $dias = (new \DateTime($ultima['fecha']))->diff(new \DateTime())->days;
        }

        $mostrarAlerta = $dias >= 45;

        // Recordatorios que vencen dentro de 1 mes (o ya caducados)
        $recordatorioModel = new RecordatorioModel();
        $recordatoriosUrgentes = [];
        foreach ($recordatorioModel->findAll() as $r) {
            $periodo = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
            $fechaEfectiva = recordatorio_fecha_efectiva($r['fecha_evento'], $periodo);
            $estado = recordatorio_estado($fechaEfectiva);

            if (in_array($estado['nivel'], ['caducado', 'urgente'], true)) {
                $r['dias']  = $estado['dias'];
                $r['texto'] = $estado['texto'];
                $r['nivel'] = $estado['nivel'];
                $recordatoriosUrgentes[] = $r;
            }
        }
        usort($recordatoriosUrgentes, fn($a, $b) => $a['dias'] <=> $b['dias']);

        return view('dashboard/index', [
            'dias' => $dias,
            'mostrarAlerta' => $mostrarAlerta,
            'recordatoriosUrgentes' => $recordatoriosUrgentes,
            'secciones' => $this->secciones(),
            'enlacesRapidos' => $this->enlacesRapidos(),
            'tareasFijadas' => (new DashboardTareaFijadaModel())->fijadasConTarea(),
            'librosLeyendo' => $this->librosLeyendo(),
        ]);
    }

    /**
     * Los libros en curso ("leyendo"), para la lista minimalista del
     * sidebar: título, autor y el % si el libro tiene páginas totales. Van
     * ordenados por el más tocado recientemente (getByStatus), que es el
     * que tienes en la cabeza.
     */
    private function librosLeyendo(): array
    {
        $model = new BookModel();

        return array_map(static fn(array $libro) => [
            'id'       => (int) $libro['id'],
            'title'    => $libro['title'],
            'author'   => $libro['author'] ?? '',
            'progreso' => $model->progreso($libro),
        ], $model->getByStatus('leyendo'));
    }

    // Placeholder editable: accesos directos del sidebar de escritorio.
    private function enlacesRapidos(): array
    {
        return [
            ['ruta' => 'journal', 'icono' => 'bi-list-check', 'titulo' => 'Journal'],
            ['ruta' => 'comidas/diario/hoy', 'icono' => 'bi-egg-fried', 'titulo' => 'Comida de hoy'],
        ];
    }

    /**
     * Fija una tarea de Journal en el sidebar del dashboard (aparte de los
     * "enlaces rápidos" de arriba, que son fijos en código: esto lo elige
     * el usuario, y puede ser más de una).
     */
    public function fijarTarea()
    {
        $taskId = (int) $this->request->getPost('task_id');
        $tarea  = $taskId ? (new TaskModel())->find($taskId) : null;

        if (!$tarea) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => 'Esa tarea no existe.'])
                : redirect()->to(site_url('dashboard'))->with('error', 'Esa tarea no existe.');
        }

        (new DashboardTareaFijadaModel())->fijar($taskId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'    => true,
                'tarea' => ['id' => (int) $tarea['id'], 'title' => $tarea['title'], 'category' => $tarea['category']],
            ]);
        }

        return redirect()->to(site_url('dashboard'))->with('success', 'Tarea fijada.');
    }

    public function desfijarTarea(int $taskId)
    {
        (new DashboardTareaFijadaModel())->desfijar($taskId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true]);
        }

        return redirect()->to(site_url('dashboard'))->with('success', 'Tarea desfijada.');
    }

    /** Buscador para el selector de "fijar tarea" — por título, sin filtrar por estado. */
    public function buscarTarea()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['resultados' => []]);
        }

        $ya = array_column((new DashboardTareaFijadaModel())->findAll(), 'task_id');

        $tareas = (new TaskModel())
            ->like('title', $q)
            ->orderBy('title', 'ASC')
            ->findAll(15);

        $resultados = [];
        foreach ($tareas as $t) {
            $resultados[] = [
                'id'       => (int) $t['id'],
                'texto'    => $t['title'] . ($t['category'] ? ' · ' . $t['category'] : ''),
                'fijada'   => in_array((int) $t['id'], $ya, true),
            ];
        }

        return $this->response->setJSON(['resultados' => $resultados]);
    }

    private function secciones(): array
    {
        return [
            ['ruta' => 'comidas/diario/hoy', 'icono' => '🍽️', 'titulo' => 'Comida', 'texto' => 'Planifica tus menús, dieta y seguimiento alimenticio.'],
            ['ruta' => 'gimnasio', 'icono' => '🏋️', 'titulo' => 'Gimnasio', 'texto' => 'Registra tus entrenamientos, progresos y objetivos físicos.'],
            ['ruta' => 'compras', 'icono' => '🛒', 'titulo' => 'Compras', 'texto' => 'Lleva control de tus compras, listas y gastos.'],
            ['ruta' => 'lentillas', 'icono' => '👁️', 'titulo' => 'Lentillas', 'texto' => 'Lleva un registro de cambios, limpieza y reemplazos.'],
            ['ruta' => 'coche', 'icono' => '🚗', 'titulo' => 'Coche', 'texto' => 'Controla cambios de aceite, revisiones, neumáticos y más.'],
            ['ruta' => 'youtube', 'icono' => '▶️', 'titulo' => 'YouTube', 'texto' => 'Permite revisar los vídeos guardados como interesantes.'],
            ['ruta' => 'enlaces', 'icono' => '📒', 'titulo' => 'Enlaces', 'texto' => 'Permite revisar los enlaces registrados interesantes.'],
            ['ruta' => 'journal', 'icono' => '📨', 'titulo' => 'Journal', 'texto' => 'Permite hacer y seguir tareas y bullet journal.'],
            ['ruta' => 'reading', 'icono' => '📖', 'titulo' => 'Lectura', 'texto' => 'Sigue tus libros a tu ritmo, sin presión ni rachas.'],
            ['ruta' => 'hogar', 'icono' => '🏠', 'titulo' => 'Hogar', 'texto' => 'Checklist rutinario de limpieza y tareas del hogar por habitación.'],
            ['ruta' => 'recordatorios', 'icono' => '📅', 'titulo' => 'Recordatorios', 'texto' => 'ITV, revisiones médicas, vacunas, DNI, carnet... y cuándo tocan.'],
            ['ruta' => 'braintogram', 'icono' => '🧠', 'titulo' => 'Braintogram', 'texto' => 'Log de ingesta del bot de Telegram: segundo cerebro en construcción.'],
            ['ruta' => 'buscapp', 'icono' => '📟', 'titulo' => 'Buscapp', 'texto' => 'Avisos de llamada estilo busca/telegrama: usuarios y envíos de la app.'],
            ['ruta' => 'rodajes', 'icono' => '🎬', 'titulo' => 'Rodajes', 'texto' => 'Permite gestionar las escenas de un rodaje.'],
            ['ruta' => 'sesiones', 'icono' => '📸', 'titulo' => 'Sesiones', 'texto' => 'Kanban de sesiones de foto/vídeo: moodboard, equipo y model releases.'],
            ['ruta' => 'piezas', 'icono' => '🧱', 'titulo' => 'Piezas', 'texto' => 'Versionado de modelos 3D en Blender: qué versión es la buena y dónde está.'],
            ['ruta' => 'silo', 'icono' => '🗄️', 'titulo' => 'Silo', 'texto' => 'Archivo de material resultante: fotos y vídeos editados, clasificados y ubicados.'],
        ];
    }
}
