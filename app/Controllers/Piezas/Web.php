<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaComposicionModel;
use App\Models\PiezaConfigModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaMaquinaModel;
use App\Models\PiezaPedidoLineaModel;
use App\Models\PiezaPedidoModel;
use App\Models\PiezaPlacaEnlaceModel;
use App\Models\PiezaPlacaImagenModel;
use App\Models\PiezaPlacaModel;
use App\Models\PiezaPlacaPruebaModel;
use App\Models\PiezaPlacaVersionImagenModel;
use App\Models\PiezaPlacaVersionModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaSubidaModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use App\Models\SubtaskModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaEmpaquetadoService;
use App\Services\PiezaImagenesPublicas;
use App\Services\PiezaService;
use App\Services\PiezaSyncService;
use RuntimeException;
use Throwable;

/**
 * La cara de navegador del módulo Piezas (spec sección 7). Sobria y
 * orientada al estado: lo que debe responder de un vistazo es cuál es la
 * versión buena y dónde está el trabajo en curso.
 *
 * Lo que esta interfaz NO hace, a propósito: mover ficheros de TRABAJO —
 * el .blend de una sesión, en cualquier sentido. La identidad de máquina
 * la declara el script, nunca el navegador (spec 4.5) — la web puede
 * abrirse desde el móvil, donde no hay ningún disco que registrar. Así que
 * para el trabajo en curso aquí se muestra el hash de la nube y el comando
 * exacto a ejecutar, y quien toca esos ficheros sigue siendo trackbitos.py.
 *
 * Excepción deliberada, y una sola razón para las tres: nada de lo que se
 * mueve desde el navegador tiene que volver. Las imágenes (referencias y
 * renders) y el STL solo se suben una vez o se miran; y el .blend de una
 * versión ya promocionada es inmutable (invariante 4) y está cerrado, así
 * que descargarlo no abre ningún asiento que cuadrar. Lo que exige
 * declarar máquina no es el formato del fichero: es que haya trabajo que
 * deba regresar.
 */
class Web extends BaseController
{
    /** Días en borrador/impresa a partir de los cuales se marca como olvidada (spec 7.2). */
    private const DIAS_PENDIENTE_DE_JUICIO = 14;

    /**
     * La "placa" de piezas para imprimir juntas: un carrito de versiones
     * validadas con STL, guardado en la sesión de navegador. No es una
     * tabla — no hace falta persistirlo entre sesiones, es de usar y
     * vaciar en cada tanda de impresión.
     */
    private const SESION_CARRITO = 'piezas_carrito';

    /**
     * De qué placa del histórico salió lo que hay ahora en la placa actual,
     * cuando se cargó desde "Cargar en la placa actual" (fase 39). Vive en la
     * sesión y no en el carrito porque no es contenido: es la procedencia, y
     * solo sirve para que la placa que se registre luego sepa a cuál repite y
     * pueda heredar sus preguntas sin responder.
     */
    private const SESION_CARRITO_ORIGEN = 'piezas_carrito_origen';

    /**
     * De qué pedido de sterclicks salió lo que hay ahora en la placa actual,
     * cuando se cargó desde "Cargar piezas a la placa" en la ficha de un
     * pedido. Mismo espíritu que SESION_CARRITO_ORIGEN: no es contenido, es
     * procedencia, y solo sirve para que la placa que se registre a
     * continuación quede enlazada a ese pedido — sin ningún cálculo de
     * cuánto del pedido cubre, eso se sigue juzgando a mano.
     */
    private const SESION_CARRITO_PEDIDO_ORIGEN = 'piezas_carrito_pedido_origen';

    /**
     * Un año y `immutable`: el navegador no vuelve a preguntar por una imagen
     * que ya tiene. `private` porque estas imágenes van detrás del filtro de
     * login — puede guardarlas quien las ve, nunca un proxy por el camino.
     */
    private const CACHE_IMAGENES = 'private, max-age=31536000, immutable';

    /**
     * El Content-Type sale de la extensión y no de `mime_content_type()`
     * porque esa función vive en la extensión `fileinfo`, que no está en todos
     * los PHP (falta en este equipo). La extensión la puso el propio módulo al
     * guardar, ya validada contra el mime real en `validarImagen()`, así que
     * es de fiar: no es lo que dijo el navegador que subía.
     */
    private const TIPOS_POR_EXTENSION = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    /** Mimes aceptados para referencias/renders → extensión con la que se guardan. */
    private const MIMES_IMAGEN = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const TAMANO_MAX_IMAGEN = 20 * 1024 * 1024; // 20 MB: fotos de móvil, no hace falta más.
    private const TAMANO_MAX_STL    = 50 * 1024 * 1024; // 50 MB: piezas pequeñas, pero con margen.

    private PiezaCategoriaModel $categoriaModel;
    private PiezaMaquinaModel $maquinaModel;
    private PiezaFamiliaModel $familiaModel;
    private PiezaVarianteModel $varianteModel;
    private PiezaVersionModel $versionModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;
    private PiezaSubidaModel $subidaModel;
    private PiezaReferenciaModel $referenciaModel;
    private PiezaRenderModel $renderModel;
    private PiezaComposicionModel $composicionModel;
    private PiezaPlacaModel $placaModel;
    private PiezaPlacaVersionModel $placaVersionModel;
    private PiezaPlacaPruebaModel $placaPruebaModel;
    private PiezaPlacaEnlaceModel $placaEnlaceModel;
    private PiezaPlacaImagenModel $placaImagenModel;
    private PiezaPlacaVersionImagenModel $placaVersionImagenModel;
    private PiezaService $servicio;
    private PiezaSyncService $sync;
    private PiezaAlmacen $almacen;
    private PiezaImagenesPublicas $publicas;
    private PiezaEmpaquetadoService $empaquetado;

    /**
     * Las vistas del módulo pintan las imágenes con `imagen_pieza()`, que
     * decide entre el fichero estático y el controlador.
     *
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'auth', 'comidas_parse', 'piezas_imagenes'];

    public function __construct()
    {
        $this->categoriaModel   = new PiezaCategoriaModel();
        $this->maquinaModel     = new PiezaMaquinaModel();
        $this->familiaModel     = new PiezaFamiliaModel();
        $this->varianteModel    = new PiezaVarianteModel();
        $this->versionModel     = new PiezaVersionModel();
        $this->ramaModel        = new PiezaRamaModel();
        $this->sesionModel      = new PiezaSesionModel();
        $this->subidaModel      = new PiezaSubidaModel();
        $this->referenciaModel  = new PiezaReferenciaModel();
        $this->renderModel      = new PiezaRenderModel();
        $this->composicionModel = new PiezaComposicionModel();
        $this->placaModel       = new PiezaPlacaModel();
        $this->placaVersionModel = new PiezaPlacaVersionModel();
        $this->placaPruebaModel = new PiezaPlacaPruebaModel();
        $this->placaEnlaceModel = new PiezaPlacaEnlaceModel();
        $this->placaImagenModel = new PiezaPlacaImagenModel();
        $this->placaVersionImagenModel = new PiezaPlacaVersionImagenModel();
        $this->servicio         = new PiezaService();
        $this->sync             = new PiezaSyncService();
        $this->almacen          = new PiezaAlmacen();
        $this->publicas         = new PiezaImagenesPublicas();
        $this->empaquetado      = new PiezaEmpaquetadoService();
    }

    /**
     * Índice: un listado denso agrupado por categoría (spec 11.1). Cada
     * pieza resumida a lo único que importa de lejos — cuál es la versión
     * buena y si hay algo en marcha.
     *
     * Listado y no las tarjetas grandes de antes: con quince piezas se
     * trata de barrerlas de un vistazo, no de lucir cada una. Las fotos de
     * referencia, que vivían en esas tarjetas, se miran ahora en la ficha,
     * que es donde de verdad se usan (mientras modelas).
     */
    public function index()
    {
        // Las borradas no aparecen aquí (invariante 6): viven aparte, en
        // /piezas/papelera, mientras dura su plazo de gracia.
        $familias = $this->familiaModel->where('borrado_en', null)->orderBy('nombre', 'ASC')->findAll();

        foreach ($familias as &$familia) {
            $variantes = array_map(
                fn($v) => $this->resumen($v),
                $this->varianteModel->where('familia_id', $familia['id'])->where('borrado_en', null)
                    ->orderBy('nombre', 'ASC')->findAll()
            );
            // Mismo orden que las piezas dentro de su categoría: sin empezar,
            // modificándose, descartadas, consolidadas y el resto. Estable,
            // así que a igual tramo se mantiene el orden alfabético.
            usort(
                $variantes,
                fn(array $a, array $b) => $this->rangoMadurezVariante($a) <=> $this->rangoMadurezVariante($b)
            );
            $familia['variantes'] = $variantes;
        }
        unset($familia);

        return view('piezas/index', [
            'categorias'       => $this->categoriaModel->ordenadas(),
            'grupos'           => $this->agruparPorCategoria($familias),
            'familias'         => $familias,
            'carritoCount'     => count($this->carritoActual()),
            'papeleraCount'    => $this->familiaModel->where('borrado_en IS NOT NULL')->countAllResults()
                + $this->varianteModel->where('borrado_en IS NOT NULL')->countAllResults(),
            'sesionesActivas'  => $this->calcularSesionesActivas(),
            'ultimoPedido'     => $this->ultimoPedidoEntrante(),
            'pendientesResumen' => $this->pendientesResumen(),
            // Para el textarea del desplegable "Pautas": el texto tal cual
            // se guardó, no la lista ya filtrada de pautasPromocion().
            'pautasTexto'      => (new PiezaConfigModel())->find(1)['pautas_promocion'] ?? '',
        ]);
    }

    /**
     * Las subtareas sin marcar de la tarea de Journal enlazada (mismo
     * cálculo que PendientesController::index()), para el vistazo rápido
     * desde el índice (modal junto al botón "Pendientes") sin entrar a
     * /piezas/pendientes. Solo el título de cada una — nada de adjuntos ni
     * de los dos verbos ("Ya existe" / "Crear pieza"): para eso está la
     * pantalla completa, a la que lleva "Ir a pendientes".
     */
    private function pendientesResumen(): array
    {
        $tareaId = (new PiezaConfigModel())->tareaJournalId();
        if (!$tareaId) {
            return [];
        }

        return array_values(array_filter(
            (new SubtaskModel())->getForTask($tareaId),
            static fn(array $s) => empty($s['is_done'])
        ));
    }

    /**
     * El pedido más reciente, para el vistazo rápido desde el índice (modal
     * junto al botón "Pedidos") sin tener que entrar a /piezas/pedidos.
     * Vista simple a propósito — solo qué pide y con qué notas, nada de
     * fotos ni de cambiar estado: para eso está la ficha del pedido.
     */
    private function ultimoPedidoEntrante(): ?array
    {
        $pedido = (new PiezaPedidoModel())->recientes(1)[0] ?? null;
        if (!$pedido) {
            return null;
        }

        $lineaModel = new PiezaPedidoLineaModel();
        $lineas = $lineaModel->where('pedido_id', $pedido['id'])->findAll();

        foreach ($lineas as &$linea) {
            $variante = $linea['variante_id'] ? $this->varianteModel->find($linea['variante_id']) : null;
            $linea['descripcionPieza'] = $variante
                ? (($this->familiaModel->find($variante['familia_id'])['nombre'] ?? '?') . ' · ' . $variante['nombre'])
                : ($linea['descripcion_libre'] ?: '(sin referencia)');
        }
        unset($linea);

        $pedido['lineas'] = $lineas;

        return $pedido;
    }

    /**
     * JSON de las sesiones abiertas ahora mismo, para el refresco parcial
     * del aviso del índice (cada 20s, spec: "arriba del todo si hay una
     * sesión activa"). Aparte de `index()` para que el JS pueda repintar
     * solo esa franja sin recargar la página entera — perdería el
     * buscador escrito o el modo "Organizar" encendido.
     */
    public function sesionesActivas()
    {
        return $this->response->setJSON(['sesiones' => $this->calcularSesionesActivas()]);
    }

    /**
     * Quién está trabajando en qué, ahora mismo, en toda la pieza. Puede
     * haber más de una a la vez (las dos máquinas en piezas distintas), así
     * que no vale con mirar una variante — hay que barrer todas las
     * sesiones abiertas y remontar de vuelta a su variante y familia.
     */
    private function calcularSesionesActivas(): array
    {
        $sesiones = $this->sesionModel->where('cerrada_en', null)->orderBy('abierta_en', 'ASC')->findAll();
        if ($sesiones === []) {
            return [];
        }

        $ramas           = $this->ramaModel->whereIn('id', array_column($sesiones, 'rama_id'))->findAll();
        $varianteDeRama  = array_column($ramas, 'variante_id', 'id');

        $variantes          = $this->varianteModel->whereIn('id', array_values($varianteDeRama))->findAll();
        $familiaDeVariante  = array_column($variantes, 'familia_id', 'id');
        $nombreDeVariante   = array_column($variantes, 'nombre', 'id');

        $nombreDeFamilia = array_column(
            $this->familiaModel->whereIn('id', array_values($familiaDeVariante))->findAll(),
            'nombre',
            'id'
        );

        $resultado = [];
        foreach ($sesiones as $sesion) {
            $varianteId = $varianteDeRama[$sesion['rama_id']] ?? null;
            // Datos huérfanos (rama o variante ya no existe) no deberían
            // pasar, pero si pasan, mejor omitir esa sesión que romper el
            // aviso entero por una fila suelta.
            if ($varianteId === null || !isset($nombreDeVariante[$varianteId])) {
                continue;
            }
            $familiaId = $familiaDeVariante[$varianteId];

            $resultado[] = [
                'variante_id' => $varianteId,
                'familia'     => $nombreDeFamilia[$familiaId] ?? '?',
                'variante'    => $nombreDeVariante[$varianteId],
                'maquina'     => $this->sync->nombreDeMaquina((int) $sesion['maquina_id']) ?? 'máquina desconocida',
                'desde'       => $sesion['abierta_en'],
                'dias'        => $this->diasDesde($sesion['abierta_en']),
            ];
        }

        return $resultado;
    }

    /**
     * Reparte las piezas en sus categorías, respetando el orden que el
     * usuario les dio. Las categorías vacías se quedan igualmente: son
     * carpetas, y una carpeta vacía sigue diciendo dónde va lo que llegue.
     * Las piezas sin clasificar van al final, en un grupo sin categoría que
     * solo aparece si hay alguna — no es una categoría más, es un "todavía
     * no colocadas".
     *
     * `$ordenarPorMadurez` solo tiene sentido para el índice, cuyas filas
     * son piezas con su lista de `variantes` dentro. La galería reutiliza
     * este reparto por categoría pero sus filas son de una variante suelta
     * (sin esa clave) y ya vienen ordenadas alfabéticamente: ahí se pasa
     * `false` y no se toca el orden.
     *
     * @return list<array{categoria: array|null, piezas: list<array>}>
     */
    private function agruparPorCategoria(array $familias, bool $ordenarPorMadurez = true): array
    {
        $grupos = [];
        foreach ($this->categoriaModel->ordenadas() as $categoria) {
            $grupos[(int) $categoria['id']] = ['categoria' => $categoria, 'piezas' => []];
        }

        $sinCategoria = [];
        foreach ($familias as $familia) {
            $categoriaId = $familia['categoria_id'] ?? null;
            if ($categoriaId !== null && isset($grupos[(int) $categoriaId])) {
                $grupos[(int) $categoriaId]['piezas'][] = $familia;
            } else {
                $sinCategoria[] = $familia;
            }
        }

        $grupos = array_values($grupos);
        if ($sinCategoria !== []) {
            $grupos[] = ['categoria' => null, 'piezas' => $sinCategoria];
        }

        // Dentro de cada categoría, por fase de madurez: sin empezar,
        // modificándose, descartadas y por último las consolidadas (con
        // versión validada). El resto ("para imprimir", "sin validar") cae
        // al final. usort es estable desde PHP 8.0, así que a igual rango se
        // conserva el orden alfabético que ya traía la consulta.
        if ($ordenarPorMadurez) {
            foreach ($grupos as &$grupo) {
                usort(
                    $grupo['piezas'],
                    fn(array $a, array $b) => $this->rangoDeMadurez($a) <=> $this->rangoDeMadurez($b)
                );
            }
            unset($grupo);
        }

        return $grupos;
    }

    /**
     * En qué tramo del listado va una pieza, mirando todas sus variantes.
     * Menor = más arriba. El orden lo pidió el usuario tal cual: primero lo
     * que ni se ha empezado, luego lo que se está tocando, después lo
     * descartado y al final lo que ya tiene una versión buena.
     */
    private function rangoDeMadurez(array $familia): int
    {
        $conVersion = false;
        $enCurso    = false;
        $validada   = false;
        $descartada = false;

        foreach ($familia['variantes'] ?? [] as $v) {
            if (!empty($v['validada'])) {
                $validada = true;
            }
            if (!empty($v['trabajo_en_curso'])) {
                $enCurso = true;
            }
            if (($v['ultima_version_estado'] ?? null) === 'descartada') {
                $descartada = true;
            }
            if (($v['ultima_version_estado'] ?? null) !== null || !empty($v['validada'])) {
                $conVersion = true;
            }
        }

        if (!$conVersion && !$enCurso) {
            return 0; // sin empezar
        }
        if ($enCurso) {
            return 1; // modificándose
        }
        if ($descartada && !$validada) {
            return 2; // la última no sirve
        }
        if ($validada) {
            return 3; // versión consolidada
        }

        return 4; // para imprimir, sin validar, sin versión...
    }

    /**
     * Lo mismo para una sola variante: en qué tramo cae por sí sola. Se usa
     * para ordenar las variantes dentro de su pieza con el mismo criterio
     * que las piezas dentro de su categoría.
     */
    private function rangoMadurezVariante(array $v): int
    {
        $estado    = $v['ultima_version_estado'] ?? null;
        $validada  = !empty($v['validada']);

        if ($estado === null && !$validada && empty($v['trabajo_en_curso'])) {
            return 0; // sin empezar
        }
        if (!empty($v['trabajo_en_curso'])) {
            return 1; // modificándose
        }
        if ($estado === 'descartada' && !$validada) {
            return 2; // la última no sirve
        }
        if ($validada) {
            return 3; // versión consolidada
        }

        return 4; // para imprimir, sin validar, sin versión...
    }

    // ---- Máquinas -------------------------------------------------------

    /**
     * Las máquinas que hablan con la API, con lo único editable de ellas:
     * el nombre (spec 4.5). Se da de alta sola con el hostname, que suele
     * ser ilegible ("DESKTOP-4F2K1"), y ese nombre es el que aparece en los
     * avisos de "sesión abierta en…" — que es justo donde tiene que
     * entenderse sin pensar.
     *
     * Pantalla aparte y no un trozo del índice: se entra aquí una vez por
     * equipo, el día que se estrena.
     */
    public function maquinas()
    {
        $maquinas = $this->maquinaModel->orderBy('ultima_vez', 'DESC')->findAll();

        foreach ($maquinas as &$maquina) {
            // Lo que tiene esta máquina en la mano ahora mismo: es el dato
            // que dice si puedes olvidarte de ella o tienes que ir a buscarla.
            $maquina['sesiones_abiertas'] = $this->sesionModel
                ->where('maquina_id', $maquina['id'])->where('cerrada_en', null)->countAllResults();
            $maquina['dias_sin_verse'] = $this->diasDesde($maquina['ultima_vez']);
        }
        unset($maquina);

        return view('piezas/maquinas', ['maquinas' => $maquinas]);
    }

    public function renombrarMaquina(int $id)
    {
        return $this->ejecutar(
            fn() => $this->servicio->renombrarMaquina($id, (string) $this->request->getPost('nombre')),
            fn() => site_url('piezas/maquinas'),
            fn($maquina) => 'Esta máquina se llama ahora "' . $maquina['nombre'] . '".'
        );
    }

    // ---- Estadísticas de almacenamiento ----------------------------------

    /**
     * Cuánto ocupa el módulo entero en disco, y qué piezas concretas pesan
     * más — para saber de un vistazo si hace falta purgar la papelera ya
     * (en vez de esperar a los 30 días) o aligerar alguna sesión suelta
     * (spec: `PiezaService::descartarFicheroSesion`). Recorre el disco de
     * verdad vía `PiezaAlmacen`, no columnas de la base de datos: ni las
     * referencias ni los renders guardan su tamaño en ninguna tabla, así
     * que una suma parcial no cuadraría con lo que `writable/piezas` ocupa
     * de verdad.
     */
    public function estadisticas()
    {
        $filas = [];
        foreach ($this->familiaModel->where('borrado_en', null)->findAll() as $familia) {
            $bytes = 0;
            foreach ($this->varianteModel->where('familia_id', $familia['id'])->findAll() as $variante) {
                $bytes += $this->pesoDeVariante((int) $variante['id'])['total'];
            }

            if ($bytes > 0) {
                $filas[] = ['familia' => $familia, 'bytes' => $bytes];
            }
        }
        usort($filas, static fn($a, $b) => $b['bytes'] <=> $a['bytes']);

        return view('piezas/estadisticas', [
            'total'         => $this->almacen->tamanoTotal(),
            'totalPapelera' => $this->almacen->tamanoPapelera(),
            'piezas'        => $filas,
        ]);
    }

    /**
     * Fotografía de este instante, no un histórico completo: por variante,
     * el `.blend` de la versión validada (o si no hay, el de la más
     * reciente en el estado que sea) más una ficha en texto con el
     * historial entero. Ni STL ni renders/referencias — se pueden
     * regenerar o rehacer sin drama; lo que de verdad hay que salvar de un
     * desastre es el máster de 3D, porque perderlo es repetir el modelado
     * desde cero. Carpetas por categoría → pieza, igual que se ven en el
     * índice, para que la copia sea reconocible sin tener que abrir cada
     * ficha.
     */
    public function backupDescargar()
    {
        $carpetaTmp = WRITEPATH . 'piezas/tmp';
        if (!is_dir($carpetaTmp) && !mkdir($carpetaTmp, 0775, true) && !is_dir($carpetaTmp)) {
            throw new RuntimeException('No se pudo crear la carpeta temporal para la copia de seguridad.');
        }
        $rutaZip = $carpetaTmp . '/piezas-backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($rutaZip, \ZipArchive::CREATE) !== true) {
            throw new RuntimeException('No se pudo crear el zip de la copia de seguridad.');
        }

