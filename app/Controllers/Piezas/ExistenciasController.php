<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaInventario;

/**
 * Existencias (fase 60): inventario de piezas fisicas producidas, contado
 * por variante. El alta automatica de lo impreso la dispara el boton de la
 * bitacora (Web::inventarioSincronizar); el alta/baja a mano vive aqui.
 *
 * Semaforo por variante: rojo a cero, amarillo entre 1 y el minimo
 * (piezas_variantes.stock_minimo), verde por encima del minimo.
 */
class ExistenciasController extends BaseController
{
    protected $helpers = ['url', 'form', 'piezas_imagenes'];

    private const FILTROS = ['existencias', 'bajo-minimo', 'sin-stock', 'todas'];

    private PiezaInventario $inventario;
    private PiezaVarianteModel $variantes;
    private PiezaFamiliaModel $familias;
    private PiezaRenderModel $renders;
    private PiezaCategoriaModel $categorias;

    public function __construct()
    {
        $this->inventario = new PiezaInventario();
        $this->variantes  = new PiezaVarianteModel();
        $this->familias   = new PiezaFamiliaModel();
        $this->renders    = new PiezaRenderModel();
        $this->categorias = new PiezaCategoriaModel();
    }

    public function index()
    {
        $vista  = $this->request->getGet('vista') === 'lista' ? 'lista' : 'galeria';
        $filtro = in_array($this->request->getGet('filtro'), self::FILTROS, true)
            ? $this->request->getGet('filtro')
            : 'todas';

        // Categoría: id numérico, 'sin' (sin clasificar) o '' (todas). Se
        // combina con el filtro de stock — son ejes distintos.
        $categoria    = (string) $this->request->getGet('categoria');
        $catValida    = $categoria === 'sin' || ($categoria !== '' && ctype_digit($categoria));
        $categoriaSel = $catValida ? $categoria : '';

        // Visibilidad en sterclicks: otro eje transversal, como la categoría.
        // No es un filtro de stock más, es "enséñame solo lo que de verdad
        // hay que llevar controlado" y se combina con todos los demás. Va
        // encendido por defecto: se apaga explícitamente con sterclicks=0.
        $soloSterclicks = $this->request->getGet('sterclicks') !== '0';

        $stock = $this->inventario->stockPorVariante();

        $familias = [];
        foreach ($this->familias->findAll() as $f) {
            $familias[(int) $f['id']] = $f;
        }

        $categorias   = $this->categorias->ordenadas();
        $catVisibleSt = [];
        foreach ($categorias as $c) {
            $catVisibleSt[(int) $c['id']] = !empty($c['visible_sterclicks']);
        }

        $variantes = $this->variantes
            ->where('borrado_en', null)
            ->orderBy('familia_id')->orderBy('nombre')
            ->findAll();

        // Todas las de la categoría elegida (los contadores de la cabecera
        // salen de aquí); el filtro de stock solo decide qué se pinta.
        $todas = [];
        foreach ($variantes as $v) {
            $familia = $familias[(int) $v['familia_id']] ?? null;
            $catFam  = $familia['categoria_id'] ?? null;

            if ($categoriaSel === 'sin' && $catFam !== null) {
                continue;
            }
            if ($categoriaSel !== '' && $categoriaSel !== 'sin' && (int) $catFam !== (int) $categoriaSel) {
                continue;
            }

            $s      = $stock[(int) $v['id']] ?? 0;
            $minimo = (int) ($v['stock_minimo'] ?? 0);

            // Visible en sterclicks = de verdad llega al catálogo: su propio
            // interruptor y los de su familia y su categoría, los tres
            // encendidos (misma cascada que SterclicksApi::catalogo y que lo
            // que deja ver el índice). Si cualquiera de los tres está
            // apagado, la pieza no se publica y aquí cuenta como oculta.
            $visibleSterclicks = !empty($v['visible_sterclicks'])
                && ($familia === null || !empty($familia['visible_sterclicks']))
                && ($catFam === null || ($catVisibleSt[(int) $catFam] ?? true));

            if ($soloSterclicks && !$visibleSterclicks) {
                continue;
            }

            $todas[] = [
                'variante' => $v,
                'familia'  => $familia,
                'nombre'   => trim(($familia['nombre'] ?? '') . ' ' . $v['nombre']),
                'stock'    => $s,
                'minimo'   => $minimo,
                'estado'   => $s <= 0 ? 'cero' : ($minimo > 0 && $s < $minimo ? 'bajo' : 'ok'),
                // "Bajo mínimo" = por debajo del mínimo fijado, incluidas las
                // que están a cero (son las más urgentes). El semáforo las
                // sigue pintando en rojo; el filtro no las deja fuera.
                'bajoMinimo' => $minimo > 0 && $s < $minimo,
                'visibleSterclicks' => $visibleSterclicks,
            ];
        }

        $filas = array_values(array_filter($todas, static function (array $f) use ($filtro) {
            $tiene = $f['stock'] > 0;

            return match ($filtro) {
                'existencias' => $tiene,
                'bajo-minimo' => $f['bajoMinimo'],
                'sin-stock'   => !$tiene,
                default       => true,
            };
        }));

        // Imágenes solo en galería y solo de lo que se va a pintar: el
        // render más reciente de cada variante (con versión o suelto).
        $imagenes = [];
        if ($vista === 'galeria' && $filas !== []) {
            $ids = array_map(static fn (array $f) => (int) $f['variante']['id'], $filas);
            foreach ($this->renders->whereIn('variante_id', $ids)->orderBy('subida_en', 'DESC')->findAll() as $r) {
                $vid = (int) $r['variante_id'];
                if (isset($imagenes[$vid]) || (empty($r['ruta_imagen']) && empty($r['hash_imagen']))) {
                    continue;
                }
                $imagenes[$vid] = [
                    't' => imagen_pieza($r, 'render', 't'),
                    'v' => imagen_pieza($r, 'render', 'v'),
                ];
            }
        }

        return view('piezas/existencias/index', [
            'filas'        => $filas,
            'vista'        => $vista,
            'filtro'       => $filtro,
            'imagenes'     => $imagenes,
            'categorias'     => $categorias,
            'categoriaSel'   => $categoriaSel,
            'soloSterclicks' => $soloSterclicks,
            'totales'        => [
                'unidades' => array_sum(array_map(static fn (array $f) => max(0, $f['stock']), $todas)),
                'conStock' => count(array_filter($todas, static fn (array $f) => $f['stock'] > 0)),
                'bajo'     => count(array_filter($todas, static fn (array $f) => $f['bajoMinimo'])),
            ],
        ]);
    }

