<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaHuecoModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaInventario;

/**
 * Ubicaciones físicas donde se guardan las piezas ya impresas — fase 1 de
 * "dónde está cada cosa" (spec en piezas-cli/SPEC.md). Dos niveles: estuche
 * (la caja) y hueco dentro de ese estuche (el compartimento, la ubicación
 * real de guardado). El código rotulado es la unión de los dos, p. ej.
 * "E1H2" para el hueco H2 del estuche E1.
 *
 * El stock por hueco no es un contador propio: sale de agrupar
 * piezas_stock_movimientos por ubicacion_id (que apunta a piezas_huecos),
 * igual que el stock total ya sale de sumar sus deltas. Ver
 * App\Services\PiezaInventario.
 */
class UbicacionesController extends BaseController
{
    protected $helpers = ['url', 'form', 'piezas_imagenes'];

    private PiezaEstucheModel $estuches;
    private PiezaHuecoModel $huecos;
    private PiezaInventario $inventario;
    private PiezaVarianteModel $variantes;
    private PiezaFamiliaModel $familias;
    private PiezaRenderModel $renders;

    public function __construct()
    {
        $this->estuches    = new PiezaEstucheModel();
        $this->huecos      = new PiezaHuecoModel();
        $this->inventario  = new PiezaInventario();
        $this->variantes   = new PiezaVarianteModel();
        $this->familias    = new PiezaFamiliaModel();
        $this->renders     = new PiezaRenderModel();
    }

    public function index()
    {
        $estuches = $this->estuches->ordenados();

        // Stock por hueco de todos los estuches de una vez, y qué
        // variantes aparecen en total, para resolver sus nombres en un
        // único lote en vez de una consulta por hueco.
        $huecosPorEstuche = [];
        $stockPorHueco    = [];
        $varianteIds      = [];
        foreach ($estuches as $estuche) {
            $huecosDelEstuche = $this->huecos->deEstuche((int) $estuche['id']);
            $huecosPorEstuche[(int) $estuche['id']] = $huecosDelEstuche;

            foreach ($huecosDelEstuche as $hueco) {
                $stock = array_filter($this->inventario->stockDeHueco((int) $hueco['id']), static fn (int $n) => $n > 0);
                $stockPorHueco[(int) $hueco['id']] = $stock;
                $varianteIds = [...$varianteIds, ...array_keys($stock)];
            }
        }

        $familias = [];
        foreach ($this->familias->findAll() as $fam) {
            $familias[(int) $fam['id']] = $fam;
        }

        $nombres = [];
        $varianteIds = array_unique($varianteIds);
        if ($varianteIds !== []) {
            foreach ($this->variantes->whereIn('id', $varianteIds)->findAll() as $v) {
                $familia = $familias[(int) $v['familia_id']] ?? null;
                $nombres[(int) $v['id']] = trim(($familia['nombre'] ?? '') . ' ' . $v['nombre']);
            }
        }

        // Miniatura de cada pieza (el render más reciente), para reconocerlas
        // a ojo en la rejilla de huecos y no solo por el nombre.
        $miniaturas = [];
        if ($varianteIds !== []) {
            foreach ($this->renders->whereIn('variante_id', $varianteIds)->orderBy('subida_en', 'DESC')->findAll() as $r) {
                $vid = (int) $r['variante_id'];
                if (isset($miniaturas[$vid]) || (empty($r['ruta_imagen']) && empty($r['hash_imagen']))) {
                    continue;
                }
                $miniaturas[$vid] = imagen_pieza($r, 'render', 't');
            }
        }

        $filas = [];
        foreach ($estuches as $estuche) {
            $huecos = [];
            foreach ($huecosPorEstuche[(int) $estuche['id']] as $hueco) {
                $stock = $stockPorHueco[(int) $hueco['id']];

                $contenido = [];
                foreach ($stock as $varianteId => $cantidad) {
                    if (isset($nombres[$varianteId])) {
                        $contenido[] = [
                            'nombre' => $nombres[$varianteId],
                            'stock'  => $cantidad,
                            'img'    => $miniaturas[$varianteId] ?? null,
                        ];
                    }
                }
                usort($contenido, static fn ($a, $b) => $a['nombre'] <=> $b['nombre']);

                $huecos[] = [
                    'hueco'     => $hueco,
                    'codigo'    => PiezaHuecoModel::codigoCompleto($estuche, $hueco),
                    'unidades'  => array_sum($stock),
                    'contenido' => $contenido,
                ];
            }
            $filas[] = [
                'estuche'   => $estuche,
                'huecos'    => $huecos,
                'siguiente' => $this->huecos->siguienteCodigo((int) $estuche['id']),
            ];
        }

        return view('piezas/ubicaciones/index', ['filas' => $filas]);
    }

