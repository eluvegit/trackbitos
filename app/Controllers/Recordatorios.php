<?php

namespace App\Controllers;

use App\Models\RecordatorioModel;

class Recordatorios extends BaseController
{
    protected RecordatorioModel $model;

    /** Categorías disponibles: valor => [etiqueta, icono por defecto] */
    public const CATEGORIAS = [
        'vehiculo'      => ['Vehículo', 'car-front'],
        'salud'         => ['Salud', 'heart-pulse'],
        'mascota'       => ['Mascota', '🐶'],
        'documentacion' => ['Documentación', 'card-text'],
        'tecnologia'    => ['Tecnología', 'hdd-stack'],
        'hogar'         => ['Hogar', 'house'],
        'otro'          => ['Otro', 'calendar-event'],
    ];

    public function __construct()
    {
        helper('recordatorio');
        $this->model = new RecordatorioModel();
    }

    public function index()
    {
        $recordatorios = $this->model->findAll();

        foreach ($recordatorios as &$r) {
            $periodoMeses = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
            $periodoDias  = $r['periodo_dias'] ? (int) $r['periodo_dias'] : null;
            $fechaEfectiva = recordatorio_fecha_efectiva($r['fecha_evento'], $periodoMeses, $periodoDias);
            $estado = recordatorio_estado($fechaEfectiva);

            $r['fecha_mostrar']  = $fechaEfectiva;
            $r['recalculada']    = $fechaEfectiva !== $r['fecha_evento'];
            $r['dias']           = $estado['dias'];
            $r['texto']          = $estado['texto'];
            $r['nivel']          = $estado['nivel'];
            $r['categoria_label'] = self::CATEGORIAS[$r['categoria']][0] ?? 'Otro';
        }
        unset($r);

        usort($recordatorios, fn($a, $b) => $a['dias'] <=> $b['dias']);

        return view('recordatorios/index', [
            'recordatorios' => $recordatorios,
            'categorias'    => self::CATEGORIAS,
        ]);
    }

    public function nuevo()
    {
        return view('recordatorios/form', ['categorias' => self::CATEGORIAS]);
    }

    public function crear()
    {
        $this->model->insert($this->datosDelFormulario());
        return redirect()->to(site_url('recordatorios'))->with('success', 'Recordatorio creado.');
    }

    public function editar(int $id)
    {
        $recordatorio = $this->model->find($id);
        if (!$recordatorio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Recordatorio no encontrado');
        }

        return view('recordatorios/form', [
            'recordatorio' => $recordatorio,
            'categorias'   => self::CATEGORIAS,
        ]);
    }

    public function actualizar(int $id)
    {
        $recordatorio = $this->model->find($id);
        if (!$recordatorio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Recordatorio no encontrado');
        }

        $this->model->update($id, $this->datosDelFormulario());
        return redirect()->to(site_url('recordatorios'))->with('success', 'Recordatorio actualizado.');
    }

    public function borrar(int $id)
    {
        $this->model->delete($id);
        return redirect()->to(site_url('recordatorios'))->with('success', 'Recordatorio eliminado.');
    }

    /**
     * Renueva: calcula la nueva fecha sumando el período a partir del día en
     * que realmente se hizo (fecha_realizado), no del día en que se pulsa el
     * botón — si se te olvida actualizarlo, el ciclo no debe desplazarse.
     */
    public function renovar(int $id)
    {
        $recordatorio = $this->model->find($id);
        if (!$recordatorio || (!$recordatorio['periodo_meses'] && !$recordatorio['periodo_dias'])) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $fechaRealizado = trim($input['fecha_realizado'] ?? '');

        try {
            $fechaBase = $fechaRealizado !== '' ? new \DateTime($fechaRealizado) : new \DateTime('today');
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Fecha inválida']);
        }

        $modStr = recordatorio_periodo_modify_string(
            $recordatorio['periodo_meses'] ? (int) $recordatorio['periodo_meses'] : null,
            $recordatorio['periodo_dias'] ? (int) $recordatorio['periodo_dias'] : null
        );

        $nuevaFecha = $fechaBase->modify($modStr)->format('Y-m-d');

        $this->model->skipValidation(true)->update($id, ['fecha_evento' => $nuevaFecha]);

        $estado = recordatorio_estado($nuevaFecha);

        return $this->response->setJSON([
            'ok'           => true,
            'fecha_evento' => $nuevaFecha,
            'fecha_fmt'    => date('d/m/Y', strtotime($nuevaFecha)),
            'texto'        => $estado['texto'],
            'nivel'        => $estado['nivel'],
        ]);
    }

    private function datosDelFormulario(): array
    {
        $categoria = $this->request->getPost('categoria');
        if (!array_key_exists($categoria, self::CATEGORIAS)) {
            $categoria = 'otro';
        }

        $periodoMeses = $this->request->getPost('periodo_meses');
        $periodoDias  = $this->request->getPost('periodo_dias');

        return [
            'titulo'        => $this->request->getPost('titulo'),
            'categoria'     => $categoria,
            'icono'         => self::CATEGORIAS[$categoria][1],
            'fecha_evento'  => $this->request->getPost('fecha_evento'),
            'periodo_meses' => $periodoMeses !== '' && $periodoMeses !== null ? (int) $periodoMeses : null,
            'periodo_dias'  => $periodoDias !== '' && $periodoDias !== null ? (int) $periodoDias : null,
            'notas'         => $this->request->getPost('notas'),
        ];
    }
}
