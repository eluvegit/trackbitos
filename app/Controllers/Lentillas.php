<?php

namespace App\Controllers;

use App\Models\LentillasComprasModel;
use App\Models\LentillasSustitucionesModel;
use App\Models\LentillasStockModel;
use App\Models\LentillasAvisosModel;

use DateTime;

class Lentillas extends BaseController
{
    public function index()
    {
        $avisoModel = new \App\Models\LentillasAvisosModel();
        $sustModel = new \App\Models\LentillasSustitucionesModel();

        // Buscar el aviso configurado para "lentillas"
        $aviso = $avisoModel->where('item', 'lentillas')->first();

        $dias = null;
        $mostrarAlerta = false;
        $ultimoCambio = null;

        if ($aviso) {
            // Buscar la última sustitución relacionada con lentillas
            $ultima = $sustModel
                ->whereIn('elemento', ['lentillas', 'lentilla izquierda', 'lentilla derecha'])
                ->orderBy('fecha', 'DESC')
                ->first();

            if ($ultima) {
                $fechaUltima = new \DateTime($ultima['fecha']);
                $hoy = new \DateTime();
                $dias = $fechaUltima->diff($hoy)->days;

                $mostrarAlerta = $dias > $aviso['periodo_dias'];

                $ultimoCambio = [
                    'fecha' => $ultima['fecha'],
                    'dias' => $dias,
                ];
            }
        }

        return view('lentillas/index', [
            'dias' => $dias,
            'mostrarAlerta' => $mostrarAlerta,
            'ultimoCambio' => $ultimoCambio,
        ]);
    }



    public function avisos()
    {
        $avisoModel = new \App\Models\LentillasAvisosModel();
        $sustModel  = new \App\Models\LentillasSustitucionesModel();

        $avisos = $avisoModel->findAll();
        $avisosProcesados = [];

        foreach ($avisos as $aviso) {
            $ultimoCambio = $sustModel
                ->where('elemento', $aviso['item'])
                ->orderBy('fecha', 'DESC')
                ->first();

            $fechaUltima = $ultimoCambio['fecha'] ?? null;
            $diasPasados = null;

            if ($fechaUltima) {
                try {
                    $diasPasados = (new \DateTime())->diff(new \DateTime($fechaUltima))->days;
                } catch (\Exception $e) {
                    log_message('error', "Error al calcular días de diferencia para {$aviso['item']}: " . $e->getMessage());
                }
            }

            $avisosProcesados[] = [
                'id'           => $aviso['id'],
                'item'         => $aviso['item'],
                'dias_maximos' => $aviso['periodo_dias'],
                'fecha'        => $fechaUltima,
                'dias_pasados' => $diasPasados,
            ];
        }

        return view('lentillas/avisos', ['avisos' => $avisosProcesados]);
    }


    public function editarAviso($id)
    {
        $model = new LentillasAvisosModel();
        $aviso = $model->find($id);

        if (!$aviso) {
            return redirect()->to(site_url('lentillas/avisos'))->with('error', 'Aviso no encontrado');
        }

        return view('lentillas/editar_aviso', ['aviso' => $aviso]);
    }

    public function actualizarAviso($id)
    {
        $model = new LentillasAvisosModel();

        $data = [
            'item'           => $this->request->getPost('item'),
            'periodo_dias'   => (int) $this->request->getPost('periodo_dias'),
        ];

        $model->update($id, $data);

        return redirect()->to(site_url('lentillas/avisos'))->with('message', 'Aviso actualizado correctamente');
    }

    public function crearAviso()
    {
        $model = new LentillasAvisosModel();

        if ($this->request->getMethod(true) === 'POST') {
            $item          = $this->request->getPost('item');
            $diasMaximos   = (int) $this->request->getPost('dias_maximos');
            $fechaCambio   = date('Y-m-d'); // O permitir input de fecha si quieres

            // Insertar usando nombres válidos
            $model->insert([
                'item'           => $item,
                'periodo_dias'   => $diasMaximos,
            ]);

            return redirect()->to(site_url('lentillas/avisos'))->with('message', 'Aviso creado correctamente');
        }

        return view('lentillas/crear_aviso');
    }



    public function eliminarAviso($id)
    {
        $model = new LentillasAvisosModel();
        $model->delete($id);
        return redirect()->to(site_url('lentillas/avisos'))->with('message', 'Aviso eliminado');
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

        return view('lentillas/compras', $data);
    }

    public function editarCompra($id)
    {
        $model = new LentillasComprasModel();
        $compra = $model->find($id);

        if (!$compra) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Compra no encontrada');
        }

        return view('lentillas/editar_compra', ['compra' => $compra]);
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

        return view('lentillas/stock', ['items' => $items]);
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

            // Guardar
            if (! $model->save([
                'elemento' => $elemento,
                'fecha'    => $fecha,
                'notas'    => $notas,
            ])) {
                log_message('error', 'Error al guardar sustitución: ' . json_encode($model->errors()));
                return redirect()->back()->with('error', 'No se pudo registrar la sustitución.');
            }

            // Actualizar stock
            $itemsASustituir = $elemento === 'lentillas'
                ? ['lentilla izquierda', 'lentilla derecha']
                : [$elemento];

            foreach ($itemsASustituir as $item) {
                $registro = $stockModel->where('item', $item)->first();
                if ($registro) {
                    $nuevaCantidad = max(0, (int) $registro['cantidad'] - 1);
                    $stockModel->update($registro['id'], [
                        'cantidad' => $nuevaCantidad,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    log_message('info', "Stock actualizado: $item -> $nuevaCantidad");
                } else {
                    log_message('error', "Elemento de stock no encontrado: $item");
                }
            }

            return redirect()->to(site_url('lentillas/sustituciones'))->with('message', 'Sustitución registrada correctamente.');
        }

        // Mostrar vista
        return view('lentillas/sustituciones', [
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

        return view('lentillas/editar_sustitucion', ['sustitucion' => $sustitucion]);
    }

    public function actualizarSustitucion($id)
    {
        $model = new \App\Models\LentillasSustitucionesModel();

        $data = [
            'elemento' => $this->request->getPost('elemento'),
            'fecha'    => $this->request->getPost('fecha'),
            'notas'    => $this->request->getPost('notas'),
        ];

        if (!$model->update($id, $data)) {
            return redirect()->back()->with('error', 'Error al actualizar sustitución.');
        }

        return redirect()->to(site_url('lentillas/sustituciones'))->with('message', 'Sustitución actualizada correctamente.');
    }

    public function eliminarSustitucion($id)
    {
        $model = new \App\Models\LentillasSustitucionesModel();

        if (! $model->find($id)) {
            return redirect()->to(site_url('lentillas/sustituciones'))->with('error', 'Sustitución no encontrada.');
        }

        $model->delete($id);
        return redirect()->to(site_url('lentillas/sustituciones'))->with('message', 'Sustitución eliminada correctamente.');
    }
}
