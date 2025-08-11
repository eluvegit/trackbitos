<?php

namespace App\Controllers;

use App\Models\CompraSupermercadoModel;
use App\Models\CompraProductoModel;
use App\Models\CompraPrecioModel;
use App\Models\CompraFaltanteModel;
use App\Models\CompraCompradoModel;
use CodeIgniter\HTTP\RedirectResponse;

class Compras extends BaseController
{
    public function index()
    {
        $superModel = new CompraSupermercadoModel();
        $data['supermercados'] = $superModel->findAll();
        return view('compras/index', $data);
    }

    public function supermercados()
    {
        $superModel = new CompraSupermercadoModel();
        $data['supermercados'] = $superModel->findAll();
        return view('compras/index', $data);
    }

    public function nuevoSupermercado()
    {
        return view('compras/supermercados/form');
    }

    public function crearSupermercado()
    {
        $superModel = new CompraSupermercadoModel();
        $superModel->insert(['nombre' => $this->request->getPost('nombre')]);
        return redirect()->to(site_url('compras/supermercados'));
    }

    public function editarSupermercado($id)
    {
        $superModel = new CompraSupermercadoModel();
        $supermercado = $superModel->find($id);

        if (!$supermercado) {
            return redirect()->to(site_url('compras/supermercados'))->with('error', 'Supermercado no encontrado.');
        }

        return view('compras/supermercados/form', ['supermercado' => $supermercado]);
    }

    public function guardarSupermercado($id)
    {
        $superModel = new CompraSupermercadoModel();

        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
        ];

        $superModel->update($id, $data);

