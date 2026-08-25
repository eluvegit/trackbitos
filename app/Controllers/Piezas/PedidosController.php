<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaFamiliaModel;
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

        // Foto y nombre de cada línea: el SKU solo no dice nada de un
        // vistazo, y esta ficha es justo donde se decide qué imprimir.
        $varianteModel = new PiezaVarianteModel();
        $familiaModel  = new PiezaFamiliaModel();
        foreach ($pedido['lineas'] as &$linea) {
            $variante = $linea['variante_id'] ? $varianteModel->find($linea['variante_id']) : null;
            $linea['nombreFamilia']  = $variante ? ($familiaModel->find($variante['familia_id'])['nombre'] ?? null) : null;
            $linea['nombreVariante'] = $variante['nombre'] ?? null;
            $linea['foto']           = $variante ? $this->miniaturaDeVariante($variante) : null;
        }
        unset($linea);

        // Qué placas se han marcado como "de este pedido" (a mano, al cargar
        // piezas a la placa desde aquí) — solo dato, sin cuadrar cantidades:
        // eso lo sigue juzgando quien mira la placa, no el sistema.
        $placas = (new \App\Models\PiezaPlacaModel())->where('pedido_id', $id)->orderBy('creado_en', 'DESC')->findAll();

        return view('piezas/pedidos/ver', [
            'pedido'  => $pedido,
            'estados' => PiezaPedidoModel::ESTADOS,
            'placas'  => $placas,
        ]);
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

    /**
     * Cuántas unidades de una línea se dan ya por buenas — a mano, sin
     * cuadrarlo contra ninguna placa: si una pieza sale mal no vale para el
     * pedido aunque esté impresa, y eso es una valoración que no le
     * corresponde adivinar al sistema. El botón +/- de la ficha del pedido
     * llama aquí con `delta`; se recorta entre 0 y la cantidad pedida.
     */
    public function ajustarCompletada(int $lineaId)
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $linea = $lineaModel->find($lineaId);
        if (!$linea) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Línea no encontrada.']);
        }

        $delta = (int) $this->request->getPost('delta');
        $nueva = max(0, min((int) $linea['cantidad'], (int) $linea['cantidad_completada'] + $delta));

        $lineaModel->update($lineaId, ['cantidad_completada' => $nueva]);
        $this->autocompletarPedidoSiProcede((int) $linea['pedido_id']);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'cantidad_completada' => $nueva]);
        }

        return redirect()->to('/piezas/pedido/' . $linea['pedido_id']);
    }

    /**
     * Si todas las líneas del pedido ya tienen su cantidad completada, el
     * pedido pasa a "completado" solo, sin esperar a que alguien cambie el
     * estado a mano — da igual en qué estado estuviera (incluso cancelado),
     * salvo que ya estuviera completado.
     */
    private function autocompletarPedidoSiProcede(int $pedidoId): void
    {
        $pedidoModel = new PiezaPedidoModel();
        $pedido = $pedidoModel->find($pedidoId);
        if (!$pedido || $pedido['estado'] === 'completado') {
            return;
        }

        $lineas = (new PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)->findAll();
        foreach ($lineas as $linea) {
            if ((int) $linea['cantidad_completada'] < (int) $linea['cantidad']) {
                return;
            }
        }

        $pedidoModel->update($pedidoId, ['estado' => 'completado', 'actualizado_en' => date('Y-m-d H:i:s')]);
    }
}