    /**
     * Documento para imprimir/guardar como PDF, en dos listados —"por
     * estuche" (qué hay en cada hueco) y "por pieza" (dónde vive cada
     * una)— sin cantidades a propósito: es una chuleta física para
     * localizar cosas a ojo, no un recuento (eso ya está en Existencias).
     * Los huecos vacíos llevan líneas en blanco para apuntar a mano lo que
     * se vaya guardando ahí sin tener que volver a imprimir cada vez.
     */
    public function imprimir()
    {
        $estuches = $this->estuches->ordenados();

        $familias = [];
        foreach ($this->familias->findAll() as $fam) {
            $familias[(int) $fam['id']] = $fam;
        }

        $variantesTodas = $this->variantes->findAll();

        $nombrePorVariante = [];
        foreach ($variantesTodas as $v) {
            $familia = $familias[(int) $v['familia_id']] ?? null;
            $nombrePorVariante[(int) $v['id']] = trim(($familia['nombre'] ?? '') . ' ' . $v['nombre']);
        }

        // Miniatura de cada pieza (el render más reciente), para que se
        // reconozcan a ojo en el papel y no solo por el nombre — igual que
        // el catálogo de "añadir pieza aquí" de verHueco().
        $miniaturas = [];
        $idsTodas = array_map(static fn (array $v) => (int) $v['id'], $variantesTodas);
        if ($idsTodas !== []) {
            foreach ($this->renders->whereIn('variante_id', $idsTodas)->orderBy('subida_en', 'DESC')->findAll() as $r) {
                $vid = (int) $r['variante_id'];
                if (isset($miniaturas[$vid]) || (empty($r['ruta_imagen']) && empty($r['hash_imagen']))) {
                    continue;
                }
                $miniaturas[$vid] = imagen_pieza($r, 'render', 't');
            }
        }

        $codigoPorHuecoId = [];
        foreach ($estuches as $e) {
            foreach ($this->huecos->deEstuche((int) $e['id']) as $h) {
                $codigoPorHuecoId[(int) $h['id']] = PiezaHuecoModel::codigoCompleto($e, $h);
            }
        }

        $porEstuche = [];
        foreach ($estuches as $e) {
            $huecos = [];
            foreach ($this->huecos->deEstuche((int) $e['id']) as $h) {
                $piezas = [];
                foreach ($this->inventario->stockDeHueco((int) $h['id']) as $varianteId => $n) {
                    if ($n > 0 && isset($nombrePorVariante[$varianteId])) {
                        $piezas[] = ['nombre' => $nombrePorVariante[$varianteId], 'img' => $miniaturas[$varianteId] ?? null];
                    }
                }
                usort($piezas, static fn (array $a, array $b) => $a['nombre'] <=> $b['nombre']);
                $huecos[] = ['codigo' => $h['codigo'], 'piezas' => $piezas];
            }
            $porEstuche[] = ['estuche' => $e, 'huecos' => $huecos];
        }

        // El reverso del listado anterior: por pieza, en qué huecos vive
        // (puede estar repartida en varios). "Sin asignar" cuenta como una
        // ubicación más aquí, para que la pieza no desaparezca del listado
        // solo porque parte de su stock aún no tiene hueco.
        $porPieza = [];
        foreach ($this->inventario->stockPorVarianteYHueco() as $varianteId => $porHueco) {
            if (!isset($nombrePorVariante[$varianteId])) {
                continue;   // variante borrada
            }

            $codigos = [];
            $sinAsignar = false;
            foreach ($porHueco as $huecoId => $n) {
                if ($n <= 0) {
                    continue;
                }
                if ($huecoId > 0) {
                    $codigos[] = $codigoPorHuecoId[$huecoId] ?? '?';
                } else {
                    $sinAsignar = true;
                }
            }
            if ($codigos === [] && !$sinAsignar) {
                continue;
            }
            sort($codigos);

            $porPieza[] = [
                'nombre'     => $nombrePorVariante[$varianteId],
                'img'        => $miniaturas[$varianteId] ?? null,
                'codigos'    => $codigos,
                'sinAsignar' => $sinAsignar,
            ];
        }
        usort($porPieza, static fn (array $a, array $b) => $a['nombre'] <=> $b['nombre']);

        return view('piezas/ubicaciones/imprimir', [
            'porEstuche' => $porEstuche,
            'porPieza'   => $porPieza,
            // Sin "Trackbitos" en la pestaña: es lo que usan la mayoría de
            // navegadores para la cabecera automática de impresión.
            'title'      => 'Dónde está cada cosa',
        ]);
    }

