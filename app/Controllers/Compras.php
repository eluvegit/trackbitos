<?php

namespace App\Controllers;

use App\Models\CompraSupermercadoModel;
use App\Models\CompraProductoModel;
use App\Models\CompraPrecioModel;
use App\Models\CompraFaltanteModel;
use App\Models\CompraCompradoModel;
use App\Models\CompraZonaModel;
use CodeIgniter\HTTP\RedirectResponse;

class Compras extends BaseController
{
    // Zonas/pasillos típicas que se ofrecen como sugerencia rápida al crear una zona.
    private const ZONAS_SUGERIDAS = [
        'Fruta y verdura',
        'Panadería',
        'Charcutería',
        'Carnicería',
        'Pescadería',
        'Lácteos',
        'Yogures',
        'Congelados',
        'Conservas',
        'Patatas',
        'Dulces',
        'Bebidas',
        'Higiene y limpieza',
    ];

    /**
     * Agrupa productos por zona respetando el orden de las zonas (el recorrido),
     * dejando los productos sin zona asignada en un grupo final.
     */
    private function agruparPorZona(array $productos, array $zonas, bool $incluirVacias = false): array
    {
        $grupos = [];
        foreach ($zonas as $zona) {
            $grupos[$zona['id']] = ['zona' => $zona, 'productos' => []];
        }
        $sinZona = ['zona' => null, 'productos' => []];

        foreach ($productos as $producto) {
            $zonaId = $producto['zona_id'] ?? null;
            if ($zonaId !== null && isset($grupos[$zonaId])) {
                $grupos[$zonaId]['productos'][] = $producto;
            } else {
                $sinZona['productos'][] = $producto;
            }
        }

        $grupos = array_values($grupos);
        if (!$incluirVacias) {
            // En las listas de la compra solo se muestran las zonas que tienen productos,
            // para no llenarlas de secciones vacías.
            $grupos = array_filter($grupos, fn ($g) => !empty($g['productos']));
        }
        if ($incluirVacias || !empty($sinZona['productos'])) {
            $grupos[] = $sinZona;
        }

        return array_values($grupos);
    }
    public function index()
    {
        $superModel = new CompraSupermercadoModel();
        $data['supermercados'] = $superModel
            ->where('visible', 1)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        return view('compras/index', $data);
    }

    public function supermercados()
    {
        $superModel = new CompraSupermercadoModel();
        $data['supermercados'] = $superModel
            ->where('visible', 1)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        return view('compras/index', $data);
    }

    public function gestionarSupermercados()
    {
        $superModel = new CompraSupermercadoModel();
        $data['supermercados'] = $superModel
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
        return view('compras/supermercados/gestionar', $data);
    }

