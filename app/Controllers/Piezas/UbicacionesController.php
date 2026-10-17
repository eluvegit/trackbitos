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
 * Qué hay en cada hueco sale del hueco de cada pieza
 * (piezas_variantes.hueco_predeterminado_id), no del stock: una pieza a 0
 * sigue en su hueco. Ver App\Services\PiezaInventario.
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

        [$piezasPorHueco, $stock] = $this->piezasPorHueco();
        $varianteIds = array_keys($stock);

        $familias = [];
        foreach ($this->familias->findAll() as $fam) {
            $familias[(int) $fam['id']] = $fam;
        }

        $nombres = [];
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
            foreach ($this->huecos->deEstuche((int) $estuche['id']) as $hueco) {
                $contenido = [];
                foreach ($piezasPorHueco[(int) $hueco['id']] ?? [] as $varianteId) {
                    $contenido[] = [
                        'nombre' => $nombres[$varianteId],
                        'stock'  => $stock[$varianteId],
                        'img'    => $miniaturas[$varianteId] ?? null,
                    ];
                }
                usort($contenido, static fn ($a, $b) => $a['nombre'] <=> $b['nombre']);

                $huecos[] = [
                    'hueco'     => $hueco,
                    'codigo'    => PiezaHuecoModel::codigoCompleto($estuche, $hueco),
                    'unidades'  => array_sum(array_column($contenido, 'stock')),
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

        [$piezasPorHueco, $stock] = $this->piezasPorHueco();

        $porEstuche = [];
        foreach ($estuches as $e) {
            $huecos = [];
            foreach ($this->huecos->deEstuche((int) $e['id']) as $h) {
                $piezas = [];
                foreach ($piezasPorHueco[(int) $h['id']] ?? [] as $varianteId) {
                    $piezas[] = ['nombre' => $nombrePorVariante[$varianteId], 'img' => $miniaturas[$varianteId] ?? null];
                }
                usort($piezas, static fn (array $a, array $b) => $a['nombre'] <=> $b['nombre']);
                $huecos[] = ['codigo' => $h['codigo'], 'piezas' => $piezas];
            }
            $porEstuche[] = ['estuche' => $e, 'huecos' => $huecos];
        }

        // El reverso del listado anterior: por pieza, en qué hueco vive.
        // Lo que tiene stock sin hueco sale como "sin asignar", para que no
        // desaparezca del listado.
        $porPieza = [];
        foreach ($piezasPorHueco as $huecoId => $ids) {
            foreach ($ids as $varianteId) {
                $porPieza[] = [
                    'nombre'     => $nombrePorVariante[$varianteId],
                    'img'        => $miniaturas[$varianteId] ?? null,
                    'codigos'    => [$codigoPorHuecoId[$huecoId] ?? '?'],
                    'sinAsignar' => false,
                ];
            }
        }
        $conHueco = array_merge([], ...array_values($piezasPorHueco));
        foreach ($this->inventario->stockPorVariante() as $varianteId => $n) {
            if ($n > 0 && isset($nombrePorVariante[$varianteId]) && !in_array($varianteId, $conHueco, true)) {
                $porPieza[] = [
                    'nombre'     => $nombrePorVariante[$varianteId],
                    'img'        => $miniaturas[$varianteId] ?? null,
                    'codigos'    => [],
                    'sinAsignar' => true,
                ];
            }
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

        // Antes de que salte el "código único" de la validación (que no dice
        // con cuál choca), se busca el conflicto a mano para poder señalarlo
        // — MySQL compara texto sin distinguir mayúsculas, así que "e1"
        // choca con "E1" aunque no se vean iguales a simple vista.
        $existente = $this->estuches->where('codigo', $codigo)->first();
        if ($existente) {
            return redirect()->to(site_url('piezas/ubicaciones'))
                ->with('error', 'Ya existe un estuche con el código «' . $existente['codigo'] . '» (mayúsculas/minúsculas no cuentan como distinto).');
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

        // Mismo aviso explícito que en crear(): si el código ya lo tiene
        // OTRO estuche, se dice cuál en vez de dejar que salte el genérico
        // "código único" de la validación.
        $existente = $this->estuches->where('codigo', $codigo)->where('id !=', $id)->first();
        if ($existente) {
            return redirect()->to(site_url('piezas/ubicaciones'))
                ->with('error', 'Ya existe otro estuche con el código «' . $existente['codigo'] . '» (mayúsculas/minúsculas no cuentan como distinto).');
        }

        $zona  = trim((string) $this->request->getPost('zona'));
        $notas = trim((string) $this->request->getPost('notas'));

        // El 'id' va también en los datos a validar (y no solo como primer
        // argumento de update()): la regla is_unique[...,id,{id}] de
        // PiezaEstucheModel necesita encontrarlo ahí para sustituir el
        // placeholder y excluir esta misma fila de la comprobación —
        // si no, "código único" salta siempre al guardar sin cambiarlo.
        $ok = $this->estuches->update($id, [
            'id'     => $id,
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

        $ids   = array_map(static fn (array $v) => (int) $v['id'], $this->inventario->variantesDeHueco($id));
        $stock = $ids === [] ? [] : array_replace(array_fill_keys($ids, 0), $this->inventario->stockPorVariante($ids));

        $familias = [];
        foreach ($this->familias->findAll() as $f) {
            $familias[(int) $f['id']] = $f;
        }

        // Miniatura de cada pieza (el render más reciente), igual que en el
        // índice de Ubicaciones — para reconocerlas a ojo, no solo por el
        // nombre.
        $miniaturas = [];
        if ($stock !== []) {
            foreach ($this->renders->whereIn('variante_id', array_keys($stock))->orderBy('subida_en', 'DESC')->findAll() as $r) {
                $vid = (int) $r['variante_id'];
                if (isset($miniaturas[$vid]) || (empty($r['ruta_imagen']) && empty($r['hash_imagen']))) {
                    continue;
                }
                $miniaturas[$vid] = imagen_pieza($r, 'render', 't');
            }
        }

        $filas = [];
        if ($stock !== []) {
            foreach ($this->variantes->whereIn('id', array_keys($stock))->findAll() as $v) {
                $familia = $familias[(int) $v['familia_id']] ?? null;
                $filas[] = [
                    'variante' => $v,
                    'nombre'   => trim(($familia['nombre'] ?? '') . ' ' . $v['nombre']),
                    'stock'    => $stock[(int) $v['id']],
                    'img'      => $miniaturas[(int) $v['id']] ?? null,
                ];
            }
            usort($filas, static fn ($a, $b) => $a['nombre'] <=> $b['nombre']);
        }

        // Destinos posibles para "mover a otro hueco", agrupados por
        // estuche (<optgroup>) — con varios estuches de una docena de
        // huecos cada uno, un <select> plano se vuelve interminable.
        $destinosPorEstuche = [];
        $codigoPorHuecoId   = [];
        foreach ($this->estuches->ordenados() as $est) {
            $huecosDelEstuche = [];
            foreach ($this->huecos->deEstuche((int) $est['id']) as $h) {
                $codigo = PiezaHuecoModel::codigoCompleto($est, $h);
                $codigoPorHuecoId[(int) $h['id']] = $codigo;
                if ((int) $h['id'] !== $id) {
                    $huecosDelEstuche[] = ['id' => (int) $h['id'], 'codigo' => $codigo];
                }
            }
            if ($huecosDelEstuche !== []) {
                $destinosPorEstuche[] = ['estuche' => $est, 'huecos' => $huecosDelEstuche];
            }
        }
        $destinos = $codigoPorHuecoId;
        unset($destinos[$id]);

        return view('piezas/ubicaciones/hueco', [
            'hueco'    => $hueco,
            'estuche'  => $estuche,
            'codigo'   => $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $hueco) : $hueco['codigo'],
            'filas'    => $filas,
            'destinos' => $destinos,
            'destinosPorEstuche' => $destinosPorEstuche,
        ]);
    }

    /**
     * Búsqueda en vivo para "traer pieza aquí": cualquier pieza que no esté
     * ya en este hueco, tenga stock o no, con dónde está ahora.
     */
    public function buscarPieza(int $id)
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['resultados' => []]);
        }

        $familiasQueEncajan = $this->familias->where('borrado_en', null)->like('nombre', $q)->findAll();
        $idsFamilia = array_column($familiasQueEncajan, 'id') ?: [0];

        $variantes = $this->variantes->where('borrado_en', null)
            ->groupStart()
                ->like('nombre', $q)
                ->orLike('sku', $q)
                ->orWhereIn('familia_id', $idsFamilia)
            ->groupEnd()
            ->orderBy('nombre')
            ->findAll(60);

        $variantes = array_values(array_filter($variantes, static fn (array $v) => (int) ($v['hueco_predeterminado_id'] ?? 0) !== $id));
        $variantes = array_slice($variantes, 0, 15);
        $stock     = $variantes === [] ? [] : $this->inventario->stockPorVariante(array_map(static fn (array $v) => (int) $v['id'], $variantes));

        $resultados = [];
        foreach ($variantes as $v) {
            $familia = $this->familias->find($v['familia_id']);
            $resultados[] = [
                'id'     => (int) $v['id'],
                'nombre' => trim(($familia['nombre'] ?? '') . ' ' . $v['nombre']),
                'stock'  => $stock[(int) $v['id']] ?? 0,
                'hueco'  => $v['hueco_predeterminado_id'] ? $this->codigoDeHueco((int) $v['hueco_predeterminado_id']) : null,
            ];
        }

        return $this->response->setJSON(['resultados' => $resultados]);
    }

    /**
     * Mueve todas las piezas de este hueco a otro — para fusionar dos huecos
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
                ->with('error', 'Elige a qué hueco mover las piezas.');
        }
        if ($destinoId === $id) {
            return redirect()->to(site_url('piezas/ubicaciones/huecos/' . $id))
                ->with('error', 'El hueco de destino no puede ser el mismo.');
        }

        $movidas = $this->inventario->moverHueco($id, $destinoId);

        $mensaje = $movidas > 0
            ? 'Movidas ' . $movidas . ' piezas a «' . $this->codigoDeHueco($destinoId) . '».'
            : 'Ese hueco ya estaba vacío, nada que mover.';

        return redirect()->to(site_url('piezas/ubicaciones/huecos/' . $destinoId))->with('success', $mensaje);
    }

    /**
     * Lleva UNA pieza de este hueco a otro — a diferencia de moverStock(),
     * que mueve todo el contenido. Funciona igual con o sin stock.
     */
    public function moverPieza(int $id)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        // Esta acción se llama tanto desde la vista de un hueco (volver ahí)
        // como desde la ficha de la variante (volver a ella) — el origen se
        // manda explícito en vez de confiar en la URL de vuelta a ciegas.
        $volverAVariante = $this->request->getPost('volver') === 'variante' && $varianteId > 0;
        $volver = $volverAVariante
            ? site_url('piezas/existencias/' . $varianteId)
            : site_url('piezas/ubicaciones/huecos/' . $id);

        if (!$this->huecos->find($id)) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese hueco no existe.');
        }

        $destinoId = (int) $this->request->getPost('destino_id');
        if ($destinoId <= 0 || !$this->huecos->find($destinoId)) {
            return redirect()->to($volver)->with('error', 'Elige a qué hueco mover la pieza.');
        }
        if ($destinoId === $id) {
            return redirect()->to($volver)->with('error', 'El hueco de destino no puede ser el mismo.');
        }

        $variante = $varianteId > 0 ? $this->variantes->find($varianteId) : null;
        if (!$variante) {
            return redirect()->to($volver)->with('error', 'Esa pieza no existe.');
        }
        if ((int) ($variante['hueco_predeterminado_id'] ?? 0) !== $id) {
            return redirect()->to($volver)->with('error', '«' . $this->nombreDe($variante) . '» no está en este hueco.');
        }

        $this->inventario->colocarEnHueco($varianteId, $destinoId);

        // Al éxito, si no se viene de la ficha de la variante, se aterriza en
        // el hueco destino (para ver que ha llegado ahí) en vez de en el de
        // origen.
        $volverExito = $volverAVariante ? $volver : site_url('piezas/ubicaciones/huecos/' . $destinoId);

        return redirect()->to($volverExito)
            ->with('success', '«' . $this->nombreDe($variante) . '» movida a «' . $this->codigoDeHueco($destinoId) . '».');
    }

    /**
     * Trae a este hueco una pieza, esté donde esté (otro hueco, o sin
     * asignar) y tenga stock o no — el "tirar" complementario a
     * moverPieza(), para cuando tienes el hueco delante.
     */
    public function traerPieza(int $id)
    {
        if (!$this->huecos->find($id)) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese hueco no existe.');
        }

        $volver = site_url('piezas/ubicaciones/huecos/' . $id);

        $varianteId = (int) $this->request->getPost('variante_id');
        $variante   = $varianteId > 0 ? $this->variantes->find($varianteId) : null;
        if (!$variante) {
            return redirect()->to($volver)->with('error', 'Elige qué pieza traer.');
        }

        if ((int) ($variante['hueco_predeterminado_id'] ?? 0) === $id) {
            return redirect()->to($volver)->with('success', '«' . $this->nombreDe($variante) . '» ya estaba aquí.');
        }

        $this->inventario->colocarEnHueco($varianteId, $id);

        return redirect()->to($volver)->with('success', 'Traída «' . $this->nombreDe($variante) . '» a este hueco.');
    }

    public function quitarPieza(int $id)
    {
        $volver = site_url('piezas/ubicaciones/huecos/' . $id);

        if (!$this->huecos->find($id)) {
            return redirect()->to(site_url('piezas/ubicaciones'))->with('error', 'Ese hueco no existe.');
        }

        $varianteId = (int) $this->request->getPost('variante_id');
        $variante   = $varianteId > 0 ? $this->variantes->find($varianteId) : null;
        if (!$variante) {
            return redirect()->to($volver)->with('error', 'Esa pieza no existe.');
        }
        if ((int) ($variante['hueco_predeterminado_id'] ?? 0) !== $id) {
            return redirect()->to($volver)->with('error', '«' . $this->nombreDe($variante) . '» no estaba en este hueco.');
        }

        $this->inventario->colocarEnHueco($varianteId, null);

        return redirect()->to($volver)->with('success', '«' . $this->nombreDe($variante) . '» quitada de este hueco: queda sin asignar.');
    }

    /**
     * Las piezas (no borradas) que tienen hueco, agrupadas por hueco, y el
     * stock de cada una (0 si no tiene movimientos).
     *
     * @return array{0: array<int, list<int>>, 1: array<int,int>}
     */
    private function piezasPorHueco(): array
    {
        $porHueco = [];
        foreach ($this->variantes->where('borrado_en', null)->where('hueco_predeterminado_id IS NOT NULL', null, false)->findAll() as $v) {
            $porHueco[(int) $v['hueco_predeterminado_id']][] = (int) $v['id'];
        }

        $ids   = array_merge([], ...array_values($porHueco));
        $stock = $ids === [] ? [] : array_replace(array_fill_keys($ids, 0), $this->inventario->stockPorVariante($ids));

        return [$porHueco, $stock];
    }

    private function nombreDe(array $variante): string
    {
        $familia = $this->familias->find($variante['familia_id']);

        return trim(($familia['nombre'] ?? '') . ' ' . $variante['nombre']);
    }

    private function codigoDeHueco(int $huecoId): string
    {
        $hueco   = $this->huecos->find($huecoId);
        $estuche = $hueco ? $this->estuches->find($hueco['estuche_id']) : null;

        return $estuche ? PiezaHuecoModel::codigoCompleto($estuche, $hueco) : ($hueco['codigo'] ?? '?');
    }
}
