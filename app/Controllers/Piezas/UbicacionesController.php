<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaHuecoModel;
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
    protected $helpers = ['url', 'form'];

    private PiezaEstucheModel $estuches;
    private PiezaHuecoModel $huecos;
    private PiezaInventario $inventario;
    private PiezaVarianteModel $variantes;
    private PiezaFamiliaModel $familias;

    public function __construct()
    {
        $this->estuches    = new PiezaEstucheModel();
        $this->huecos      = new PiezaHuecoModel();
        $this->inventario  = new PiezaInventario();
        $this->variantes   = new PiezaVarianteModel();
        $this->familias    = new PiezaFamiliaModel();
    }

    public function index()
    {
        $filas = [];
        foreach ($this->estuches->ordenados() as $estuche) {
            $huecos = [];
            foreach ($this->huecos->deEstuche((int) $estuche['id']) as $hueco) {
                $stock = $this->inventario->stockDeHueco((int) $hueco['id']);
                $huecos[] = [
                    'hueco'    => $hueco,
                    'codigo'   => PiezaHuecoModel::codigoCompleto($estuche, $hueco),
                    'unidades' => array_sum(array_map(static fn (int $n) => max(0, $n), $stock)),
                    'skus'     => count(array_filter($stock, static fn (int $n) => $n > 0)),
                ];
            }
            $filas[] = ['estuche' => $estuche, 'huecos' => $huecos];
        }

        return view('piezas/ubicaciones/index', ['filas' => $filas]);
    }

    public function crear()
    {
        $codigo = trim((string) $this->request->getPost('codigo'));
        if ($codigo === '') {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'El código del estuche es obligatorio.');
        }

        $zona  = trim((string) $this->request->getPost('zona'));
        $notas = trim((string) $this->request->getPost('notas'));

        $ok = $this->estuches->insert([
            'codigo' => $codigo,
            'zona'   => $zona === '' ? null : $zona,
            'notas'  => $notas === '' ? null : $notas,
        ]);

        if (!$ok) {
            $errores = implode(' ', $this->estuches->errors());

            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', $errores ?: 'No se pudo crear el estuche.');
        }

        return redirect()->to(site_url('piezas/ubicaciones'))->with('success', 'Estuche «' . $codigo . '» creado.');
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

        $codigo = trim((string) $this->request->getPost('codigo'));
        if ($codigo === '') {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'El código del hueco es obligatorio.');
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

        return view('piezas/ubicaciones/hueco', [
            'hueco'   => $hueco,
            'estuche' => $estuche,
            'codigo'  => $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $hueco) : $hueco['codigo'],
            'filas'   => $filas,
        ]);
    }
}
