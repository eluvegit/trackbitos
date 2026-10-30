<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaEstucheModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaHuecoModel;
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
    private PiezaEstucheModel $estuches;
    private PiezaHuecoModel $huecos;

    public function __construct()
    {
        $this->inventario = new PiezaInventario();
        $this->variantes  = new PiezaVarianteModel();
        $this->familias   = new PiezaFamiliaModel();
        $this->renders    = new PiezaRenderModel();
        $this->categorias = new PiezaCategoriaModel();
        $this->estuches   = new PiezaEstucheModel();
        $this->huecos     = new PiezaHuecoModel();
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
            $padresVisibles = ($familia === null || !empty($familia['visible_sterclicks']))
                && ($catFam === null || ($catVisibleSt[(int) $catFam] ?? true));
            $visibleSterclicks = !empty($v['visible_sterclicks']) && $padresVisibles;

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
                'padresVisibles'    => $padresVisibles,
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
        // "Dónde está", igual que la ficha de la variante pero resuelto en
        // una sola pasada para toda la galería: el código del hueco de cada
        // una, para verlo de un vistazo sin entrar pieza por pieza.
        $ubicaciones = [];
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

            $codigoPorHuecoId = [];
            foreach ($this->estuches->ordenados() as $est) {
                foreach ($this->huecos->deEstuche((int) $est['id']) as $h) {
                    $codigoPorHuecoId[(int) $h['id']] = PiezaHuecoModel::codigoCompleto($est, $h);
                }
            }
            foreach ($filas as $f) {
                $huecoId = (int) ($f['variante']['hueco_predeterminado_id'] ?? 0);
                $ubicaciones[(int) $f['variante']['id']] = $huecoId > 0 ? [$codigoPorHuecoId[$huecoId] ?? '?'] : [];
            }
        }

        return view('piezas/existencias/index', [
            'filas'        => $filas,
            'vista'        => $vista,
            'filtro'       => $filtro,
            'imagenes'     => $imagenes,
            'ubicaciones'  => $ubicaciones,
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

        // Huecos disponibles, agrupados por estuche para el <select> del
        // modal, con su código completo ("E1H2") ya resuelto.
        $estuchesPorId = [];
        foreach ($this->estuches->ordenados() as $e) {
            $estuchesPorId[(int) $e['id']] = $e;
        }
        $huecosDisponibles = [];
        $huecosPorId = [];
        $ocupados = $this->inventario->huecosOcupados();
        foreach ($estuchesPorId as $estucheId => $e) {
            foreach ($this->huecos->deEstuche($estucheId) as $h) {
                $codigo = PiezaHuecoModel::codigoCompleto($e, $h);
                $huecosDisponibles[] = ['estuche' => $e, 'hueco' => $h, 'codigo' => $codigo, 'libre' => !isset($ocupados[(int) $h['id']])];
                $huecosPorId[(int) $h['id']] = ['hueco' => $h, 'codigo' => $codigo];
            }
        }

        $huecoId = $this->inventario->huecoDeVariante($id);

        return view('piezas/existencias/variante', [
            'variante'   => $variante,
            'familia'    => $this->familias->find($variante['familia_id']),
            'stock'      => $stock,
            'minimo'     => $minimo,
            'estado'     => $stock <= 0 ? 'cero' : ($minimo > 0 && $stock < $minimo ? 'bajo' : 'ok'),
            'historial'  => $this->inventario->historialDeVariante($id),
            'hueco'      => $huecoId !== null ? ($huecosPorId[$huecoId] ?? null) : null,
            'huecosDisponibles' => $huecosDisponibles,
        ]);
    }

    /**
     * Mete en un hueco una pieza que no tiene ninguno, tenga stock o no. Lo
     * que estuviera sin asignar va con ella.
     */
    public function asignarSueltos(int $id)
    {
        $volver = site_url('piezas/existencias/' . $id);

        $variante = $this->variantes->find($id);
        if (!$variante) {
            return redirect()->to(site_url('piezas/existencias'))->with('error', 'Esa variante no existe.');
        }

        $huecoId = (int) $this->request->getPost('hueco_id') ?: null;
        if ($huecoId === null || !$this->huecos->find($huecoId)) {
            return redirect()->to($volver)->with('error', 'Elige en qué hueco va.');
        }

        if ($error = $this->errorOtroHueco($id, $huecoId)) {
            return redirect()->to($volver)->with('error', $error);
        }

        $this->inventario->colocarEnHueco($id, $huecoId);

        return redirect()->to($volver)->with('success', 'Pieza colocada en su hueco.');
    }

    /**
     * Borra un movimiento manual metido por error (p. ej. una alta
     * duplicada al colocar una pieza en un hueco). Solo admite movimientos
     * origen='manual' — ver PiezaInventario::borrarMovimientoManual().
     */
    public function borrarMovimiento(int $id)
    {
        $ok = $this->inventario->borrarMovimientoManual($id);

        $varianteId = (int) $this->request->getPost('variante_id');
        $volver      = $varianteId > 0
            ? site_url('piezas/existencias/' . $varianteId)
            : site_url('piezas/existencias');

        return redirect()->to($volver)->with($ok ? 'success' : 'error', $ok
            ? 'Movimiento borrado.'
            : 'Ese movimiento no existe o no se puede borrar a mano (es un reflejo de una placa).');
    }

    public function movimiento()
    {
        $varianteId = (int) $this->request->getPost('variante_id');
        $sentido    = (string) $this->request->getPost('sentido');
        $cantidad   = (int) $this->request->getPost('cantidad');
        $nota       = trim((string) $this->request->getPost('nota'));
        $huecoId    = (int) $this->request->getPost('ubicacion_id') ?: null;

        // Una baja quita stock del total y punto — no va ligada a ningún
        // hueco. Las asignaciones de hueco son solo para saber dónde está
        // lo que queda (distribución), no cuentan para el stock, así que
        // no se tocan al dar de baja.
        if ($sentido === 'baja') {
            $huecoId = null;
        } elseif ($huecoId !== null && !$this->huecos->find($huecoId)) {
            $huecoId = null;
        }

        $variante = $this->variantes->find($varianteId);
        $volver   = $variante
            ? site_url('piezas/existencias/' . $varianteId)
            : site_url('piezas/existencias');
        if ($this->request->getPost('volver') === 'ubicaciones') {
            $volver = site_url('piezas/ubicaciones');
        }

        $error = !$variante ? 'Esa variante no existe.'
            : (!in_array($sentido, ['alta', 'baja'], true) || $cantidad < 1 ? 'Indica una cantidad de al menos 1.'
            : ($nota === '' ? 'Escribe el motivo del movimiento.' : null));
        if ($error !== null) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $error])
                : redirect()->to($volver)->with('error', $error);
        }

        if ($sentido === 'alta') {
            $actual = $this->inventario->huecoDeVariante($varianteId);
            if ($huecoId === null) {
                $huecoId = $actual;
            } elseif ($error = $this->errorOtroHueco($varianteId, $huecoId)) {
                return redirect()->to($volver)->with('error', $error);
            } elseif ($actual === null) {
                $this->inventario->colocarEnHueco($varianteId, $huecoId);
            }
        }

        $delta  = $sentido === 'alta' ? $cantidad : -$cantidad;
        $motivo = $sentido === 'alta' ? 'alta_manual' : 'baja_manual';
        $this->inventario->movimientoManual($varianteId, $delta, $motivo, $nota, $huecoId);

        $nuevo = $this->inventario->stockDeVariante($varianteId);
        $aviso = $nuevo < 0 ? ' Ojo: el stock queda en negativo.' : '';
        $mensaje = 'Movimiento registrado. Stock actual: ' . $nuevo . '.' . $aviso;

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'stock' => $nuevo, 'mensaje' => $mensaje]);
        }

        return redirect()->to($volver)->with('success', $mensaje);
    }

    /**
     * Fija (o quita) el hueco de una pieza desde la bitácora. Lo que salga
     * de sus placas irá ahí (PiezaInventario::sincronizarPlaca()), y lo que
     * tuviera sin asignar se coloca ya.
     */
    public function fijarHuecoPredeterminado(int $id)
    {
        $variante = $this->variantes->find($id);
        if (!$variante) {
            $mensaje = 'Esa variante no existe.';

            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje])
                : redirect()->back()->with('error', $mensaje);
        }

        $huecoId = (int) $this->request->getPost('hueco_id') ?: null;
        $hueco   = $huecoId !== null ? $this->huecos->find($huecoId) : null;
        if ($huecoId !== null && !$hueco) {
            $mensaje = 'Ese hueco no existe.';

            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje])
                : redirect()->back()->with('error', $mensaje);
        }

        if ($huecoId !== null && ($mensaje = $this->errorOtroHueco($id, $huecoId))) {
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje])
                : redirect()->back()->with('error', $mensaje);
        }

        $this->inventario->colocarEnHueco($id, $huecoId);

        $mensaje = $huecoId === null ? 'Hueco quitado.' : 'Hueco fijado.';

        if ($this->request->isAJAX()) {
            // El JS repinta solo la fila de esta variante (badge + botón
            // "Fijar"), sin volver a pedir el panel entero al servidor.
            $codigo = null;
            if ($hueco) {
                $estuche = $this->estuches->find($hueco['estuche_id']);
                $codigo  = $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $hueco) : $hueco['codigo'];
            }

            return $this->response->setJSON([
                'ok'      => true,
                'mensaje' => $mensaje,
                'huecoId' => $huecoId,
                'codigo'  => $codigo,
            ]);
        }

        return redirect()->back()->with('success', $mensaje);
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

    private function errorOtroHueco(int $varianteId, int $huecoId): ?string
    {
        $actual = $this->inventario->huecoDeVariante($varianteId);
        if ($actual === null || $actual === $huecoId) {
            return null;
        }

        $hueco   = $this->huecos->find($actual);
        $estuche = $hueco ? $this->estuches->find($hueco['estuche_id']) : null;
        $codigo  = $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $hueco) : ($hueco['codigo'] ?? '#' . $actual);

        return 'Esta pieza ya está en el hueco «' . $codigo . '» y solo puede estar en uno. '
            . 'Muévela desde allí o usa «Traer pieza aquí» en el hueco nuevo.';
    }
}
