<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaPedidoLineaModel;
use App\Models\PiezaPedidoModel;
use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaImagenesPublicas;

/**
 * API dedicada a la integración con sterclicks (token propio, filtro
 * 'sterclicksApi', ver App\Filters\SterclicksApiAuth). Dos direcciones:
 * catálogo de piezas validadas hacia sterclicks, y pedidos desde sterclicks
 * hacia aquí. No comparte token con piezas-cli/trackbitos.py (Api.php).
 */
class SterclicksApi extends BaseController
{
    /**
     * Catálogo de piezas con versión validada: sku, nombre, familia,
     * imagen (última render de esa versión, si existe) y notas. Solo
     * variantes con sku asignado y no borradas.
     */
    public function catalogo()
    {
        $db = db_connect();

        $filas = $db->table('piezas_variantes v')
            ->select('v.id as variante_id, v.familia_id, v.sku, v.nombre, v.notas, f.nombre as familia, c.nombre as categoria, ver.id as version_id, ver.promocionada_en')
            ->join('piezas_versiones ver', 'ver.variante_id = v.id AND ver.estado = "validada"', 'inner')
            ->join('piezas_familias f', 'f.id = v.familia_id', 'left')
            ->join('piezas_categorias c', 'c.id = f.categoria_id', 'left')
            ->where('v.borrado_en', null)
            ->where('v.sku IS NOT NULL', null, false)
            ->where('v.visible_sterclicks', 1)
            ->groupStart()
                ->where('f.visible_sterclicks', 1)
                ->orWhere('f.visible_sterclicks', null)
            ->groupEnd()
            ->groupStart()
                ->where('c.visible_sterclicks', 1)
                ->orWhere('c.visible_sterclicks', null)
            ->groupEnd()
            ->get()
            ->getResultArray();

        $renderModel = new PiezaRenderModel();
        $referenciaModel = new PiezaReferenciaModel();
        $imagenesPublicas = new PiezaImagenesPublicas();
        $token = urlencode((string) env('sterclicks.apiToken'));
        $piezas = [];
        foreach ($filas as $fila) {
            $render = $renderModel->where('version_id', $fila['version_id'])->orderBy('id', 'DESC')->first()
                ?? $renderModel->where('variante_id', $fila['variante_id'])->orderBy('id', 'DESC')->first();

            // Se prefiere siempre la copia pública estática (public/piezas-img,
            // servida directo por Apache) sobre el controlador: con el catálogo
            // entero pintando miniaturas a la vez, pasar cada una por el
            // framework saturaba el hosting y hacía que unas u otras fallaran
            // al azar en cada recarga. Solo se cae al controlador si esa
            // imagen concreta todavía no se ha publicado con
            // `piezas:publicar-imagenes`.
            $imagenUrl = null;
            if ($render) {
                $imagenUrl = $imagenesPublicas->url($render['hash_imagen'] ?? null, PiezaImagenesPublicas::VISTA)
                    ?? site_url('piezas/sterclicks-api/render/' . $render['id'] . '/imagen') . '?token=' . $token;
            } else {
                // Sin render todavía: cae en la foto de referencia del original,
                // igual que hace la galería de trackbitos (Web::fotosDe()).
                $referencia = $referenciaModel->deVariante((int) $fila['familia_id'] ?: 0, (int) $fila['variante_id'])[0] ?? null;
                if ($referencia) {
                    $imagenUrl = $imagenesPublicas->url($referencia['hash_imagen'] ?? null, PiezaImagenesPublicas::VISTA)
                        ?? site_url('piezas/sterclicks-api/referencia/' . $referencia['id'] . '/imagen') . '?token=' . $token;
                }
            }

            $piezas[] = [
                'sku'              => $fila['sku'],
                'nombre'           => $fila['nombre'],
                'familia'          => $fila['familia'],
                'categoria'        => $fila['categoria'],
                'notas'            => $fila['notas'],
                'imagen_url'       => $imagenUrl,
                'actualizado_en'   => $fila['promocionada_en'],
            ];
        }

        return $this->response->setJSON(['piezas' => $piezas]);
    }

