<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\RecordatorioServicio;
use App\Models\CarActionModel;
use App\Models\CarFaultModel;
use App\Models\RecordatorioModel;

/**
 * Coche: historial de acciones, averías y avisos de mantenimiento.
 *
 * Los avisos ya no viven en `car_reminders`: son los recordatorios de
 * categoría «vehiculo» de la lista común (ver RecordatorioServicio). Cada
 * acción puede ir ligada a uno (car_actions.recordatorio_id) y, al
 * guardarla o borrarla, ese recordatorio se recalcula.
 */
class Coche extends BaseController
{
    protected RecordatorioServicio $servicio;

    public function __construct()
    {
        helper('recordatorio');
        $this->servicio = new RecordatorioServicio();
    }

    public function index()
    {
        $actionModel = new CarActionModel();
        $avisos      = $this->servicio->lista('vehiculo');

        // Vista clásica: mantenimientos (los que dependen de una acción) ya vencidos.
        $vencidos = [];
        foreach ($avisos as $a) {
            if ($a['nivel'] === 'caducado' && !empty($a['persistente']) && !empty($a['ultima_vez'])) {
                $vencidos[] = [
                    'title'     => $a['titulo'],
                    'dias'      => (int) (new \DateTime($a['ultima_vez']))->diff(new \DateTime('today'))->days,
                    'intervalo' => $a['periodo_texto'],
                ];
            }
        }

        return view(rec_vista('coche', 'coche/index'), [
            'avisos'          => $avisos,
            'ultimasAcciones' => $actionModel->orderBy('date', 'DESC')->orderBy('id', 'DESC')->findAll(rec_vista('coche', 'x') === 'x' ? 3 : 6),
            'totalAcciones'   => $actionModel->countAllResults(),
            'ultimaAveria'    => (new CarFaultModel())->orderBy('date', 'DESC')->first(),
            'avisosVencidos'  => $vencidos,
        ]);
    }

