<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaPedidoLineaModel;
use App\Models\PiezaPedidoModel;
use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaImagenesPublicas;

/**
 * Vista web de los pedidos entrantes desde sterclicks (recibidos por
 * App\Controllers\Piezas\SterclicksApi::pedidos). Solo lectura + cambio de
 * estado; las líneas en sí no se editan aquí.
 */
class PedidosController extends BaseController
{
    protected $helpers = ['url', 'form', 'piezas_imagenes'];

    public function index()
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $varianteModel = new PiezaVarianteModel();

        $pedidos = (new PiezaPedidoModel())->recientes();
        foreach ($pedidos as &$pedido) {
            $lineas = $lineaModel->where('pedido_id', $pedido['id'])->findAll();

            $fotos = [];
            $totalPiezas = 0;
            foreach ($lineas as $linea) {
                $totalPiezas += (int) $linea['cantidad'];
                if (!$linea['variante_id']) {
                    continue;
                }
                $variante = $varianteModel->find($linea['variante_id']);
                if ($variante && ($foto = $this->miniaturaDeVariante($variante))) {
                    $fotos[] = $foto;
                }
            }

            $pedido['numLineas'] = count($lineas);
            $pedido['totalPiezas'] = $totalPiezas;
            $pedido['fotos'] = $fotos;
        }
        unset($pedido);

        // Tablero por estado, mismo orden que el flujo real: recién llegado ->
        // en producción -> hecho, con cancelados aparte al final.
        $columnas = [
            'nuevo'         => ['titulo' => 'Pendientes', 'pedidos' => []],
            'en_produccion' => ['titulo' => 'Produciendo', 'pedidos' => []],
            'completado'    => ['titulo' => 'Hecho', 'pedidos' => []],
            'cancelado'     => ['titulo' => 'Cancelados', 'pedidos' => []],
        ];
        foreach ($pedidos as $pedido) {
            $columnas[$pedido['estado']]['pedidos'][] = $pedido;
        }

        return view('piezas/pedidos/index', ['columnas' => $columnas, 'estados' => PiezaPedidoModel::ESTADOS]);
    }

    /** Misma cascada que Web::fotosDe(), pero por variante suelta (sin versión concreta). */
    private function miniaturaDeVariante(array $variante): ?string
    {
        $render = (new PiezaRenderModel())->where('variante_id', $variante['id'])->orderBy('subida_en', 'DESC')->first();
        $registro = $render ?: ((new PiezaReferenciaModel())->deVariante((int) $variante['familia_id'], (int) $variante['id'])[0] ?? null);

        if (!$registro) {
            return null;
        }

        return imagen_pieza($registro, $render ? 'render' : 'referencia', PiezaImagenesPublicas::MINIATURA);
    }

    public function ver(int $id)
    {
        $pedido = (new PiezaPedidoModel())->conLineas($id);
        if (!$pedido) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        return view('piezas/pedidos/ver', ['pedido' => $pedido, 'estados' => PiezaPedidoModel::ESTADOS]);
    }

    public function cambiarEstado(int $id)
    {
        $model = new PiezaPedidoModel();
        $pedido = $model->find($id);
        if (!$pedido) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        $estado = $this->request->getPost('estado');
        if (!in_array($estado, PiezaPedidoModel::ESTADOS, true)) {
            return redirect()->back()->with('error', 'Estado no válido.');
        }

        $model->update($id, ['estado' => $estado, 'actualizado_en' => date('Y-m-d H:i:s')]);

        return redirect()->to('/piezas/pedido/' . $id)->with('success', 'Estado actualizado.');
    }
}