    public function reordenarSupermercados()
    {
        $ids = $this->request->getJSON(true)['orden'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        $superModel = new CompraSupermercadoModel();
        foreach ($ids as $index => $id) {
            $superModel->skipValidation(true)->update((int) $id, ['orden' => $index + 1]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function toggleVisibleSupermercado($id)
    {
        $superModel = new CompraSupermercadoModel();
        $supermercado = $superModel->find($id);
        if (!$supermercado) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $nuevo = $supermercado['visible'] ? 0 : 1;
        $superModel->skipValidation(true)->update($id, ['visible' => $nuevo]);

        return $this->response->setJSON(['ok' => true, 'visible' => (bool) $nuevo]);
    }

    public function nuevoSupermercado()
    {
        return view('compras/supermercados/form');
    }

    public function crearSupermercado()
    {
        $superModel = new CompraSupermercadoModel();
        $siguienteOrden = (int) ($superModel->selectMax('orden')->first()['orden'] ?? 0) + 1;

        $superModel->insert([
            'nombre' => $this->request->getPost('nombre'),
            'orden'  => $siguienteOrden,
        ]);
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
        $zonaModel         = new CompraZonaModel();
        $faltanteModel     = new \App\Models\CompraFaltanteModel();
        $compradoModel     = new \App\Models\CompraCompradoModel();

        $supermercado = $superModel->find($supermercadoId);
        if (!$supermercado) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Supermercado no encontrado');
        }

        $zonas = $zonaModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $productos = $productoModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();

        foreach ($productos as &$producto) {
            $producto['faltante'] = $faltanteModel->where('producto_id', $producto['id'])->countAllResults() > 0;
            $producto['comprado'] = $compradoModel->where('producto_id', $producto['id'])->countAllResults() > 0;
        }

        return view('compras/productos', [
            'grupos' => $this->agruparPorZona($productos, $zonas, true),
            'zonas' => $zonas,
            'supermercado_id' => $supermercadoId,
            'supermercado_nombre' => $supermercado['nombre']
        ]);
    }

    public function crearProducto()
    {
        $productoModel = new CompraProductoModel();

        $zonaId = $this->request->getPost('zona_id');
        $zonaId = $zonaId !== '' ? $zonaId : null;

        // Nuevo producto al final de su zona, para no desordenar el recorrido ya guardado.
        $maxOrden = $productoModel
            ->where('supermercado_id', $this->request->getPost('supermercado_id'))
            ->where('zona_id', $zonaId)
            ->selectMax('orden')
            ->first()['orden'] ?? null;

        $data = [
            'supermercado_id' => $this->request->getPost('supermercado_id'),
            'zona_id' => $zonaId,
            'nombre' => $this->request->getPost('nombre'),
            'imagen' => $this->request->getPost('imagen'),
            'orden' => $maxOrden !== null ? $maxOrden + 1 : 0,
        ];

        $productoModel->insert($data);

        return redirect()->to(site_url('compras/productos/' . $data['supermercado_id']));
    }

    // Guarda, tras arrastrar, el orden (y la zona) de los productos de una zona/dropzone.
    public function reordenarProductos()
    {
        $data = $this->request->getJSON(true);
        $zonaId = $data['zona_id'] ?? null;
        $zonaId = ($zonaId === '' || $zonaId === null) ? null : (int) $zonaId;
        $orden = $data['orden'] ?? [];

        if (!is_array($orden) || empty($orden)) {
            return $this->response->setJSON(['ok' => true]);
        }

        $productoModel = new CompraProductoModel();
        foreach ($orden as $index => $productoId) {
            $productoModel->skipValidation(true)->update((int) $productoId, [
                'zona_id' => $zonaId,
                'orden'   => $index,
            ]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    // Alterna el favorito de un producto (⭐ = compra habitual, para distinguirlo
    // de los que solo se han comprado una vez o rara vez).
    public function toggleFavorito($id)
    {
        $productoModel = new CompraProductoModel();
        $producto = $productoModel->find($id);

        if (!$producto) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $favorito = $producto['favorito'] ? 0 : 1;
        $productoModel->skipValidation(true)->update($id, ['favorito' => $favorito]);

        return $this->response->setJSON(['ok' => true, 'favorito' => (bool) $favorito]);
    }

    // Actualiza el precio de un producto desde el modal de edición rápida.
    public function actualizarPrecioProducto($id)
    {
        $productoModel = new CompraProductoModel();
        $producto = $productoModel->find($id);

        if (!$producto) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $precio = $this->request->getJSON(true)['precio'] ?? null;
        $precio = ($precio === '' || $precio === null) ? null : round((float) $precio, 2);

        $productoModel->skipValidation(true)->update($id, ['precio' => $precio]);

        return $this->response->setJSON(['ok' => true, 'precio' => $precio]);
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
        $zonaModel = new CompraZonaModel();

        $producto = $productoModel->find($id);

        if (!$producto) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Producto no encontrado');
        }

        $supermercado = $superModel->find($producto['supermercado_id']);
        $zonas = $zonaModel
            ->where('supermercado_id', $producto['supermercado_id'])
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return view('compras/productos/form', [
            'producto' => $producto,
            'supermercado' => $supermercado,
            'zonas' => $zonas
        ]);
    }

    public function actualizarProducto($id)
    {
        $productoModel = new CompraProductoModel();
        $producto = $productoModel->find($id);

        if (!$producto) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Producto no encontrado');
        }

        $zonaId = $this->request->getPost('zona_id');

        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'imagen' => $this->request->getPost('imagen'),
            'zona_id' => $zonaId !== '' ? $zonaId : null,
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
        $zonaModel = new CompraZonaModel();

        $supermercado = $superModel->find($supermercadoId);
        $zonas = $zonaModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $productos = $productoModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('nombre', 'ASC')
            ->findAll();

        $productoIds = array_column($productos, 'id');
        if (empty($productoIds)) {
            $faltanteIds = [];
        } else {
            $faltanteModel = new CompraFaltanteModel();
            $faltanteIds = array_flip(array_column(
                $faltanteModel->whereIn('producto_id', $productoIds)->findAll(),
                'producto_id'
            ));
        }

        foreach ($productos as &$producto) {
            $producto['faltante'] = isset($faltanteIds[$producto['id']]);
        }

        return view('compras/supermercados/faltantes', [
            'grupos' => $this->agruparPorZona($productos, $zonas),
            'supermercado_id' => $supermercadoId,
            'supermercado_nombre' => $supermercado['nombre'] ?? 'Supermercado'
        ]);
    }

    public function comprados($supermercadoId)
    {
        $superModel      = new CompraSupermercadoModel();
        $productoModel   = new CompraProductoModel();
        $zonaModel       = new CompraZonaModel();
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
                ->orderBy('orden', 'asc')
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

            // Ordenar: comprados primero, luego por el orden del recorrido
            usort($productos, function ($a, $b) {
                if ($a['comprado'] !== $b['comprado']) {
                    return $a['comprado'] ? 1 : -1;
                }
                return $a['orden'] <=> $b['orden'];
            });
        }

        $zonas = $zonaModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return view('compras/supermercados/comprados', [
            'grupos' => $this->agruparPorZona($productos, $zonas),
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
        return $this->response->setStatusCode(204);
    }

    public function desmarcarFaltante($id)
    {
        $faltanteModel = new \App\Models\CompraFaltanteModel();
        $compradoModel = new \App\Models\CompraCompradoModel();

        // Eliminar de la tabla de faltantes
        $faltanteModel->where('producto_id', $id)->delete();

        // También eliminar de la tabla de comprados si existe
        $compradoModel->where('producto_id', $id)->delete();

        return $this->response->setStatusCode(204);
    }


    public function marcarComprado($id)
    {
        $this->marcarEstado(new CompraCompradoModel(), $id);
        return $this->response->setStatusCode(204);
    }

    public function desmarcarComprado($id)
    {
        $this->desmarcarEstado(new CompraCompradoModel(), $id);
        return $this->response->setStatusCode(204);
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

    // Zonas / pasillos: definen el recorrido a seguir dentro de un supermercado
    public function zonas($supermercadoId)
    {
        $superModel = new CompraSupermercadoModel();
        $zonaModel = new CompraZonaModel();

        $supermercado = $superModel->find($supermercadoId);
        if (!$supermercado) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Supermercado no encontrado');
        }

        $zonas = $zonaModel
            ->where('supermercado_id', $supermercadoId)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $nombresExistentes = array_map('mb_strtolower', array_column($zonas, 'nombre'));
        $sugerencias = array_values(array_filter(
            self::ZONAS_SUGERIDAS,
            fn ($nombre) => !in_array(mb_strtolower($nombre), $nombresExistentes, true)
        ));

        return view('compras/supermercados/zonas', [
            'zonas' => $zonas,
            'sugerencias' => $sugerencias,
            'supermercado_id' => $supermercadoId,
            'supermercado_nombre' => $supermercado['nombre']
        ]);
    }

    public function crearZona()
    {
        $zonaModel = new CompraZonaModel();
        $supermercadoId = $this->request->getPost('supermercado_id');
        $nombre = trim((string) $this->request->getPost('nombre'));

        if ($nombre !== '') {
            $yaExiste = $zonaModel
                ->where('supermercado_id', $supermercadoId)
                ->where('nombre', $nombre)
                ->first();

            if (!$yaExiste) {
                $siguienteOrden = (int) ($zonaModel
                    ->where('supermercado_id', $supermercadoId)
                    ->selectMax('orden')
                    ->first()['orden'] ?? 0) + 1;

                $zonaModel->insert([
                    'supermercado_id' => $supermercadoId,
                    'nombre' => $nombre,
                    'orden' => $siguienteOrden,
                ]);
            }
        }

        return redirect()->to(site_url('compras/supermercados/' . $supermercadoId . '/zonas'));
    }

    public function renombrarZona($id)
    {
        $zonaModel = new CompraZonaModel();
        $zona = $zonaModel->find($id);

        if (!$zona) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        $zonaModel->update($id, ['nombre' => $nombre]);

        return $this->response->setJSON(['ok' => true, 'nombre' => $nombre]);
    }

    public function reordenarZonas()
    {
        $ids = $this->request->getJSON(true)['orden'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        $zonaModel = new CompraZonaModel();
        foreach ($ids as $index => $id) {
            $zonaModel->skipValidation(true)->update((int) $id, ['orden' => $index + 1]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function eliminarZona($id)
    {
        $zonaModel = new CompraZonaModel();
        $productoModel = new CompraProductoModel();

        $zona = $zonaModel->find($id);
        if (!$zona) {
            return redirect()->back();
        }

        // Los productos de esta zona se quedan sin zona asignada, no se borran.
        $productoModel
            ->where('zona_id', $id)
            ->set(['zona_id' => null])
            ->update();

        $zonaModel->delete($id);

        return redirect()->to(site_url('compras/supermercados/' . $zona['supermercado_id'] . '/zonas'))
            ->with('message', 'Zona eliminada.');
    }
}