    // ========== ACCIONES REALIZADAS ==========
    public function acciones()
    {
        $model = new CarActionModel();

        if (rec_vista('coche', 'x') === 'x_nueva') {
            return view('coche/acciones/index_nueva', [
                'acciones' => $model->orderBy('date', 'DESC')->orderBy('id', 'DESC')->findAll(),
                'avisos'   => $this->servicio->lista('vehiculo'),
            ]);
        }

        // Clásica: solo la última acción de cada título.
        $acciones = \Config\Database::connect()->query('
            SELECT * FROM car_actions ca1
            WHERE date = (SELECT MAX(date) FROM car_actions ca2 WHERE ca2.title = ca1.title)
            ORDER BY date DESC
        ')->getResultArray();

        return view('coche/acciones/index', ['acciones' => $acciones]);
    }

    /** Acción rápida por título (vista clásica): si hay un aviso con ese nombre, lo renueva. */
    public function accionRapida($tituloCodificado)
    {
        $title = ucwords(str_replace('-', ' ', urldecode($tituloCodificado)));
        $fecha = $this->request->getPost('fecha') ?: date('Y-m-d');

        $aviso = null;
        foreach ($this->servicio->lista('vehiculo') as $a) {
            if (RecordatorioServicio::norm($a['titulo']) === RecordatorioServicio::norm($title)) { $aviso = $a; break; }
        }

        if ($aviso) {
            $this->servicio->marcarHecho((int) $aviso['id'], $fecha);
        } else {
            (new CarActionModel())->insert(['title' => $title, 'date' => $fecha, 'kilometers' => null, 'notes' => '']);
        }

        return redirect()->to('/coche/acciones')->with('success', 'Acción "' . esc($title) . '" registrada correctamente.');
    }

    public function nuevaAccion()
    {
        return view(rec_vista('coche', 'coche/acciones/form'), [
            'reminders' => $this->avisosParaSelect(),
            'previo'    => (int) $this->request->getGet('aviso'),
        ]);
    }

    public function editarAccion($id)
    {
        $accion = (new CarActionModel())->find($id);
        if (!$accion) return redirect()->to('/coche/acciones')->with('error', 'Acción no encontrada.');
        // La vista clásica lee «reminder_id»: se le da el enlace nuevo.
        $accion['reminder_id'] = $accion['recordatorio_id'];

        return view(rec_vista('coche', 'coche/acciones/form'), [
            'accion'    => $accion,
            'reminders' => $this->avisosParaSelect(),
            'previo'    => 0,
        ]);
    }

    public function guardarAccion()
    {
        $model = new CarActionModel();
        $id    = (int) $this->request->getPost('id');
        $antes = $id ? $model->find($id) : null;

        // El formulario clásico manda «reminder_id»; el nuevo, «recordatorio_id». Los dos son ya ids de recordatorio.
        $rid   = (int) ($this->request->getPost('recordatorio_id') ?: $this->request->getPost('reminder_id'));
        $aviso = $rid ? (new RecordatorioModel())->where('categoria', 'vehiculo')->find($rid) : null;

        $titulo = trim((string) $this->request->getPost('title')) ?: ($aviso['titulo'] ?? '');
        $km     = $this->request->getPost('kilometers');
        $datos  = [
            'title'           => $titulo,
            'date'            => $this->request->getPost('date') ?: date('Y-m-d'),
            'kilometers'      => $km !== '' && $km !== null ? (int) $km : null,
            'notes'           => trim((string) $this->request->getPost('notes')),
            'recordatorio_id' => $aviso ? (int) $aviso['id'] : null,
        ];

        if ($titulo === '' || !($antes ? $model->update($id, $datos) : $model->insert($datos))) {
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar la acción: indica qué se hizo.');
        }

        // El aviso que tenía antes y el de ahora pueden haber cambiado de «última vez».
        foreach (array_unique(array_filter([(int) ($antes['recordatorio_id'] ?? 0), (int) $datos['recordatorio_id']])) as $r) {
            $this->servicio->recalcular($r);
        }

        return redirect()->to('/coche/acciones')->with('success', 'Acción guardada correctamente.');
    }

    public function borrarAccion($id)
    {
        $model  = new CarActionModel();
        $accion = $model->find($id);
        $model->delete($id);
        if (!empty($accion['recordatorio_id'])) $this->servicio->recalcular((int) $accion['recordatorio_id']);

        return redirect()->to('/coche/acciones')->with('success', 'Acción eliminada correctamente.');
    }

    // ========== AVERÍAS ==========
    public function averias()
    {
        return view(rec_vista('coche', 'coche/averias/index'), [
            'averias' => (new CarFaultModel())->orderBy('date', 'DESC')->findAll(),
        ]);
    }

    public function nuevaAveria()
    {
        return view(rec_vista('coche', 'coche/averias/form'));
    }

    public function guardarAveria()
    {
        $model = new CarFaultModel();

        if (! $model->save($this->request->getPost())) {
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar la avería.');
        }

        return redirect()->to('/coche/averias')->with('success', 'Avería guardada correctamente.');
    }

    public function editarAveria($id)
    {
        return view(rec_vista('coche', 'coche/averias/form'), ['averia' => (new CarFaultModel())->find($id)]);
    }

    public function borrarAveria($id)
    {
        (new CarFaultModel())->delete($id);
        return redirect()->to('/coche/averias')->with('success', 'Avería eliminada correctamente.');
    }

    // ========== AVISOS (recordatorios de categoría «vehiculo») ==========
    public function recordatorios()
    {
        return view('coche/avisos', ['avisos' => $this->servicio->lista('vehiculo')]);
    }

    /** El alta y la edición se hacen en el formulario común de Recordatorios. */
    public function nuevoRecordatorio()
    {
        return redirect()->to(site_url('recordatorios/nuevo?categoria=vehiculo&volver=coche'));
    }

    public function editarRecordatorio($id)
    {
        return redirect()->to(site_url('recordatorios/editar/' . (int) $id . '?volver=coche'));
    }

    /** Avisos del coche para el desplegable del formulario de acción: [['id', 'title'], ...] por nombre. */
    private function avisosParaSelect(): array
    {
        $avisos = array_map(static fn ($a) => ['id' => $a['id'], 'title' => $a['titulo']], $this->servicio->lista('vehiculo'));
        usort($avisos, static fn ($a, $b) => strnatcasecmp($a['title'], $b['title']));
        return $avisos;
    }
}
