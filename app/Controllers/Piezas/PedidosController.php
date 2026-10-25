<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaPedidoImagenModel;
use App\Models\PiezaPedidoLineaModel;
use App\Models\PiezaPedidoModel;
use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaImagenesPublicas;

/**
 * Vista web de los pedidos: los entrantes desde sterclicks (recibidos por
 * App\Controllers\Piezas\SterclicksApi::pedidos) y los de alta manual
 * (crear()). Sus líneas se editan aquí mismo, en la ficha del pedido — los
 * verbos que la tocan (agregarLinea, editarLinea, borrarLinea,
 * ajustarCompletada) responden con JSON y el HTML ya renderizado de la fila
 * cuando la petición viene por AJAX, para que ver.php pueda guardar sin
 * recargar la página.
 */
class PedidosController extends BaseController
{
    protected $helpers = ['url', 'form', 'piezas_imagenes'];

    // Cuántos de "Hecho" trae el tablero de golpe; el resto, al bajar.
    private const HECHOS_POR_TANDA = 15;

    public function index()
    {
        $pedidoModel = new PiezaPedidoModel();

        // Tablero por estado, mismo orden que el flujo real: recién llegado ->
        // diseñando -> para impresión -> hecho.
        $columnas = [
            'nuevo'          => ['titulo' => 'Nuevos', 'pedidos' => []],
            'en_produccion'  => ['titulo' => 'Diseñando', 'pedidos' => []],
            'para_impresion' => ['titulo' => 'Para impresión', 'pedidos' => []],
            'completado'     => ['titulo' => 'Hecho', 'pedidos' => []],
        ];
        foreach ($this->paraTarjetas($pedidoModel->abiertos()) as $pedido) {
            $columnas[$pedido['estado']]['pedidos'][] = $pedido;
        }
        $columnas['completado']['pedidos'] = $this->paraTarjetas($pedidoModel->hechos(self::HECHOS_POR_TANDA));
        foreach ($columnas as $estado => &$columna) {
            $columna['total'] = $estado === 'completado'
                ? $pedidoModel->where('estado', 'completado')->countAllResults()
                : count($columna['pedidos']);
        }
        unset($columna);

        return view('piezas/pedidos/index', ['columnas' => $columnas, 'estados' => PiezaPedidoModel::ESTADOS]);
    }

    /**
     * Siguiente tanda de la columna "Hecho" (el "cargar más" del tablero):
     * HTML de las tarjetas ya pintadas y si quedan más por pedir.
     */
    public function masHechos()
    {
        $desde = max(0, (int) $this->request->getGet('desde'));
        $pedidoModel = new PiezaPedidoModel();
        $pedidos = $this->paraTarjetas($pedidoModel->hechos(self::HECHOS_POR_TANDA, $desde));

        $html = '';
        foreach ($pedidos as $pedido) {
            $html .= view('piezas/pedidos/_tarjeta', ['pedido' => $pedido]);
        }
        $total = $pedidoModel->where('estado', 'completado')->countAllResults();

        return $this->response->setJSON([
            'ok'     => true,
            'html'   => $html,
            'quedan' => $desde + count($pedidos) < $total,
        ]);
    }

    /**
     * Lo que pinta cada tarjeta del tablero además de la fila del pedido:
     * miniaturas, piezas hechas y líneas listas.
     */
    private function paraTarjetas(array $pedidos): array
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $varianteModel = new PiezaVarianteModel();
        $imagenModel = new PiezaPedidoImagenModel();

        foreach ($pedidos as &$pedido) {
            $lineas = $lineaModel->where('pedido_id', $pedido['id'])->findAll();

            $fotos = [];
            $totalPiezas = 0;
            // Mismas cifras que la cabecera de la ficha (refrescar() en
            // pedidos/ver): unidades hechas y líneas listas para imprimir.
            $piezasHechas = 0;
            $lineasListas = 0;
            foreach ($lineas as $linea) {
                $totalPiezas += (int) $linea['cantidad'];
                $piezasHechas += (int) $linea['cantidad_completada'];
                $lineasListas += (int) $linea['hecha'] ? 1 : 0;
                if (!$linea['variante_id']) {
                    continue;
                }
                $variante = $varianteModel->find($linea['variante_id']);
                if ($variante && ($foto = $this->miniaturaDeVariante($variante))) {
                    $fotos[] = $foto;
                }
            }

            // Las referencias del cliente detrás de las de las piezas: son lo
            // único que enseña un pedido cuyas piezas aún no existen.
            foreach ($imagenModel->dePedido((int) $pedido['id']) as $imagen) {
                $fotos[] = imagen_pieza($imagen, 'pedido-imagen');
            }

            $pedido['numLineas'] = count($lineas);
            $pedido['totalPiezas'] = $totalPiezas;
            $pedido['piezasHechas'] = $piezasHechas;
            $pedido['lineasListas'] = $lineasListas;
            $pedido['fotos'] = $fotos;
        }
        unset($pedido);

