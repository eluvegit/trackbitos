<?php

namespace App\Controllers;

use App\Libraries\RecordatorioServicio;
use App\Models\RecordatorioModel;

/**
 * Recordatorios: la lista común que también usan Coche (categoría
 * «vehiculo») y Lentillas (categoría «lentillas»). El alta, la edición y el
 * «hecho» de los avisos de esos módulos pasan por aquí; el parámetro `volver`
 * devuelve a la pantalla del módulo desde la que se entró.
 */
class Recordatorios extends BaseController
{
    /** Se mantiene por compatibilidad (Telegram, Braintogram); la fuente es el servicio. */
    public const CATEGORIAS = RecordatorioServicio::CATEGORIAS;

    /** Pantallas a las que se puede volver tras guardar: clave => ruta */
    private const VOLVER = ['coche' => 'coche/recordatorios', 'lentillas' => 'lentillas/avisos'];

    protected RecordatorioModel $model;
    protected RecordatorioServicio $servicio;

    public function __construct()
    {
        helper('recordatorio');
        $this->model    = new RecordatorioModel();
        $this->servicio = new RecordatorioServicio();
    }

    public function index()
    {
        return view('recordatorios/index_nueva', [
            'recordatorios' => $this->servicio->lista(),
            'categorias'    => RecordatorioServicio::CATEGORIAS,
        ]);
    }

    public function nuevo()
    {
        $categoria = (string) $this->request->getGet('categoria');
        return view('recordatorios/form_nueva', [
            'recordatorio' => null,
            'categoria'    => array_key_exists($categoria, RecordatorioServicio::CATEGORIAS) ? $categoria : 'otro',
            'categorias'   => RecordatorioServicio::CATEGORIAS,
            'volver'       => $this->volverClave(),
        ]);
    }

    public function crear()
    {
        if (!$this->servicio->guardar($this->datosDelFormulario())) {
            return redirect()->back()->withInput()->with('errors', $this->servicio->errores());
        }
        return redirect()->to($this->volverUrl())->with('success', 'Recordatorio creado.');
    }

    public function editar(int $id)
    {
        $recordatorio = $this->model->find($id);
        if (!$recordatorio) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Recordatorio no encontrado');
        }

        return view('recordatorios/form_nueva', [
            'recordatorio' => $recordatorio,
            'categoria'    => $recordatorio['categoria'],
            'categorias'   => RecordatorioServicio::CATEGORIAS,
            'volver'       => $this->volverClave(),
        ]);
    }

    public function actualizar(int $id)
    {
        if (!$this->model->find($id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Recordatorio no encontrado');
        }
        if (!$this->servicio->guardar($this->datosDelFormulario(), $id)) {
            return redirect()->back()->withInput()->with('errors', $this->servicio->errores());
        }
        return redirect()->to($this->volverUrl())->with('success', 'Recordatorio actualizado.');
    }

    public function borrar(int $id)
    {
        $this->servicio->borrar($id);
        return redirect()->to($this->volverUrl())->with('success', 'Recordatorio eliminado.');
    }

    /**
     * Hecho / renovar: la nueva fecha se calcula desde el día en que
     * realmente se hizo (fecha_realizado), no desde el día en que se pulsa.
     * En los de Coche apunta la acción; en los de Lentillas, la sustitución
     * (y descuenta stock). Ver RecordatorioServicio::marcarHecho().
     */
    public function renovar(int $id)
    {
        $recordatorio = $this->model->find($id);
        $deModulo = $recordatorio && isset(RecordatorioServicio::MODULOS[$recordatorio['categoria']]);
        if (!$recordatorio || (!$recordatorio['periodo_meses'] && !$recordatorio['periodo_dias'] && !$deModulo)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $fecha = trim((string) ($input['fecha_realizado'] ?? ''));
        try {
            $fecha = ($fecha !== '' ? new \DateTime($fecha) : new \DateTime('today'))->format('Y-m-d');
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'error' => 'Fecha inválida']);
        }

        $r = $this->servicio->marcarHecho($id, $fecha, ['km' => $input['km'] ?? null, 'notas' => $input['notas'] ?? '']);

        return $this->response->setJSON([
            'ok'           => true,
            'fecha_evento' => $r['fecha_evento'],
            'fecha_fmt'    => $r['fecha_evento'] ? date('d/m/Y', strtotime($r['fecha_evento'])) : '',
            'texto'        => $r['texto'],
            'nivel'        => $r['nivel'],
        ]);
    }

    private function datosDelFormulario(): array
    {
        $p = fn (string $k) => $this->request->getPost($k);

        return [
            'titulo'        => trim((string) $p('titulo')),
            'categoria'     => (string) $p('categoria'),
            'fecha_evento'  => $p('fecha_evento'),
            'periodo_meses' => $p('periodo_meses'),
            'periodo_dias'  => $p('periodo_dias'),
            'intervalo_km'  => $p('intervalo_km'),
            'elemento'      => $p('elemento'),
            'persistente'   => $p('persistente'),
            'avisar'        => $p('avisar'),
            'notas'         => $p('notas'),
        ];
    }

    private function volverClave(): string
    {
        $v = (string) ($this->request->getPost('volver') ?? $this->request->getGet('volver'));
        return isset(self::VOLVER[$v]) ? $v : '';
    }

    private function volverUrl(): string
    {
        return site_url(self::VOLVER[$this->volverClave()] ?? 'recordatorios');
    }
}
