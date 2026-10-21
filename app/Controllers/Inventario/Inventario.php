<?php

namespace App\Controllers\Inventario;

use App\Controllers\BaseController;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaRenderModel;
use App\Services\InventarioCatalogo;
use App\Services\PiezaAlmacen;
use App\Services\PiezaGuias;
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
        helper('guias');
        $this->catalogo = new InventarioCatalogo();
    }

    public function index()
    {
        return $this->pintar('inventario/index', [
            'seccion'    => 'inicio',
            'categorias' => $this->catalogo->categorias(),
            'estuches'   => count($this->catalogo->estuches()),
            'total'      => count($this->catalogo->piezas()),
        ]);
    }

    public function buscar()
    {
        $cat = (int) $this->request->getGet('cat');

        return $this->pintar('inventario/buscar', [
            'seccion'    => 'buscar',
            'categorias' => $this->catalogo->categorias(),
            'piezas'     => $this->catalogo->piezas(),
            'cat'        => $cat > 0 ? $cat : null,
            'q'          => trim((string) $this->request->getGet('q')),
        ]);
    }

    public function categorias()
    {
        return $this->pintar('inventario/categorias', [
            'seccion'    => 'categorias',
            'categorias' => $this->catalogo->categorias(),
        ]);
    }

    public function estuches()
    {
        return $this->pintar('inventario/estuches', [
            'seccion'  => 'estuches',
            'estuches' => $this->catalogo->estuches(),
        ]);
    }

    public function ayuda()
    {
        return $this->pintar('inventario/ayuda', [
            'seccion' => 'ayuda',
            'total'   => count($this->catalogo->piezas()),
        ]);
    }

    public function estuche(int $id)
    {
        $estuche = (new PiezaEstucheModel())->find($id);
        if (!$estuche) {
            return redirect()->to(inventario_url('estuches'));
        }

        return $this->pintar('inventario/estuche', [
            'seccion' => 'estuches',
            'estuche' => $estuche,
            'huecos'  => $this->catalogo->huecosDeEstuche($estuche),
        ]);
    }

    /**
     * Asistente paso a paso de una guía. Lo elegido va en ?o=3,7 (ids de
     * opción en orden de pasos). Solo se ofrecen opciones que lleven a
     * alguna pieza visible en esta sección.
     */
    public function guia(int $id)
    {
        $guias      = new PiezaGuias();
        $estructura = $guias->estructura($id);
        if (!$estructura || !$estructura['guia']['activa']) {
            return redirect()->to(inventario_url());
        }

        $piezas = array_column($this->catalogo->piezas(), null, 'id');

        $resultados = [];
        foreach ($guias->resultados($id) as $clave => $varianteId) {
            if (isset($piezas[$varianteId])) {
                $resultados[] = ['ids' => array_map('intval', explode('-', $clave)), 'pieza' => $piezas[$varianteId]];
            }
        }

        $alcanzables = static function (array $ids) use ($resultados): array {
            return array_values(array_filter($resultados, static fn (array $r) => array_diff($ids, $r['ids']) === []));
        };

        // Lo elegido, validado paso a paso: se corta en la primera opción
        // que no sea de su paso o que ya no lleve a ninguna pieza.
        $pedidas  = array_map('intval', array_filter(explode(',', (string) $this->request->getGet('o'))));
        $elegidas = [];
        foreach ($estructura['pasos'] as $i => $paso) {
            $oid    = $pedidas[$i] ?? null;
            $opcion = null;
            foreach ($paso['opciones'] as $o) {
                if ((int) $o['id'] === $oid) {
                    $opcion = $o;
                }
            }
            if (!$opcion || $alcanzables([...array_column($elegidas, 'id'), $oid]) === []) {
                break;
            }
            $elegidas[] = $opcion + ['paso' => $paso['nombre'], 'auto' => false];
        }

        // Un paso con una sola opción posible no se pregunta: se elige solo
        // (p. ej. la talla de una manga que solo existe en una talla).
        while (count($elegidas) < count($estructura['pasos'])) {
            $paso  = $estructura['pasos'][count($elegidas)];
            $ids   = array_map('intval', array_column($elegidas, 'id'));
            $unica = array_values(array_filter($paso['opciones'], static fn (array $o) => $alcanzables([...$ids, (int) $o['id']]) !== []));
            if (count($unica) !== 1) {
                break;
            }
            $elegidas[] = $unica[0] + ['paso' => $paso['nombre'], 'auto' => true];
        }

        $idsElegidos = array_map('intval', array_column($elegidas, 'id'));
        $migas = [];
        foreach ($elegidas as $k => $e) {
            $migas[] = ['texto' => $e['nombre'], 'paso' => $e['paso'], 'auto' => $e['auto'], 'o' => implode(',', array_slice($idsElegidos, 0, $k))];
        }

        $datos = [
            'seccion'   => 'guia-' . $id,
            'guia'      => $estructura['guia'],
            'migas'     => $migas,
            'total'     => count($estructura['pasos']),
            'resultado' => null,
            'paso'      => null,
            'opciones'  => [],
        ];

        if ($estructura['pasos'] !== [] && count($elegidas) === count($estructura['pasos'])) {
            $datos['resultado'] = $alcanzables($idsElegidos)[0]['pieza'];
        } elseif ($resultados !== []) {
            $paso = $estructura['pasos'][count($elegidas)];
            foreach ($paso['opciones'] as $o) {
                $llegan = $alcanzables([...$idsElegidos, (int) $o['id']]);
                if ($llegan === []) {
                    continue;
                }
                $conFoto = array_values(array_filter($llegan, static fn (array $r) => $r['pieza']['img'] !== null));
                $datos['opciones'][] = [
                    'nombre' => $o['nombre'],
                    'o'      => implode(',', [...$idsElegidos, (int) $o['id']]),
                    'img'    => $conFoto[0]['pieza']['img_grande'] ?? $conFoto[0]['pieza']['img'] ?? null,
                    'piezas' => count($llegan),
                ];
            }
            $datos['paso'] = $paso;
        }

        return $this->pintar('inventario/guia', $datos);
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

    /**
     * Todas las vistas llevan en el lateral las guías activas: el usuario de
     * inventario solo las que tienen alguna pieza; un admin todas, para
     * poder revisarlas antes de asignarles piezas.
     */
    private function pintar(string $vista, array $datos)
    {
        $guias  = new PiezaGuias();
        $piezas = array_column($this->catalogo->piezas(), 'id', 'id');
        $admin  = in_groups('admin');

        $datos['guias'] = array_values(array_filter($guias->activas(), static function (array $g) use ($guias, $piezas, $admin) {
            if ($admin) {
                return true;
            }
            foreach ($guias->resultados((int) $g['id']) as $varianteId) {
                if (isset($piezas[$varianteId])) {
                    return true;
                }
            }

            return false;
        }));

        return view($vista, $datos);
    }
}
