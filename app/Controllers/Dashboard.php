<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\MenuPrincipal;
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

        $modo = MenuPrincipal::modo();

        return view($modo === 'clasica' ? 'dashboard/index' : 'dashboard/index_' . $modo, [
            'modo' => $modo,
            'vivos' => $modo === 'clasica' ? [] : $this->datosVivos($dias, $recordatoriosUrgentes),
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
            ['ruta' => 'journal/focalizar', 'icono' => 'bi-bullseye', 'titulo' => 'Focalizar'],
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
        return array_map(static fn (array $m) => ['ruta' => $m['ruta'], 'icono' => $m['emoji'], 'titulo' => $m['titulo'], 'texto' => $m['texto']], MenuPrincipal::modulos());
    }

    private function datosVivos(int $dias, array $urgentes): array
    {
        $db = db_connect();
        $v = [];
        $uno = static function (callable $fn) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                return null;
            }
        };
        $plural = static fn (int $n, string $s, string $p) => $n . ' ' . ($n === 1 ? $s : $p);

        $v['lentillas'] = ['t' => $plural($dias, 'día', 'días'), 'e' => $dias >= 45 ? 'warn' : null, 'n' => $dias];

        $cad = count(array_filter($urgentes, fn ($r) => $r['nivel'] === 'caducado'));
        $urg = count($urgentes) - $cad;
        $v['recordatorios'] = $cad
            ? ['t' => $plural($cad, 'caducado', 'caducados'), 'e' => 'bad', 'n' => count($urgentes)]
            : ($urg ? ['t' => $urg . ' por vencer', 'e' => 'warn', 'n' => $urg] : ['t' => 'Nada cerca', 'e' => null, 'n' => 0]);

        $n = $uno(fn () => (int) $db->table('tasks')->where('en_foco', 1)->where('completed', 0)->countAllResults());
        if ($n !== null) {
            $v['journal'] = ['t' => $n ? $plural($n, 'en foco', 'en foco') : 'Nada en foco', 'e' => null, 'n' => $n];
        }

        $f = $uno(fn () => $db->table('gimnasio_entrenamientos')->selectMax('fecha')->get()->getRow('fecha'));
        if ($f) {
            $d = (new \DateTime($f))->diff(new \DateTime('today'))->days;
            $v['gimnasio'] = ['t' => $d === 0 ? 'Hoy' : 'Hace ' . $plural($d, 'día', 'días'), 'e' => $d >= 7 ? 'warn' : null, 'n' => $d];
        }

        $n = $uno(fn () => (int) $db->table('compra_faltantes')->countAllResults());
        if ($n !== null) {
            $v['compras'] = ['t' => $n ? $plural($n, 'en la lista', 'en la lista') : 'Lista vacía', 'e' => null, 'n' => $n];
        }

        $n = $uno(fn () => (int) $db->table('youtube_videos')->where('visto', 0)->countAllResults());
        if ($n !== null) {
            $v['youtube'] = ['t' => $n . ' sin ver', 'e' => null, 'n' => $n];
        }

        $n = $uno(function () {
            helper('hogar');
            $t = (new \App\Models\HogarTareaModel())->where('estado', 0)->findAll();
            return count(array_filter($t, fn ($x) => hogar_esta_atrasada($x['frecuencia_dias'] ? (int) $x['frecuencia_dias'] : null, $x['ultima_vez'])));
        });
        if ($n !== null) {
            $v['hogar'] = $n ? ['t' => $plural($n, 'atrasada', 'atrasadas'), 'e' => 'warn', 'n' => $n] : ['t' => 'Al día', 'e' => 'ok', 'n' => 0];
        }

        $n = $uno(fn () => count((new BookModel())->getByStatus('leyendo')));
        if ($n !== null) {
            $v['reading'] = ['t' => $n ? $plural($n, 'en curso', 'en curso') : 'Ninguno en curso', 'e' => null, 'n' => $n];
        }

        $n = $uno(fn () => (int) $db->table('braintogram_mensajes')->where('created_at >=', date('Y-m-d 00:00:00'))->countAllResults());
        if ($n !== null) {
            $v['braintogram'] = ['t' => $n ? $plural($n, 'entrada hoy', 'entradas hoy') : 'Nada hoy', 'e' => null, 'n' => $n];
        }

        $n = $uno(fn () => (int) $db->table('sesiones')->where('fecha_sesion >=', date('Y-m-d'))->countAllResults());
        if ($n !== null) {
            $v['sesiones'] = ['t' => $n ? $plural($n, 'próxima', 'próximas') : 'Ninguna próxima', 'e' => $n ? 'info' : null, 'n' => $n];
        }

        $n = $uno(fn () => (int) $db->table('rodajes_proyectos')->where('deleted_at', null)->countAllResults());
        if ($n !== null) {
            $v['rodajes'] = ['t' => $plural($n, 'proyecto', 'proyectos'), 'e' => null, 'n' => $n];
        }

        $n = $uno(fn () => (int) $db->table('silo_piezas')->countAllResults());
        if ($n !== null) {
            $v['silo'] = ['t' => $plural($n, 'pieza', 'piezas'), 'e' => null, 'n' => $n];
        }

        return array_filter($v);
    }
}
