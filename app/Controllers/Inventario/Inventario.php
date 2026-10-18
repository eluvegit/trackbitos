<?php

namespace App\Controllers\Inventario;

use App\Controllers\BaseController;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaRenderModel;
use App\Services\InventarioCatalogo;
use App\Services\PiezaAlmacen;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Sección Inventario: consulta de stock y ubicaciones con aspecto de TPV,
 * pensada para leerse de lejos y pulsarse con el dedo. Solo lectura.
 */
class Inventario extends BaseController
{
    private InventarioCatalogo $catalogo;

    public function __construct()
    {
        $this->catalogo = new InventarioCatalogo();
    }

    public function index()
    {
        return view('inventario/index', [
            'seccion'    => 'inicio',
            'categorias' => $this->catalogo->categorias(),
            'estuches'   => count($this->catalogo->estuches()),
            'total'      => count($this->catalogo->piezas()),
        ]);
    }

    public function buscar()
    {
        $cat = (int) $this->request->getGet('cat');

        return view('inventario/buscar', [
            'seccion'    => 'buscar',
            'categorias' => $this->catalogo->categorias(),
            'piezas'     => $this->catalogo->piezas(),
            'cat'        => $cat > 0 ? $cat : null,
            'q'          => trim((string) $this->request->getGet('q')),
        ]);
    }

    public function categorias()
    {
        return view('inventario/categorias', [
            'seccion'    => 'categorias',
            'categorias' => $this->catalogo->categorias(),
        ]);
    }

    public function estuches()
    {
        return view('inventario/estuches', [
            'seccion'  => 'estuches',
            'estuches' => $this->catalogo->estuches(),
        ]);
    }

    public function estuche(int $id)
    {
        $estuche = (new PiezaEstucheModel())->find($id);
        if (!$estuche) {
            return redirect()->to(site_url('inventario/estuches'));
        }

        return view('inventario/estuche', [
            'seccion' => 'estuches',
            'estuche' => $estuche,
            'huecos'  => $this->catalogo->huecosDeEstuche($estuche),
        ]);
    }

    /** Respaldo para fotos sin copia pública en piezas-img/. */
    public function imagen(int $id)
    {
        $render  = (new PiezaRenderModel())->find($id);
        $almacen = new PiezaAlmacen();
        if (!$render || !$almacen->existe($render['ruta_imagen'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        session()->close();
        $ruta = $almacen->absoluta($render['ruta_imagen']);

        return $this->response
            ->setHeader('Content-Type', mime_content_type($ruta) ?: 'application/octet-stream')
            ->setHeader('Cache-Control', 'private, max-age=31536000, immutable')
            ->setBody(file_get_contents($ruta));
    }
}