        return $pedidos;
    }

    /**
     * Alta manual de un pedido (a diferencia de los que llegan solos desde
     * sterclicks por SterclicksApi::pedidos). Nace sin líneas — se añaden
     * después desde la ficha, con el mismo formulario que usa cualquier
     * otro pedido (agregarLinea).
     */
    public function crear()
    {
        $pedidoId = (new PiezaPedidoModel())->insert([
            'origen'             => 'manual',
            'estado'             => 'nuevo',
            'referencia_externa' => trim((string) $this->request->getPost('referencia_externa')) ?: null,
            'notas'              => trim((string) $this->request->getPost('notas')) ?: null,
            'fecha_entrega'      => $this->fechaEntregaDelPost(),
            'creado_en'          => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/piezas/pedido/' . $pedidoId)->with('success', 'Pedido creado. Añade sus líneas aquí abajo.');
    }

    /** Misma cascada que Web::fotosDe(), pero por variante suelta (sin versión concreta). */
    private function miniaturaDeVariante(array $variante): ?string
    {
        $render = (new PiezaRenderModel())->where('variante_id', $variante['id'])->orderBy('subida_en', 'DESC')->first();
        $registro = $render ?: ((new PiezaReferenciaModel())->deVariante((int) $variante['familia_id'], (int) $variante['id'])[0] ?? null);

        if (!$registro) {
            return null;
        }

        return imagen_pieza($registro, $render ? 'render' : 'referencia', PiezaImagenesPublicas::MINIATURA);
    }

    /**
     * Nombre de familia/variante y foto de una línea, a partir de su
     * variante_id — lo que necesita _linea_fila para pintarse. Aparte para
     * poder reusarlo también al responder por AJAX a añadir/editar/marcar
     * completada una línea, sin duplicar esta resolución en cada verbo.
     */
    private function enriquecerLinea(array $linea): array
    {
        $variante = $linea['variante_id'] ? (new PiezaVarianteModel())->find($linea['variante_id']) : null;
        $linea['nombreFamilia']  = $variante ? ((new PiezaFamiliaModel())->find($variante['familia_id'])['nombre'] ?? null) : null;
        $linea['nombreVariante'] = $variante['nombre'] ?? null;
        $linea['foto']           = $variante ? $this->miniaturaDeVariante($variante) : null;

        return $linea;
    }

    /**
     * Respuesta de error común a los verbos de línea: JSON 422 si la
     * petición viene por AJAX (así el JS de ver.php puede mostrarlo sin
     * recargar), redirect con flash si no.
     */
    private function fallo(string $mensaje, ?string $redirectA = null)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje]);
        }

        return $redirectA
            ? redirect()->to($redirectA)->with('error', $mensaje)
            : redirect()->back()->with('error', $mensaje);
    }

    public function ver(int $id)
    {
        $pedido = (new PiezaPedidoModel())->conLineas($id);
        if (!$pedido) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        // Foto y nombre de cada línea: el SKU solo no dice nada de un
        // vistazo, y esta ficha es justo donde se decide qué imprimir.
        foreach ($pedido['lineas'] as &$linea) {
            $linea = $this->enriquecerLinea($linea);
        }
        unset($linea);

        // Qué placas se han marcado como "de este pedido" (a mano, al cargar
        // piezas a la placa desde aquí) — solo dato, sin cuadrar cantidades:
        // eso lo sigue juzgando quien mira la placa, no el sistema.
        $placas = (new \App\Models\PiezaPlacaModel())
            ->select('piezas_placas.*')
            ->join('piezas_placa_pedidos pp', 'pp.placa_id = piezas_placas.id')
            ->where('pp.pedido_id', $id)
            ->orderBy('piezas_placas.creado_en', 'DESC')->findAll();

        // Destinos posibles para "copiar líneas a…": solo pedidos aún
        // abiertos — copiar a uno hecho no tiene sentido.
        $pedidosAbiertos = (new PiezaPedidoModel())
            ->whereIn('estado', PiezaPedidoModel::ABIERTOS)
            ->where('id !=', $id)
            ->orderBy('creado_en', 'DESC')
            ->findAll(30);

        return view('piezas/pedidos/ver', [
            'pedido'          => $pedido,
            'estados'         => PiezaPedidoModel::ESTADOS,
            'placas'          => $placas,
            'imagenes'        => (new PiezaPedidoImagenModel())->dePedido($id),
            'pedidosAbiertos' => $pedidosAbiertos,
            'sugerencias'     => $this->sugerenciasParaPedido(array_column($pedido['lineas'], 'variante_id')),
        ]);
    }

    /**
     * Piezas en marcha que aún no están en el pedido, para añadirlas de un
     * toque desde la barra de abajo: las "Modificando" (sesión abierta o
     * subida sin promocionar) y las "Sin empezar" (sin ninguna versión ni
     * trabajo encima) — mismos criterios que los filtros del índice
     * (Web::resumen), con el mismo PiezaSyncService para que no discrepen.
     * Fuera las borradas y las de la nevera.
     *
     * @param array<int|string|null> $yaEnPedido variante_id de las líneas actuales
     * @return list<array{variante_id: int, texto: string, foto: ?string, tipo: string}>
     */
    private function sugerenciasParaPedido(array $yaEnPedido): array
    {
        $excluir = array_flip(array_map('intval', array_filter($yaEnPedido)));

        $variantes = (new PiezaVarianteModel())
            ->select('piezas_variantes.*, piezas_familias.nombre AS familia_nombre')
            ->join('piezas_familias', 'piezas_familias.id = piezas_variantes.familia_id')
            ->where('piezas_variantes.borrado_en', null)
            ->where('piezas_variantes.congelado_en', null)
            ->where('piezas_familias.borrado_en', null)
            ->findAll();

        $conVersiones = array_flip(array_map('intval', array_column(
            \Config\Database::connect()->table('piezas_versiones')->select('variante_id')->distinct()->get()->getResultArray(),
            'variante_id'
        )));

        $sync = new \App\Services\PiezaSyncService();
        $sugerencias = [];
        foreach ($variantes as $variante) {
            $id = (int) $variante['id'];
            if (isset($excluir[$id])) {
                continue;
            }

            $estado = $sync->estadoDeSincronizacion($id);
            $modificando = $estado['sesion_abierta'] !== null || $estado['ultima_subida'] !== null;
            if (!$modificando && isset($conVersiones[$id])) {
                continue;
            }

            $sugerencias[] = [
                'variante_id' => $id,
                'texto'       => $variante['familia_nombre'] . ' · ' . $variante['nombre'],
                'foto'        => $this->miniaturaDeVariante($variante),
                'tipo'        => $modificando ? 'modificando' : 'sin-empezar',
            ];
        }

        // Primero las que tienen trabajo encima (lo más probable de imprimir
        // pronto), luego las sin empezar; dentro, por nombre.
        usort($sugerencias, static fn($a, $b) => [$a['tipo'] !== 'modificando', $a['texto']] <=> [$b['tipo'] !== 'modificando', $b['texto']]);

        return $sugerencias;
    }

    /**
     * Copia las líneas marcadas a otro pedido (uno abierto o uno nuevo que
     * nace aquí) — p. ej. lo que se quedó pendiente de una impresión o
     * necesita rediseño, para seguirlo como tarea aparte. El pedido de
     * origen no se toca. Cada copia lleva solo lo que faltaba (cantidad -
     * completada, mínimo 1) y nace sin completar ni marcar como hecha.
     */
    public function copiarLineas(int $pedidoId)
    {
        return $this->trasladarLineas($pedidoId, false);
    }

    /**
     * Como copiarLineas, pero las líneas marcadas se MUEVEN: salen de este
     * pedido y pasan al destino tal cual (cantidad, completado y hecha
     * incluidos), conservando su id — así nada que las referencie se rompe.
     */
    public function moverLineas(int $pedidoId)
    {
        return $this->trasladarLineas($pedidoId, true);
    }

    private function trasladarLineas(int $pedidoId, bool $mover)
    {
        $verbo       = $mover ? 'mover' : 'copiar';
        $pedidoModel = new PiezaPedidoModel();
        if (!$pedidoModel->find($pedidoId)) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        $ids = array_filter(array_map('intval', (array) $this->request->getPost('lineas')));
        $lineas = $ids
            ? (new PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)->whereIn('id', $ids)
                ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll()
            : [];
        if (!$lineas) {
            return redirect()->back()->with('error', 'Marca al menos una línea para ' . $verbo . '.');
        }

        $destino = (string) $this->request->getPost('destino');
        $db = \Config\Database::connect();
        $db->transStart();

        if ($destino === 'nuevo') {
            $destinoId = $pedidoModel->insert([
                'origen'    => 'manual',
                'estado'    => 'nuevo',
                'notas'     => ($mover ? 'Movido' : 'Copiado') . ' del pedido #' . $pedidoId,
                'creado_en' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $destinoId = (int) $destino;
            $pedidoDestino = $pedidoModel->find($destinoId);
            if (!$pedidoDestino || $destinoId === $pedidoId) {
                $db->transRollback();

                return redirect()->back()->with('error', 'Elige un pedido de destino válido.');
            }
        }

        $lineaModel = new PiezaPedidoLineaModel();
        $orden = $lineaModel->siguienteOrden($destinoId);
        foreach ($lineas as $linea) {
            if ($mover) {
                $lineaModel->update($linea['id'], ['pedido_id' => $destinoId, 'orden' => $orden++]);
                continue;
            }
            $lineaModel->insert([
                'pedido_id'           => $destinoId,
                'orden'               => $orden++,
                'variante_id'         => $linea['variante_id'],
                'sku'                 => $linea['sku'],
                'descripcion_libre'   => $linea['descripcion_libre'],
                'cantidad'            => max(1, (int) $linea['cantidad'] - (int) $linea['cantidad_completada']),
                'cantidad_completada' => 0,
                'hecha'               => 0,
                'notas'               => $linea['notas'],
            ]);
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'No se pudieron ' . $verbo . ' las líneas.');
        }

        $n = count($lineas);

        return redirect()->to('/piezas/pedido/' . $destinoId)
            ->with('success', $n . ($n === 1 ? ' línea ' : ' líneas ') . ($mover ? 'movida' : 'copiada') . ($n === 1 ? '' : 's')
                . ' desde el pedido #' . $pedidoId . '.');
    }

    /** Borrado duro: las líneas se van solas por el ON DELETE CASCADE. */
    public function borrar(int $id)
    {
        $model = new PiezaPedidoModel();
        if (!$model->find($id)) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        // Las imágenes de referencia, a la papelera como cuando se borra una
        // suelta: la FK se llevaría los registros, pero no los ficheros.
        $imagenModel = new PiezaPedidoImagenModel();
        $almacen = new PiezaAlmacen();
        $publicas = new PiezaImagenesPublicas();
        foreach ($imagenModel->dePedido($id) as $imagen) {
            $almacen->aPapelera($imagen['ruta_imagen']);
            $publicas->retirar($imagen['hash_imagen'] ?? null);
        }
        $imagenModel->where('pedido_id', $id)->delete();

        $model->delete($id);

        return redirect()->to('/piezas/pedidos')->with('success', 'Pedido borrado.');
    }

    /**
     * Nueva línea a mano: con variante_id (elegida en el buscador) o, si no
     * hay variante todavía, solo una descripción libre — para apuntar piezas
     * futuras que aún no existen en el catálogo.
     */
    public function agregarLinea(int $pedidoId)
    {
        $pedidoModel = new PiezaPedidoModel();
        if (!$pedidoModel->find($pedidoId)) {
            return $this->fallo('Pedido no encontrado.', '/piezas/pedidos');
        }

        $varianteId = (int) $this->request->getPost('variante_id') ?: null;
        $descripcionLibre = trim((string) $this->request->getPost('descripcion_libre'));
        $cantidad = (int) $this->request->getPost('cantidad');

        $variante = $varianteId ? (new PiezaVarianteModel())->find($varianteId) : null;
        if (!$variante && $descripcionLibre === '') {
            return $this->fallo('Elige una pieza del catálogo o escribe una descripción.');
        }
        if ($cantidad < 1) {
            return $this->fallo('La cantidad debe ser al menos 1.');
        }

        $lineaModel = new PiezaPedidoLineaModel();
        $lineaId = $lineaModel->insert([
            'pedido_id'         => $pedidoId,
            'orden'             => $lineaModel->siguienteOrden($pedidoId),
            'variante_id'       => $variante['id'] ?? null,
            'sku'               => $variante['sku'] ?? null,
            'descripcion_libre' => $variante ? null : $descripcionLibre,
            'cantidad'          => $cantidad,
            'notas'             => trim((string) $this->request->getPost('notas')) ?: null,
        ], true);

        if ($this->request->isAJAX()) {
            $linea = $this->enriquecerLinea($lineaModel->find($lineaId));

            return $this->response->setJSON([
                'ok'       => true,
                'mensaje'  => 'Línea añadida.',
                'formHtml' => view('piezas/pedidos/_linea_form', ['linea' => $linea]),
                'rowHtml'  => view('piezas/pedidos/_linea_fila', ['linea' => $linea]),
            ]);
        }

        return redirect()->to('/piezas/pedido/' . $pedidoId)->with('success', 'Línea añadida.');
    }

    /**
     * Nuevo orden de las líneas tras arrastrarlas en la ficha: llega la
     * lista de ids tal como quedan de arriba abajo. Solo se tocan las que
     * son de este pedido; siempre por AJAX.
     */
    public function reordenarLineas(int $pedidoId)
    {
        if (!(new PiezaPedidoModel())->find($pedidoId)) {
            return $this->fallo('Pedido no encontrado.', '/piezas/pedidos');
        }

        $ids = array_values(array_filter(array_map('intval', (array) $this->request->getPost('orden'))));
        $builder = (new PiezaPedidoLineaModel())->builder();
        foreach ($ids as $posicion => $lineaId) {
            $builder->where('pedido_id', $pedidoId)->where('id', $lineaId)->update(['orden' => $posicion + 1]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /** Cambia producto (o descripción libre), cantidad y notas de una línea existente. */
    public function editarLinea(int $lineaId)
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $linea = $lineaModel->find($lineaId);
        if (!$linea) {
            return $this->fallo('Línea no encontrada.', '/piezas/pedidos');
        }

        $varianteId = (int) $this->request->getPost('variante_id') ?: null;
        $descripcionLibre = trim((string) $this->request->getPost('descripcion_libre'));
        $cantidad = (int) $this->request->getPost('cantidad');

        $variante = $varianteId ? (new PiezaVarianteModel())->find($varianteId) : null;
        if (!$variante && $descripcionLibre === '') {
            return $this->fallo('Elige una pieza del catálogo o escribe una descripción.');
        }
        if ($cantidad < 1) {
            return $this->fallo('La cantidad debe ser al menos 1.');
        }

        $lineaModel->update($lineaId, [
            'variante_id'         => $variante['id'] ?? null,
            'sku'                 => $variante['sku'] ?? null,
            'descripcion_libre'   => $variante ? null : $descripcionLibre,
            'cantidad'            => $cantidad,
            'cantidad_completada' => min((int) $linea['cantidad_completada'], $cantidad),
            'notas'               => trim((string) $this->request->getPost('notas')) ?: null,
        ]);

        if ($this->request->isAJAX()) {
            $linea = $this->enriquecerLinea($lineaModel->find($lineaId));

            return $this->response->setJSON([
                'ok'      => true,
                'mensaje' => 'Línea actualizada.',
                'rowHtml' => view('piezas/pedidos/_linea_fila', ['linea' => $linea]),
            ]);
        }

        return redirect()->to('/piezas/pedido/' . $linea['pedido_id'])->with('success', 'Línea actualizada.');
    }

    public function borrarLinea(int $lineaId)
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $linea = $lineaModel->find($lineaId);
        if (!$linea) {
            return $this->fallo('Línea no encontrada.', '/piezas/pedidos');
        }

        $lineaModel->delete($lineaId);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'mensaje' => 'Línea borrada.']);
        }

        return redirect()->to('/piezas/pedido/' . $linea['pedido_id'])->with('success', 'Línea borrada.');
    }

    /** Mismo criterio de búsqueda que Web::piezaBuscar(), pero sin exigir versión imprimible: aquí solo hace falta el catálogo. */
    public function buscarVariante()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['resultados' => []]);
        }

        $familiaModel = new PiezaFamiliaModel();
        $varianteModel = new PiezaVarianteModel();

        $familiasQueEncajan = $familiaModel->where('borrado_en', null)->like('nombre', $q)->findAll();
        $idsFamilia = array_column($familiasQueEncajan, 'id') ?: [0];

        $variantes = $varianteModel->where('borrado_en', null)
            ->groupStart()
                ->like('nombre', $q)
                ->orLike('sku', $q)
                ->orWhereIn('familia_id', $idsFamilia)
            ->groupEnd()
            ->orderBy('nombre')
            ->findAll(15);

        $resultados = [];
        foreach ($variantes as $variante) {
            $familia = $familiaModel->find($variante['familia_id']);
            $resultados[] = [
                'variante_id' => (int) $variante['id'],
                'texto'       => ($familia['nombre'] ?? '?') . ' · ' . $variante['nombre'],
            ];
        }

        return $this->response->setJSON(['resultados' => $resultados]);
    }

    /** Referencia externa y notas del pedido — lo mismo que se pide al darlo de alta a mano. */
    public function editarDatos(int $id)
    {
        $model = new PiezaPedidoModel();
        if (!$model->find($id)) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        $model->update($id, [
            'referencia_externa' => trim((string) $this->request->getPost('referencia_externa')) ?: null,
            'notas'              => trim((string) $this->request->getPost('notas')) ?: null,
            'fecha_entrega'      => $this->fechaEntregaDelPost(),
            'actualizado_en'     => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/piezas/pedido/' . $id)->with('success', 'Pedido actualizado.');
    }

    /** Fecha de entrega del formulario (input type=date): Y-m-d válida o null. */
    private function fechaEntregaDelPost(): ?string
    {
        $fecha = trim((string) $this->request->getPost('fecha_entrega'));
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        return $dt && $dt->format('Y-m-d') === $fecha ? $fecha : null;
    }

    /**
     * Pone o quita la estrella de prioridad. Por AJAX (tablero y ficha)
     * responde JSON con el valor nuevo; sin AJAX, vuelve a donde estaba.
     */
    public function alternarPrioridad(int $id)
    {
        $model = new PiezaPedidoModel();
        $pedido = $model->find($id);
        if (!$pedido) {
            return $this->fallo('Pedido no encontrado.', '/piezas/pedidos');
        }

        $prioritario = (int) $pedido['prioritario'] ? 0 : 1;
        $model->update($id, ['prioritario' => $prioritario]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'prioritario' => (bool) $prioritario]);
        }

        return redirect()->back();
    }

    /**
     * Orden manual de los pedidos dentro de una columna del tablero (se
     * arrastran las tarjetas). Recibe los ids de esa columna en su orden
     * nuevo; los de otras columnas no se tocan.
     */
    public function reordenar()
    {
        $ids = array_values(array_filter(array_map('intval', (array) $this->request->getPost('orden'))));
        $builder = (new PiezaPedidoModel())->builder();
        foreach ($ids as $posicion => $pedidoId) {
            $builder->where('id', $pedidoId)->update(['orden' => $posicion + 1]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function cambiarEstado(int $id)
    {
        $model = new PiezaPedidoModel();
        $pedido = $model->find($id);
        if (!$pedido) {
            return redirect()->to('/piezas/pedidos')->with('error', 'Pedido no encontrado.');
        }

        $estado = $this->request->getPost('estado');
        if (!in_array($estado, PiezaPedidoModel::ESTADOS, true)) {
            return redirect()->back()->with('error', 'Estado no válido.');
        }

        $model->update($id, ['estado' => $estado, 'actualizado_en' => date('Y-m-d H:i:s')]);

        return redirect()->to('/piezas/pedido/' . $id)->with('success', 'Estado actualizado.');
    }

    /**
     * Cuántas unidades de una línea se dan ya por buenas — a mano, sin
     * cuadrarlo contra ninguna placa: si una pieza sale mal no vale para el
     * pedido aunque esté impresa, y eso es una valoración que no le
     * corresponde adivinar al sistema. El botón +/- de la ficha del pedido
     * llama aquí con `delta`; se recorta entre 0 y la cantidad pedida.
     */
    public function ajustarCompletada(int $lineaId)
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $linea = $lineaModel->find($lineaId);
        if (!$linea) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Línea no encontrada.']);
        }

        $delta = (int) $this->request->getPost('delta');
        $nueva = max(0, min((int) $linea['cantidad'], (int) $linea['cantidad_completada'] + $delta));

        $lineaModel->update($lineaId, ['cantidad_completada' => $nueva]);
        $this->autocompletarPedidoSiProcede((int) $linea['pedido_id']);

        if ($this->request->isAJAX()) {
            $linea = $this->enriquecerLinea($lineaModel->find($lineaId));

            return $this->response->setJSON([
                'ok'      => true,
                'rowHtml' => view('piezas/pedidos/_linea_fila', ['linea' => $linea]),
            ]);
        }

        return redirect()->to('/piezas/pedido/' . $linea['pedido_id']);
    }

    /**
     * Checklist de preparación propio del usuario ("qué tengo listo antes
     * de imprimir"), independiente de cantidad_completada (esa cuenta
     * piezas ya impresas y válidas, después de imprimir). Se marca y
     * desmarca libremente, sin más lógica.
     */
    public function marcarHecha(int $lineaId)
    {
        $lineaModel = new PiezaPedidoLineaModel();
        $linea = $lineaModel->find($lineaId);
        if (!$linea) {
            return $this->fallo('Línea no encontrada.', '/piezas/pedidos');
        }

        $lineaModel->update($lineaId, ['hecha' => $this->request->getPost('hecha') ? 1 : 0]);
        $paraImpresion = $this->pasarAParaImpresionSiProcede((int) $linea['pedido_id']);
        if ($paraImpresion) {
            session()->setFlashdata('success', 'Todas las líneas están listas: el pedido pasa a "Para impresión".');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'recargar' => $paraImpresion]);
        }

        return redirect()->to('/piezas/pedido/' . $linea['pedido_id']);
    }

    /**
     * Si todas las líneas del pedido están marcadas como listas (preparadas
     * para imprimir), el pedido pasa a "para impresión" solo. Únicamente
     * avanza desde nuevo/diseñando: uno ya hecho no retrocede, y desmarcar
     * una línea después no lo devuelve atrás. Devuelve si lo ha cambiado.
     */
    private function pasarAParaImpresionSiProcede(int $pedidoId): bool
    {
        $pedidoModel = new PiezaPedidoModel();
        $pedido = $pedidoModel->find($pedidoId);
        if (!$pedido || !in_array($pedido['estado'], ['nuevo', 'en_produccion'], true)) {
            return false;
        }

        $lineas = (new PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)->findAll();
        if ($lineas === []) {
            return false;
        }
        foreach ($lineas as $linea) {
            if (empty($linea['hecha'])) {
                return false;
            }
        }

        $pedidoModel->update($pedidoId, ['estado' => 'para_impresion', 'actualizado_en' => date('Y-m-d H:i:s')]);

        return true;
    }

    /**
     * Si todas las líneas del pedido ya tienen su cantidad completada, el
     * pedido pasa a "completado" solo, sin esperar a que alguien cambie el
     * estado a mano — da igual en qué fase estuviera, salvo que ya
     * estuviera completado.
     */
    private function autocompletarPedidoSiProcede(int $pedidoId): void
    {
        $pedidoModel = new PiezaPedidoModel();
        $pedido = $pedidoModel->find($pedidoId);
        if (!$pedido || $pedido['estado'] === 'completado') {
            return;
        }

        $lineas = (new PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)->findAll();
        foreach ($lineas as $linea) {
            if ((int) $linea['cantidad_completada'] < (int) $linea['cantidad']) {
                return;
            }
        }

        $pedidoModel->update($pedidoId, ['estado' => 'completado', 'actualizado_en' => date('Y-m-d H:i:s')]);
    }
}