        // Puede haber bastantes .blend que copiar: el límite por defecto de
        // PHP se queda corto para una colección grande. Si el hosting tiene
        // esta función deshabilitada, sigue con el límite que haya.
        @set_time_limit(300);

        $categorias = array_column($this->categoriaModel->ordenadas(), null, 'id');

        $lineasCategorias = ['Categorías (orden del índice):', ''];
        foreach ($categorias as $categoria) {
            $lineasCategorias[] = '- ' . $categoria['nombre'];
        }
        $zip->addFromString('categorias.txt', implode("\n", $lineasCategorias) . "\n");

        foreach ($this->familiaModel->where('borrado_en', null)->orderBy('nombre', 'ASC')->findAll() as $familia) {
            $carpetaCategoria = !empty($familia['categoria_id']) && isset($categorias[$familia['categoria_id']])
                ? $this->paraNombreDeArchivo($categorias[$familia['categoria_id']]['nombre'])
                : 'sin-categoria';
            $carpetaFamilia = $this->paraNombreDeArchivo($familia['nombre']) ?: ('pieza-' . $familia['id']);

            foreach ($this->varianteModel->where('familia_id', $familia['id'])->where('borrado_en', null)
                ->orderBy('nombre', 'ASC')->findAll() as $variante) {
                $this->empaquetarVarianteEnBackup($zip, $carpetaCategoria . '/' . $carpetaFamilia, $familia, $variante);
            }
        }

        $zip->close();

        // El fichero tiene que seguir existiendo cuando DownloadResponse lo
        // lea durante send(), que ocurre después de que este método
        // retorne — por eso el borrado va en un shutdown function, no aquí
        // (mismo patrón que carritoDescargar).
        register_shutdown_function(static function () use ($rutaZip) {
            @unlink($rutaZip);
        });