    /**
     * Sirve la imagen de un render (misma imagen que Web::imagenRender,
     * pero sin exigir sesión de navegador: la consulta sterclicks con el
     * token de esta API).
     */
    public function imagenRender(int $id)
    {
        $render = (new PiezaRenderModel())->find($id);

        return $this->servirImagenAlmacen($render['ruta_imagen'] ?? null);
    }

    /** Igual que imagenRender(), para cuando la pieza aún no tiene render (fallback en catalogo()). */
    public function imagenReferencia(int $id)
    {
        $referencia = (new PiezaReferenciaModel())->find($id);

        return $this->servirImagenAlmacen($referencia['ruta_imagen'] ?? null);
    }

    private function servirImagenAlmacen(?string $rutaRelativa)
    {
        $almacen = new PiezaAlmacen();
        if (!$rutaRelativa || !$almacen->existe($rutaRelativa)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $ruta = $almacen->absoluta($rutaRelativa);
        $tipo = match (strtolower(pathinfo($ruta, PATHINFO_EXTENSION))) {
            'png'          => 'image/png',
            'jpg', 'jpeg'  => 'image/jpeg',
            'webp'         => 'image/webp',
            default        => 'application/octet-stream',
        };

        return $this->response
            ->setHeader('Content-Type', $tipo)
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->setBody(file_get_contents($ruta));
    }

    /**
     * Recibe un pedido de sterclicks: {referencia_externa?, notas?, lineas:[{sku, cantidad}]}.
     * Cada línea se resuelve contra piezas_variantes.sku; si el sku no existe
     * se rechaza el pedido entero (422) para que sterclicks no crea que se
     * apuntó algo que en realidad no se ha guardado.
     */
    public function pedidos()
    {
        $body = $this->request->getJSON(true) ?? [];
        $lineas = $body['lineas'] ?? [];

        if (!is_array($lineas) || count($lineas) === 0) {
            return $this->response->setJSON(['error' => 'El pedido necesita al menos una línea (sku, cantidad).'])->setStatusCode(422);
        }

        $varianteModel = new PiezaVarianteModel();
        $resueltas = [];
        foreach ($lineas as $linea) {
            $sku = trim((string) ($linea['sku'] ?? ''));
            $cantidad = (int) ($linea['cantidad'] ?? 0);

            if ($sku === '' || $cantidad <= 0) {
                return $this->response->setJSON(['error' => 'Cada línea necesita sku y cantidad > 0.'])->setStatusCode(422);
            }

            $variante = $varianteModel->where('sku', $sku)->first();
            if (!$variante) {
                return $this->response->setJSON(['error' => "No existe ninguna pieza con sku {$sku}."])->setStatusCode(422);
            }

            $resueltas[] = ['variante_id' => $variante['id'], 'sku' => $sku, 'cantidad' => $cantidad];
        }

        $pedidoModel = new PiezaPedidoModel();
        $lineaModel = new PiezaPedidoLineaModel();

        $db = db_connect();
        $db->transStart();

        $pedidoId = $pedidoModel->insert([
            'origen'             => 'sterclicks',
            'estado'             => 'nuevo',
            'referencia_externa' => $body['referencia_externa'] ?? null,
            'notas'              => $body['notas'] ?? null,
            'creado_en'          => date('Y-m-d H:i:s'),
        ]);

        foreach ($resueltas as $linea) {
            $linea['pedido_id'] = $pedidoId;
            $lineaModel->insert($linea);
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->response->setJSON(['error' => 'No se pudo guardar el pedido.'])->setStatusCode(500);
        }

        return $this->response->setJSON(['pedido_id' => $pedidoId])->setStatusCode(201);
    }
}
