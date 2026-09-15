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
        // "Dónde está", igual que la ficha de la variante pero resuelto en
        // una sola pasada para toda la galería: los códigos de hueco donde
        // hay stock de cada una, para verlo de un vistazo sin entrar pieza
        // por pieza.
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
            $stockPorVarianteYHueco = $this->inventario->stockPorVarianteYHueco();
            foreach ($ids as $vid) {
                $codigos = [];
                foreach ($stockPorVarianteYHueco[$vid] ?? [] as $huecoId => $n) {
                    if ($n > 0 && $huecoId > 0) {
                        $codigos[] = $codigoPorHuecoId[$huecoId] ?? '?';
                    }
                }
                sort($codigos);
                $ubicaciones[$vid] = $codigos;
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
        foreach ($estuchesPorId as $estucheId => $e) {
            foreach ($this->huecos->deEstuche($estucheId) as $h) {
                $codigo = PiezaHuecoModel::codigoCompleto($e, $h);
                $huecosDisponibles[] = ['estuche' => $e, 'hueco' => $h, 'codigo' => $codigo];
                $huecosPorId[(int) $h['id']] = ['hueco' => $h, 'codigo' => $codigo];
            }
        }

        // Desglose por hueco (0 = sin asignar), con el código completo a
        // mano para pintarlo sin otra vuelta a la base de datos.
        $desglose = [];
        foreach ($this->inventario->stockPorHueco($id) as $huecoId => $n) {
            if ($n === 0) {
                continue;
            }
            $desglose[] = [
                'hueco'  => $huecoId > 0 ? ($huecosPorId[$huecoId] ?? null) : null,
                'stock'  => $n,
            ];
        }

        return view('piezas/existencias/variante', [
            'variante'   => $variante,
            'familia'    => $this->familias->find($variante['familia_id']),
            'stock'      => $stock,
            'minimo'     => $minimo,
            'estado'     => $stock <= 0 ? 'cero' : ($minimo > 0 && $stock < $minimo ? 'bajo' : 'ok'),
            'historial'  => $this->inventario->historialDeVariante($id),
            'desglose'   => $desglose,
            'huecosDisponibles' => $huecosDisponibles,
            // Para preseleccionar en el formulario de "mover lo suelto": si
            // todo el stock ya asignado vive en un único hueco, ese es el
            // destino obvio; si no (repartido o sin nada asignado todavía),
            // que elija a mano.
            'huecoSugerido' => $this->inventario->huecoUnicoDeVariante($id),
        ]);
    }

    /**
     * Mueve todo lo "sin asignar" (ubicacion_id NULL) de una variante a un
     * hueco — para cuando se dio de alta suelto (a mano o desde una placa
     * sin hueco por defecto) y luego se decide dónde va físicamente, sin
     * tener que hacerlo movimiento a movimiento.
     */
    public function asignarSueltos(int $id)
    {
        $variante = $this->variantes->find($id);
        if (!$variante) {
            return redirect()->to(site_url('piezas/existencias'))->with('error', 'Esa variante no existe.');
        }

        $huecoId = (int) $this->request->getPost('hueco_id') ?: null;
        if ($huecoId === null || !$this->huecos->find($huecoId)) {
            return redirect()->to(site_url('piezas/existencias/' . $id))->with('error', 'Elige a qué hueco mover lo suelto.');
        }

        $movidos = $this->inventario->asignarSinAsignar($id, $huecoId);
        $mensaje = $movidos > 0
            ? 'Colocado en el hueco elegido lo que estaba sin asignar.'
            : 'No había nada sin asignar que mover.';

        return redirect()->to(site_url('piezas/existencias/' . $id))->with('success', $mensaje);
    }

    public function movimiento()
    {
        $varianteId = (int) $this->request->getPost('variante_id');
        $sentido    = (string) $this->request->getPost('sentido');
        $cantidad   = (int) $this->request->getPost('cantidad');
        $nota       = trim((string) $this->request->getPost('nota'));
        $huecoId    = (int) $this->request->getPost('ubicacion_id') ?: null;

        if ($huecoId !== null && !$this->huecos->find($huecoId)) {
            $huecoId = null;
        }

        // Si el movimiento se registra desde la ficha de un hueco (en vez
        // de desde Existencias), se vuelve ahí en lugar de a la ficha de
        // la variante — es donde físicamente está el usuario.
        $volverHuecoId = (int) $this->request->getPost('volver_hueco') ?: null;
        $volverAHueco  = $volverHuecoId !== null && $this->huecos->find($volverHuecoId);

        $variante = $this->variantes->find($varianteId);
        $volver   = match (true) {
            $volverAHueco => site_url('piezas/ubicaciones/huecos/' . $volverHuecoId),
            (bool) $variante => site_url('piezas/existencias/' . $varianteId),
            default => site_url('piezas/existencias'),
        };

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
        $this->inventario->movimientoManual($varianteId, $delta, $motivo, $nota, $huecoId);

        // Casilla "usar este hueco por defecto" del formulario de la ficha
        // del hueco: solo tiene sentido en un alta con hueco elegido — a
        // partir de ahora sincronizarPlaca() manda aquí lo que se imprima
        // de esta pieza, sin tener que fijarlo aparte.
        if ($sentido === 'alta' && $huecoId !== null && $this->request->getPost('fijar_defecto')) {
            $this->variantes->update($varianteId, ['hueco_predeterminado_id' => $huecoId]);
        }

        $nuevo = $this->inventario->stockDeVariante($varianteId);
        $aviso = $nuevo < 0 ? ' Ojo: el stock queda en negativo.' : '';

        return redirect()->to($volver)->with('success', 'Movimiento registrado. Stock actual: ' . $nuevo . '.' . $aviso);
    }

    /**
     * Fija (o quita) el hueco por defecto de una pieza: a dónde va
     * automáticamente lo que salga de una placa suya a partir de ahora
     * (App\Services\PiezaInventario::sincronizarPlaca()). Si ya había
     * stock de esta pieza sin asignar —lo normal es que sea justo lo
     * recién impreso, esperando destino—, se coloca ya mismo ahí.
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

        $movidos = $this->inventario->fijarHuecoPredeterminado($id, $huecoId);

        $mensaje = $huecoId === null
            ? 'Hueco por defecto quitado.'
            : 'Hueco por defecto fijado.' . ($movidos > 0 ? ' Se ha colocado ahí lo que tenía sin asignar.' : '');

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
}