    public function crear()
    {
        $codigo = trim((string) $this->request->getPost('codigo'));
        if ($codigo === '') {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'El código del estuche es obligatorio.');
        }

        $zona      = trim((string) $this->request->getPost('zona'));
        $notas     = trim((string) $this->request->getPost('notas'));
        $numHuecos = (int) $this->request->getPost('num_huecos');
        $numHuecos = max(0, min(50, $numHuecos));

        $id = $this->estuches->insert([
            'codigo' => $codigo,
            'zona'   => $zona === '' ? null : $zona,
            'notas'  => $notas === '' ? null : $notas,
        ]);

        if (!$id) {
            $errores = implode(' ', $this->estuches->errors());

            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', $errores ?: 'No se pudo crear el estuche.');
        }

        // Alta rápida: si se indica un número de huecos, se generan ya
        // rotulados H1..Hn con la nomenclatura establecida, en vez de
        // tener que añadirlos uno a uno.
        if ($numHuecos > 0) {
            $filas = [];
            for ($i = 1; $i <= $numHuecos; $i++) {
                $filas[] = ['estuche_id' => $id, 'codigo' => 'H' . $i];
            }
            $this->huecos->insertBatch($filas);
        }

        $mensaje = 'Estuche «' . $codigo . '» creado';
        $mensaje .= $numHuecos > 0 ? ' con ' . $numHuecos . ' huecos (H1-H' . $numHuecos . ').' : '.';

