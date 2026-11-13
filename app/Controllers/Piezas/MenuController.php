<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaHuecoModel;
use App\Models\PiezaRenderModel;
use App\Services\PiezaInventario;

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
            ->select('v.id, v.nombre, v.sku, v.congelado_en, v.hueco_predeterminado_id, f.nombre AS familia')
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
        $plano = static fn (string $s): string => mb_strtolower(preg_replace('/\p{Mn}/u', '', \Normalizer::normalize($s, \Normalizer::FORM_D) ?: $s));
        $frase = $plano(implode(' ', $palabras));
        $puntuar = static function (array $f) use ($palabras, $plano, $frase): int {
            $texto = $plano($f['familia'] . ' ' . $f['nombre'] . ' ' . ($f['sku'] ?? ''));
            $n = str_contains($texto, $frase) ? 10 : 0;
            foreach ($palabras as $p) {
                $p = preg_quote($plano($p), '/');
                $n += preg_match('/(?<![\p{L}\p{N}])' . $p . '(?![\p{L}\p{N}])/u', $texto) ? 4
                    : (preg_match('/(?<![\p{L}\p{N}])' . $p . '/u', $texto) ? 2 : 1);
            }

            return $n;
        };
        $filas = $qb->get()->getResultArray();
        foreach ($filas as &$f) {
            $f['_puntos'] = $puntuar($f);
        }
        unset($f);
        usort($filas, static fn ($a, $b) => $b['_puntos'] <=> $a['_puntos'] ?: strcmp($a['familia'] . $a['nombre'], $b['familia'] . $b['nombre']));
        $filas = array_slice($filas, 0, 15);

        $imagenes = [];
        if ($filas !== []) {
            foreach ((new PiezaRenderModel())->whereIn('variante_id', array_column($filas, 'id'))->orderBy('subida_en', 'DESC')->findAll() as $r) {
                $vid = (int) $r['variante_id'];
                if (!isset($imagenes[$vid]) && (!empty($r['ruta_imagen']) || !empty($r['hash_imagen']))) {
                    $imagenes[$vid] = imagen_pieza($r, 'render', 't');
                }
            }
        }

        // Dónde está y cuántas hay: el total de la pieza (mismo criterio que
        // el resto de pantallas) y, aparte, lo que aún está en las cajas.
        $ids      = array_map(static fn (array $f) => (int) $f['id'], $filas);
        $desglose = (new PiezaInventario())->desglose($ids);
        $codigos  = [];
        $huecoIds = array_values(array_unique(array_filter(array_map(static fn (array $f) => (int) ($f['hueco_predeterminado_id'] ?? 0), $filas))));
        if ($huecoIds !== []) {
            $estuches = new PiezaEstucheModel();
            foreach ((new PiezaHuecoModel())->whereIn('id', $huecoIds)->findAll() as $h) {
                $estuche = $estuches->find($h['estuche_id']);
                $codigos[(int) $h['id']] = $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $h) : $h['codigo'];
            }
        }

        return $this->response->setJSON(['piezas' => array_map(static function (array $f) use ($imagenes, $desglose, $codigos) {
            $id = (int) $f['id'];
            $d  = $desglose[$id];

            return [
                'nombre' => $f['familia'] . ' · ' . $f['nombre'],
                'dato'   => trim(($f['sku'] ?? '') . ($f['congelado_en'] ? ' · en la nevera' : '')),
                'url'    => site_url('piezas/variante/' . $id),
                'stock'  => site_url('piezas/existencias/' . $id),
                'img'    => $imagenes[$id] ?? null,
                'hueco'  => $codigos[(int) ($f['hueco_predeterminado_id'] ?? 0)] ?? null,
                'uds'    => $d['total'],
                'cajas'  => $d['impresas'] + $d['pedido'],
            ];
        }, $filas)]);
    }
}
