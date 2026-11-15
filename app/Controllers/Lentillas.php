<?php

namespace App\Controllers;

use App\Models\LentillasComprasModel;
use App\Models\LentillasSustitucionesModel;
use App\Models\LentillasStockModel;
use App\Libraries\RecordatorioServicio;

use DateTime;

/**
 * Lentillas: cambios (sustituciones), stock, compras y avisos.
 *
 * Los avisos ya no viven en `lentillas_avisos`: son los recordatorios de
 * categoría «lentillas» de la lista común (ver RecordatorioServicio), ligados
 * a un elemento («lentillas», «presión»…). Registrar una sustitución renueva
 * el aviso de ese elemento, y marcar el aviso como hecho desde Recordatorios
 * apunta la sustitución y descuenta el stock.
 */
class Lentillas extends BaseController
{
    protected RecordatorioServicio $servicio;

    public function __construct()
    {
        helper('recordatorio');
        $this->servicio = new RecordatorioServicio();
    }

    public function index()
    {
        $sustModel = new LentillasSustitucionesModel();

        // Aviso de cambio de lentillas (su plazo marca cuándo toca).
        $aviso = $this->servicio->deElemento('lentillas');

        $ultima = $sustModel
            ->whereIn('elemento', ['lentillas', 'lentilla izquierda', 'lentilla derecha'])
            ->orderBy('fecha', 'DESC')
            ->first();

        $dias = null;
        $mostrarAlerta = false;
        $ultimoCambio = null;
        if ($ultima) {
            $dias = (new \DateTime($ultima['fecha']))->diff(new \DateTime('today'))->days;
            $mostrarAlerta = $aviso && !empty($aviso['periodo_dias']) && $dias > (int) $aviso['periodo_dias'];
            $ultimoCambio = ['fecha' => $ultima['fecha'], 'dias' => $dias];
        }

        return view(rec_vista('lentillas', 'lentillas/index'), [
            'dias'          => $dias,
            'mostrarAlerta' => $mostrarAlerta,
            'ultimoCambio'  => $ultimoCambio,
            'stockItems'    => (new LentillasStockModel())->findAll(),
            'periodo'       => $aviso && !empty($aviso['periodo_dias']) ? (int) $aviso['periodo_dias'] : null,
            'avisos'        => $this->servicio->lista('lentillas'),
            'ultimos'       => $sustModel->orderBy('fecha', 'DESC')->orderBy('id', 'DESC')->findAll(4),
        ]);
    }

    // ---- Avisos: recordatorios de categoría «lentillas» (alta y edición en el formulario común) ----
    public function avisos()
    {
        return view('lentillas/avisos_nueva', ['avisos' => $this->servicio->lista('lentillas')]);
    }

    public function crearAviso()
    {
        return redirect()->to(site_url('recordatorios/nuevo?categoria=lentillas&volver=lentillas'));
    }

    public function editarAviso($id)
    {
        return redirect()->to(site_url('recordatorios/editar/' . (int) $id . '?volver=lentillas'));
    }

    public function compras()
    {
        $model = new LentillasComprasModel();

        if ($this->request->getMethod(true) === 'POST') {
            $data = [
                'tipo'    => $this->request->getPost('tipo'),
                'precio'  => $this->request->getPost('precio'),
                'fecha'   => $this->request->getPost('fecha'),
                'notas'   => $this->request->getPost('notas'),
            ];

            if (! $model->save($data)) {
                dd($model->errors()); // ← aquí verás si hay errores de validación
            }

            return redirect()->to(site_url('lentillas/compras'));
        }


        $data = [
            'compras' => $model->orderBy('fecha', 'DESC')->findAll(),
        ];

        return view(rec_vista('lentillas', 'lentillas/compras'), $data);
    }

    public function editarCompra($id)
    {
        $model = new LentillasComprasModel();
        $compra = $model->find($id);

        if (!$compra) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Compra no encontrada');
        }