        return $this->response->download($rutaZip, null, true);
    }

    /**
     * Una variante = una carpeta con su ficha en texto y, si hay a qué
     * apuntar, el `.blend` de la versión de referencia. El nombre de fichero
     * lleva el número de versión para que quede claro de qué momento es la
     * fotografía si algún día se compara con una copia más reciente.
     */
    private function empaquetarVarianteEnBackup(\ZipArchive $zip, string $carpetaBase, array $familia, array $variante): void
    {
        $carpeta = $carpetaBase . '/' . ($this->paraNombreDeArchivo($variante['nombre']) ?: ('variante-' . $variante['id']));

        // Todas las validadas (nunca se sabe cuál hará falta consultar más
        // adelante) más, aparte, lo último conocido si hay trabajo posterior
        // sin consolidar: o bien una versión más nueva sin validar todavía,
        // o bien —cuando la validada SÍ es la última versión creada— una
        // sesión ya subida en la rama abierta que parte de ella. En los dos
        // casos se guarda aparte, con sufijo EN-DESARROLLO, sin sustituir a
        // la validada.
        $versiones = $this->versionModel->where('variante_id', $variante['id'])->orderBy('numero', 'DESC')->findAll();
        $incluidas = array_values(array_filter($versiones, static fn(array $v) => $v['estado'] === 'validada'));
        $ultima    = $versiones[0] ?? null;

        $sesionSinVersion = null;
        if ($ultima !== null && $ultima['estado'] !== 'validada') {
            // Ya hay una versión más nueva (sin validar) que la última
            // validada: eso ya es "lo último conocido", no hace falta mirar
            // la rama abierta.
            $incluidas[] = $ultima;
        } else {
            // O no hay ninguna versión todavía, o la última que hay ya está
            // validada: en los dos casos, lo que pueda haber en la rama
            // abierta (invariante 2/3: como mucho una) es lo único más nuevo
            // que puede existir.
            $rama = $this->ramaModel->abiertaDe((int) $variante['id']);
            $sesionSinVersion = $rama ? $this->sesionModel->ultimaSubida((int) $rama['id']) : null;
        }

        foreach ($incluidas as $version) {
            if ($this->almacen->existe($version['ruta_blend'])) {
                $esValidada = $version['estado'] === 'validada';
                $zip->addFile(
                    $this->almacen->absoluta($version['ruta_blend']),
                    $carpeta . '/' . $this->nombreArchivo($variante, $version, 'blend', $esValidada ? null : 'EN-DESARROLLO')
                );
            }
        }

        if ($sesionSinVersion && $this->almacen->existe($sesionSinVersion['ruta_blend'])) {
            // Con validada de por medio, el nombre lleva su "vNNN-" delante:
            // sin eso, el fichero EN-DESARROLLO no dice a partir de qué
            // versión se sigue trabajando, y esa referencia se pierde en
            // cuanto sale de esta carpeta.
            $prefijoVersion = ($ultima !== null && $ultima['estado'] === 'validada')
                ? sprintf('v%03d-', (int) $ultima['numero'])
                : '';
            // Mismas partes que nombreArchivo() (sku, familia, variante): sin
            // la familia, "base-v002-..." no distingue de qué pieza es en
            // cuanto hay dos variantes con el mismo nombre suelto.
            $partes = array_filter([
                $this->paraNombreDeArchivo($variante['sku'] ?? null),
                $this->paraNombreDeArchivo($familia['nombre'] ?? null),
                $this->paraNombreDeArchivo($variante['nombre'] ?? null) ?: 'variante-' . $variante['id'],
            ]);
            $nombre = implode('-', $partes)
                . sprintf('-%ssesion-%03d-EN-DESARROLLO.blend', $prefijoVersion, (int) $sesionSinVersion['numero']);
            $zip->addFile($this->almacen->absoluta($sesionSinVersion['ruta_blend']), $carpeta . '/' . $nombre);
        }

        $zip->addFromString(
            $carpeta . '/ficha.md',
            $this->fichaMarkdown($familia, $variante, $versiones, $incluidas, $sesionSinVersion)
        );
    }

    /**
     * @param array      $incluidas        versiones cuyo .blend viaja en esta copia (validadas + la última en curso, si aplica)
     * @param array|null $sesionSinVersion última sesión subida, solo cuando la variante aún no tiene ninguna versión promocionada
     */
    private function fichaMarkdown(array $familia, array $variante, array $versiones, array $incluidas, ?array $sesionSinVersion = null): string
    {
        $l = [];
        $l[] = '# ' . $familia['nombre'] . ' — ' . $variante['nombre'];
        $l[] = '';
        if (!empty($variante['sku'])) {
            $l[] = '- SKU: ' . $variante['sku'];
        }
        if (!empty($variante['enlace_original'])) {
            $l[] = '- Enlace al original: ' . $variante['enlace_original'];
        }
        if (!empty($familia['notas'])) {
            $l[] = '- Notas de la pieza: ' . $familia['notas'];
        }
        if (!empty($variante['notas'])) {
            $l[] = '- Notas de la variante: ' . $variante['notas'];
        }
        $l[] = '';

        $l[] = '## Versiones incluidas en esta copia';
        if (empty($incluidas) && !$sesionSinVersion) {
            $l[] = '';
            $l[] = 'Sin ninguna versión promocionada ni ninguna subida todavía: no hay `.blend` que incluir.';
        } else {
            foreach ($incluidas as $version) {
                $esValidada = $version['estado'] === 'validada';
                $l[] = '';
                $l[] = sprintf(
                    '**v%03d** · %s%s · %s',
                    (int) $version['numero'],
                    $version['estado'],
                    $esValidada ? ' (la buena)' : ' · **EN DESARROLLO — todavía sin validar**',
                    $version['promocionada_en']
                );
                if (!empty($version['cambio'])) {
                    $l[] = '';
                    $l[] = $version['cambio'];
                }
                if (!empty($version['medidas'])) {
                    $l[] = '';
                    $l[] = 'Medidas: ' . $version['medidas'];
                }
                if (!empty($version['resultado'])) {
                    $l[] = '';
                    $l[] = 'Resultado: ' . $version['resultado'];
                }
            }

            // Trabajo posterior a la última versión (validada o no) que
            // todavía no se ha promocionado: existe en paralelo, no sustituye
            // a ninguna de las de arriba. Si parte de una validada, se dice
            // de cuál — es la misma referencia que ya lleva el nombre del
            // fichero.
            if ($sesionSinVersion) {
                $baseValidada = $incluidas[0] ?? null;
                $l[] = '';
                $l[] = sprintf(
                    '**Sesión %d**%s · sin promocionar todavía · **EN DESARROLLO — todavía sin validar** · %s',
                    (int) $sesionSinVersion['numero'],
                    $baseValidada ? sprintf(' · a partir de v%03d', (int) $baseValidada['numero']) : '',
                    $sesionSinVersion['subida_en']
                );
                if (!empty($sesionSinVersion['log'])) {
                    $l[] = '';
                    $l[] = $sesionSinVersion['log'];
                }
            }
        }
        $l[] = '';

        $l[] = '## Historial completo';
        $l[] = '';
        if (empty($versiones)) {
            $l[] = 'Sin versiones.';
        } else {
            foreach ($versiones as $v) {
                $l[] = sprintf('- v%03d · %s · %s · %s', (int) $v['numero'], $v['estado'], $v['promocionada_en'], $v['cambio']);
            }
        }
        $l[] = '';

        $componentes = $this->componentesDe((int) $variante['id']);
        if (!empty($componentes)) {
            $l[] = '## Compuesta de';
            $l[] = '';
            foreach ($componentes as $c) {
                if (!$c['version'] || !$c['variante'] || !$c['familia']) {
                    continue;
                }
                $l[] = sprintf(
                    '- %s / %s · v%03d%s',
                    $c['familia']['nombre'],
                    $c['variante']['nombre'],
                    (int) $c['version']['numero'],
                    !empty($c['notas']) ? ' — ' . $c['notas'] : ''
                );
            }
            $l[] = '';
        }

        // Cuenta, no ficheros: esta copia es solo texto + el .blend de
        // referencia (ver cabecera de backupDescargar). Sirve para saber
        // qué se dejó fuera sin tener que adivinarlo.
        $nReferencias = count($this->referenciaModel->deVariante((int) $familia['id'], (int) $variante['id']));
        $nRenders     = $this->renderModel->where('variante_id', $variante['id'])->countAllResults();
        $nStl         = array_sum(array_map(fn(array $v) => count($this->servicio->stlsDe((int) $v['id'])), $incluidas));
        $l[] = '## No incluido en esta copia';
        $l[] = '';
        $l[] = sprintf(
            '%d referencia(s) de esta variante, %d render(es) de esta variante, %d STL de las versiones incluidas.',
            $nReferencias,
            $nRenders,
            $nStl
        );

        return implode("\n", $l) . "\n";
    }

    /**
     * Cuánto pesa una variante en disco: sus versiones (.blend + .stl) y
     * TODAS sus sesiones, de cualquier rama, abierta o cerrada, purgada o
     * no — el fichero puede estar ya en la papelera de ficheros, pero sigue
     * siendo peso que le pertenece a esta variante hasta que se purgue de
     * verdad. Reutilizado por la ficha (tarjeta de estadísticas) y por
     * `/piezas/estadisticas` (ranking por pieza).
     *
     * @return array{versiones: int, sesiones: int, total: int} bytes
     */
    private function pesoDeVariante(int $varianteId): array
    {
        $bytesVersiones = 0;
        foreach ($this->versionModel->where('variante_id', $varianteId)->findAll() as $version) {
            $bytesVersiones += $this->almacen->tamano($version['ruta_blend']) ?? 0;
            foreach ($this->servicio->stlsDe((int) $version['id']) as $stl) {
                $bytesVersiones += $this->almacen->tamano($stl['ruta_stl']) ?? 0;
            }
        }

        $bytesSesiones = 0;
        foreach ($this->ramaModel->where('variante_id', $varianteId)->findAll() as $rama) {
            foreach ($this->sesionModel->where('rama_id', $rama['id'])->findAll() as $sesion) {
                $bytesSesiones += $this->almacen->tamano($sesion['ruta_blend']) ?? 0;
            }
        }

        return [
            'versiones' => $bytesVersiones,
            'sesiones'  => $bytesSesiones,
            'total'     => $bytesVersiones + $bytesSesiones,
        ];
    }

    // ---- Categorías -----------------------------------------------------

    /**
     * Pautas de promoción: se reescriben enteras cada vez (el textarea del
     * desplegable manda el texto completo, una pauta por línea), no hay
     * alta/borrado de una pauta suelta.
     */
    public function guardarPautas()
    {
        return $this->ejecutar(
            function () {
                (new PiezaConfigModel())->guardarPautas((string) $this->request->getPost('texto'));

                return true;
            },
            fn() => site_url('piezas'),
            fn() => 'Pautas guardadas.'
        );
    }

    public function crearCategoria()
    {
        return $this->ejecutar(
            fn() => $this->servicio->crearCategoria((string) $this->request->getPost('nombre')),
            fn() => site_url('piezas'),
            fn($categoria) => 'Categoría "' . $categoria['nombre'] . '" creada.'
        );
    }

    /**
     * Ocultar/mostrar en el catálogo de sterclicks (SterclicksApi::catalogo()).
     * Ocultar una categoría o familia oculta también todo lo que cuelga de
     * ella, aunque la variante en sí siga marcada como visible.
     */
    public function toggleVisibilidadCategoria(int $id)
    {
        return $this->ejecutar(
            function () use ($id) {
                $categoria = $this->categoriaModel->find($id);
                if (!$categoria) {
                    throw new RuntimeException('Esa categoría no existe.');
                }
                $visible = empty($categoria['visible_sterclicks']) ? 1 : 0;
                $this->categoriaModel->update($id, ['visible_sterclicks' => $visible]);
                (new \App\Services\SterclicksClient())->sincronizarCatalogo();
                return ['nombre' => $categoria['nombre'], 'visible' => $visible];
            },
            fn() => site_url('piezas'),
            fn($r) => $r['visible']
                ? '"' . $r['nombre'] . '" vuelve a verse en sterclicks.'
                : '"' . $r['nombre'] . '" oculta de sterclicks (y todo lo de dentro).'
        );
    }

    public function toggleVisibilidadFamilia(int $id)
    {
        return $this->ejecutar(
            function () use ($id) {
                $familia = $this->familiaModel->find($id);
                if (!$familia) {
                    throw new RuntimeException('Esa pieza no existe.');
                }
                $visible = empty($familia['visible_sterclicks']) ? 1 : 0;
                $this->familiaModel->update($id, ['visible_sterclicks' => $visible]);
                (new \App\Services\SterclicksClient())->sincronizarCatalogo();
                return ['nombre' => $familia['nombre'], 'visible' => $visible];
            },
            fn() => site_url('piezas'),
            fn($r) => $r['visible']
                ? '"' . $r['nombre'] . '" vuelve a verse en sterclicks.'
                : '"' . $r['nombre'] . '" oculta de sterclicks (y todas sus variantes).'
        );
    }

    public function toggleVisibilidadVariante(int $id)
    {
        return $this->ejecutar(
            function () use ($id) {
                $variante = $this->varianteModel->find($id);
                if (!$variante) {
                    throw new RuntimeException('Esa variante no existe.');
                }
                $visible = empty($variante['visible_sterclicks']) ? 1 : 0;
                $this->varianteModel->update($id, ['visible_sterclicks' => $visible]);
                (new \App\Services\SterclicksClient())->sincronizarCatalogo();
                return ['id' => $id, 'nombre' => $variante['nombre'], 'visible' => $visible];
            },
            fn($r) => site_url('piezas/variante/' . $r['id']),
            fn($r) => $r['visible']
                ? '"' . $r['nombre'] . '" vuelve a verse en sterclicks.'
                : '"' . $r['nombre'] . '" oculta de sterclicks.'
        );
    }

    public function renombrarCategoria(int $id)
    {
        return $this->ejecutar(
            fn() => $this->servicio->renombrarCategoria($id, (string) $this->request->getPost('nombre')),
            fn() => site_url('piezas'),
            fn($categoria) => 'Categoría renombrada a "' . $categoria['nombre'] . '".'
        );
    }

    public function borrarCategoria(int $id)
    {
        return $this->ejecutar(
            fn() => $this->servicio->borrarCategoria($id),
            fn() => site_url('piezas'),
            // Decir cuántas piezas se han quedado sueltas evita el susto de
            // verlas aparecer de golpe al final del índice.
            fn($resultado) => sprintf(
                'Categoría "%s" borrada.%s',
                $resultado['categoria']['nombre'],
                $resultado['descolocadas'] > 0
                    ? sprintf(' %d pieza(s) quedan sin clasificar, al final del listado.', $resultado['descolocadas'])
                    : ''
            )
        );
    }

    public function moverCategoria(int $id, string $direccion)
    {
        return $this->ejecutar(
            fn() => $this->servicio->moverCategoria($id, $direccion === 'subir' ? -1 : 1),
            fn() => site_url('piezas'),
            fn($categoria) => 'Orden actualizado.'
        );
    }

    /**
     * Mueve una pieza de categoría desde el propio listado (el modo
     * "Organizar"): valor vacío = sacarla de todas, que es distinto de
     * borrarla y hay que poder hacerlo sin pasar por ningún formulario.
     */
    public function clasificarFamilia(int $familiaId)
    {
        $categoriaId = $this->request->getPost('categoria_id');

        return $this->ejecutar(
            fn() => $this->servicio->clasificarFamilia(
                $familiaId,
                ($categoriaId === null || $categoriaId === '') ? null : (int) $categoriaId
            ),
            fn() => site_url('piezas'),
            function ($familia) {
                if (empty($familia['categoria_id'])) {
                    return '"' . $familia['nombre'] . '" queda sin clasificar.';
                }
                $categoria = $this->categoriaModel->find($familia['categoria_id']);

                return '"' . $familia['nombre'] . '" movida a ' . ($categoria['nombre'] ?? 'su categoría') . '.';
            }
        );
    }

    /**
     * Ficha de variante: el aviso de trabajo vivo va ARRIBA, antes que nada
     * (spec 7.1). Es el que evita ponerse a trabajar desde el sobremesa
     * sobre algo que quedó a medias en el portátil.
     */
    public function variante(int $id)
    {
        $variante = $this->varianteModel->find($id);
        if (!$variante) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa variante no existe.');
        }

        $estado    = $this->sync->estadoDeSincronizacion($id);
        $versiones = $this->versionModel->where('variante_id', $id)->orderBy('numero', 'DESC')->findAll();

        // Sugerencia para "marcar impresa" (exposición, posición en la
        // placa...): la última vez que se imprimió ALGUNA versión de esta
        // variante es el mejor punto de partida para la próxima, en vez de
        // un textarea en blanco cada vez — sobre todo para la posición en
        // la placa, que rara vez cambia entre reimpresiones de la misma pieza.
        $sugerenciaImpresion = null;
        foreach ($versiones as $version) {
            if (!empty($version['params_impresion'])) {
                $sugerenciaImpresion = $version['params_impresion'];
                break;
            }
        }

        foreach ($versiones as &$version) {
            $version['pendiente_de_juicio'] = $this->llevaDemasiadoPendiente($version);
            $version['sesiones']            = $this->sesionesQueLlevaronA((int) $version['id']);
            // Por versión, no por familia (a diferencia de las referencias):
            // es el resultado visual de esa iteración concreta.
            $version['renders']             = $this->renderModel
                ->where('version_id', $version['id'])->orderBy('subida_en', 'DESC')->findAll();
            // Cuánto pesa esta versión en disco (spec: "qué ocupa cada
            // fichero"), junto a sus botones de descarga.
            $version['tamano_blend']        = $this->almacen->tamano($version['ruta_blend']);
            // Varios STL por versión (fase 21): la pieza entera, o cada
            // trozo si se imprime por partes y se monta.
            $version['stls'] = array_map(function (array $stl) {
                $stl['tamano'] = $this->almacen->tamano($stl['ruta_stl']);

                return $stl;
            }, $this->servicio->stlsDe((int) $version['id']));
        }
        unset($version);

        // Tarjeta de estadísticas de la ficha: tamaño en disco (desglosado,
        // no solo el total, para saber si el peso está en las versiones que
        // importan o en sesiones intermedias que ya podrían aligerarse) más
        // un par de datos que solo tienen sentido mirados por pieza — no
        // caben en el listado principal sin abarrotarlo.
        $sesionesTotales = 0;
        foreach ($this->ramaModel->where('variante_id', $id)->findAll() as $rama) {
            $sesionesTotales += $this->sesionModel->where('rama_id', $rama['id'])->countAllResults();
        }
        $estadisticasPieza = [
            'peso'      => $this->pesoDeVariante($id),
            'intentos'  => count(array_filter($versiones, static fn($v) => $v['estado'] !== 'borrador')),
            'sesiones'  => $sesionesTotales,
            'dias_vida' => $this->diasDesde($variante['creado_en']),
        ];

        $historialPlacas = $this->placasDeLaVariante($id);

        // Misma fila que ya está en $versiones (con sus 'renders' ya cargados),
        // no una consulta aparte: así la cabecera puede mostrar la imagen de
        // la versión buena sin duplicar la carga de renders.
        $validada = null;
        foreach ($versiones as $v) {
            if ($v['estado'] === 'validada') {
                $validada = $v;
                break;
            }
        }

        return view('piezas/variante', [
            'variante'  => $variante,
            'familia'   => $this->familiaModel->find($variante['familia_id']),
            // Solo las de esta variante (más las de antes del cambio que
            // aún no distinguía variante, ver PiezaReferenciaModel).
            'referencias' => $this->referenciaModel->deVariante((int) $variante['familia_id'], $id),
            // Renders sueltos (fase 31): de esta variante pero sin versión
            // concreta todavía — antes de la primera promoción es la única
            // clase de render que puede existir. Los que sí tienen versión
            // ya se ven en su historial (arriba, dentro de $versiones).
            'rendersSueltos' => $this->renderModel
                ->where('variante_id', $id)->where('version_id', null)->orderBy('subida_en', 'DESC')->findAll(),
            'origen'    => $this->versionDeOrigen($variante),
            'versiones' => $versiones,
            'validada'  => $validada,
            'rama'      => $estado['rama'],
            'ramaNombre' => $estado['rama'] ? $this->ramaModel->nombre($estado['rama']) : null,
            'sesiones'  => $this->sesionesDeRama($estado['rama']),
            'estado'    => $estado,
            'bloqueo'   => $this->descripcionBloqueo($estado['sesion_abierta'], $estado['descargas_pendientes']),
            'pendientes' => $this->descripcionPendientes($estado['descargas_pendientes']),
            'acciones'  => $this->accionesDisponibles($estado, $versiones),
            // Checklist recordatorio antes de promocionar (spec: pautas de
            // promoción). Vacío cuando aún no se ha configurado ninguna.
            'pautas'    => (new PiezaConfigModel())->pautasPromocion(),
            'familias'  => $this->familiaModel->orderBy('nombre', 'ASC')->findAll(),
            'carrito'   => $this->carritoActual(),
            // "Compuesta de" (spec 11.1 ampliado): qué otras piezas estaban
            // en la escena de esta variante. Puramente informativo.
            'componentes'           => $this->componentesDe($id),
            'versionesParaComponer' => $this->versionesParaComponer($id),
            'sugerenciaImpresion'   => $sugerenciaImpresion,
            'estadisticasPieza'     => $estadisticasPieza,
            // En qué placas ha ido esta pieza y cómo salió cada una (fase
            // 39): cierra el círculo del cuaderno — lo que se aprendió
            // imprimiéndola se consulta desde la pieza, que es donde uno
            // está cuando decide volver a mandarla a la impresora. Trae
            // también las capturas de cada fila y el conjunto entero, para
            // comparar posiciones (fase 44).
            'placasDeLaPieza'    => $historialPlacas['placas'],
            'capturasDeLaPieza'  => $historialPlacas['capturas'],
        ]);
    }

    /**
     * Las placas en las que se imprimió alguna versión de esta variante, de
     * la más reciente a la más vieja, con el veredicto de cada una, qué
     * versión llevaba y las capturas de cada fila (fase 44).
     *
     * @return array{placas: list<array>, capturas: list<array>}
     */
    private function placasDeLaVariante(int $varianteId): array
    {
        $versionIds = array_column(
            $this->versionModel->select('id')->where('variante_id', $varianteId)->findAll(),
            'id'
        );
        if ($versionIds === []) {
            return ['placas' => [], 'capturas' => []];
        }

        $filas = $this->placaVersionModel->whereIn('version_id', $versionIds)->findAll();
        if ($filas === []) {
            return ['placas' => [], 'capturas' => []];
        }

        $placas = $this->placaModel
            ->whereIn('id', array_unique(array_column($filas, 'placa_id')))
            ->orderBy('creado_en', 'DESC')
            ->findAll();

        // Una placa puede llevar dos versiones distintas de la misma pieza
        // (una prueba con dos alturas de capa, por ejemplo): se indexa por
        // placa y se guardan todas las versiones que puso. `fila_id` viaja
        // con cada una para poder colgarle capturas (fase 44): la gestión
        // de esas fotos vive aquí, en la ficha de la pieza, no en la placa.
        $porPlaca = [];
        $todasLasImagenes = [];
        foreach ($filas as $fila) {
            $version = $this->versionModel->find($fila['version_id']);
            if ($version) {
                $imagenes = $this->placaVersionImagenModel->where('placa_version_id', $fila['id'])
                    ->orderBy('orden')->orderBy('id')->findAll();

                $porPlaca[(int) $fila['placa_id']][] = [
                    'fila_id'  => (int) $fila['id'],
                    'numero'   => (int) $version['numero'],
                    'cantidad' => (int) $fila['cantidad'],
                    'notas'    => $fila['notas'],
                    'imagenes' => $imagenes,
                ];

                foreach ($imagenes as $imagen) {
                    $todasLasImagenes[] = $imagen + ['placa_id' => (int) $fila['placa_id']];
                }
            }
        }

        return [
            'placas'   => array_map(static fn(array $placa) => [
                'placa'     => $placa,
                'versiones' => $porPlaca[(int) $placa['id']] ?? [],
            ], $placas),
            // Todas las capturas juntas, para poder comparar de un vistazo
            // qué posición salió bien y cuál no repetir, sin tener que abrir
            // placa por placa.
            'capturas' => $todasLasImagenes,
        ];
    }

    /**
     * Galería: solo las piezas con versión validada — es la vista de "qué
     * tengo listo para imprimir", no el catálogo de trabajo en curso (para
     * eso está el índice). Miniatura: el render más reciente de la versión
     * validada y, si no hay, la referencia más reciente de la familia.
     */
    public function galeria()
    {
        $piezas = [];
        $stockPorSku = (new \App\Services\SterclicksClient())->stockPorSku();

        // Las piezas en la papelera no cuentan como "listas para imprimir",
        // aunque conserven una versión validada: siguen existiendo hasta que
        // se purguen, pero ya no aparecen en ningún sitio de cara al usuario.
        $activas = $this->familiaModel->where('borrado_en', null)->findAll();

        $nombresFamilia    = array_column($activas, 'nombre', 'id');
        $categoriaDeFamilia = array_column($activas, 'categoria_id', 'id');
        $activas            = array_column($activas, 'id');

        // Cuántas variantes tiene cada pieza: el nombre de la variante solo
        // se muestra si hay más de una (spec fase 12 — con una sola, que
        // siempre se llama "base", el nombre no dice nada). La tarjeta debe
        // leerse como la pieza, no como una variante suelta sin dueño.
        $conteoVariantes = [];
        foreach ($this->varianteModel->whereIn('familia_id', $activas)->where('borrado_en', null)->select('familia_id')->findAll() as $v) {
            $fid = (int) $v['familia_id'];
            $conteoVariantes[$fid] = ($conteoVariantes[$fid] ?? 0) + 1;
        }

        foreach ($this->varianteModel->whereIn('familia_id', $activas)->where('borrado_en', null)->findAll() as $variante) {
            // Validada si la hay; si no, la más reciente que siga siendo
            // "para imprimir" (borrador) o "impresa, pendiente de juzgar" —
            // este apartado es para meter STL en placas, y esas dos ya
            // pueden tener uno adjunto aunque el resultado físico no esté
            // juzgado. Ni "descartada" ni "superada" cuentan: de esas ya se
            // sabe que no sirven.
            $version = $this->versionParaImprimir((int) $variante['id']);

            if (!$version) {
                continue;
            }

            $fotos = $this->fotosDe($version, $variante);

            $familiaId = (int) $variante['familia_id'];

            $piezas[] = [
                'variante'        => $variante,
                'familiaNombre'   => $nombresFamilia[$familiaId] ?? '?',
                'variosVariantes' => ($conteoVariantes[$familiaId] ?? 0) > 1,
                // La versión que se ofrece para la placa: validada si la
                // hay, si no la más reciente "para imprimir"/"sin validar".
                'version'         => $version,
                // Cuántos trozos hay que imprimir (fase 21): la galería solo
                // necesita saber si hay alguno y cuántos, no cuáles.
                'stls'            => count($this->servicio->stlsDe((int) $version['id'])),
                'miniatura'       => $fotos['miniatura'],
                // La misma foto en grande, para el ojo que la abre aparte.
                'foto'            => $fotos['vista'],
                // Mismo campo que espera agruparPorCategoria() para las
                // filas del índice: se reutiliza tal cual, sin duplicar la
                // lógica de reparto por categoría (spec 11.1).
                'categoria_id'    => $categoriaDeFamilia[$familiaId] ?? null,
                'stock'           => $variante['sku'] ? ($stockPorSku[$variante['sku']] ?? null) : null,
            ];
        }

        // Se ordena por pieza, no por variante: es lo que ahora encabeza la
        // tarjeta, y es lo que se busca de un vistazo en una parrilla.
        usort($piezas, fn($a, $b) => [$a['familiaNombre'], $a['variante']['nombre']] <=> [$b['familiaNombre'], $b['variante']['nombre']]);

        return view('piezas/galeria', [
            // Filas de una variante suelta (no piezas con `variantes` dentro)
            // y ya ordenadas alfabéticamente: sin el reordenado por madurez,
            // que es cosa del índice.
            'grupos'  => $this->agruparPorCategoria($piezas, false),
            'carrito' => $this->carritoActual(),
        ]);
    }

    /**
     * Foto representativa de una versión concreta: su propio render si lo
     * tiene, si no un render suelto de la variante (fase 31 — puede que se
     * subiera antes de la primera promoción, o sin ligar a ninguna versión
     * en concreto, y sigue siendo más fiel que la referencia), y si tampoco
     * hay eso, la referencia del original como último recurso. Compartido
     * por la galería y por el histórico de placas.
     *
     * Devuelve las dos medidas de la misma foto en una sola pasada: la
     * miniatura para la cuadrícula y la vista para abrirla en grande. Las
     * dos salen de la misma búsqueda porque encontrar cuál es la foto de
     * esta versión cuesta dos consultas, y hacerlas otra vez para lo mismo
     * sería tirar el doble de consultas por cada tarjeta de la galería.
     *
     * La versión puede no existir: en el índice salen también las piezas
     * que todavía no tienen ninguna promocionada, y esas se quedan con el
     * render suelto o con la referencia, que es exactamente lo que la
     * cascada de abajo hace ya cuando la versión no tiene render propio.
     *
     * @return array{miniatura: ?string, vista: ?string}
     */
    private function fotosDe(?array $version, array $variante): array
    {
        $render = $version === null ? null : $this->renderModel
            ->where('version_id', $version['id'])->orderBy('subida_en', 'DESC')->first();

        if (!$render) {
            $render = $this->renderModel
                ->where('variante_id', $variante['id'])->where('version_id', null)
                ->orderBy('subida_en', 'DESC')->first();
        }

        $registro = $render ?: ($this->referenciaModel->deVariante((int) $variante['familia_id'], (int) $variante['id'])[0] ?? null);

        if (!$registro) {
            return ['miniatura' => null, 'vista' => null];
        }

        $tipo = $render ? 'render' : 'referencia';

        return [
            'miniatura' => imagen_pieza($registro, $tipo, PiezaImagenesPublicas::MINIATURA),
            'vista'     => imagen_pieza($registro, $tipo, PiezaImagenesPublicas::VISTA),
        ];
    }

    /**
     * La galería llama a estos tres por fetch() para no perder el filtro
     * en el que está trabajando (estado/STL, fase 29) con una recarga
     * completa — de ahí la rama AJAX en los tres. `variante.php` sigue
     * usando el formulario normal de toda la vida, que no tiene ese problema.
     */
    public function carritoAgregar(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version || $this->servicio->stlsDe($versionId) === []) {
            $mensaje = 'Esa versión no tiene ningún STL adjunto: no se puede añadir a la placa.';
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje]);
            }

            return redirect()->back()->with('error', $mensaje);
        }

        $carrito = $this->carritoActual();
        if (!in_array($versionId, $carrito, true)) {
            $carrito[] = $versionId;
            $this->carritoGuardar($carrito);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'enCarrito' => true, 'total' => count($this->carritoActual())]);
        }

        return redirect()->back()->with('success', 'Añadida a la placa.');
    }

    public function carritoQuitar(int $versionId)
    {
        $this->carritoGuardar(array_values(array_diff($this->carritoActual(), [$versionId])));

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'enCarrito' => false, 'total' => count($this->carritoActual())]);
        }

        return redirect()->back()->with('success', 'Quitada de la placa.');
    }

    public function carritoVaciar()
    {
        $this->carritoGuardar([]);
        // Vaciar es empezar de cero: lo que se monte a partir de ahora ya no
        // repite ninguna placa vieja ni sigue enlazado a un pedido anterior.
        session()->remove(self::SESION_CARRITO_ORIGEN);
        session()->remove(self::SESION_CARRITO_PEDIDO_ORIGEN);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'total' => 0]);
        }

        return redirect()->to(site_url('piezas/galeria'))->with('success', 'Placa vaciada.');
    }

    /**
     * Empaqueta los STL de la placa en un .zip para importar de golpe en
     * el laminador. El carrito NO se vacía solo: si la descarga falla a
     * mitad (conexión, lo que sea) el usuario no quiere volver a marcar
     * todo desde cero — "Vaciar placa" es una acción aparte y explícita.
     */
    public function carritoDescargar()
    {
        $carrito = $this->carritoActual();
        if (empty($carrito)) {
            return redirect()->to(site_url('piezas/galeria'))->with('error', 'La placa está vacía.');
        }

        $rutaZip = $this->construirZipDePlaca($carrito);
        if (!$rutaZip) {
            return redirect()->to(site_url('piezas/galeria'))
                ->with('error', 'Ninguno de los STL de la placa está ya disponible en el almacén.');
        }

        // Guardar en el histórico dejó de ser automático (fase 38): la galería
        // también sirve para bajar STL sueltos de golpe, sin que eso sea una
        // placa que vaya a la impresora ni tenga bitácora que llevar. El modal
        // pregunta las dos cosas —nombre y si se guarda— y solo se anota si el
        // usuario dijo que sí. Por GET (el enlace de siempre, sin modal) se
        // guarda: es como se comportaba antes y nadie espera perder el registro.
        $esPost  = strtoupper($this->request->getMethod()) === 'POST';
        $guardar = !$esPost || $this->request->getPost('guardar') !== null;

        if ($guardar) {
            $this->registrarPlacaDesdeCarrito($carrito, $this->request->getPost('nombre'), true);
        }

        // El fichero tiene que seguir existiendo cuando DownloadResponse lo
        // lea durante send(), que ocurre después de que este método
        // retorne — por eso el borrado va en un shutdown function, no aquí.
        register_shutdown_function(static function () use ($rutaZip) {
            @unlink($rutaZip);
        });

        return $this->response->download($rutaZip, null, true);
    }

    /**
     * "Guardar para después", sin descargar nada — como una lista de la
     * compra: no siempre se elige para bajar el zip en el momento, a veces
     * es solo apuntar qué se quiere imprimir más adelante. Mismo registro
     * que deja `carritoDescargar()`, solo que sin generar el zip.
     */
    public function carritoGuardarPlaca()
    {
        $carrito = $this->carritoActual();
        if (empty($carrito)) {
            $mensaje = 'La placa está vacía.';
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje]);
            }

            return redirect()->to(site_url('piezas/galeria'))->with('error', $mensaje);
        }

        $placa = $this->registrarPlacaDesdeCarrito($carrito);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'nombre' => $placa['nombre']]);
        }

        return redirect()->to(site_url('piezas/galeria'))
            ->with('success', 'Guardada como "' . $placa['nombre'] . '". La puedes ver en Placas.');
    }

    /**
     * Queda registrada sola, con fecha y qué llevaba (fase 36): así hay
     * histórico sin depender de acordarse de guardar nada, y desde
     * /piezas/placas se puede volver a cargar la misma combinación, bajar el
     * zip más tarde, o borrar la entrada si solo era una prueba. El nombre
     * es un punto de partida editable, no definitivo: si no llega ninguno
     * (el usuario no lo escribió, o la acción no lo pregunta) se usa la
     * fecha, que al menos sitúa la tanda en el tiempo.
     *
     * `$descargada` distingue las dos acciones que llegan aquí: descargar el
     * zip de verdad (carritoDescargar) frente a "Guardar para después"
     * (carritoGuardarPlaca) — es lo que decide si la placa nace "lista para
     * imprimir" o solo "guardada". Se fija una vez, al crear la fila, y no
     * se vuelve a tocar: volver a descargar una placa ya guardada desde el
     * histórico (placaDescargar) no pasa por aquí, así que no la asciende.
     */
    private function registrarPlacaDesdeCarrito(array $carrito, ?string $nombre = null, bool $descargada = false): array
    {
        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            $nombre = 'Placa ' . date('d/m/Y H:i');
        }
        // Mismo tope que la columna y que el formulario de renombrar.
        $nombre = mb_substr($nombre, 0, 150);

        $datos = ['nombre' => $nombre, 'descargada_en' => $descargada ? date('Y-m-d H:i:s') : null]
            + $this->herenciaDeLaPlacaAnterior();

        $pedidoId = (int) session(self::SESION_CARRITO_PEDIDO_ORIGEN);
        if ($pedidoId) {
            $datos['pedido_id'] = $pedidoId;
            session()->remove(self::SESION_CARRITO_PEDIDO_ORIGEN);
        }

        $placaId = $this->placaModel->insert($datos, true);
        if ($placaId) {
            foreach ($carrito as $versionId) {
                $this->placaVersionModel->insert(['placa_id' => $placaId, 'version_id' => $versionId]);
            }
            $this->heredarPreguntasPendientes((int) $placaId);
        }

        return $this->placaModel->find($placaId) ?? ['id' => $placaId, 'nombre' => '?'];
    }

    /**
     * Lo que una placa nueva puede dar por sabido antes de que nadie escriba
     * nada (fase 39): la exposición, la resina y la temperatura salen de la
     * placa que se está repitiendo si la hay, y si no de la última — casi
     * nunca cambian de una tanda a la siguiente, y cuando cambian es justo
     * el dato que uno va a corregir a mano de todas formas.
     *
     * Son valores de partida, no un registro de lo que pasó: se sobreescriben
     * en cuanto se toca la bitácora.
     */
    private function herenciaDeLaPlacaAnterior(): array
    {
        $ultima = $this->placaModel->orderBy('creado_en', 'DESC')->orderBy('id', 'DESC')->first();
        if (!$ultima) {
            return [];
        }

        $origenId = (int) session(self::SESION_CARRITO_ORIGEN);
        $modelo = $origenId ? ($this->placaModel->find($origenId) ?? $ultima) : $ultima;

        return array_filter([
            'origen_placa_id' => $origenId ?: null,
            'exposicion'      => $modelo['exposicion'],
            'resina'          => $modelo['resina'],
            'temperatura'     => $modelo['temperatura'],
        ], static fn($v) => $v !== null);
    }

    /**
     * Las preguntas que quedaron sin responder en la placa que se repite se
     * copian a la nueva, en blanco. Si algo se preguntó y la impresión no
     * llegó a contestarlo, la razón para volver a imprimir suele ser
     * exactamente esa — y tenerla ya escrita evita que se pierda.
     */
    private function heredarPreguntasPendientes(int $placaId): void
    {
        $origenId = (int) session(self::SESION_CARRITO_ORIGEN);
        if (!$origenId) {
            return;
        }
        session()->remove(self::SESION_CARRITO_ORIGEN);

        $orden = 0;
        foreach ($this->placaPruebaModel->where('placa_id', $origenId)->orderBy('orden')->orderBy('id')->findAll() as $prueba) {
            if (trim((string) $prueba['respuesta']) !== '') {
                continue;
            }

            $this->placaPruebaModel->insert([
                'placa_id'  => $placaId,
                'pregunta'  => $prueba['pregunta'],
                'respuesta' => null,
                'orden'     => $orden++,
            ]);
        }
    }

    /**
     * Junta en un zip los STL de una lista de versiones (una versión puede
     * aportar varios si la pieza se imprime en trozos — fase 21). Null si
     * ninguno sigue disponible en el almacén: compartido por la descarga de
     * la placa actual y por "descargar de nuevo" una placa guardada, cuyos
     * ficheros pueden llevar meses purgados si la versión cambió de estado.
     */
    private function construirZipDePlaca(array $versionIds): ?string
    {
        $versiones = $this->versionModel->whereIn('id', $versionIds)->findAll();
        $porVersion = $this->servicio->stlsDeVersiones(array_map(static fn($v) => (int) $v['id'], $versiones));

        $aEmpaquetar = [];
        foreach ($versiones as $version) {
            foreach ($porVersion[(int) $version['id']] ?? [] as $stl) {
                if ($this->almacen->existe($stl['ruta_stl'])) {
                    $aEmpaquetar[] = [$version, $stl];
                }
            }
        }

        if ($aEmpaquetar === []) {
            return null;
        }

        $carpetaTmp = WRITEPATH . 'piezas/tmp';
        if (!is_dir($carpetaTmp) && !mkdir($carpetaTmp, 0775, true) && !is_dir($carpetaTmp)) {
            throw new RuntimeException('No se pudo crear la carpeta temporal para la placa.');
        }
        $rutaZip = $carpetaTmp . '/placa-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($rutaZip, \ZipArchive::CREATE) !== true) {
            throw new RuntimeException('No se pudo crear el zip de la placa.');
        }
        foreach ($aEmpaquetar as [$version, $stl]) {
            $variante = $this->varianteModel->find($version['variante_id']);
            $zip->addFile(
                $this->almacen->absoluta($stl['ruta_stl']),
                $this->nombreArchivo($variante, $version, 'stl', $stl['nombre'])
            );
        }
        $zip->close();

        return $rutaZip;
    }

    // ---- Historial de placas ---------------------------------------------

    /**
     * Cada fila con sus piezas resueltas (nombre, estado, si el STL sigue
     * disponible) para poder decidir de un vistazo si merece la pena
     * "descargar de nuevo" o "cargar en la placa actual".
     */
    public function placas()
    {
        $placas = $this->placaModel->orderBy('creado_en', 'DESC')->findAll();

        // Pruebas y enlaces de todas las placas de una vez: son dos consultas
        // para el histórico entero en vez de dos por tarjeta.
        $pruebasPorPlaca = [];
        foreach ($this->placaPruebaModel->findAll() as $prueba) {
            $pruebasPorPlaca[(int) $prueba['placa_id']][] = $prueba;
        }
        $enlacesPorPlaca = [];
        foreach ($this->placaEnlaceModel->findAll() as $enlace) {
            $enlacesPorPlaca[(int) $enlace['placa_id']] = ($enlacesPorPlaca[(int) $enlace['placa_id']] ?? 0) + 1;
        }

        // Nombre de la placa de origen, para las que vienen de "Repartir en
        // otra placa" o de "Cargar en la placa actual" — una consulta para
        // el histórico entero, no una por tarjeta.
        $origenIds = array_values(array_unique(array_filter(array_column($placas, 'origen_placa_id'))));
        $origenNombres = [];
        if ($origenIds !== []) {
            foreach ($this->placaModel->select('id, nombre')->whereIn('id', $origenIds)->findAll() as $o) {
                $origenNombres[(int) $o['id']] = $o['nombre'];
            }
        }

        $piezas = [];
        $resumenes = [];
        // Tres cajones, en el mismo orden en que avanza una placa por la
        // vida real: guardada (idea suelta) -> lista (ya tienes el zip) ->
        // impresa (ya se montó). Que veredicto/impresa_en se vean fuera
        // significa que ya pasó por la bitácora; que descargada_en esté
        // puesto significa que el zip salió de verdad (spec: "volver a
        // descargar" desde el histórico no cuenta, solo la descarga que
        // registra la placa).
        $guardadas = [];
        $listas    = [];
        $impresas  = [];
        $sugerenciasReparto = [];
        $cuadrosPorPlaca    = [];
        foreach ($placas as $placa) {
            $idPlaca = (int) $placa['id'];
            // Una versión purgada (variante borrada hace 30+ días, invariante
            // 6) se cae sola de la lista: el FK en cascada ya se habrá llevado
            // su fila, pero piezasDeLaPlaca() lo comprueba igual.
            $piezas[$idPlaca] = $this->piezasDeLaPlaca($idPlaca);
            $resumenes[$idPlaca] = $this->resumenConDatos(
                $placa,
                $pruebasPorPlaca[$idPlaca] ?? [],
                $enlacesPorPlaca[$idPlaca] ?? 0
            );
            // Qué piezas sobran de la primera placa según el reparto
            // calculado (spec: empaquetado) — para preseleccionarlas en el
            // desplegable "Repartir en otra placa" y ahorrar el marcado a
            // mano. Sigue siendo editable: es una sugerencia, no una orden.
            $sugerenciasReparto[$idPlaca] = $this->filasFueraDeLaPrimeraPlaca($piezas[$idPlaca]);

            // Cuánto ocupa lo que YA lleva esta placa concreta (no un
            // reparto hipotético): para no perder de vista, sobre todo en
            // una placa nacida de un reparto, cuánto le queda por llenar.
            $itemsInfo = $this->itemsParaEmpaquetar($piezas[$idPlaca]);
            $areaUsada = 0.0;
            foreach ($itemsInfo['items'] as $item) {
                $areaUsada += $item['ancho'] * $item['fondo'];
            }
            $areaPlaca = PiezaEmpaquetadoService::PLACA_ANCHO_MM * PiezaEmpaquetadoService::PLACA_FONDO_MM;
            $cuadrosPorPlaca[$idPlaca] = [
                'porcentajeUsado' => $areaPlaca > 0 ? min(100, $areaUsada / $areaPlaca * 100) : 0,
                'sinMedir'        => $itemsInfo['sinMedir'],
            ];

            if ($placa['impresa_en']) {
                $impresas[] = $placa;
            } elseif ($placa['descargada_en']) {
                $listas[] = $placa;
            } else {
                $guardadas[] = $placa;
            }
        }

        // Familias de reparto: qué placas nacieron de dividir cuál, para
        // poder mostrar en cada una un vínculo con sus hermanas ("de la
        // misma placa dividida") en vez de solo la flecha unidireccional
        // hacia el origen que ya se veía antes. La raíz de una placa es ella
        // misma si no viene de un reparto, o si no, la raíz de su origen.
        $porId = [];
        foreach ($placas as $p) {
            $porId[(int) $p['id']] = $p;
        }
        $raizCache = [];
        $raizDe = function (int $id) use (&$raizDe, &$raizCache, $porId): int {
            if (isset($raizCache[$id])) {
                return $raizCache[$id];
            }
            $p = $porId[$id] ?? null;
            if (!$p || !$p['es_reparto'] || !$p['origen_placa_id'] || !isset($porId[(int) $p['origen_placa_id']])) {
                return $raizCache[$id] = $id;
            }

            return $raizCache[$id] = $raizDe((int) $p['origen_placa_id']);
        };

        $miembrosPorRaiz = [];
        foreach ($placas as $p) {
            $idPlaca = (int) $p['id'];
            $miembrosPorRaiz[$raizDe($idPlaca)][] = $idPlaca;
        }

        $gruposReparto = [];
        $nombresPlacas = [];
        foreach ($placas as $p) {
            $idPlaca = (int) $p['id'];
            $nombresPlacas[$idPlaca] = $p['nombre'];
            $hermanas = array_values(array_diff($miembrosPorRaiz[$raizDe($idPlaca)] ?? [], [$idPlaca]));
            if ($hermanas !== []) {
                $gruposReparto[$idPlaca] = ['raiz' => $raizDe($idPlaca), 'hermanas' => $hermanas];
            }
        }

        // Las impresas se ordenan y agrupan por cuándo se montaron de
        // verdad, no por cuándo se bajó el zip: es la fecha que responde
        // "qué hice y cuándo", el resto de la fila ya viene en orden de
        // creado_en por el orderBy de arriba.
        usort($impresas, static fn($a, $b) => strcmp((string) $b['impresa_en'], (string) $a['impresa_en']));

        return view('piezas/placas', [
            'piezas'             => $piezas,
            'resumenes'          => $resumenes,
            'origenNombres'      => $origenNombres,
            'sugerenciasReparto' => $sugerenciasReparto,
            'cuadrosPorPlaca'    => $cuadrosPorPlaca,
            'gruposReparto'      => $gruposReparto,
            'nombresPlacas'      => $nombresPlacas,
            'bloques'       => [
                'guardada' => ['titulo' => 'Guardadas para después', 'grupos' => $this->agruparPorPeriodo($guardadas, 'creado_en')],
                'lista'    => ['titulo' => 'Listas para imprimir', 'grupos' => $this->agruparPorPeriodo($listas, 'creado_en')],
                'impresa'  => ['titulo' => 'Impresas', 'grupos' => $this->agruparPorPeriodo($impresas, 'impresa_en')],
            ],
            'hayPlacas' => $placas !== [],
        ]);
    }

    /**
     * Reparte una lista de placas (ya ordenadas por la fecha que toque) en
     * grupos "Hoy / Ayer / Esta semana / Semana pasada / Este mes / <Mes
     * Año>" — el vistazo de actividad que se ve en cualquier app de tareas.
     * No hace falta ordenar los grupos aparte: como la lista de entrada ya
     * viene de más reciente a más antigua, cada etiqueta aparece por primera
     * vez en el orden correcto y los arrays asociativos de PHP conservan el
     * orden de inserción.
     *
     * @return array<string, list<array>> etiqueta => placas de ese grupo
     */
    private function agruparPorPeriodo(array $placas, string $campoFecha): array
    {
        $hoy = new \DateTimeImmutable('today');

        $grupos = [];
        foreach ($placas as $placa) {
            $fechaTexto = $placa[$campoFecha] ?? $placa['creado_en'];
            if (!$fechaTexto) {
                continue;
            }

            $grupos[$this->etiquetaPeriodo(new \DateTimeImmutable($fechaTexto), $hoy)][] = $placa;
        }

        return $grupos;
    }

    /** A qué "cajón" temporal pertenece una fecha, visto desde hoy. */
    private function etiquetaPeriodo(\DateTimeImmutable $fecha, \DateTimeImmutable $hoy): string
    {
        $fecha = $fecha->setTime(0, 0);

        if ($fecha == $hoy) {
            return 'Hoy';
        }
        if ($fecha == $hoy->modify('-1 day')) {
            return 'Ayer';
        }

        $lunesEstaSemana = $hoy->modify('monday this week');
        if ($fecha >= $lunesEstaSemana) {
            return 'Esta semana';
        }

        $lunesSemanaPasada = $lunesEstaSemana->modify('-7 days');
        if ($fecha >= $lunesSemanaPasada) {
            return 'Semana pasada';
        }

        $inicioMes = $hoy->modify('first day of this month');
        if ($fecha >= $inicioMes) {
            return 'Este mes';
        }

        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
            'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $etiqueta = ucfirst($meses[(int) $fecha->format('n') - 1]);
        if ($fecha->format('Y') !== $hoy->format('Y')) {
            $etiqueta .= ' ' . $fecha->format('Y');
        }

        return $etiqueta;
    }

    public function placaDescargar(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        $versionIds = array_column($this->placaVersionModel->where('placa_id', $id)->findAll(), 'version_id');
        $rutaZip = $versionIds ? $this->construirZipDePlaca($versionIds) : null;
        if (!$rutaZip) {
            return redirect()->to(site_url('piezas/placas'))
                ->with('error', 'Ninguno de los STL de esta placa está ya disponible en el almacén.');
        }

        register_shutdown_function(static function () use ($rutaZip) {
            @unlink($rutaZip);
        });

        return $this->response->download($rutaZip, null, true);
    }

    /**
     * Sustituye la placa actual por esta — no la suma —, para no acabar con
     * piezas mezcladas de dos combinaciones distintas sin darse cuenta.
     */
    public function placaCargar(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        $versionIds = array_column($this->placaVersionModel->where('placa_id', $id)->findAll(), 'version_id');
        $this->carritoGuardar(array_map('intval', $versionIds));
        // Se recuerda de dónde viene: cuando esta tanda se registre como
        // placa nueva heredará de aquí los ajustes y las preguntas que se
        // quedaron sin responder, que es la razón habitual de repetirla.
        session()->set(self::SESION_CARRITO_ORIGEN, $id);
        // Repetir una placa entera es un ciclo aparte del de un pedido: si
        // había uno pendiente de antes, ya no aplica a esta tanda.
        session()->remove(self::SESION_CARRITO_PEDIDO_ORIGEN);

        $aviso = 'Cargada en la placa actual: "' . $placa['nombre'] . '".';
        if (trim((string) $placa['conclusiones']) !== '') {
            $aviso .= ' Sus conclusiones: ' . mb_substr(trim($placa['conclusiones']), 0, 300);
        }

        return redirect()->to(site_url('piezas/galeria'))->with('success', $aviso);
    }

    /**
     * Vuelca en la placa actual las piezas de un pedido de sterclicks: por
     * cada línea con variante viva busca la versión que se ofrece para
     * imprimir (validada; si no, la más reciente en borrador/impresa —
     * misma cascada que galeria()) y, si tiene STL adjunto, la añade.
     * Sustituye la placa actual en vez de sumarse a ella, igual que
     * placaCargar(), para no mezclar sin darse cuenta con lo que hubiera
     * antes.
     */
    public function pedidoCargarACarrito(int $pedidoId)
    {
        $pedido = (new \App\Models\PiezaPedidoModel())->find($pedidoId);
        if (!$pedido) {
            return redirect()->to(site_url('piezas/pedidos'))->with('error', 'Ese pedido ya no existe.');
        }

        $lineas = (new \App\Models\PiezaPedidoLineaModel())->where('pedido_id', $pedidoId)->findAll();

        $versionIds = [];
        $sinVersion = 0;
        $sinStl     = 0;
        foreach ($lineas as $linea) {
            $version = $linea['variante_id'] ? $this->versionParaImprimir((int) $linea['variante_id']) : null;
            if (!$version) {
                $sinVersion++;
                continue;
            }
            if ($this->servicio->stlsDe((int) $version['id']) === []) {
                $sinStl++;
                continue;
            }
            $versionIds[] = (int) $version['id'];
        }

        $this->carritoGuardar(array_values(array_unique($versionIds)));
        session()->remove(self::SESION_CARRITO_ORIGEN);
        // Igual que placaCargar() con SESION_CARRITO_ORIGEN: se recuerda de
        // qué pedido sale esto para que la placa que se registre a
        // continuación quede enlazada, sin ningún cálculo de cobertura.
        session()->set(self::SESION_CARRITO_PEDIDO_ORIGEN, $pedidoId);

        if ($versionIds === []) {
            return redirect()->to(site_url('piezas/pedido/' . $pedidoId))
                ->with('error', 'Ninguna línea de este pedido tiene una versión con STL para cargar.');
        }

        $aviso = count($versionIds) . ' pieza(s) cargada(s) en la placa actual.';
        if ($sinStl > 0) {
            $aviso .= ' ' . $sinStl . ' sin STL todavía.';
        }
        if ($sinVersion > 0) {
            $aviso .= ' ' . $sinVersion . ' sin versión para imprimir.';
        }

        return redirect()->to(site_url('piezas/galeria'))->with('success', $aviso);
    }

    /**
     * La versión que se ofrece para imprimir de una variante: la más
     * reciente que siga siendo imprimible. 'validada' es la buena
     * confirmada, pero si DESPUÉS se ha promocionado otra ('borrador' o
     * 'impresa'), esa es el modelo actual y es lo que se quiere imprimir —
     * antes se devolvía siempre la validada aunque hubiera una posterior.
     * 'descartada'/'superada' no cuentan: de esas ya se sabe que no sirven.
     * Misma cascada que usa galeria() para elegir la miniatura/STL de cada
     * tarjeta — aquí se reutiliza para que "cargar pedido en la placa" saque
     * exactamente la misma versión que el usuario ya ve ofrecida allí.
     */
    private function versionParaImprimir(int $varianteId): ?array
    {
        return $this->versionModel->where('variante_id', $varianteId)
            ->whereIn('estado', ['validada', 'borrador', 'impresa'])
            ->orderBy('numero', 'DESC')->first();
    }

    /**
     * La versión VIGENTE de una variante: aquella cuyos ficheros (.blend y
     * STL) son los que valen ahora mismo. Es la última promocionada que
     * sigue en juego — la de mayor `numero` que NO sea `superada`.
     *
     * Se diferencia de `versionParaImprimir` en que aquí SÍ cuenta una
     * `descartada` si es lo último que hay: mientras nadie promocione algo
     * por encima, sus ficheros siguen siendo el último estado consolidado
     * del modelo, y el índice debe reflejar en qué punto está la pieza, no
     * solo lo que está listo para mandar a la impresora. `superada` se
     * salta porque, por definición, ya tiene una `validada` más nueva
     * encima. Y a diferencia de "la validada si la hay", un `borrador`
     * posterior a la validada gana: es la iteración vigente.
     */
    private function versionVigente(int $varianteId): ?array
    {
        return $this->versionModel->where('variante_id', $varianteId)
            ->where('estado !=', 'superada')
            ->orderBy('numero', 'DESC')->first();
    }

    public function placaRenombrar(int $id)
    {
        return $this->ejecutar(
            fn() => $this->placaModel->update($id, ['nombre' => (string) $this->request->getPost('nombre')])
                ? $this->placaModel->find($id)
                : throw new RuntimeException('No se pudo renombrar la placa: ' . implode(' ', $this->placaModel->errors())),
            fn() => site_url('piezas/placas'),
            fn($placa) => 'Renombrada a "' . $placa['nombre'] . '".'
        );
    }

    public function placaBorrar(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        // Solo borra el registro del histórico — invariante 6 es para
        // ficheros; esto no destruye ningún STL ni versión, solo la
        // anotación de que un día se descargaron juntos.
        //
        // Las placas que decían repetir a esta se quedan sin antecedente en
        // vez de irse con ella: `origen_placa_id` no tiene clave ajena (CI4
        // no sabe añadirlas a una tabla ya creada), así que el SET NULL lo
        // hacemos aquí a mano.
        $this->placaModel->where('origen_placa_id', $id)->set(['origen_placa_id' => null])->update();

        // Las fotos de la plataforma sí son ficheros propios (a diferencia
        // del STL, que es de la versión): la fila se va con la placa por
        // cascada de FK, pero el fichero no se aparta solo.
        foreach ($this->placaImagenModel->where('placa_id', $id)->findAll() as $imagen) {
            if (!empty($imagen['ruta_imagen'])) {
                $this->almacen->aPapelera($imagen['ruta_imagen']);
            }
            $this->publicas->retirar($imagen['hash_imagen'] ?? null);
        }

        // Igual, pero con las capturas de cada pieza (piezas_placas_versiones
        // se va sola por cascada de FK; el fichero, no).
        foreach ($this->placaVersionModel->where('placa_id', $id)->findAll() as $filaVersion) {
            foreach ($this->placaVersionImagenModel->where('placa_version_id', $filaVersion['id'])->findAll() as $imagen) {
                if (!empty($imagen['ruta_imagen'])) {
                    $this->almacen->aPapelera($imagen['ruta_imagen']);
                }
                $this->publicas->retirar($imagen['hash_imagen'] ?? null);
            }
        }

        $this->placaModel->delete($id);

        return redirect()->to(site_url('piezas/placas'))->with('success', 'Placa "' . $placa['nombre'] . '" borrada del histórico.');
    }

    /**
     * No cupo entera en la plataforma: mueve un subconjunto de sus piezas a
     * una placa nueva, enlazada a esta como origen (mismo `origen_placa_id`
     * que ya usa "Cargar en la placa actual", generalizado a un trozo en vez
     * de al total). Se mueven, no se copian, así entre las dos placas siguen
     * sumando exactamente lo que había antes. La nueva hereda el estado de
     * la original (descargada/guardada, pedido) y los ajustes de impresión:
     * es la misma tanda física partida en dos, no una tanda distinta.
     */
    /**
     * Reparte por CANTIDAD, no por fila entera: si de las 3 copias de una
     * pieza solo sobraban 2 según el cálculo, mover las 3 dejaría la placa
     * nueva con más cuadrículas de las que el reparto decía — justo el
     * error de cálculo que se cuela si "repartir" es un todo-o-nada. Cuando
     * se pide menos que el total de la fila, esta se parte en dos: la
     * original se queda con el resto y una fila nueva (mismo version_id,
     * mismas notas) nace ya en la placa nueva con la cantidad movida.
     */
    public function placaRepartir(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        $cantidades = (array) $this->request->getPost('cantidades');
        $movidas = 0;
        $piezasMovidas = 0;
        $nuevaId = null;

        foreach ($cantidades as $filaId => $cantidad) {
            $cantidad = (int) $cantidad;
            if ($cantidad <= 0) {
                continue;
            }

            $fila = $this->placaVersionModel->where('placa_id', $id)->find((int) $filaId);
            if (!$fila) {
                continue; // ya no está en esta placa: no bloquea el resto del reparto
            }
            $cantidad = min($cantidad, (int) $fila['cantidad']);

            if ($nuevaId === null) {
                $nuevaId = $this->placaModel->insert([
                    'nombre'          => mb_substr($placa['nombre'] . ' (parte)', 0, 150),
                    'origen_placa_id' => $id,
                    'es_reparto'      => 1,
                    'descargada_en'   => $placa['descargada_en'],
                    'pedido_id'       => $placa['pedido_id'],
                    'exposicion'      => $placa['exposicion'],
                    'resina'          => $placa['resina'],
                    'temperatura'     => $placa['temperatura'],
                ], true);
            }

            if ($cantidad >= (int) $fila['cantidad']) {
                $this->placaVersionModel->update((int) $fila['id'], ['placa_id' => $nuevaId]);
            } else {
                $this->placaVersionModel->update((int) $fila['id'], ['cantidad' => (int) $fila['cantidad'] - $cantidad]);
                $this->placaVersionModel->insert([
                    'placa_id'   => $nuevaId,
                    'version_id' => $fila['version_id'],
                    'cantidad'   => $cantidad,
                    'notas'      => $fila['notas'],
                ]);
            }

            $movidas++;
            $piezasMovidas += $cantidad;
        }

        if ($nuevaId === null) {
            return redirect()->back()->with('error', 'Elige al menos una pieza para repartir.');
        }

        $nueva = $this->placaModel->find($nuevaId);

        return redirect()->to(site_url('piezas/placas'))
            ->with('success', $piezasMovidas . ' pieza(s) (' . $movidas . ' referencia(s)) repartidas en "' . $nueva['nombre'] . '".');
    }

    /**
     * Deshace un "Repartir en otra placa": junta de vuelta las filas de esta
     * placa con las de la placa origen (sumando cantidad si ya había una
     * fila igual, o si no volviendo a colgar la fila de la origen tal cual)
     * y borra esta. Hace falta porque un reparto calculado a ojo puede
     * resultar mal — a veces no se nota hasta montar la placa de verdad — y
     * entonces hay que poder deshacerlo, no solo repartir hacia delante.
     */
    public function placaDeshacerReparto(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }
        if (!$placa['es_reparto'] || !$placa['origen_placa_id']) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esta placa no viene de un reparto.');
        }

        $origen = $this->placaModel->find((int) $placa['origen_placa_id']);
        if (!$origen) {
            return redirect()->to(site_url('piezas/placas'))
                ->with('error', 'La placa de origen ya no existe: no se puede deshacer el reparto.');
        }

        foreach ($this->placaVersionModel->where('placa_id', $id)->findAll() as $fila) {
            $gemela = $this->placaVersionModel->where('placa_id', $origen['id'])
                ->where('version_id', $fila['version_id'])
                ->where('notas', $fila['notas'])
                ->first();

            if ($gemela) {
                $this->placaVersionModel->update((int) $gemela['id'], ['cantidad' => (int) $gemela['cantidad'] + (int) $fila['cantidad']]);
                // Las capturas de la fila que desaparece se quedan con la
                // gemela en la que se fusiona, igual que las fotos/preguntas
                // de la placa entera unas líneas más abajo.
                $this->placaVersionImagenModel->where('placa_version_id', $fila['id'])
                    ->set(['placa_version_id' => $gemela['id']])->update();
                $this->placaVersionModel->delete((int) $fila['id']);
            } else {
                $this->placaVersionModel->update((int) $fila['id'], ['placa_id' => $origen['id']]);
            }
        }

        // Fotos, preguntas y enlaces propios de esta placa vuelven con ella:
        // deshacer el reparto es volver a como estaba, no perder lo anotado.
        $this->placaImagenModel->where('placa_id', $id)->set(['placa_id' => $origen['id']])->update();
        $this->placaPruebaModel->where('placa_id', $id)->set(['placa_id' => $origen['id']])->update();
        $this->placaEnlaceModel->where('placa_id', $id)->set(['placa_id' => $origen['id']])->update();

        // Si de esta placa había salido a su vez otro reparto, ese trozo pasa
        // a colgar directamente de la origen en vez de quedarse huérfano.
        $this->placaModel->where('origen_placa_id', $id)->set(['origen_placa_id' => $origen['id']])->update();

        $this->placaModel->delete($id);

        return redirect()->to(site_url('piezas/placas'))
            ->with('success', 'Reparto deshecho: vuelto a juntar con "' . $origen['nombre'] . '".');
    }

    // ---- Bitácora de una placa (fase 38) ---------------------------------

    public function bitacoraEditar(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        return view('piezas/bitacora_editar', $this->datosDeLaBitacora($placa));
    }

    /**
     * El vistazo rápido para el modal de Placas (fase 48): piezas impresas,
     * foto, fecha, tiempo, estado, notas y conclusiones — solo lectura, para
     * enterarse sin salir del histórico. Editar de verdad es "Ver completa",
     * a pantalla completa (antes esto cargaba el formulario editable entero;
     * con piezas/pruebas/fotos/ajustes ya no cabía en un vistazo rápido).
     */
    public function bitacoraResumen(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return $this->response->setStatusCode(404)->setBody('Esa placa ya no existe.');
        }

        return view('piezas/_bitacora_resumen', $this->datosDeLaBitacora($placa));
    }

    /**
     * En qué punto está el cuaderno de una placa, para poder verlo desde
     * fuera sin abrirla: si tiene algo apuntado, cuántas preguntas siguen sin
     * respuesta y cuántos enlaces cuelgan de ella. Las preguntas sin
     * responder son la lista de lo que queda por cerrar — se escriben antes
     * de imprimir y es fácil que la pieza acabe curada y nadie vuelva.
     */
    private function resumenConDatos(array $placa, array $pruebas, int $enlaces): array
    {
        $sinResponder = 0;
        foreach ($pruebas as $prueba) {
            if (trim((string) $prueba['respuesta']) === '') {
                $sinResponder++;
            }
        }

        // "Anotada" mira solo lo que no se puede saber hasta después de
        // imprimir: el nombre y la fecha de montaje los pone la propia app al
        // registrar la placa, así que contarlos daría por escrita una
        // bitácora en blanco.
        $anotada = $placa['impresa_en'] !== null
            || $placa['numero_capas'] !== null
            || $placa['minutos_reales'] !== null
            || $placa['veredicto'] !== null
            || trim((string) $placa['notas']) !== ''
            || trim((string) $placa['conclusiones']) !== ''
            || $pruebas !== []
            || $enlaces > 0;

        return [
            'anotada'      => $anotada,
            'sinResponder' => $sinResponder,
            'enlaces'      => $enlaces,
            'veredicto'    => $placa['veredicto'],
        ];
    }

    private function resumenDeBitacora(int $id, array $placa): array
    {
        return $this->resumenConDatos(
            $placa,
            $this->placaPruebaModel->where('placa_id', $id)->findAll(),
            $this->placaEnlaceModel->where('placa_id', $id)->countAllResults()
        );
    }

    /** Lo que necesitan las tres pantallas de la bitácora, resuelto una vez. */
    private function datosDeLaBitacora(array $placa): array
    {
        $id = (int) $placa['id'];
        $piezas = $this->piezasDeLaPlaca($id);

        return [
            'placa'    => $placa,
            'piezas'   => $piezas,
            'pruebas'  => $this->placaPruebaModel->where('placa_id', $id)->orderBy('orden')->orderBy('id')->findAll(),
            'enlaces'  => $this->placaEnlaceModel->where('placa_id', $id)->orderBy('orden')->orderBy('id')->findAll(),
            'imagenes' => $this->placaImagenModel->where('placa_id', $id)->orderBy('orden')->orderBy('id')->findAll(),
            // Con las cantidades de AHORA MISMO (spec: en Galería aún no se
            // sabe cuántas copias de cada una entran, así que el cálculo
            // solo tiene sentido aquí, una vez la placa ya existe y tiene
            // "Copias" que editar).
            'reparto'  => $this->repartoLegible($piezas),
            'sinMedir' => $this->itemsParaEmpaquetar($piezas)['sinMedir'],
            // Cuántas copias de cada fila sobran de la primera placa, para
            // preseleccionarlas en el formulario de "Repartir" (fase 47:
            // ahora también en el sidebar de la bitácora, no solo en el
            // desplegable del histórico).
            'sugerenciaReparto' => $this->filasFueraDeLaPrimeraPlaca($piezas),
        ];
    }

    /**
     * Guarda la bitácora entera de una vez: los campos de la placa, las
     * cantidades y notas de cada pieza, y las pruebas. Las pruebas se
     * reescriben (borrar y volver a insertar) en vez de ir casando ids: la
     * lista es corta, se edita entera en un formulario, y así una fila
     * quitada en el navegador desaparece de verdad sin arrastrar el id.
     */
    public function bitacoraGuardar(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        $veredicto = (string) $this->request->getPost('veredicto');

        // Algunos campos venían pensados para poderse guardar sueltos desde
        // el modal (fase 44); ya no existe ese guardado parcial (fase 48: el
        // modal es de solo lectura), pero el guardián se queda porque sigue
        // siendo verdad que solo se toca lo que llega en el POST — así un
        // formulario a medio montar no borra ajustes, notas, etc.
        $tieneCampo = fn(string $clave) => $this->request->getPost($clave) !== null;

        // El peso antes/después se quitó de la bitácora (fase 51):
        // logísticamente casi nunca se llega a pesar el tanque, y cuando
        // hace falta contarlo ahora va como texto suelto en notas.
        $numeroCapas = trim((string) $this->request->getPost('numero_capas'));

        $datos = [
            'nombre'       => trim((string) $this->request->getPost('nombre')) ?: $placa['nombre'],
            'impresa_en'   => $this->request->getPost('impresa_en') ?: null,
            'minutos_reales'    => $this->aMinutos($this->request->getPost('minutos_reales')),
            'numero_capas' => $numeroCapas !== '' ? (int) $numeroCapas : null,
            'veredicto'   => isset(PiezaPlacaModel::VEREDICTOS[$veredicto]) ? $veredicto : null,

            'exposicion'   => $tieneCampo('exposicion') ? (trim((string) $this->request->getPost('exposicion')) ?: null) : $placa['exposicion'],
            'notas'        => $tieneCampo('notas') ? (trim((string) $this->request->getPost('notas')) ?: null) : $placa['notas'],
            'conclusiones' => $tieneCampo('conclusiones') ? (trim((string) $this->request->getPost('conclusiones')) ?: null) : $placa['conclusiones'],
            'resina'      => $tieneCampo('resina') ? (trim((string) $this->request->getPost('resina')) ?: null) : $placa['resina'],
            'temperatura' => $tieneCampo('temperatura') ? $this->aPeso($this->request->getPost('temperatura')) : $placa['temperatura'],

            'resina_estimada'   => $tieneCampo('resina_estimada') ? $this->aPeso($this->request->getPost('resina_estimada')) : $placa['resina_estimada'],
            'minutos_estimados' => $tieneCampo('minutos_estimados') ? $this->aMinutos($this->request->getPost('minutos_estimados')) : $placa['minutos_estimados'],
            'minutos_previstos' => $tieneCampo('minutos_previstos') ? $this->aMinutos($this->request->getPost('minutos_previstos')) : $placa['minutos_previstos'],
        ];

        if (!$this->placaModel->update($id, $datos)) {
            $mensaje = implode(' ', $this->placaModel->errors());
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $mensaje]);
            }

            return redirect()->back()->withInput()->with('error', $mensaje);
        }

        // Cantidades y notas por pieza. Se recorre lo que hay en la base, no lo
        // que llega del formulario: así un id inventado en el POST no puede
        // tocar la fila de otra placa.
        $cantidades = (array) $this->request->getPost('cantidad');
        $notasPieza = (array) $this->request->getPost('nota_pieza');
        foreach ($this->placaVersionModel->where('placa_id', $id)->findAll() as $fila) {
            $filaId = (int) $fila['id'];
            if (!array_key_exists($filaId, $cantidades) && !array_key_exists($filaId, $notasPieza)) {
                continue;
            }

            $this->placaVersionModel->update($filaId, [
                'cantidad' => max(1, (int) ($cantidades[$filaId] ?? 1)),
                'notas'    => trim((string) ($notasPieza[$filaId] ?? '')) ?: null,
            ]);
        }

        // Se reescriben enteros, pero solo si la sección venía en el POST:
        // el modal no la manda, y sin este guardián un guardado desde ahí
        // vaciaría las pruebas de la placa por no tener dónde volver a
        // escribirlas.
        if ($tieneCampo('pregunta') || $tieneCampo('respuesta')) {
            $this->placaPruebaModel->where('placa_id', $id)->delete();
            $preguntas  = (array) $this->request->getPost('pregunta');
            $respuestas = (array) $this->request->getPost('respuesta');
            $orden = 0;
            foreach ($preguntas as $i => $pregunta) {
                $pregunta = trim((string) $pregunta);
                if ($pregunta === '') {
                    continue;   // una fila en blanco es una fila que el usuario dejó sin usar
                }

                $this->placaPruebaModel->insert([
                    'placa_id'  => $id,
                    'pregunta'  => mb_substr($pregunta, 0, 255),
                    'respuesta' => trim((string) ($respuestas[$i] ?? '')) ?: null,
                    'orden'     => $orden++,
                ]);
            }
        }

        // Los enlaces, igual que las pruebas: se reescriben enteros, y solo
        // si venían en el POST. Una fila sin URL es una fila que el usuario
        // dejó a medias, no un enlace.
        if ($tieneCampo('enlace_url') || $tieneCampo('enlace_titulo')) {
            $this->placaEnlaceModel->where('placa_id', $id)->delete();
            $urls    = (array) $this->request->getPost('enlace_url');
            $titulos = (array) $this->request->getPost('enlace_titulo');
            $orden = 0;
            foreach ($urls as $i => $url) {
                $url = $this->aUrl($url);
                if ($url === null) {
                    continue;
                }

                $this->placaEnlaceModel->insert([
                    'placa_id' => $id,
                    'url'      => $url,
                    'titulo'   => mb_substr(trim((string) ($titulos[$i] ?? '')), 0, 150) ?: null,
                    'orden'    => $orden++,
                ]);
            }
        }

        if ($this->request->isAJAX()) {
            // El modal se queda donde está: lo que devuelve esto es lo justo
            // para repintar la tarjeta de detrás (nombre y veredicto) sin
            // recargar el histórico entero.
            $placa = $this->placaModel->find($id);
            $piezasActuales = $this->piezasDeLaPlaca($id);

            return $this->response->setJSON([
                'ok'        => true,
                'nombre'    => $placa['nombre'],
                'veredicto' => $placa['veredicto'],
                'resumen'   => $this->resumenDeBitacora($id, $placa),
                // Las cantidades acaban de cambiar (el "Copias" de cada
                // fila), así que el reparto de antes de guardar ya no vale.
                'reparto'   => $this->repartoLegible($piezasActuales),
                'sinMedir'  => $this->itemsParaEmpaquetar($piezasActuales)['sinMedir'],
            ]);
        }

        // Se queda en la misma pantalla (fase 49): guardar no es "terminar y
        // salir a ver el resultado", es una acción suelta más mientras se
        // sigue editando — igual que ya pasaba en el modal antes de que
        // pasara a ser de solo lectura.
        return redirect()->to(site_url('piezas/placa/' . $id . '/bitacora/editar'))
            ->with('success', 'Bitácora guardada.');
    }

    /**
     * Piezas candidatas a añadir a una bitácora ya guardada: busca por
     * nombre de familia, nombre de variante o SKU, y para cada variante que
     * encaje ofrece la misma versión que ofrecería la Galería (validada, o
     * si no la más reciente para imprimir) — así lo que se añade aquí es
     * justo lo que se habría podido llevar desde allí.
     */
    public function piezaBuscar()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['resultados' => []]);
        }

        $familiasQueEncajan = $this->familiaModel->where('borrado_en', null)->like('nombre', $q)->findAll();
        $idsFamilia = array_column($familiasQueEncajan, 'id') ?: [0];

        $variantes = $this->varianteModel->where('borrado_en', null)
            ->groupStart()
                ->like('nombre', $q)
                ->orLike('sku', $q)
                ->orWhereIn('familia_id', $idsFamilia)
            ->groupEnd()
            ->orderBy('nombre')
            ->findAll(30);

        $resultados = [];
        foreach ($variantes as $variante) {
            $version = $this->versionParaImprimir((int) $variante['id']);
            if (!$version) {
                continue;   // sin versión validada ni para imprimir, no hay STL que meter en la placa
            }

            $familia = $this->familiaModel->find($variante['familia_id']);
            $resultados[] = [
                'version_id' => (int) $version['id'],
                'texto'      => ($familia['nombre'] ?? '?') . ' - ' . $variante['nombre']
                    . ' · v' . sprintf('%03d', (int) $version['numero']),
                'miniatura'  => $this->fotosDe($version, $variante)['miniatura'],
            ];

            if (count($resultados) >= 15) {
                break;
            }
        }

        return $this->response->setJSON(['resultados' => $resultados]);
    }

    /**
     * Añade una pieza suelta a una placa ya guardada, sin pasar por el
     * carrito de la Galería: para corregir un olvido (spec: pedido del
     * usuario) sin tener que borrar la placa y volver a montarla entera. Si
     * esa versión ya estaba en la placa, suma cantidad en vez de duplicar la
     * fila — mismo criterio que ya usa deshacerReparto al juntar filas
     * gemelas.
     */
    public function bitacoraPiezaAgregar(int $id)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa placa ya no existe.']);
        }

        $versionId = (int) $this->request->getPost('version_id');
        $version = $versionId ? $this->versionModel->find($versionId) : null;
        if (!$version) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => 'Esa pieza ya no existe.']);
        }

        $cantidad = max(1, (int) $this->request->getPost('cantidad') ?: 1);

        $existente = $this->placaVersionModel->where('placa_id', $id)->where('version_id', $versionId)->first();
        if ($existente) {
            $this->placaVersionModel->update((int) $existente['id'], [
                'cantidad' => (int) $existente['cantidad'] + $cantidad,
            ]);
        } else {
            $this->placaVersionModel->insert([
                'placa_id'   => $id,
                'version_id' => $versionId,
                'cantidad'   => $cantidad,
            ]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Quita una pieza de la bitácora: para cuando se metió por error y no
     * hace falta rehacer la placa entera para sacarla. Se comprueba que la
     * fila sea de esta placa antes de tocarla —igual que en
     * bitacoraGuardar— para que un id ajeno en la URL no pueda borrar la
     * fila de otra placa.
     */
    public function bitacoraPiezaQuitar(int $id, int $filaId)
    {
        $placa = $this->placaModel->find($id);
        if (!$placa) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa placa ya no existe.']);
        }

        $fila = $this->placaVersionModel->where('placa_id', $id)->find($filaId);
        if (!$fila) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'mensaje' => 'Esa pieza ya no está en esta placa.']);
        }

        // La fila se va con sus capturas: cascada de FK para el registro,
        // pero el fichero de cada foto hay que apartarlo a mano.
        foreach ($this->placaVersionImagenModel->where('placa_version_id', $filaId)->findAll() as $imagen) {
            if (!empty($imagen['ruta_imagen'])) {
                $this->almacen->aPapelera($imagen['ruta_imagen']);
            }
            $this->publicas->retirar($imagen['hash_imagen'] ?? null);
        }

        $this->placaVersionModel->delete($filaId);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Una URL tal y como se pega desde el navegador: a veces con esquema y a
     * veces sin él ("drive.google.com/..."). Se le pone https:// delante en
     * vez de rechazarla — lo contrario sería perder el enlace por una
     * formalidad. Null si no hay nada que guardar.
     */
    private function aUrl($valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $valor)) {
            $valor = 'https://' . ltrim($valor, '/');
        }

        return mb_substr($valor, 0, 700);
    }

    /**
     * Un peso tal y como lo teclea una persona: vacío, "1234,56" o "1234.56".
     * La coma es lo que sale del teclado numérico en español y lo que muestra
     * la báscula, así que se acepta en vez de exigir punto.
     */
    private function aPeso($valor): ?string
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return null;
        }

        return str_replace(',', '.', $valor);
    }

    /**
     * Una duración tal y como se lee de la pantalla del laminador o de la
     * impresora, a minutos: "2h 35", "2:35", "2h", "155" o "155 min" valen
     * todos. Se guarda en minutos porque el interés de estos tres campos
     * (estimado, previsto, real) está en restarlos entre sí, y para eso hay
     * que poder hacer cuentas — no en volver a enseñar el texto tal cual.
     */
    private function aMinutos($valor): ?int
    {
        $valor = strtolower(trim((string) $valor));
        if ($valor === '') {
            return null;
        }

        // "2:35" y "2h35" son la misma cosa escrita de dos maneras.
        if (preg_match('/^(\d+)\s*(?::|h)\s*(\d*)/', $valor, $m)) {
            return (int) $m[1] * 60 + (int) ($m[2] === '' ? 0 : $m[2]);
        }

        if (preg_match('/(\d+)/', $valor, $m)) {
            return (int) $m[1];   // un número suelto son minutos
        }

        return null;
    }

    /**
     * Las piezas de una placa ya resueltas (familia, variante, versión, si el
     * STL sigue estando, miniatura), compartido por el histórico y por las dos
     * pantallas de la bitácora.
     */
    private function piezasDeLaPlaca(int $placaId): array
    {
        $lista = [];
        foreach ($this->placaVersionModel->where('placa_id', $placaId)->orderBy('id')->findAll() as $fila) {
            $version = $this->versionModel->find($fila['version_id']);
            if (!$version) {
                continue;
            }

            $variante = $this->varianteModel->find($version['variante_id']);
            $lista[] = [
                'fila'       => $fila,
                'version'    => $version,
                'variante'   => $variante,
                'familia'    => $variante ? $this->familiaModel->find($variante['familia_id']) : null,
                'disponible' => $this->servicio->stlsDe((int) $version['id']) !== [],
                'miniatura'  => $variante ? $this->fotosDe($version, $variante)['miniatura'] : null,
                'imagenes'   => $this->placaVersionImagenModel->where('placa_version_id', $fila['id'])
                    ->orderBy('orden')->orderBy('id')->findAll(),
            ];
        }

        return $lista;
    }

    /**
     * De la lista de piezasDeLaPlaca() a items sueltos para
     * PiezaEmpaquetadoService::repartir(): una unidad física por copia (la
     * "cantidad" de la fila multiplica) y por cada trozo de STL que tenga la
     * versión — un STL sin medir no entra, y se cuenta aparte para poder
     * avisar de que el cálculo no es completo.
     *
     * @return array{items: list<array>, sinMedir: int}
     */
    private function itemsParaEmpaquetar(array $piezas): array
    {
        $items = [];
        $sinMedir = 0;

        foreach ($piezas as $p) {
            if (!$p['version']) {
                continue;
            }
            $cantidad = max(1, (int) $p['fila']['cantidad']);
            $etiquetaBase = $p['familia'] && $p['variante']
                ? $p['familia']['nombre'] . ' - ' . $p['variante']['nombre']
                : '(pieza borrada)';

            foreach ($this->servicio->stlsDe((int) $p['version']['id']) as $stl) {
                if (!$stl['ancho_mm'] || !$stl['fondo_mm']) {
                    $sinMedir++;
                    continue;
                }

                $etiqueta = $etiquetaBase . (mb_strtolower($stl['nombre']) === 'completo' ? '' : ' · ' . $stl['nombre']);
                for ($i = 0; $i < $cantidad; $i++) {
                    $items[] = [
                        'etiqueta'     => $etiqueta,
                        // Sin el trozo: es como se agrupa el desglose por
                        // placa (una copia entera, no un trozo suelto).
                        'etiquetaBase' => $etiquetaBase,
                        'ancho'    => (float) $stl['ancho_mm'],
                        'fondo'    => (float) $stl['fondo_mm'],
                        'filaId'   => (int) $p['fila']['id'],
                        // Qué copia de esa fila es (0, 1, 2... hasta cantidad-1):
                        // los trozos de una misma copia comparten índice, así se
                        // puede saber luego cuántas copias COMPLETAS caen fuera
                        // de la primera placa, no solo cuántos trozos sueltos —
                        // spec: mover una fila entera cuando solo sobraba una
                        // copia de tres movía de más.
                        'copia'    => $i,
                    ];
                }
            }
        }

        return ['items' => $items, 'sinMedir' => $sinMedir];
    }

    /**
     * Cuántas COPIAS de cada fila sobrarían de la primera placa, según
     * PiezaEmpaquetadoService — para preseleccionar "Repartir en otra placa"
     * con la cantidad justa, no la fila entera. Si una fila tiene 3 copias y
     * solo 1 no cabe en la primera placa, aquí sale 1 — mover las 3 movería
     * cuadrículas de más y el reparto real ya no cuadraría con el calculado.
     *
     * Una copia cuenta como "fuera" si CUALQUIERA de sus trozos de STL cayó
     * en la segunda placa o posterior: los trozos de una misma copia se
     * mueven siempre juntos (son la misma pieza física, aunque se imprima
     * en partes), nunca repartidos entre dos placas.
     *
     * @return array<int, int> filaId => cantidad de copias fuera
     */
    private function filasFueraDeLaPrimeraPlaca(array $piezas): array
    {
        $bins = $this->empaquetado->repartir($this->itemsParaEmpaquetar($piezas)['items']);
        if (count($bins) <= 1) {
            return [];
        }

        $copiasFuera = []; // filaId => set de índices de copia
        foreach (array_slice($bins, 1) as $bin) {
            foreach ($bin['piezas'] as $item) {
                $copiasFuera[$item['filaId']][$item['copia']] = true;
            }
        }

        return array_map('count', $copiasFuera);
    }

    /**
     * El reparto, listo para enseñar: por cada placa que hace falta, cuántas
     * cuadrículas usa y qué piezas lleva (agrupadas por copia entera, no por
     * trozo de STL suelto — spec: "el reparto no dice cuántas piezas
     * sobran"). Aparte de PiezaEmpaquetadoService::repartir() porque ese
     * devuelve los items en bruto (uno por copia×trozo), que sirven para
     * calcular pero no para leer de un vistazo.
     *
     * @return array{
     *     bins: list<array{porcentajeUsado: float, piezas: list<array{etiqueta: string, cantidad: int}>}>,
     *     piezasPrimeraPlacaPorSuperficie: int,
     *     piezasPrimeraPlacaConMargen: int,
     *     placasPorSuperficie: int
     * }
     */
    private function repartoLegible(array $piezas): array
    {
        $items = $this->itemsParaEmpaquetar($piezas)['items'];
        $areaPlaca = PiezaEmpaquetadoService::PLACA_ANCHO_MM * PiezaEmpaquetadoService::PLACA_FONDO_MM;

        // Dos versiones del mismo cálculo por superficie (spec: enseñar las
        // dos, no solo la conservadora): sin ningún colchón (el máximo
        // teórico, "las piezas caben si se pudieran tocar entre sí") y con
        // el 10% de la placa reservado como margen de seguridad. 'bins' —
        // lo que de verdad se usa para sugerir qué mover a otra placa — sale
        // siempre de la versión con margen, la conservadora.
        $binsPorSuperficie = $this->empaquetado->repartir($items, 0.0);
        $bins = $this->empaquetado->repartir($items);

        return [
            'bins' => array_map(function (array $bin) use ($areaPlaca): array {
                $porFila = [];
                foreach ($bin['piezas'] as $item) {
                    $id = $item['filaId'];
                    $porFila[$id]['etiqueta'] = $item['etiquetaBase'];
                    $porFila[$id]['copias'][$item['copia']] = true;
                }

                return [
                    'porcentajeUsado' => $areaPlaca > 0 ? min(100, $bin['areaUsadaMm2'] / $areaPlaca * 100) : 0,
                    'piezas' => array_values(array_map(
                        static fn(array $f) => ['etiqueta' => $f['etiqueta'], 'cantidad' => count($f['copias'])],
                        $porFila
                    )),
                ];
            }, $bins),
            'piezasPrimeraPlacaPorSuperficie' => count($binsPorSuperficie[0]['piezas'] ?? []),
            'piezasPrimeraPlacaConMargen'     => count($bins[0]['piezas'] ?? []),
            'placasPorSuperficie'             => count($binsPorSuperficie),
        ];
    }

    /**
     * Nombre con el que se descarga un fichero de una versión. Lleva el SKU
     * delante cuando lo hay: es el código por el que se pide la pieza fuera
     * de Trackbitos, y es lo que la hace reconocible en la carpeta de
     * descargas o dentro del laminador, donde ya no está la ficha al lado
     * para mirarlo.
     */
    private function nombreArchivo(?array $variante, array $version, string $extension, ?string $sufijo = null): string
    {
        // La familia va incluida (igual que en las descargas del CLI, ver
        // PiezaSyncService::nombreFichero): "estandar-v001.stl" no dice de
        // qué pieza es en cuanto hay dos variantes llamadas igual, y estos
        // ficheros acaban sueltos en una carpeta de descargas o en el
        // laminador, lejos de la ficha que los explicaba.
        $familia = $variante ? $this->familiaModel->find($variante['familia_id']) : null;

        $partes = array_filter([
            $this->paraNombreDeArchivo($variante['sku'] ?? null),
            $this->paraNombreDeArchivo($familia['nombre'] ?? null),
            $this->paraNombreDeArchivo($variante['nombre'] ?? null) ?: 'variante-' . $version['variante_id'],
        ]);

        // El nombre del trozo va al final ("...-v002-brazo-izquierdo.stl"):
        // con varios STL por versión, sin él se descargarían tres ficheros
        // con el mismo nombre y el navegador los numeraría (1), (2)...
        $sufijo = $this->paraNombreDeArchivo($sufijo);

        return sprintf(
            '%s-v%03d%s.%s',
            implode('-', $partes),
            (int) $version['numero'],
            $sufijo !== '' ? '-' . $sufijo : '',
            $extension
        );
    }

    /** Deja solo lo que sobrevive intacto a cualquier sistema de ficheros. */
    private function paraNombreDeArchivo(?string $texto): string
    {
        return trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $texto), '-');
    }

    private function carritoActual(): array
    {
        return array_values(array_unique((array) session(self::SESION_CARRITO)));
    }

    private function carritoGuardar(array $ids): void
    {
        session()->set(self::SESION_CARRITO, array_values(array_unique($ids)));
    }

    // ---- Alta de familias y variantes ----------------------------------

    /**
     * Alta de una pieza. Se queda en el índice en vez de saltar a la ficha
     * de su variante: lo normal al empezar es dar de alta varias piezas
     * seguidas, y volver atrás cada vez sería peor que un clic de más.
     */
    public function crearFamilia()
    {
        return $this->ejecutar(
            fn() => $this->servicio->crearFamilia(
                trim((string) $this->request->getPost('nombre')),
                $this->request->getPost('notas') ?: null,
                $this->request->getPost('categoria_id') ? (int) $this->request->getPost('categoria_id') : null
            ),
            fn($creado) => site_url('piezas'),
            fn($creado) => 'Pieza "' . $creado['familia']['nombre'] . '" creada, ya lista para trabajar.'
        );
    }

    /**
     * El nombre de la pieza entera, a diferencia de `renombrarVariante`
     * (que solo toca la línea de diseño). Se edita desde la ficha, así que
     * vuelve a ella con la variante que se estaba mirando.
     */
    public function renombrarFamilia(int $familiaId)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        return $this->ejecutar(
            fn() => $this->servicio->renombrarFamilia($familiaId, (string) $this->request->getPost('nombre')),
            fn($familia) => site_url('piezas/variante/' . $varianteId),
            fn($familia) => 'Ahora se llama "' . $familia['nombre'] . '".'
        );
    }

    /**
     * "Borrar pieza" (invariante 6): no la destruye, la manda a la papelera
     * — desaparece del índice y de la galería, pero se puede restaurar
     * durante 30 días desde /piezas/papelera antes de que `piezas:purgar`
     * se la lleve de verdad.
     */
    public function borrarFamilia(int $familiaId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->borrarFamilia($familiaId),
            fn() => site_url('piezas'),
            fn($familia) => 'Pieza "' . $familia['nombre'] . '" movida a la papelera. Se puede restaurar en los próximos 30 días.'
        );
    }

    /**
     * "Borrar variante" (invariante 6, ahora también suelta): igual que
     * borrarFamilia, pero para una sola línea de diseño de la pieza — el
     * resto sigue intacto. Vuelve al índice, no a la ficha que se acaba de
     * borrar.
     */
    public function borrarVariante(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->borrarVariante($varianteId),
            fn() => site_url('piezas'),
            fn($variante) => 'Variante "' . $variante['nombre'] . '" movida a la papelera. Se puede restaurar en los próximos 30 días.'
        );
    }

    /**
     * La papelera de piezas: lo que se borró y todavía está a tiempo de
     * volver. Ordenada por fecha de borrado, la más reciente primero —
     * es la que con más probabilidad se quiere deshacer. Familias y
     * variantes sueltas se listan aparte: son dos verbos distintos
     * (`borrarFamilia`/`borrarVariante`), aunque compartan la misma idea.
     */
    public function papelera()
    {
        $variantes = $this->varianteModel
            ->where('borrado_en IS NOT NULL')
            ->orderBy('borrado_en', 'DESC')
            ->findAll();

        $nombresFamilia = [];
        if ($variantes !== []) {
            $nombresFamilia = array_column(
                $this->familiaModel->whereIn('id', array_column($variantes, 'familia_id'))->findAll(),
                'nombre',
                'id'
            );
        }
        foreach ($variantes as &$variante) {
            $variante['familia_nombre'] = $nombresFamilia[$variante['familia_id']] ?? '?';
        }
        unset($variante);

        return view('piezas/papelera', [
            'familias' => $this->familiaModel
                ->where('borrado_en IS NOT NULL')
                ->orderBy('borrado_en', 'DESC')
                ->findAll(),
            'variantes' => $variantes,
        ]);
    }

    public function restaurarFamilia(int $familiaId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->restaurarFamilia($familiaId),
            fn() => site_url('piezas/papelera'),
            fn($familia) => 'Pieza "' . $familia['nombre'] . '" restaurada.'
        );
    }

    public function restaurarVariante(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->restaurarVariante($varianteId),
            fn() => site_url('piezas/papelera'),
            fn($variante) => 'Variante "' . $variante['nombre'] . '" restaurada.'
        );
    }

    public function crearVariante()
    {
        return $this->ejecutar(
            fn() => $this->servicio->crearVariante(
                (int) $this->request->getPost('familia_id'),
                trim((string) $this->request->getPost('nombre')),
                $this->request->getPost('notas') ?: null
            ),
            fn($variante) => site_url('piezas/variante/' . $variante['id']),
            fn($variante) => 'Variante "' . $variante['nombre'] . '" creada, con su rama inicial abierta.'
        );
    }

    /**
     * Renombrar la variante. El aviso del mensaje no es adorno: el cliente
     * se refiere a las variantes por nombre, así que el comando que el
     * usuario tenga apuntado deja de valer en cuanto esto cambia.
     */
    public function renombrarVariante(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->renombrarVariante($varianteId, (string) $this->request->getPost('nombre')),
            fn($variante) => site_url('piezas/variante/' . $variante['id']),
            fn($variante) => 'Ahora se llama "' . $variante['nombre'] . '". Desde el script, llámala por ese nombre.'
        );
    }

    /**
     * Notas de la pieza entera. Se edita desde la ficha de la variante que
     * se estaba mirando, así que vuelve a ella.
     */
    public function editarNotasFamilia(int $familiaId)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        return $this->ejecutar(
            fn() => $this->servicio->actualizarNotasFamilia($familiaId, $this->request->getPost('notas')),
            fn($familia) => site_url('piezas/variante/' . $varianteId),
            fn($familia) => 'Notas de la pieza guardadas.'
        );
    }

    /**
     * Notas de esta línea de diseño (variante).
     */
    public function editarNotasVariante(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->actualizarNotasVariante($varianteId, $this->request->getPost('notas')),
            fn($variante) => site_url('piezas/variante/' . $variante['id']),
            fn($variante) => 'Notas de la variante guardadas.'
        );
    }

    /**
     * Enlace al máster de máxima calidad (Drive u otro sitio fuera del
     * tracker), no un fichero: aquí solo se guarda dónde está.
     */
    public function editarEnlaceOriginal(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->actualizarEnlaceOriginal($varianteId, $this->request->getPost('enlace_original')),
            fn($variante) => site_url('piezas/variante/' . $variante['id']),
            fn($variante) => $variante['enlace_original']
                ? 'Enlace al original guardado.'
                : 'Enlace al original quitado.'
        );
    }

    public function derivarVariante(int $versionId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->derivarVariante(
                $versionId,
                trim((string) $this->request->getPost('nombre')),
                $this->request->getPost('notas') ?: null
            ),
            fn($variante) => site_url('piezas/variante/' . $variante['id']),
            fn($variante) => 'Variante "' . $variante['nombre'] . '" derivada. Empieza su propia numeración desde v001.'
        );
    }

    /**
     * "Compuesta de": anota que la escena de esta variante también incluía
     * la versión de otra pieza — un torso modelado con el brazo ya hecho al
     * lado, un "Mini playmobil" que es varias piezas de cuerpo juntas.
     * Puramente informativo, aparte de derivarVariante (spec 11.1 ampliado).
     */
    public function declararComponente(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->declararComponente(
                $varianteId,
                (int) $this->request->getPost('version_componente_id'),
                trim((string) $this->request->getPost('notas')) ?: null
            ),
            fn() => site_url('piezas/variante/' . $varianteId),
            fn() => 'Anotado como parte de esta pieza.'
        );
    }

    public function quitarComponente(int $composicionId)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        return $this->ejecutar(
            fn() => $this->servicio->quitarComponente($composicionId),
            fn() => site_url('piezas/variante/' . $varianteId),
            fn() => 'Quitado de la lista.'
        );
    }

    // ---- Verbos sobre la variante y sus versiones -----------------------

    public function promocionar(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->promocionar(
                $varianteId,
                trim((string) $this->request->getPost('cambio')),
                $this->request->getPost('medidas') ?: null
            ),
            fn($version) => site_url('piezas/variante/' . $varianteId),
            // Spec 7.3: promocionar cierra un ciclo, y la confirmación tiene
            // que dejarlo ver — número, fecha y la rama nueva ya abierta.
            fn($version) => sprintf(
                'v%03d promocionada el %s. Rama nueva abierta: %s.',
                (int) $version['numero'],
                date('d/m/Y H:i', strtotime($version['promocionada_en'])),
                $this->ramaModel->nombre($this->ramaModel->abiertaDe($varianteId) ?? [])
            )
        );
    }

    public function marcarImpresa(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            fn() => $this->servicio->marcarImpresa($versionId, $this->request->getPost('params_impresion') ?: null),
            fn($version) => sprintf('v%03d marcada como impresa. Cuando la juzgues: validar o descartar.', (int) $version['numero'])
        );
    }

    public function validar(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            fn() => $this->servicio->validar($versionId, $this->request->getPost('resultado') ?: null),
            fn($version) => sprintf('v%03d es ahora la versión buena. La anterior pasó a superada.', (int) $version['numero'])
        );
    }

    public function descartar(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            fn() => $this->servicio->descartar($versionId, trim((string) $this->request->getPost('resultado'))),
            fn($version) => sprintf('v%03d descartada. Se conserva con el motivo: nada se borra.', (int) $version['numero'])
        );
    }

    /**
     * Deshacer un botón mal pulsado (impresa/descartada/validada ->
     * borrador). El mensaje se elige antes de tocar nada: después ya está
     * en borrador y no se sabría de dónde venía.
     */
    public function deshacer(int $versionId)
    {
        $antes = $this->versionModel->find($versionId);
        $desde = $antes['estado'] ?? '';

        return $this->verboDeVersion(
            $versionId,
            fn() => $this->servicio->devolverABorrador($versionId),
            fn($version) => sprintf(
                match ($desde) {
                    'descartada' => 'Descarte deshecho: v%03d vuelve a borrador, sin el motivo. Márcala impresa cuando toque.',
                    'validada'   => 'Validación deshecha: v%03d vuelve a borrador, sin el resultado. Si reemplazaba a una '
                        . 'versión "superada", esa no se restaura sola.',
                    default      => 'v%03d vuelve a borrador. Los parámetros de impresión se han borrado: vuelve a marcarla impresa con los buenos.',
                },
                (int) $version['numero']
            )
        );
    }

    public function devolverATrabajo(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            fn() => $this->servicio->devolverATrabajo($versionId, (bool) $this->request->getPost('abandonar_rama')),
            fn($version) => 'Rama nueva abierta. La versión no se ha tocado: se trabaja encima, nunca sobre ella.'
        );
    }

    /**
     * La válvula de escape (spec 4.4): solo vive aquí, en la web, y solo
     * para cuando la máquina que tiene la copia ya no puede devolverla. No
     * es un atajo, y por eso exige motivo escrito y queda marcada distinto
     * de un cierre con prueba.
     */
    public function forzarCierre(int $descargaId)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        return $this->ejecutar(
            fn() => $this->sync->forzarCierre($descargaId, (string) $this->request->getPost('motivo')),
            fn($resultado) => site_url('piezas/variante/' . $varianteId),
            fn($resultado) => 'Descarga cerrada a la fuerza. Queda registrada como cierre sin prueba, con tu motivo.'
        );
    }

    /**
     * La misma válvula de escape, para una sesión que nunca pasó por una
     * descarga ("abrir" en variante estrenada, o el reabrir-sola de
     * "subir"): si el disco que la abrió desaparece, esto es lo único que
     * puede liberar el bloqueo (invariante 3).
     */
    public function forzarCierreSesion(int $sesionId)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        return $this->ejecutar(
            fn() => $this->sync->forzarCierreSesion($sesionId, (string) $this->request->getPost('motivo')),
            fn($resultado) => site_url('piezas/variante/' . $varianteId),
            fn($resultado) => 'Sesión cerrada a la fuerza. Queda registrada como cierre sin prueba, con tu motivo.'
        );
    }

    /**
     * Aparta a mano el .blend de una sesión ya cerrada (de la rama actual o
     * de una ya cerrada por una versión antigua) — libera el sitio en disco
     * sin perder el registro de que existió.
     */
    public function descartarFicheroSesion(int $sesionId)
    {
        $varianteId = (int) $this->request->getPost('variante_id');

        return $this->ejecutar(
            fn() => $this->servicio->descartarFicheroSesion($sesionId, (string) $this->request->getPost('motivo')),
            fn($resultado) => site_url('piezas/variante/' . $varianteId),
            fn($resultado) => 'Fichero apartado a la papelera. La sesión sigue en el historial, sin ocupar sitio.'
        );
    }

    /**
     * Purga de golpe todas las sesiones (sin purgar todavía) de la rama que
     * llevó a esta versión. Validar y descartar ya no lo hacen solas — es
     * una decisión aparte, a conveniencia, desde el historial de la versión.
     */
    public function purgarSesionesVersion(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            fn() => $this->servicio->purgarSesionesDe($versionId),
            fn($n) => $n > 0
                ? sprintf('%d sesión(es) purgada(s): sus .blend se apartaron a la papelera.', $n)
                : 'No había ninguna sesión pendiente de purgar.'
        );
    }

    // ---- Imágenes: referencias (familia) y renders (versión) ------------

    public function subirReferencia(int $varianteId)
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa variante no existe.');
        }

        $familiaId = (int) $variante['familia_id'];

        return $this->ejecutar(
            function () use ($familiaId, $varianteId) {
                $extension = $this->validarImagen($this->request->getFile('imagen'));

                $id = $this->referenciaModel->insert([
                    'familia_id'  => $familiaId,
                    'variante_id' => $varianteId,
                    'ruta_imagen' => '',
                    'notas'       => trim((string) $this->request->getPost('notas')) ?: null,
                    'subida_en'   => date('Y-m-d H:i:s'),
                ], true);
                if (!$id) {
                    // Sin esto, un id=false se cuela como 0 en la ruta de
                    // fichero (PHP no tiene strict_types aquí) y se guarda
                    // un fichero huérfano antes de que reviente el update.
                    throw new RuntimeException('No se pudo registrar la referencia: ' . implode(' ', $this->referenciaModel->errors()));
                }

                $ruta = $this->almacen->rutaReferencia($familiaId, $id, $extension);
                $this->almacen->guardar($this->request->getFile('imagen')->getTempName(), $ruta);
                $hash = $this->almacen->hash($ruta);

                $this->referenciaModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $hash,
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                $this->publicarCopias($ruta, $hash);

                return $varianteId;
            },
            fn() => site_url('piezas/variante/' . $varianteId),
            fn() => 'Referencia añadida.'
        );
    }

    /**
     * A dónde volver tras borrar una referencia. Si tiene `variante_id`
     * (subida tras este cambio) se sabe sin más; las de antes (compartidas,
     * sin variante) siguen dependiendo del campo oculto del formulario —
     * comprobando que la variante indicada sea de la misma familia, para que
     * no se convierta en un redirector a cualquier sitio.
     */
    private function vueltaALaFicha(int $familiaId): string
    {
        $varianteId = (int) $this->request->getPost('volver_a_variante');
        $variante   = $varianteId ? $this->varianteModel->find($varianteId) : null;

        return ($variante && (int) $variante['familia_id'] === $familiaId)
            ? site_url('piezas/variante/' . $varianteId)
            : site_url('piezas');
    }

    public function borrarReferencia(int $id)
    {
        $referencia = $this->referenciaModel->find($id);
        if (!$referencia) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa referencia no existe.');
        }

        // Invariante 6 en espíritu: el fichero se aparta, no se destruye —
        // el registro de la referencia sí se quita, porque a diferencia de
        // una sesión o una versión no es parte del histórico de trabajo.
        $destino = !empty($referencia['variante_id'])
            ? site_url('piezas/variante/' . $referencia['variante_id'])
            : $this->vueltaALaFicha((int) $referencia['familia_id']);

        $this->almacen->aPapelera($referencia['ruta_imagen']);
        $this->publicas->retirar($referencia['hash_imagen'] ?? null);
        $this->referenciaModel->delete($id);

        return redirect()->to($destino)->with('success', 'Referencia apartada a la papelera.');
    }

    /**
     * Captura de la plataforma del laminador (fase 43): de dónde partía la
     * impresión y cómo quedó orientada/soportada, no solo el resultado ya
     * curado. Una placa compleja puede necesitar varias, así que se suben
     * de una en una y se acumulan, igual que las referencias de una
     * variante — no forman parte del guardado general de la bitácora.
     */
    public function subirImagenPlaca(int $placaId)
    {
        $placa = $this->placaModel->find($placaId);
        if (!$placa) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa placa ya no existe.');
        }

        return $this->ejecutar(
            function () use ($placaId) {
                $extension = $this->validarImagen($this->request->getFile('imagen'));

                $id = $this->placaImagenModel->insert([
                    'placa_id'    => $placaId,
                    'ruta_imagen' => '',
                    'notas'       => trim((string) $this->request->getPost('notas')) ?: null,
                    'orden'       => $this->placaImagenModel->siguienteOrden($placaId),
                    'subida_en'   => date('Y-m-d H:i:s'),
                ], true);
                if (!$id) {
                    throw new RuntimeException('No se pudo registrar la imagen: ' . implode(' ', $this->placaImagenModel->errors()));
                }

                $ruta = $this->almacen->rutaPlacaImagen($placaId, $id, $extension);
                $this->almacen->guardar($this->request->getFile('imagen')->getTempName(), $ruta);
                $hash = $this->almacen->hash($ruta);

                $this->placaImagenModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $hash,
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                $this->publicarCopias($ruta, $hash);

                return $placaId;
            },
            fn() => site_url('piezas/placa/' . $placaId . '/bitacora/editar'),
            fn() => 'Foto añadida.'
        );
    }

    public function borrarImagenPlaca(int $id)
    {
        $imagen = $this->placaImagenModel->find($id);
        if (!$imagen) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa foto ya no existe.');
        }

        // Invariante 6 en espíritu, igual que las referencias: el fichero se
        // aparta, no se destruye; el registro sí se quita.
        $this->almacen->aPapelera($imagen['ruta_imagen']);
        $this->publicas->retirar($imagen['hash_imagen'] ?? null);
        $this->placaImagenModel->delete($id);

        return redirect()->to(site_url('piezas/placa/' . (int) $imagen['placa_id'] . '/bitacora/editar'))
            ->with('success', 'Foto apartada a la papelera.');
    }

    public function imagenPlaca(int $id)
    {
        $imagen = $this->placaImagenModel->find($id);
        if (!$imagen || !$this->almacen->existe($imagen['ruta_imagen'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->servirImagen(
            $this->almacen->absoluta($imagen['ruta_imagen']),
            $imagen['hash_imagen']
        );
    }

    /**
     * Captura de cómo quedó UNA pieza dentro de la placa: la mejor posición
     * impresa, con notas de cómo estaba puesta y un veredicto rápido. Varias
     * por fila y en momentos distintos (antes de imprimir, ya curada), igual
     * que subirImagenPlaca() pero a nivel de pieza.
     */
    public function subirImagenPlacaVersion(int $placaVersionId)
    {
        $fila = $this->placaVersionModel->find($placaVersionId);
        if (!$fila) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa pieza ya no está en ninguna placa.');
        }
        $placaId = (int) $fila['placa_id'];

        return $this->ejecutar(
            function () use ($placaVersionId) {
                $extension = $this->validarImagen($this->request->getFile('imagen'));

                $resultado = (string) $this->request->getPost('resultado');
                $resultado = isset(PiezaPlacaVersionImagenModel::RESULTADOS[$resultado]) ? $resultado : null;

                $id = $this->placaVersionImagenModel->insert([
                    'placa_version_id' => $placaVersionId,
                    'ruta_imagen'      => '',
                    'notas'            => trim((string) $this->request->getPost('notas')) ?: null,
                    'resultado'        => $resultado,
                    'orden'            => $this->placaVersionImagenModel->siguienteOrden($placaVersionId),
                    'subida_en'        => date('Y-m-d H:i:s'),
                ], true);
                if (!$id) {
                    throw new RuntimeException('No se pudo registrar la imagen: ' . implode(' ', $this->placaVersionImagenModel->errors()));
                }

                $ruta = $this->almacen->rutaPlacaVersionImagen($placaVersionId, $id, $extension);
                $this->almacen->guardar($this->request->getFile('imagen')->getTempName(), $ruta);
                $hash = $this->almacen->hash($ruta);

                $this->placaVersionImagenModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $hash,
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                $this->publicarCopias($ruta, $hash);

                return $placaVersionId;
            },
            fn() => site_url('piezas/placa/' . $placaId . '/bitacora/editar'),
            fn() => 'Foto añadida.'
        );
    }

    public function borrarImagenPlacaVersion(int $id)
    {
        $imagen = $this->placaVersionImagenModel->find($id);
        if (!$imagen) {
            return redirect()->to(site_url('piezas/placas'))->with('error', 'Esa foto ya no existe.');
        }

        $fila = $this->placaVersionModel->find((int) $imagen['placa_version_id']);

        // Invariante 6 en espíritu, igual que las fotos de placa: el fichero
        // se aparta, no se destruye; el registro sí se quita.
        $this->almacen->aPapelera($imagen['ruta_imagen']);
        $this->publicas->retirar($imagen['hash_imagen'] ?? null);
        $this->placaVersionImagenModel->delete($id);

        $destino = $fila ? site_url('piezas/placa/' . (int) $fila['placa_id'] . '/bitacora/editar') : site_url('piezas/placas');

        return redirect()->to($destino)->with('success', 'Foto apartada a la papelera.');
    }

    public function imagenPlacaVersion(int $id)
    {
        $imagen = $this->placaVersionImagenModel->find($id);
        if (!$imagen || !$this->almacen->existe($imagen['ruta_imagen'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->servirImagen(
            $this->almacen->absoluta($imagen['ruta_imagen']),
            $imagen['hash_imagen']
        );
    }

    /**
     * `version_id` es opcional (fase 31): un render cuelga siempre de la
     * variante (el ancla que existe desde la creación de la pieza), y
     * además de una versión concreta cuando se sube desde el historial de
     * esa versión — ahí sigue significando "así salió esa iteración". Sin
     * `version_id` es una foto de progreso suelta, útil antes de la
     * primera promoción, cuando todavía no hay ninguna versión que elegir.
     */
    public function subirRender(int $varianteId)
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa variante no existe.');
        }

        $versionId = (int) ($this->request->getPost('version_id') ?: 0);
        $version   = $versionId > 0 ? $this->versionModel->find($versionId) : null;
        if ($versionId > 0 && (!$version || (int) $version['variante_id'] !== $varianteId)) {
            return redirect()->to(site_url('piezas/variante/' . $varianteId))->with('error', 'Esa versión no es de esta variante.');
        }

        return $this->ejecutar(
            function () use ($varianteId, $versionId) {
                $extension = $this->validarImagen($this->request->getFile('imagen'));

                $id = $this->renderModel->insert([
                    'variante_id' => $varianteId,
                    'version_id'  => $versionId > 0 ? $versionId : null,
                    'ruta_imagen' => '',
                    'notas'       => trim((string) $this->request->getPost('notas')) ?: null,
                    'subida_en'   => date('Y-m-d H:i:s'),
                ], true);
                if (!$id) {
                    throw new RuntimeException('No se pudo registrar el render: ' . implode(' ', $this->renderModel->errors()));
                }

                // El render se guarda tal cual llega, como las referencias.
                // Antes se aplastaba a un JPEG de 1024 px y 300 KB porque
                // era ese mismo fichero el que se pintaba en las galerías y
                // había que hacerlo ligero a la fuerza; con la transparencia
                // aplanada a blanco, además, que sobre una tarjeta oscura
                // se veía fatal. Ahora quien se pinta es la miniatura de
                // `public/piezas-img`, así que no hay razón para estropear
                // el original al entrar.
                $ruta = $versionId > 0
                    ? $this->almacen->rutaRender($varianteId, $versionId, $id, $extension)
                    : $this->almacen->rutaRenderSuelto($varianteId, $id, $extension);
                $this->almacen->guardar($this->request->getFile('imagen')->getTempName(), $ruta);
                $hash = $this->almacen->hash($ruta);

                $this->renderModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $hash,
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                $this->publicarCopias($ruta, $hash);

                return $varianteId;
            },
            fn() => site_url('piezas/variante/' . $varianteId),
            fn() => $version
                ? sprintf('Render añadido a v%03d.', (int) $version['numero'])
                : 'Render añadido.'
        );
    }

    public function borrarRender(int $id)
    {
        $render = $this->renderModel->find($id);
        if (!$render) {
            return redirect()->to(site_url('piezas'))->with('error', 'Ese render no existe.');
        }
        $version = $this->versionModel->find($render['version_id']);

        $this->almacen->aPapelera($render['ruta_imagen']);
        // Las copias públicas se nombran por el hash del contenido: si otra
        // fila de render tiene la misma imagen (se reutilizó de una versión a
        // otra con "Reutilizar imagen"), retirarlas dejaría a esa otra sin
        // miniatura hasta el próximo `piezas:publicar-imagenes`.
        $hash = $render['hash_imagen'] ?? null;
        $compartida = $hash !== null && $hash !== ''
            && $this->renderModel->where('hash_imagen', $hash)->where('id !=', $id)->countAllResults() > 0;
        if (!$compartida) {
            $this->publicas->retirar($hash);
        }
        $this->renderModel->delete($id);

        $destino = $version ? site_url('piezas/variante/' . $version['variante_id']) : site_url('piezas');

        return redirect()->to($destino)->with('success', 'Render apartado a la papelera.');
    }

    /**
     * Reutiliza en ESTA versión el render de otra versión de la misma
     * variante (o uno suelto): copia su imagen a una fila de render nueva
     * colgada de esta versión. Para cuando una iteración salió idéntica por
     * fuera y no merece una foto propia, pero se quiere que la versión —
     * sobre todo la validada, que es la miniatura del índice y la galería —
     * tenga imagen.
     *
     * Se COPIA el fichero, no se comparte la fila: cada render sigue siendo
     * dueño de su copia y borrar uno no toca al otro. Las copias públicas
     * (nombradas por hash) sí coincidirían — `borrarRender` ya comprueba si
     * otra fila las respalda antes de retirarlas.
     */
    public function reutilizarRender(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa versión no existe.');
        }
        $volver = site_url('piezas/variante/' . (int) $version['variante_id']);

        $origenId = (int) ($this->request->getPost('render_id') ?: 0);
        $origen   = $origenId > 0 ? $this->renderModel->find($origenId) : null;
        if (!$origen || (int) $origen['variante_id'] !== (int) $version['variante_id']) {
            return redirect()->to($volver)->with('error', 'Esa imagen no es de esta pieza.');
        }
        if ((int) ($origen['version_id'] ?? 0) === $versionId) {
            return redirect()->to($volver)->with('error', 'Esa imagen ya es de esta versión.');
        }
        if (!$this->almacen->existe($origen['ruta_imagen'])) {
            return redirect()->to($volver)->with('error', 'El fichero de esa imagen ya no está.');
        }

        return $this->ejecutar(
            function () use ($versionId, $version, $origen) {
                $extension = strtolower(pathinfo((string) $origen['ruta_imagen'], PATHINFO_EXTENSION)) ?: 'jpg';

                // Alta en dos pasos, como subirRender: la fila primero porque
                // su id va dentro de la ruta del fichero.
                $id = $this->renderModel->insert([
                    'variante_id' => (int) $version['variante_id'],
                    'version_id'  => $versionId,
                    'ruta_imagen' => '',
                    'notas'       => $origen['notas'] ?? null,
                    'subida_en'   => date('Y-m-d H:i:s'),
                ], true);
                if (!$id) {
                    throw new RuntimeException('No se pudo registrar el render: ' . implode(' ', $this->renderModel->errors()));
                }

                $ruta = $this->almacen->rutaRender((int) $version['variante_id'], $versionId, $id, $extension);
                try {
                    $this->almacen->copiar($origen['ruta_imagen'], $ruta);
                    $hash = $this->almacen->hash($ruta);
                } catch (Throwable $e) {
                    $this->renderModel->delete($id);

                    throw $e;
                }

                $this->renderModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $hash,
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                $this->publicarCopias($ruta, $hash);

                return (int) $version['variante_id'];
            },
            fn(int $varianteId) => site_url('piezas/variante/' . $varianteId),
            fn() => sprintf('Imagen reutilizada en v%03d.', (int) $version['numero'])
        );
    }

    /**
     * Adjunta el STL para imprimir esta versión. Separado de "Marcar
     * impresa" a propósito: no siempre se exporta en el mismo momento en
     * que se sube el .blend, y así se puede adjuntar en cuanto esté listo,
     * antes o después de imprimir.
     */
    public function subirStl(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            function () use ($versionId) {
                $version = $this->versionModel->find($versionId);

                $file = $this->request->getFile('stl');
                if (!$file || !$file->isValid() || $file->hasMoved()) {
                    throw new RuntimeException('No ha llegado ningún fichero STL válido.');
                }
                if ($file->getSize() > self::TAMANO_MAX_STL) {
                    throw new RuntimeException('El STL pesa más de 50 MB.');
                }
                if (strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION)) !== 'stl') {
                    throw new RuntimeException('Solo se admiten ficheros .stl.');
                }

                // Sin nombre no se distinguiría un trozo de otro. Si solo hay
                // uno, "completo" es lo que de verdad es y ahorra escribirlo.
                $nombre = trim((string) $this->request->getPost('nombre'));
                if ($nombre === '') {
                    $nombre = $this->servicio->stlsDe($versionId) === [] ? 'completo' : '';
                }

                // Alta en dos pasos: la fila primero, porque la ruta del
                // fichero lleva dentro el id del STL (varios por versión,
                // fase 21). Así el segundo no puede pisar al primero.
                $stl  = $this->servicio->reservarStl($versionId, $nombre);
                $ruta = $this->almacen->rutaStl(
                    (int) $version['variante_id'],
                    (int) $version['numero'],
                    (int) $stl['id']
                );

                try {
                    $this->almacen->guardar($file->getTempName(), $ruta);
                    $hash = $this->almacen->hash($ruta);

                    $this->servicio->adjuntarStl((int) $stl['id'], $ruta, $hash, (int) $file->getSize());
                } catch (Throwable $e) {
                    // La reserva quedaría como un STL fantasma sin fichero.
                    $this->servicio->quitarStl((int) $stl['id']);

                    throw $e;
                }

                return $version;
            },
            fn($version) => sprintf('STL adjuntado a v%03d. Ya se puede descargar para imprimir.', (int) $version['numero'])
        );
    }

    /**
     * Quitar un STL: con varios por versión, subir el equivocado deja de ser
     * un accidente raro. Va a la papelera, no se borra (invariante 6).
     */
    public function quitarStl(int $stlId)
    {
        $stl = $this->servicio->stl($stlId);
        if (!$stl) {
            return redirect()->to(site_url('piezas'))->with('error', 'Ese STL no existe.');
        }

        $version = $this->versionModel->find($stl['version_id']);

        return $this->ejecutar(
            fn() => $this->servicio->quitarStl($stlId),
            fn() => site_url('piezas/variante/' . (int) $version['variante_id']),
            fn($quitado) => sprintf(
                'STL "%s" quitado de v%03d. El fichero queda 30 días en la papelera.',
                $quitado['nombre'],
                (int) $version['numero']
            )
        );
    }

    /**
     * Cuánto ocupa este STL en la placa, en mm (fase 53) — la caja de
     * ocupación que da Chitubox con la pieza ya orientada como se va a
     * imprimir, no una lectura del fichero. Vacío en cualquiera de los dos
     * campos borra la medida: "sin medir" es un estado válido, ese STL solo
     * se queda fuera del cálculo de cuántas placas hacen falta hasta que
     * alguien lo mida.
     */
    public function actualizarMedidasStl(int $stlId)
    {
        $stl = $this->servicio->stl($stlId);
        if (!$stl) {
            return redirect()->to(site_url('piezas'))->with('error', 'Ese STL no existe.');
        }
        $version = $this->versionModel->find($stl['version_id']);

        return $this->ejecutar(
            function () use ($stlId) {
                $aMm = function ($valor, float $max): ?string {
                    $valor = $this->aPeso($valor);
                    if ($valor === null) {
                        return null;
                    }

                    return (string) max(0.1, min($max, (float) $valor));
                };

                $datos = [
                    'ancho_mm' => $aMm($this->request->getPost('ancho'), PiezaEmpaquetadoService::PLACA_ANCHO_MM),
                    'fondo_mm' => $aMm($this->request->getPost('fondo'), PiezaEmpaquetadoService::PLACA_FONDO_MM),
                ];
                (new \App\Models\PiezaVersionStlModel())->update($stlId, $datos);

                return $datos;
            },
            fn() => site_url('piezas/variante/' . (int) $version['variante_id']),
            fn() => 'Medida guardada.'
        );
    }

    /**
     * Revisión de malla de una versión (fase 54): manifold, normales
     * invertidas, agujeros... — lo que se ve al abrirla en el laminador y
     * hay que arreglar antes de imprimir. `estado` llega como 'ok',
     * 'fallos' o vacío (= sin comprobar, se guarda NULL). Por versión, no
     * por STL: es el "¿lista para el laminador?" que se ve en el índice.
     */
    public function actualizarRevisionMalla(int $versionId)
    {
        return $this->verboDeVersion(
            $versionId,
            function () use ($versionId) {
                $estado = (string) $this->request->getPost('estado');
                $valor  = in_array($estado, ['ok', 'fallos'], true) ? $estado : null;
                $this->versionModel->update($versionId, ['revision_malla' => $valor]);

                return $valor;
            },
            fn($valor) => match ($valor) {
                'ok'     => 'Malla marcada como revisada y limpia.',
                'fallos' => 'Malla marcada con fallos por arreglar.',
                default  => 'Malla marcada como sin comprobar.',
            }
        );
    }

    /**
     * A diferencia de las imágenes, el STL se sirve para descargar (no
     * inline): se abre en el laminador, no en el navegador.
     */
    public function descargarStl(int $stlId)
    {
        $stl = $this->servicio->stl($stlId);
        if (!$stl || empty($stl['ruta_stl']) || !$this->almacen->existe($stl['ruta_stl'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $version  = $this->versionModel->find($stl['version_id']);
        $variante = $this->varianteModel->find($version['variante_id']);

        // En disco el fichero se llama version-v002-stl-7.stl (nombres
        // derivados de IDs, spec 8); quien lo descarga necesita saber de qué
        // pieza es y, con varios trozos, cuál de ellos.
        return $this->response->download($this->almacen->absoluta($stl['ruta_stl']), null, true)
            ->setFileName($this->nombreArchivo($variante, $version, 'stl', $stl['nombre']));
    }

    /**
     * El .blend de una VERSIÓN, descargable sin declarar máquina.
     *
     * Es la única excepción a "la web no descarga .blend" (spec 7) y se
     * sostiene en que una versión es inmutable (invariante 4) y está
     * cerrada: nadie espera que la devuelvas, así que no hay asiento que
     * cuadrar ni cadena hash_padre que romper. Sirve para mirar el modelo
     * desde una máquina sin el cliente instalado.
     *
     * Las sesiones siguen fuera: ahí sí hay trabajo en curso que debe
     * volver, y una copia sin registrar es justo lo que la sección 4.4
     * existe para evitar. Si alguien acaba trabajando sobre esta copia, el
     * cliente lo verá como divergencia (tabla 4.3, fila 4) al faltarle el
     * .sesion.json — nunca la dará por buena.
     */
    public function descargarBlend(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version || !$this->almacen->existe($version['ruta_blend'] ?? null)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $variante = $this->varianteModel->find($version['variante_id']);

        // El aviso del modal se lee una vez; el nombre del fichero viaja con
        // él. Dentro de un mes, en una carpeta de descargas, "solo-lectura"
        // es lo único que queda para no ponerse a trabajar sobre esta copia
        // creyendo que cuenta (no abrió asiento: el sistema no sabe que
        // existe, y lo que se suba desde aquí no cuadraría con nada).
        return $this->response->download($this->almacen->absoluta($version['ruta_blend']), null, true)
            ->setFileName($this->nombreArchivo($variante, $version, 'blend', 'solo-lectura'));
    }

    /**
     * El .blend "vivo" de una sesión (el de la última subida), de solo
     * lectura desde la web — mismo espíritu que `descargarBlend`, pero para
     * trabajo en curso: sirve para comprobar a ojo que una subida llegó bien
     * sin tener que fiarse solo del CLI, sobre todo si hay fallos recientes
     * de subida y hace falta verificar que no se ha perdido nada. No abre
     * ningún asiento ni afecta a la sincronización — igual que
     * `PiezaSyncService::entregarParaVerificacion`, que ya hace lo mismo
     * para el cliente (fase 34).
     */
    public function descargarSesionBlend(int $sesionId)
    {
        $sesion = $this->sesionModel->find($sesionId);
        if (!$sesion || !$this->almacen->existe($sesion['ruta_blend'] ?? null)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $variante = $this->varianteModel->find($this->ramaModel->find($sesion['rama_id'])['variante_id']);

        return $this->response->download($this->almacen->absoluta($sesion['ruta_blend']), null, true)
            ->setFileName($this->nombreDescargaSesion($variante, (int) $sesion['numero'], 'solo-lectura'));
    }

    /**
     * Igual que `descargarSesionBlend`, pero de una subida concreta del
     * histórico (fase 41) en vez del último estado de la sesión: la subida
     * 2 de una sesión que ya lleva 3 sigue siendo descargable, aunque ya no
     * sea la que se vería al abrir la sesión hoy.
     */
    public function descargarSubidaBlend(int $subidaId)
    {
        $subida = $this->subidaModel->find($subidaId);
        if (!$subida || !$this->almacen->existe($subida['ruta_blend'] ?? null)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $sesion   = $this->sesionModel->find($subida['sesion_id']);
        $variante = $this->varianteModel->find($this->ramaModel->find($sesion['rama_id'])['variante_id']);

        return $this->response->download($this->almacen->absoluta($subida['ruta_blend']), null, true)
            ->setFileName($this->nombreDescargaSesion(
                $variante,
                (int) $sesion['numero'],
                'solo-lectura',
                (int) $subida['numero']
            ));
    }

    /** Nombre de fichero para descargas de sesión/subida: no hay "versión" que numerar con v%03d, así que no reutiliza nombreArchivo(). */
    private function nombreDescargaSesion(?array $variante, int $numeroSesion, string $sufijo, ?int $numeroSubida = null): string
    {
        $familia = $variante ? $this->familiaModel->find($variante['familia_id']) : null;

        $partes = array_filter([
            $this->paraNombreDeArchivo($variante['sku'] ?? null),
            $this->paraNombreDeArchivo($familia['nombre'] ?? null),
            $this->paraNombreDeArchivo($variante['nombre'] ?? null) ?: 'variante-' . ($variante['id'] ?? '0'),
        ]);

        return sprintf(
            '%s-sesion-%03d%s-%s.blend',
            implode('-', $partes),
            $numeroSesion,
            $numeroSubida !== null ? sprintf('-subida-%03d', $numeroSubida) : '',
            $this->paraNombreDeArchivo($sufijo)
        );
    }

    /**
     * Sirve la imagen desde writable/ (spec sección 8: fuera del directorio
     * público). A diferencia del .blend, aquí no hace falta declarar
     * máquina — el filtro 'auth' de sesión de navegador basta.
     */
    public function imagenReferencia(int $id)
    {
        $referencia = $this->referenciaModel->find($id);
        if (!$referencia || !$this->almacen->existe($referencia['ruta_imagen'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->servirImagen(
            $this->almacen->absoluta($referencia['ruta_imagen']),
            $referencia['hash_imagen']
        );
    }

    public function imagenRender(int $id)
    {
        $render = $this->renderModel->find($id);
        if (!$render || !$this->almacen->existe($render['ruta_imagen'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->servirImagen(
            $this->almacen->absoluta($render['ruta_imagen']),
            $render['hash_imagen']
        );
    }

    /**
     * Sirve una imagen del almacén con caché de verdad.
     *
     * Una imagen ya subida no cambia nunca: el mismo id devuelve siempre el
     * mismo fichero (cambiarla es subir otra, con otro id). Así que se puede
     * cachear para siempre — y esto no es un detalle de rendimiento, es lo que
     * evita que una galería de treinta piezas dispare treinta peticiones a
     * PHP, con su sesión y su conexión a la base, cada vez que se abre.
     *
     * NO se usa `$response->download()->inline()` aunque sería lo natural:
     * `DownloadResponse` llama a `noCache()` al construir sus cabeceras, justo
     * antes de enviarlas, y machaca cualquier `Cache-Control` que se le ponga
     * — el `max-age` acababa detrás de un `no-store` que lo anula. Por eso se
     * arma la respuesta a mano.
     *
     * Y se suelta la sesión antes de mandar nada: mientras una petición la
     * tiene abierta, PHP mantiene un bloqueo exclusivo sobre su fichero y
     * TODAS las demás peticiones del mismo navegador esperan en fila. Una
     * galería con veinte miniaturas son veinte peticiones que el navegador
     * lanza a la vez y que, sin esto, se sirven de una en una: las últimas
     * tardan tanto que el navegador las aborta, y como el orden de la
     * carrera cambia en cada recarga, cada vez quedan rotas unas distintas.
     * Aquí ya no hace falta para nada: el filtro 'auth' ya comprobó quién
     * es, y lo único que queda es volcar bytes de disco.
     */
    private function servirImagen(string $rutaAbsoluta, ?string $hash): ResponseInterface
    {
        session()->close();

        // Nada más arrancar la sesión, PHP escupe `Expires` en 1981 y
        // `Pragma: no-cache` (session.cache_limiter, que viene en 'nocache'
        // de serie). Son cabeceras de HTTP/1.0 que no las pone CodeIgniter y
        // que dejan sin efecto el Cache-Control de abajo en buena parte de
        // los navegadores: sin quitarlas, la caché no llega a funcionar
        // nunca y cada recarga vuelve a pedir todas las miniaturas enteras.
        header_remove('Expires');
        header_remove('Pragma');

        $etag = $hash ? '"' . $hash . '"' : null;

        // Ya la tiene: 304 y ni se lee el fichero del disco ni viaja por la red.
        if ($etag !== null && $this->etagRecibido() === $hash) {
            return $this->response->setStatusCode(304)
                ->setHeader('Cache-Control', self::CACHE_IMAGENES)
                ->setHeader('ETag', $etag);
        }

        $contenido = file_get_contents($rutaAbsoluta);

        $respuesta = $this->response
            ->setHeader('Content-Type', self::TIPOS_POR_EXTENSION[strtolower(pathinfo($rutaAbsoluta, PATHINFO_EXTENSION))]
                ?? 'application/octet-stream')
            ->setHeader('Cache-Control', self::CACHE_IMAGENES)
            // Sin Content-Length el navegador no sabe cuánto espera, y una
            // conexión cortada a media imagen le parece una imagen entera:
            // la pinta a medias en vez de reintentarla.
            ->setHeader('Content-Length', (string) strlen($contenido))
            ->setBody($contenido);

        return $etag !== null ? $respuesta->setHeader('ETag', $etag) : $respuesta;
    }

    /**
     * El hash pelado que devuelve el navegador en `If-None-Match`, sin los
     * adornos que le va poniendo el camino: las comillas de siempre, el `W/`
     * de las validaciones débiles y, sobre todo, el sufijo `-gzip` que
     * mod_deflate le añade al comprimir la respuesta. Comparando la cadena
     * tal cual llegaba, ese sufijo hacía que el ETag no casara jamás y que
     * ninguna imagen se sirviera nunca con un 304.
     */
    private function etagRecibido(): ?string
    {
        $recibido = trim((string) $this->request->getHeaderLine('If-None-Match'));
        if ($recibido === '') {
            return null;
        }

        $recibido = trim(preg_replace('/^W\//', '', $recibido), '"');

        return preg_replace('/-(gzip|br)$/', '', $recibido);
    }

    /**
     * Genera las copias públicas de una imagen recién subida sin poder
     * tumbar la subida.
     *
     * La imagen ya está guardada y registrada cuando se llega aquí: si GD
     * se atraganta con ella, lo que corresponde es quedarse sin miniatura,
     * no perder la subida entera y dejar al usuario delante de un error
     * después de haber esperado a que se transfiriera el fichero. Mientras
     * no estén, `imagen_pieza()` tira del controlador y la imagen se ve
     * igual; `php spark piezas:publicar-imagenes` lo reintenta.
     */
    private function publicarCopias(string $rutaRelativa, string $hash): void
    {
        try {
            $this->publicas->publicar($this->almacen->absoluta($rutaRelativa), $hash);
        } catch (Throwable $e) {
            log_message('error', '[Piezas] no se pudieron publicar las copias de ' . $rutaRelativa . ': ' . $e->getMessage());
        }
    }

    /**
     * Valida el fichero subido y devuelve la extensión con la que se debe
     * guardar. El mime se comprueba con el detectado por el servidor
     * (finfo), no el que declara el navegador, que se puede falsear.
     */
    private function validarImagen($file): string
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            throw new RuntimeException('No ha llegado ninguna imagen válida.');
        }
        if ($file->getSize() > self::TAMANO_MAX_IMAGEN) {
            throw new RuntimeException('La imagen pesa más de 20 MB.');
        }

        $mime = $file->getMimeType();
        if (!isset(self::MIMES_IMAGEN[$mime])) {
            throw new RuntimeException("Formato no admitido ({$mime}). Solo JPEG, PNG o WEBP.");
        }

        return self::MIMES_IMAGEN[$mime];
    }

    // ---- Plomería -------------------------------------------------------

    /**
     * Los verbos del dominio se niegan lanzando excepción con el motivo ya
     * redactado para leerse (ver PiezaService). Aquí solo se traslada ese
     * texto al usuario: no se reescribe ni se convierte en "ha ocurrido un
     * error", que es justo lo que impediría entender por qué no se puede.
     */
    private function ejecutar(callable $accion, callable $destino, callable $mensaje)
    {
        try {
            $resultado = $accion();
        } catch (Throwable $e) {
            log_message('debug', '[Piezas web] ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => $e->getMessage()]);
            }

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'mensaje' => $mensaje($resultado)] + (is_array($resultado) ? $resultado : []));
        }

        return redirect()->to($destino($resultado))->with('success', $mensaje($resultado));
    }

    private function verboDeVersion(int $versionId, callable $accion, callable $mensaje)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa versión no existe.');
        }

        return $this->ejecutar(
            $accion,
            fn() => site_url('piezas/variante/' . (int) $version['variante_id']),
            $mensaje
        );
    }

    private function resumen(array $variante): array
    {
        $estado       = $this->sync->estadoDeSincronizacion((int) $variante['id']);
        $validada     = $this->versionModel->where('variante_id', $variante['id'])->where('estado', 'validada')->first();
        $ultimaVersion = $this->versionModel->where('variante_id', $variante['id'])->orderBy('numero', 'DESC')->first();
        $vigente       = $this->versionVigente((int) $variante['id']);
        // Una sola lectura de los STL de la vigente: la usan la columna de
        // STL y la de medidas de placa.
        $stlsVigente   = $vigente ? $this->servicio->stlsDe((int) $vigente['id']) : [];

        return $variante + [
            'validada'      => $validada,
            'versiones'     => $this->versionModel->where('variante_id', $variante['id'])->countAllResults(),
            'bloqueo'       => $this->descripcionBloqueo($estado['sesion_abierta']),
            'pendientes'    => $this->descripcionPendientes($estado['descargas_pendientes']),
            // Sin esto, todo lo que no fuera una versión validada se veía
            // igual en el listado: "sin versión", "para imprimir",
            // "impresa, pendiente de juzgar" y "la última se descartó" son
            // cuatro sitios muy distintos de la vida de una pieza.
            'ultima_version_estado' => $ultimaVersion['estado'] ?? null,
            // El número de la última, para el listado: si es un borrador
            // posterior a la validada, se enseña al lado de esta ("v001 v003").
            'ultima_version_numero' => isset($ultimaVersion['numero']) ? (int) $ultimaVersion['numero'] : null,
            // El otro eje: si además hay trabajo encima ahora mismo. Basta
            // con una sesión abierta (alguien la tiene en su máquina) o con
            // una ya subida y sin promocionar — ese segundo caso no se veía
            // en ningún sitio del listado, porque no hay bloqueo ni descarga
            // pendiente que avisar. Recién promocionada NO cuenta: la rama
            // nueva nace vacía, y si contase saldría en casi todas siempre.
            'trabajo_en_curso' => $estado['sesion_abierta'] !== null || $estado['ultima_subida'] !== null,
            // El .blend y el STL de la fila: los de la versión VIGENTE — la
            // última promocionada que sigue en juego (borrador, impresa,
            // validada o incluso descartada si es lo último que hay), nunca
            // una `superada`. Ver versionVigente(): son los ficheros
            // utilizables ahora mismo, no forzosamente los de la validada.
            'stl' => $this->estadoStl($vigente, $stlsVigente),
            // Medidas de placa (fase 53) de la versión vigente: cuánto ocupa
            // en la plataforma. Con varios trozos se suma el área de todos.
            // Solo cuenta como "medida" cuando TODOS los trozos lo están.
            'medidas_placa' => $this->estadoMedidasPlaca($stlsVigente),
            // Revisión de malla (fase 54) de la versión vigente: 'ok',
            // 'fallos' o null (sin comprobar). tiene_vigente distingue el
            // "sin comprobar" real de "aún no hay ninguna versión".
            'revision_malla' => $vigente['revision_malla'] ?? null,
            'tiene_vigente'  => $vigente !== null,
            // La foto en el listado: en una lista de treinta nombres, «Cabeza
            // – calva» y «Cabeza – base» son la misma línea de texto, y hay
            // que entrar en las dos para saber cuál es cuál. Misma versión
            // que el STL de arriba (la vigente) para que la foto y el estado
            // de la fila hablen de lo mismo.
            'miniatura' => $this->fotosDe($vigente, $variante)['miniatura'],
        ];
    }

    /**
     * @return array{aplica: bool, trozos: int, version_id: int|null, stl_id: int|null}
     *         aplica=false si no hay ninguna versión todavía: no es que falte
     *         el STL, es que aún no hay nada que exportar. version_id es de
     *         esa misma versión, para poder ofrecer su .blend desde el
     *         índice. stl_id solo se rellena cuando hay exactamente un STL
     *         (descarga directa desde el índice); con varios trozos hay que
     *         elegir cuál, así que el índice manda a la ficha en su lugar.
     */
    private function estadoStl(?array $version, ?array $stls = null): array
    {
        if (!$version) {
            return ['aplica' => false, 'trozos' => 0, 'version_id' => null, 'stl_id' => null];
        }

        $stls ??= $this->servicio->stlsDe((int) $version['id']);

        return [
            'aplica'     => true,
            'trozos'     => count($stls),
            'version_id' => (int) $version['id'],
            'stl_id'     => count($stls) === 1 ? (int) $stls[0]['id'] : null,
        ];
    }

    /**
     * Estado de las medidas de placa de un conjunto de STL (los trozos de la
     * versión vigente). Una pieza a trozos ocupa la SUMA de las cajas de sus
     * partes, así que el índice mira el conjunto: solo cuenta como "medida"
     * si todos los trozos lo están, y el área es la suma.
     *
     * `aplica=false` si no hay ningún STL: no faltan medidas, es que aún no
     * hay nada que medir.
     *
     * @param list<array> $stls filas de piezas_version_stls
     * @return array{aplica: bool, completas: bool, medidos: int, total: int, area_mm2: float}
     */
    private function estadoMedidasPlaca(array $stls): array
    {
        $total = count($stls);
        if ($total === 0) {
            return ['aplica' => false, 'completas' => false, 'medidos' => 0, 'total' => 0, 'area_mm2' => 0.0];
        }

        $medidos = 0;
        $areaMm2 = 0.0;
        foreach ($stls as $stl) {
            if ($stl['ancho_mm'] !== null && $stl['fondo_mm'] !== null) {
                $medidos++;
                $areaMm2 += (float) $stl['ancho_mm'] * (float) $stl['fondo_mm'];
            }
        }

        return [
            'aplica'    => true,
            'completas' => $medidos === $total,
            'medidos'   => $medidos,
            'total'     => $total,
            'area_mm2'  => $areaMm2,
        ];
    }

    private function versionDeOrigen(array $variante): ?array
    {
        if (empty($variante['origen_version_id'])) {
            return null;
        }

        $version = $this->versionModel->find($variante['origen_version_id']);
        if (!$version) {
            return null;
        }

        return $version + ['variante' => $this->varianteModel->find($version['variante_id'])];
    }

    private function sesionesDeRama(?array $rama): array
    {
        if (!$rama) {
            return [];
        }

        $sesiones = $this->sesionModel->where('rama_id', $rama['id'])->orderBy('numero', 'DESC')->findAll();

        return array_map(fn($s) => $s + [
            'maquina' => $this->sync->nombreDeMaquina((int) $s['maquina_id']),
            // Histórico de subidas de esta sesión (fase 41): antes cada
            // `subir` pisaba el .blend anterior, así que solo quedaba el
            // último. Se enseñan aparte para poder revisar/descargar
            // cualquier punto intermedio, no solo el que sobrevivió.
            'subidas' => $this->subidaModel->deSesion((int) $s['id']),
        ], $sesiones);
    }

    /**
     * Cuánto trabajo hubo detrás de una versión, y cuánto de él ya se purgó
     * — más la lista completa (con sus subidas), para poder verla y liberar
     * sitio a mano igual que en "Trabajo en curso". Es la única ventana a
     * las ramas cerradas: sus sesiones no se listan ahí (ya no lo son) pero
     * su rastro es justo lo que da sentido al historial dentro de tres
     * meses, y algunas (las que su versión nunca llegó a validarse) pueden
     * seguir sin purgar años después si nadie las aparta a mano.
     */
    private function sesionesQueLlevaronA(int $versionId): array
    {
        $rama = $this->ramaModel->where('cerrada_por_version_id', $versionId)->first();
        if (!$rama) {
            return ['total' => 0, 'purgadas' => 0, 'lista' => []];
        }

        $lista = $this->sesionesDeRama($rama);

        return [
            'total'    => count($lista),
            'purgadas' => count(array_filter($lista, fn($s) => !empty($s['purgada']))),
            'lista'    => $lista,
        ];
    }

    /**
     * Las piezas que ya se anotaron como "presentes en la escena" de esta
     * variante, con lo necesario para leerlas de un vistazo: de qué pieza y
     * variante son, en qué estado sigue esa versión (para el aviso pasivo
     * si ya quedó superada/descartada — spec 11.1 ampliado).
     */
    private function componentesDe(int $varianteId): array
    {
        $filas = $this->composicionModel->where('variante_id', $varianteId)->orderBy('creado_en', 'ASC')->findAll();

        return array_map(function ($fila) {
            $version  = $this->versionModel->find($fila['version_componente_id']);
            $variante = $version ? $this->varianteModel->find($version['variante_id']) : null;
            $familia  = $variante ? $this->familiaModel->find($variante['familia_id']) : null;

            return $fila + ['version' => $version, 'variante' => $variante, 'familia' => $familia];
        }, $filas);
    }

    /**
     * Candidatas para el selector de "añadir componente": todas las
     * versiones de las demás piezas activas (ni la propia variante, para
     * que no puedas elegirte a ti mismo, ni las que están en la papelera).
     * No se filtra por estado — una versión ya superada puede seguir
     * siendo, con toda razón, la que estaba en la escena en su momento.
     */
    private function versionesParaComponer(int $varianteExcluidaId): array
    {
        $activas = array_column($this->familiaModel->where('borrado_en', null)->findAll(), 'id');
        if ($activas === []) {
            return [];
        }

        $nombresFamilia = array_column($this->familiaModel->whereIn('id', $activas)->findAll(), 'nombre', 'id');

        $variantes = $this->varianteModel->whereIn('familia_id', $activas)
            ->where('borrado_en', null)->where('id !=', $varianteExcluidaId)->findAll();
        if ($variantes === []) {
            return [];
        }
        $familiaDeVariante = array_column($variantes, 'familia_id', 'id');
        $nombreDeVariante  = array_column($variantes, 'nombre', 'id');

        $versiones = $this->versionModel
            ->whereIn('variante_id', array_keys($familiaDeVariante))
            ->orderBy('variante_id', 'ASC')->orderBy('numero', 'DESC')
            ->findAll();

        return array_map(fn($v) => $v + [
            'familia_nombre'  => $nombresFamilia[$familiaDeVariante[$v['variante_id']]] ?? '?',
            'variante_nombre' => $nombreDeVariante[$v['variante_id']] ?? '?',
        ], $versiones);
    }

    private function descripcionBloqueo(?array $sesionAbierta, array $descargasPendientes = []): ?array
    {
        if (!$sesionAbierta) {
            return null;
        }

        // Si esta sesión ya tiene una descarga sin cerrar, esa descarga
        // aparece aparte (en $pendientes) con su propio botón de cierre
        // forzado, y ese cierre se lleva la sesión por delante (spec 4.4).
        // Ofrecer aquí un segundo botón para lo mismo sería redundante, y
        // PiezaSyncService::forzarCierreSesion lo rechazaría de todos modos.
        $tieneDescargaAbierta = (bool) array_filter(
            $descargasPendientes,
            fn($d) => (int) ($d['sesion_id'] ?? 0) === (int) $sesionAbierta['id']
        );

        return [
            'id'       => (int) $sesionAbierta['id'],
            'maquina'  => $this->sync->nombreDeMaquina((int) $sesionAbierta['maquina_id']) ?? 'otra máquina',
            'numero'   => (int) $sesionAbierta['numero'],
            'desde'    => $sesionAbierta['abierta_en'],
            'dias'     => $this->diasDesde($sesionAbierta['abierta_en']),
            'forzable' => !$tieneDescargaAbierta,
        ];
    }

    private function descripcionPendientes(array $descargas): array
    {
        return array_map(fn($d) => [
            'id'      => (int) $d['id'],
            'maquina' => $this->sync->nombreDeMaquina((int) $d['maquina_id']) ?? 'máquina desconocida',
            'motivo'  => $d['motivo'],
            'fecha'   => $d['descargado_en'],
            'dias'    => $this->diasDesde($d['descargado_en']),
        ], $descargas);
    }

    /**
     * Qué se puede hacer ahora mismo y, cuando no se puede, por qué. Los
     * botones se muestran siempre (spec 7.1: deshabilitados con explicación,
     * nunca ocultos — el usuario debe entender qué le falta para poder).
     */
    private function accionesDisponibles(array $estado, array $versiones): array
    {
        $motivos = [];

        if (!$estado['rama']) {
            $motivos['promocionar'] = 'No hay ninguna rama de trabajo abierta.';
        } elseif ($estado['sesion_abierta']) {
            $motivos['promocionar'] = 'Hay una sesión sin cerrar: lo que no esté subido no entraría en la versión.';
        } elseif (!$estado['ultima_subida']) {
            $motivos['promocionar'] = 'Todavía no hay ninguna sesión subida en esta rama. Sube el .blend primero.';
        }

        // Invariante 9: mientras haya una impresión sin juzgar no se abre
        // trabajo nuevo. Va antes que el aviso de sesión abierta porque es la
        // condición de fondo: cerrar la sesión no desbloquea nada.
        $sinJuzgar = array_values(array_filter($versiones, static fn($v) => $v['estado'] === 'impresa'));

        if ($sinJuzgar !== []) {
            $motivos['devolver'] = 'Hay una impresión sin juzgar: di si sirve o descártala antes de abrir trabajo nuevo.';
        } elseif ($estado['sesion_abierta']) {
            $motivos['devolver'] = 'Hay una sesión sin cerrar. Ciérrala antes de cambiar de línea de trabajo.';
        }

        return [
            'puede_promocionar' => !isset($motivos['promocionar']),
            'puede_devolver'    => !isset($motivos['devolver']),
            'motivos'           => $motivos,
            // Para que la ficha pueda decirlo arriba, no solo al pulsar: es lo
            // que impide trabajar desde el CLI, y ahí el usuario no ve botones.
            'sin_juzgar' => array_map(static fn($v) => (int) $v['numero'], $sinJuzgar),
            // La versión de la que ya parte la rama abierta (spec: "Devolver
            // a trabajo" en ESA versión sería cerrar esa misma rama para
            // abrir una idéntica — nada que devolver, solo confusión). Las
            // demás versiones sí pueden ofrecerlo con normalidad.
            'rama_desde_version_id' => $estado['rama']['desde_version_id'] ?? null,
        ];
    }

    private function llevaDemasiadoPendiente(array $version): bool
    {
        return in_array($version['estado'], ['borrador', 'impresa'], true)
            && $this->diasDesde($version['promocionada_en']) >= self::DIAS_PENDIENTE_DE_JUICIO;
    }

    private function diasDesde(?string $fecha): int
    {
        if (!$fecha) {
            return 0;
        }

        return (int) floor((time() - strtotime($fecha)) / 86400);
    }
}