        return redirect()->to(site_url('compras/supermercados'))->with('message', 'Supermercado actualizado.');
    }

    public function eliminarSupermercado($id)
    {
        $superModel = new CompraSupermercadoModel();
        $superModel->delete($id);
        return redirect()->to(site_url('compras/supermercados'));
    }

    public function productos($supermercadoId)
    {
        $productoModel     = new CompraProductoModel();
        $superModel        = new CompraSupermercadoModel();
        $faltanteModel     = new \App\Models\CompraFaltanteModel();
        $compradoModel     = new \App\Models\CompraCompradoModel();

        $supermercado = $superModel->find($supermercadoId);
        if (!$supermercado) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Supermercado no encontrado');
        }

        $productos = $productoModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('nombre', 'ASC')
            ->findAll();

        foreach ($productos as &$producto) {
            $producto['faltante'] = $faltanteModel->where('producto_id', $producto['id'])->countAllResults() > 0;
            $producto['comprado'] = $compradoModel->where('producto_id', $producto['id'])->countAllResults() > 0;
        }

        return view('compras/productos', [
            'productos' => $productos,
            'supermercado_id' => $supermercadoId,
            'supermercado_nombre' => $supermercado['nombre']
        ]);
    }

    public function crearProducto()
    {
        $productoModel = new CompraProductoModel();

        $data = [
            'supermercado_id' => $this->request->getPost('supermercado_id'),
            'nombre' => $this->request->getPost('nombre'),
            'imagen' => $this->request->getPost('imagen')
        ];

        $productoModel->insert($data);

        return redirect()->to(site_url('compras/productos/' . $data['supermercado_id']));
    }

    public function eliminarProducto($id)
    {
        $productoModel = new CompraProductoModel();
        $producto = $productoModel->find($id);

        if ($producto) {
            $productoModel->delete($id);
        }

        return redirect()->to(site_url('compras/productos/' . $producto['supermercado_id']));
    }

    public function editarProducto($id)
    {
        $productoModel = new CompraProductoModel();
        $superModel = new CompraSupermercadoModel();

        $producto = $productoModel->find($id);

        if (!$producto) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Producto no encontrado');
        }

        $supermercado = $superModel->find($producto['supermercado_id']);

        return view('compras/productos/form', [
            'producto' => $producto,
            'supermercado' => $supermercado
        ]);
    }

    public function actualizarProducto($id)
    {
        $productoModel = new CompraProductoModel();
        $producto = $productoModel->find($id);

        if (!$producto) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Producto no encontrado');
        }

        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'imagen' => $this->request->getPost('imagen'),
        ];

        $productoModel->update($id, $data);

        return redirect()->to(site_url('compras/productos/' . $producto['supermercado_id']))->with('message', 'Producto actualizado.');
    }


    public function crearPrecio()
    {
        $precioModel = new CompraPrecioModel();

        $data = [
            'id' => $this->request->getPost('id'),
            'formato' => $this->request->getPost('formato'),
            'precio' => $this->request->getPost('precio')
        ];

        $precioModel->insert($data);

        $producto = (new CompraProductoModel())->find($data['id']);
        return redirect()->to(site_url('compras/productos/' . $producto['supermercado_id']));
    }

    public function eliminarPrecio($id)
    {
        $precioModel = new CompraPrecioModel();
        $precio = $precioModel->find($id);

        if ($precio) {
            $producto = (new CompraProductoModel())->find($precio['id']);
            $precioModel->delete($id);
            return redirect()->to(site_url('compras/productos/' . $producto['supermercado_id']));
        }

        return redirect()->back();
    }

    // Estado de productos
    private function actualizarEstado($productoId, $campo, $valor)
    {
        $estadoModel = new CompraProductoModel();
        $estado = $estadoModel->where('producto_id', $productoId)->first();

        if ($estado) {
            $estado[$campo] = $valor;
            $estadoModel->update($estado['id'], $estado);
        } else {
            $estadoModel->insert([
                'producto_id' => $productoId,
                $campo => $valor
            ]);
        }
    }

    public function faltantes($supermercadoId)
    {
        $superModel = new CompraSupermercadoModel();
        $productoModel = new CompraProductoModel();
        $estadoModel = new CompraProductoModel();

        $supermercado = $superModel->find($supermercadoId);
        $productos = $productoModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('nombre', 'ASC')
            ->findAll();


        foreach ($productos as &$producto) {
            $faltanteModel = new CompraFaltanteModel();
            $producto['faltante'] = $faltanteModel->where('producto_id', $producto['id'])->countAllResults() > 0;
        }

        return view('compras/supermercados/faltantes', [
            'productos' => $productos,
            'supermercado_id' => $supermercadoId,
            'supermercado_nombre' => $supermercado['nombre'] ?? 'Supermercado'
        ]);
    }

    public function comprados($supermercadoId)
    {
        $superModel      = new CompraSupermercadoModel();
        $productoModel   = new CompraProductoModel();
        $faltanteModel   = new \App\Models\CompraFaltanteModel();
        $compradoModel   = new \App\Models\CompraCompradoModel();

        $supermercado = $superModel->find($supermercadoId);
        if (!$supermercado) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Supermercado no encontrado');
        }

        // Obtener los productos marcados como faltantes en este supermercado
        $faltantes = $faltanteModel
            ->select('compra_faltantes.producto_id')
            ->join('compra_productos', 'compra_productos.id = compra_faltantes.producto_id')
            ->where('compra_productos.supermercado_id', $supermercadoId)
            ->findAll();

        $faltanteIds = array_column($faltantes, 'producto_id');

        if (empty($faltanteIds)) {
            $productos = [];
        } else {
            $productos = $productoModel
                ->whereIn('id', $faltanteIds)
                ->orderBy('nombre', 'asc')
                ->findAll();

            $comprados = $compradoModel
                ->select('producto_id')
                ->whereIn('producto_id', $faltanteIds)
                ->findAll();

            $idsComprados = array_column($comprados, 'producto_id');

            foreach ($productos as &$producto) {
                $producto['comprado'] = in_array($producto['id'], $idsComprados);
            }

            // Ordenar: comprados primero, luego por nombre
            usort($productos, function ($a, $b) {
                if ($a['comprado'] !== $b['comprado']) {
                    return $a['comprado'] ? 1 : -1;
                }
                return strcmp($a['nombre'], $b['nombre']);
            });
        }

        return view('compras/supermercados/comprados', [
            'productos' => $productos,
            'supermercado_id' => $supermercadoId,
            'supermercado_nombre' => $supermercado['nombre']
        ]);
    }

    private function marcarEstado($modelo, $productoId)
    {
        if (!$modelo->where('producto_id', $productoId)->first()) {
            $modelo->insert(['producto_id' => $productoId]);
        }
    }

    private function desmarcarEstado($modelo, $productoId)
    {
        $modelo->where('producto_id', $productoId)->delete();
    }

    public function marcarFaltante($id)
    {
        $this->marcarEstado(new CompraFaltanteModel(), $id);
        return redirect()->back();
    }

    public function desmarcarFaltante($id)
    {
        $faltanteModel = new \App\Models\CompraFaltanteModel();
        $compradoModel = new \App\Models\CompraCompradoModel();

        // Eliminar de la tabla de faltantes
        $faltanteModel->where('producto_id', $id)->delete();

        // También eliminar de la tabla de comprados si existe
        $compradoModel->where('producto_id', $id)->delete();

        return redirect()->back();
    }


    public function marcarComprado($id)
    {
        $this->marcarEstado(new CompraCompradoModel(), $id);
        return redirect()->back();
    }

    public function desmarcarComprado($id)
    {
        $this->desmarcarEstado(new CompraCompradoModel(), $id);
        return redirect()->back();
    }

    public function limpiarFaltantes($supermercadoId)
    {
        $faltanteModel = new \App\Models\CompraFaltanteModel();
        $compradoModel = new \App\Models\CompraCompradoModel();
        $productoModel = new \App\Models\CompraProductoModel();

        // Obtener los productos de ese supermercado
        $productos = $productoModel->where('supermercado_id', $supermercadoId)->findAll();
        $productoIds = array_column($productos, 'id');

        if (!empty($productoIds)) {
            // Borrar todos los registros faltantes
            $faltanteModel->whereIn('producto_id', $productoIds)->delete();

            // Borrar también todos los registros comprados de los mismos productos
            $compradoModel->whereIn('producto_id', $productoIds)->delete();
        }

        return redirect()->back()->with('message', 'Faltantes y comprados reiniciados.');
    }



    public function limpiarComprados($supermercadoId)
    {
        $compradoModel = new CompraCompradoModel();
        $productoModel = new CompraProductoModel();

        $productos = $productoModel->where('supermercado_id', $supermercadoId)->findAll();
        foreach ($productos as $producto) {
            $compradoModel->where('producto_id', $producto['id'])->delete();
        }

        return redirect()->back();
    }
}