        return view(rec_vista('lentillas', 'lentillas/editar_compra'), ['compra' => $compra]);
    }

    public function eliminarCompra($id)
    {
        $model = new LentillasComprasModel();
        $model->delete($id);

        return redirect()->to(site_url('lentillas/compras'));
    }


    public function actualizarCompra($id)
    {
        $model = new LentillasComprasModel();

        $model->update($id, [
            'tipo'   => $this->request->getPost('tipo'),
            'precio' => $this->request->getPost('precio'),
            'fecha'  => $this->request->getPost('fecha'),
            'notas'  => $this->request->getPost('notas'),
        ]);

        return redirect()->to(site_url('lentillas/compras'));
    }

    public function stock()
    {
        $model = new LentillasStockModel();
        $items = $model->findAll();

        return view(rec_vista('lentillas', 'lentillas/stock'), ['items' => $items]);
    }

    public function actualizarStock()
    {
        $model = new LentillasStockModel();

        foreach ($this->request->getPost('items') as $id => $cantidad) {
            $model->update($id, [
                'cantidad' => (int) $cantidad,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to(site_url('lentillas/stock'))->with('message', 'Inventario actualizado');
    }
    public function sustituciones()
    {
        helper('text'); // Por si hace falta en algún punto

        $model = new \App\Models\LentillasSustitucionesModel();
        $stockModel = new \App\Models\LentillasStockModel();

        // Cargar historial siempre
        $sustituciones = $model->orderBy('fecha', 'DESC')->findAll();

        // Manejo POST
        if ($this->request->getMethod(true) === 'POST') {
            $elemento = strtolower(trim($this->request->getPost('elemento')));
            $fecha    = $this->request->getPost('fecha') ?: date('Y-m-d');
            $notas    = trim($this->request->getPost('notas') ?? '');

            log_message('info', "INTENTO DE REGISTRO: elemento=$elemento, fecha=$fecha, notas=$notas");

            if (empty($elemento)) {
                return redirect()->back()->with('error', 'Debes indicar qué elemento fue sustituido.');
            }

            // Guarda, descuenta stock y renueva el aviso de ese elemento (si lo hay).
            $cambiosStock = $this->servicio->registrarSustitucion($elemento, $fecha, $notas);

            return redirect()->to(site_url('lentillas/sustituciones'))
                ->with('message', 'Sustitución registrada correctamente.')
                ->with('stock_cambios', $cambiosStock);
        }

        // Mostrar vista
        return view(rec_vista('lentillas', 'lentillas/sustituciones'), [
            'sustituciones' => $sustituciones,
        ]);
    }

    public function editarSustitucion($id)
    {
        $model = new \App\Models\LentillasSustitucionesModel();
        $sustitucion = $model->find($id);

        if (!$sustitucion) {
            return redirect()->to(site_url('lentillas/sustituciones'))->with('error', 'Sustitución no encontrada.');
        }

        return view(rec_vista('lentillas', 'lentillas/editar_sustitucion'), ['sustitucion' => $sustitucion]);
    }

    public function actualizarSustitucion($id)
    {
        $model = new \App\Models\LentillasSustitucionesModel();

        $data = [
            'elemento' => $this->request->getPost('elemento'),
            'fecha'    => $this->request->getPost('fecha'),
            'notas'    => $this->request->getPost('notas'),
        ];

        $antes = $model->find($id);
        $data['elemento'] = mb_strtolower(trim((string) $data['elemento']));
        if (!$antes || !$model->update($id, $data)) {
            return redirect()->back()->with('error', 'Error al actualizar sustitución.');
        }
        $this->servicio->reenlazarSustitucion($antes, $model->find($id));

        return redirect()->to(site_url('lentillas/sustituciones'))->with('message', 'Sustitución actualizada correctamente.');
    }

    public function eliminarSustitucion($id)
    {
        $model = new \App\Models\LentillasSustitucionesModel();

        $antes = $model->find($id);
        if (! $antes) {
            return redirect()->to(site_url('lentillas/sustituciones'))->with('error', 'Sustitución no encontrada.');
        }

        $model->delete($id);
        $this->servicio->reenlazarSustitucion($antes, null);
        return redirect()->to(site_url('lentillas/sustituciones'))->with('message', 'Sustitución eliminada correctamente.');
    }
}