    public function variante(int $id)
    {
        $variante = $this->variantes->find($id);
        if (!$variante) {
            return redirect()->to(site_url('piezas/existencias'))->with('error', 'Esa variante no existe.');
        }

        $stock  = $this->inventario->stockDeVariante($id);
        $minimo = (int) ($variante['stock_minimo'] ?? 0);

        return view('piezas/existencias/variante', [
            'variante'  => $variante,
            'familia'   => $this->familias->find($variante['familia_id']),
            'stock'     => $stock,
            'minimo'    => $minimo,
            'estado'    => $stock <= 0 ? 'cero' : ($minimo > 0 && $stock < $minimo ? 'bajo' : 'ok'),
            'historial' => $this->inventario->historialDeVariante($id),
        ]);
    }

    public function movimiento()
    {
        $varianteId = (int) $this->request->getPost('variante_id');
        $sentido    = (string) $this->request->getPost('sentido');
        $cantidad   = (int) $this->request->getPost('cantidad');
        $nota       = trim((string) $this->request->getPost('nota'));

        $variante = $this->variantes->find($varianteId);
        $volver   = $variante
            ? site_url('piezas/existencias/' . $varianteId)
            : site_url('piezas/existencias');

        if (!$variante) {
            return redirect()->to($volver)->with('error', 'Esa variante no existe.');
        }
        if (!in_array($sentido, ['alta', 'baja'], true) || $cantidad < 1) {
            return redirect()->to($volver)->with('error', 'Indica una cantidad de al menos 1.');
        }
        if ($nota === '') {
            return redirect()->to($volver)->with('error', 'Escribe el motivo del movimiento.');
        }

        $delta  = $sentido === 'alta' ? $cantidad : -$cantidad;
        $motivo = $sentido === 'alta' ? 'alta_manual' : 'baja_manual';
        $this->inventario->movimientoManual($varianteId, $delta, $motivo, $nota);

        $nuevo = $this->inventario->stockDeVariante($varianteId);
        $aviso = $nuevo < 0 ? ' Ojo: el stock queda en negativo.' : '';

        return redirect()->to($volver)->with('success', 'Movimiento registrado. Stock actual: ' . $nuevo . '.' . $aviso);
    }

    public function minimo(int $id)
    {
        $variante = $this->variantes->find($id);
        if (!$variante) {
            return redirect()->to(site_url('piezas/existencias'))->with('error', 'Esa variante no existe.');
        }

        $min = max(0, (int) $this->request->getPost('stock_minimo'));
        $this->variantes->update($id, ['stock_minimo' => $min]);

        return redirect()->to(site_url('piezas/existencias/' . $id))
            ->with('success', $min > 0 ? 'Mínimo fijado en ' . $min . '.' : 'Mínimo quitado.');
    }
}
