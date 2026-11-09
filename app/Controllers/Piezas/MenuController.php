<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaRenderModel;

class MenuController extends BaseController
{
    protected $helpers = ['url', 'piezas_imagenes'];

    public function buscar()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['piezas' => []]);
        }

        $palabras = array_slice(preg_split('/\s+/', $q), 0, 5);
        $db = db_connect();
        $qb = $db->table('piezas_variantes v')
            ->select('v.id, v.nombre, v.sku, v.congelado_en, f.nombre AS familia')
            ->join('piezas_familias f', 'f.id = v.familia_id')
            ->where('v.borrado_en', null)
            ->where('f.borrado_en', null);
        foreach ($palabras as $p) {
            $qb->groupStart()
                ->like('f.nombre', $p)
                ->orLike('v.nombre', $p)
                ->orLike('v.sku', $p)
                ->groupEnd();
        }
        $filas = $qb->orderBy('f.nombre')->orderBy('v.nombre')->limit(8)->get()->getResultArray();

        $imagenes = [];
        if ($filas !== []) {
            foreach ((new PiezaRenderModel())->whereIn('variante_id', array_column($filas, 'id'))->orderBy('subida_en', 'DESC')->findAll() as $r) {
                $vid = (int) $r['variante_id'];
                if (!isset($imagenes[$vid]) && (!empty($r['ruta_imagen']) || !empty($r['hash_imagen']))) {
                    $imagenes[$vid] = imagen_pieza($r, 'render', 't');
                }
            }
        }

        return $this->response->setJSON(['piezas' => array_map(static fn (array $f) => [
            'nombre' => $f['familia'] . ' · ' . $f['nombre'],
            'dato'   => trim(($f['sku'] ?? '') . ($f['congelado_en'] ? ' · en la nevera' : '')),
            'url'    => site_url('piezas/variante/' . (int) $f['id']),
            'stock'  => site_url('piezas/existencias/' . (int) $f['id']),
            'img'    => $imagenes[(int) $f['id']] ?? null,
        ], $filas)]);
    }
}