        return redirect()->to(site_url('piezas/ubicaciones'))->with('success', $mensaje);
    }

    public function actualizar(int $id)
    {
        $estuche = $this->estuches->find($id);
        if (!$estuche) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese estuche no existe.');
        }

        $codigo = trim((string) $this->request->getPost('codigo'));
        if ($codigo === '') {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'El código del estuche es obligatorio.');
        }

        $zona  = trim((string) $this->request->getPost('zona'));
        $notas = trim((string) $this->request->getPost('notas'));

        $ok = $this->estuches->update($id, [
            'codigo' => $codigo,
            'zona'   => $zona === '' ? null : $zona,
            'notas'  => $notas === '' ? null : $notas,
        ]);

        if (!$ok) {
            $errores = implode(' ', $this->estuches->errors());

            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', $errores ?: 'No se pudo guardar.');
        }

        return redirect()->to(site_url('piezas/ubicaciones'))->with('success', 'Estuche actualizado.');
    }

    public function borrar(int $id)
    {
        $estuche = $this->estuches->find($id);
        if (!$estuche) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese estuche no existe.');
        }

        // Se lleva sus huecos por delante (CASCADE en el esquema); el
        // historial de movimientos que tuvieran esos huecos no se borra,
        // se queda sin ubicación asignada (SET NULL).
        $this->estuches->delete($id);

        return redirect()->to(site_url('piezas/ubicaciones'))
            ->with('success', 'Estuche «' . $estuche['codigo'] . '» borrado con sus huecos. El historial de stock queda sin asignar.');
    }

    public function crearHueco(int $estucheId)
    {
        $estuche = $this->estuches->find($estucheId);
        if (!$estuche) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese estuche no existe.');
        }

        // El código es opcional: si no se manda (caso normal, el botón
        // "Añadir hueco" no pide nombre), se asigna el siguiente libre de
        // la nomenclatura H1, H2... Se puede seguir mandando uno manual
        // (p. ej. desde otra integración) si hiciera falta.
        $codigo = trim((string) $this->request->getPost('codigo'));
        if ($codigo === '') {
            $codigo = $this->huecos->siguienteCodigo($estucheId);
        }

        $notas = trim((string) $this->request->getPost('notas'));

        if ($this->huecos->where('estuche_id', $estucheId)->where('codigo', $codigo)->first()) {
            return redirect()->to(site_url('piezas/ubicaciones'))
                ->with('error', 'El estuche «' . $estuche['codigo'] . '» ya tiene un hueco «' . $codigo . '».');
        }

        $ok = $this->huecos->insert([
            'estuche_id' => $estucheId,
            'codigo'     => $codigo,
            'notas'      => $notas === '' ? null : $notas,
        ]);

        if (!$ok) {
            $errores = implode(' ', $this->huecos->errors());

            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', $errores ?: 'No se pudo crear el hueco.');
        }

        return redirect()->to(site_url('piezas/ubicaciones'))
            ->with('success', 'Hueco «' . PiezaHuecoModel::codigoCompleto($estuche, ['codigo' => $codigo]) . '» creado.');
    }

    public function borrarHueco(int $id)
    {
        $hueco = $this->huecos->find($id);
        if (!$hueco) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese hueco no existe.');
        }

        $this->huecos->delete($id);

        return redirect()->to(site_url('piezas/ubicaciones'))
            ->with('success', 'Hueco borrado. El historial de stock que tenía queda sin asignar.');
    }

    public function verHueco(int $id)
    {
        $hueco = $this->huecos->find($id);
        if (!$hueco) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese hueco no existe.');
        }
        $estuche = $this->estuches->find($hueco['estuche_id']);

        $stock = array_filter($this->inventario->stockDeHueco($id), static fn (int $n) => $n !== 0);

        $familias = [];
        foreach ($this->familias->findAll() as $f) {
            $familias[(int) $f['id']] = $f;
        }

        $filas = [];
        if ($stock !== []) {
            foreach ($this->variantes->whereIn('id', array_keys($stock))->findAll() as $v) {
                $familia = $familias[(int) $v['familia_id']] ?? null;
                $filas[] = [
                    'variante' => $v,
                    'nombre'   => trim(($familia['nombre'] ?? '') . ' ' . $v['nombre']),
                    'stock'    => $stock[(int) $v['id']],
                ];
            }
            usort($filas, static fn ($a, $b) => $a['nombre'] <=> $b['nombre']);
        }

        // Código completo de todos los huecos que existan (de cualquier
        // estuche), en un solo mapa — para los destinos de "mover stock".
        $codigoPorHuecoId = [];
        foreach ($this->estuches->ordenados() as $est) {
            foreach ($this->huecos->deEstuche((int) $est['id']) as $h) {
                $codigoPorHuecoId[(int) $h['id']] = PiezaHuecoModel::codigoCompleto($est, $h);
            }
        }

        // Destinos posibles para "mover stock a otro hueco": todos los
        // demás huecos de cualquier estuche.
        $destinos = $codigoPorHuecoId;
        unset($destinos[$id]);

        return view('piezas/ubicaciones/hueco', [
            'hueco'    => $hueco,
            'estuche'  => $estuche,
            'codigo'   => $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $hueco) : $hueco['codigo'],
            'filas'    => $filas,
            'destinos' => $destinos,
        ]);
    }

    /**
     * Mueve todo el stock de este hueco a otro — para fusionar dos huecos
     * (se mueve y luego se borra el que queda vacío con "Borrar hueco") o
     * para corregir que algo se apuntó en el hueco equivocado.
     */
    public function moverStock(int $id)
    {
        $hueco = $this->huecos->find($id);
        if (!$hueco) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese hueco no existe.');
        }

        $destinoId = (int) $this->request->getPost('destino_id');
        $destino   = $destinoId > 0 ? $this->huecos->find($destinoId) : null;

        if (!$destino) {
            return redirect()->to(site_url('piezas/ubicaciones/huecos/' . $id))
                ->with('error', 'Elige a qué hueco mover el stock.');
        }
        if ($destinoId === $id) {
            return redirect()->to(site_url('piezas/ubicaciones/huecos/' . $id))
                ->with('error', 'El hueco de destino no puede ser el mismo.');
        }

        $unidades = array_sum(array_map(static fn (int $n) => max(0, $n), $this->inventario->stockDeHueco($id)));
        $movidos  = $this->inventario->moverStockDeHueco($id, $destinoId);

        $estDestino    = $this->estuches->find($destino['estuche_id']);
        $codigoDestino = $estDestino ? PiezaHuecoModel::codigoCompleto($estDestino, $destino) : $destino['codigo'];

        $mensaje = $movidos > 0
            ? 'Movidas ' . $unidades . ' uds. a «' . $codigoDestino . '».'
            : 'Ese hueco ya estaba vacío, nada que mover.';

        return redirect()->to(site_url('piezas/ubicaciones/huecos/' . $destinoId))->with('success', $mensaje);
    }
}
