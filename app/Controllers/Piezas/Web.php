<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaComposicionModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaMaquinaModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaReferenciaModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use App\Services\PiezaAlmacen;
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
    private PiezaReferenciaModel $referenciaModel;
    private PiezaRenderModel $renderModel;
    private PiezaComposicionModel $composicionModel;
    private PiezaService $servicio;
    private PiezaSyncService $sync;
    private PiezaAlmacen $almacen;

    public function __construct()
    {
        $this->categoriaModel   = new PiezaCategoriaModel();
        $this->maquinaModel     = new PiezaMaquinaModel();
        $this->familiaModel     = new PiezaFamiliaModel();
        $this->varianteModel    = new PiezaVarianteModel();
        $this->versionModel     = new PiezaVersionModel();
        $this->ramaModel        = new PiezaRamaModel();
        $this->sesionModel      = new PiezaSesionModel();
        $this->referenciaModel  = new PiezaReferenciaModel();
        $this->renderModel      = new PiezaRenderModel();
        $this->composicionModel = new PiezaComposicionModel();
        $this->servicio         = new PiezaService();
        $this->sync             = new PiezaSyncService();
        $this->almacen          = new PiezaAlmacen();
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
            $familia['variantes'] = array_map(
                fn($v) => $this->resumen($v),
                $this->varianteModel->where('familia_id', $familia['id'])->orderBy('nombre', 'ASC')->findAll()
            );
        }
        unset($familia);

        return view('piezas/index', [
            'categorias'    => $this->categoriaModel->ordenadas(),
            'grupos'        => $this->agruparPorCategoria($familias),
            'familias'      => $familias,
            'carritoCount'  => count($this->carritoActual()),
            'papeleraCount' => $this->familiaModel->where('borrado_en IS NOT NULL')->countAllResults(),
        ]);
    }

    /**
     * Reparte las piezas en sus categorías, respetando el orden que el
     * usuario les dio. Las categorías vacías se quedan igualmente: son
     * carpetas, y una carpeta vacía sigue diciendo dónde va lo que llegue.
     * Las piezas sin clasificar van al final, en un grupo sin categoría que
     * solo aparece si hay alguna — no es una categoría más, es un "todavía
     * no colocadas".
     *
     * @return list<array{categoria: array|null, piezas: list<array>}>
     */
    private function agruparPorCategoria(array $familias): array
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

        return $grupos;
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
            $bytesVersiones += $this->almacen->tamano($version['ruta_stl']) ?? 0;
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

    public function crearCategoria()
    {
        return $this->ejecutar(
            fn() => $this->servicio->crearCategoria((string) $this->request->getPost('nombre')),
            fn() => site_url('piezas'),
            fn($categoria) => 'Categoría "' . $categoria['nombre'] . '" creada.'
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
            $version['tamano_stl']          = $this->almacen->tamano($version['ruta_stl']);
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

        return view('piezas/variante', [
            'variante'  => $variante,
            'familia'   => $this->familiaModel->find($variante['familia_id']),
            // Comunes a toda la pieza, no por variante (spec 1.1): las
            // referencias del original ayudan a modelar cualquiera de sus
            // variantes, y es aquí — no en el índice — donde se miran.
            'referencias' => $this->referenciaModel
                ->where('familia_id', $variante['familia_id'])->orderBy('subida_en', 'DESC')->findAll(),
            'origen'    => $this->versionDeOrigen($variante),
            'versiones' => $versiones,
            'validada'  => $this->versionModel->where('variante_id', $id)->where('estado', 'validada')->first(),
            'rama'      => $estado['rama'],
            'ramaNombre' => $estado['rama'] ? $this->ramaModel->nombre($estado['rama']) : null,
            'sesiones'  => $this->sesionesDeRama($estado['rama']),
            'estado'    => $estado,
            'bloqueo'   => $this->descripcionBloqueo($estado['sesion_abierta'], $estado['descargas_pendientes']),
            'pendientes' => $this->descripcionPendientes($estado['descargas_pendientes']),
            'acciones'  => $this->accionesDisponibles($estado, $versiones),
            'familias'  => $this->familiaModel->orderBy('nombre', 'ASC')->findAll(),
            'carrito'   => $this->carritoActual(),
            // "Compuesta de" (spec 11.1 ampliado): qué otras piezas estaban
            // en la escena de esta variante. Puramente informativo.
            'componentes'           => $this->componentesDe($id),
            'versionesParaComponer' => $this->versionesParaComponer($id),
            'sugerenciaImpresion'   => $sugerenciaImpresion,
            'estadisticasPieza'     => $estadisticasPieza,
        ]);
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
        foreach ($this->varianteModel->whereIn('familia_id', $activas)->select('familia_id')->findAll() as $v) {
            $fid = (int) $v['familia_id'];
            $conteoVariantes[$fid] = ($conteoVariantes[$fid] ?? 0) + 1;
        }

        foreach ($this->varianteModel->whereIn('familia_id', $activas)->findAll() as $variante) {
            $validada = $this->versionModel
                ->where('variante_id', $variante['id'])->where('estado', 'validada')->first();
            if (!$validada) {
                continue;
            }

            $render = $this->renderModel
                ->where('version_id', $validada['id'])->orderBy('subida_en', 'DESC')->first();
            $miniatura = $render ? site_url('piezas/render/' . $render['id'] . '/imagen') : null;

            if (!$miniatura) {
                $referencia = $this->referenciaModel
                    ->where('familia_id', $variante['familia_id'])->orderBy('subida_en', 'DESC')->first();
                $miniatura = $referencia ? site_url('piezas/referencia/' . $referencia['id'] . '/imagen') : null;
            }

            $familiaId = (int) $variante['familia_id'];

            $piezas[] = [
                'variante'        => $variante,
                'familiaNombre'   => $nombresFamilia[$familiaId] ?? '?',
                'variosVariantes' => ($conteoVariantes[$familiaId] ?? 0) > 1,
                'validada'        => $validada,
                'miniatura'       => $miniatura,
                // Mismo campo que espera agruparPorCategoria() para las
                // filas del índice: se reutiliza tal cual, sin duplicar la
                // lógica de reparto por categoría (spec 11.1).
                'categoria_id'    => $categoriaDeFamilia[$familiaId] ?? null,
            ];
        }

        // Se ordena por pieza, no por variante: es lo que ahora encabeza la
        // tarjeta, y es lo que se busca de un vistazo en una parrilla.
        usort($piezas, fn($a, $b) => [$a['familiaNombre'], $a['variante']['nombre']] <=> [$b['familiaNombre'], $b['variante']['nombre']]);

        return view('piezas/galeria', [
            'grupos'  => $this->agruparPorCategoria($piezas),
            'carrito' => $this->carritoActual(),
        ]);
    }

    public function carritoAgregar(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version || empty($version['ruta_stl'])) {
            return redirect()->back()->with('error', 'Esa versión no tiene STL adjunto: no se puede añadir a la placa.');
        }

        $carrito = $this->carritoActual();
        if (!in_array($versionId, $carrito, true)) {
            $carrito[] = $versionId;
            $this->carritoGuardar($carrito);
        }

        return redirect()->back()->with('success', 'Añadida a la placa.');
    }

    public function carritoQuitar(int $versionId)
    {
        $this->carritoGuardar(array_values(array_diff($this->carritoActual(), [$versionId])));

        return redirect()->back()->with('success', 'Quitada de la placa.');
    }

    public function carritoVaciar()
    {
        $this->carritoGuardar([]);

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

        $versiones = array_filter(
            $this->versionModel->whereIn('id', $carrito)->findAll(),
            fn($v) => !empty($v['ruta_stl']) && $this->almacen->existe($v['ruta_stl'])
        );
        if (empty($versiones)) {
            return redirect()->to(site_url('piezas/galeria'))
                ->with('error', 'Ninguno de los STL de la placa está ya disponible en el almacén.');
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
        foreach ($versiones as $version) {
            $variante = $this->varianteModel->find($version['variante_id']);
            $zip->addFile($this->almacen->absoluta($version['ruta_stl']), $this->nombreArchivo($variante, $version, 'stl'));
        }
        $zip->close();

        // El fichero tiene que seguir existiendo cuando DownloadResponse lo
        // lea durante send(), que ocurre después de que este método
        // retorne — por eso el borrado va en un shutdown function, no aquí.
        register_shutdown_function(static function () use ($rutaZip) {
            @unlink($rutaZip);
        });

        return $this->response->download($rutaZip, null, true);
    }

    /**
     * Nombre con el que se descarga un fichero de una versión. Lleva el SKU
     * delante cuando lo hay: es el código por el que se pide la pieza fuera
     * de Trackbitos, y es lo que la hace reconocible en la carpeta de
     * descargas o dentro del laminador, donde ya no está la ficha al lado
     * para mirarlo.
     */
    private function nombreArchivo(?array $variante, array $version, string $extension): string
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

        return sprintf('%s-v%03d.%s', implode('-', $partes), (int) $version['numero'], $extension);
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
                $this->request->getPost('sku') ?: null,
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
     * La papelera de piezas: lo que se borró y todavía está a tiempo de
     * volver. Ordenada por fecha de borrado, la más reciente primero —
     * es la que con más probabilidad se quiere deshacer.
     */
    public function papelera()
    {
        return view('piezas/papelera', [
            'familias' => $this->familiaModel
                ->where('borrado_en IS NOT NULL')
                ->orderBy('borrado_en', 'DESC')
                ->findAll(),
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

    public function crearVariante()
    {
        return $this->ejecutar(
            fn() => $this->servicio->crearVariante(
                (int) $this->request->getPost('familia_id'),
                trim((string) $this->request->getPost('nombre')),
                $this->request->getPost('notas') ?: null,
                $this->request->getPost('sku') ?: null
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

    public function editarSku(int $varianteId)
    {
        return $this->ejecutar(
            fn() => $this->servicio->actualizarSku($varianteId, $this->request->getPost('sku')),
            fn($variante) => site_url('piezas/variante/' . $variante['id']),
            fn($variante) => $variante['sku']
                ? 'SKU actualizado: ' . $variante['sku'] . '.'
                : 'SKU quitado.'
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
     * Aparta a mano el .blend de una sesión ya cerrada y sin promocionar
     * (p. ej. una subida de prueba demasiado pesada que se va a reemplazar):
     * libera el sitio en disco sin perder el registro de que existió.
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

    // ---- Imágenes: referencias (familia) y renders (versión) ------------

    public function subirReferencia(int $familiaId)
    {
        $familia = $this->familiaModel->find($familiaId);
        if (!$familia) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa pieza no existe.');
        }

        return $this->ejecutar(
            function () use ($familiaId) {
                $extension = $this->validarImagen($this->request->getFile('imagen'));

                $id = $this->referenciaModel->insert([
                    'familia_id' => $familiaId,
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

                $this->referenciaModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $this->almacen->hash($ruta),
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                return $familiaId;
            },
            fn() => $this->vueltaALaFicha($familiaId),
            fn() => 'Referencia añadida.'
        );
    }

    /**
     * A dónde volver tras tocar una referencia. Se sube desde la ficha de
     * una variante concreta, pero la referencia es de la pieza entera, así
     * que el destino viene en el formulario en vez de deducirse. Se
     * comprueba que esa variante sea de esta pieza: es lo que impide
     * convertir el campo en un redirector a cualquier sitio.
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
        $destino = $this->vueltaALaFicha((int) $referencia['familia_id']);

        $this->almacen->aPapelera($referencia['ruta_imagen']);
        $this->referenciaModel->delete($id);

        return redirect()->to($destino)->with('success', 'Referencia apartada a la papelera.');
    }

    public function subirRender(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version) {
            return redirect()->to(site_url('piezas'))->with('error', 'Esa versión no existe.');
        }

        return $this->verboDeVersion(
            $versionId,
            function () use ($versionId, $version) {
                $extension = $this->validarImagen($this->request->getFile('imagen'));

                $id = $this->renderModel->insert([
                    'version_id'  => $versionId,
                    'ruta_imagen' => '',
                    'notas'       => trim((string) $this->request->getPost('notas')) ?: null,
                    'subida_en'   => date('Y-m-d H:i:s'),
                ], true);
                if (!$id) {
                    throw new RuntimeException('No se pudo registrar el render: ' . implode(' ', $this->renderModel->errors()));
                }

                $ruta = $this->almacen->rutaRender((int) $version['variante_id'], $versionId, $id, $extension);
                $this->almacen->guardar($this->request->getFile('imagen')->getTempName(), $ruta);

                $this->renderModel->update($id, [
                    'ruta_imagen'  => $ruta,
                    'hash_imagen'  => $this->almacen->hash($ruta),
                    'tamano_bytes' => filesize($this->almacen->absoluta($ruta)),
                ]);

                return $version;
            },
            fn() => sprintf('Render añadido a v%03d.', (int) $version['numero'])
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
        $this->renderModel->delete($id);

        $destino = $version ? site_url('piezas/variante/' . $version['variante_id']) : site_url('piezas');

        return redirect()->to($destino)->with('success', 'Render apartado a la papelera.');
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

                // Comprobar ANTES de tocar disco: PiezaService::adjuntarStl
                // repite este chequeo, pero si se hiciera solo ahí, un
                // segundo intento ya habría sobrescrito el fichero de la
                // ruta (determinista por variante+número) antes de que la
                // base de datos lo rechazara.
                if (!empty($version['ruta_stl'])) {
                    throw new RuntimeException(
                        "La versión {$versionId} ya tiene un STL adjunto. Es inmutable: "
                        . 'si el modelo cambió, promociona una versión nueva.'
                    );
                }

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

                $ruta = $this->almacen->rutaStl((int) $version['variante_id'], (int) $version['numero']);
                $this->almacen->guardar($file->getTempName(), $ruta);
                $hash = $this->almacen->hash($ruta);

                return $this->servicio->adjuntarStl($versionId, $ruta, $hash);
            },
            fn($version) => sprintf('STL adjuntado a v%03d. Ya se puede descargar para imprimir.', (int) $version['numero'])
        );
    }

    /**
     * A diferencia de las imágenes, el STL se sirve para descargar (no
     * inline): se abre en el laminador, no en el navegador.
     */
    public function descargarStl(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version || empty($version['ruta_stl']) || !$this->almacen->existe($version['ruta_stl'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $variante = $this->varianteModel->find($version['variante_id']);

        // En disco el fichero se llama version-v002.stl (nombres derivados de
        // IDs, spec 8); quien lo descarga necesita saber de qué pieza es.
        return $this->response->download($this->almacen->absoluta($version['ruta_stl']), null, true)
            ->setFileName($this->nombreArchivo($variante, $version, 'stl'));
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

        return $this->response->download($this->almacen->absoluta($version['ruta_blend']), null, true)
            ->setFileName($this->nombreArchivo($variante, $version, 'blend'));
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

        // setMime=true para que el Content-Type salga del tipo real de la
        // imagen, e inline() para que el navegador la pinte en el <img> en
        // vez de ofrecer descargarla.
        return $this->response->download($this->almacen->absoluta($referencia['ruta_imagen']), null, true)->inline();
    }

    public function imagenRender(int $id)
    {
        $render = $this->renderModel->find($id);
        if (!$render || !$this->almacen->existe($render['ruta_imagen'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($this->almacen->absoluta($render['ruta_imagen']), null, true)->inline();
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

            return redirect()->back()->withInput()->with('error', $e->getMessage());
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

        return $variante + [
            'validada'      => $validada,
            'versiones'     => $this->versionModel->where('variante_id', $variante['id'])->countAllResults(),
            'bloqueo'       => $this->descripcionBloqueo($estado['sesion_abierta']),
            'pendientes'    => $this->descripcionPendientes($estado['descargas_pendientes']),
            // Sin esto, "sin versión buena" no distinguía entre "nunca se ha
            // llegado a imprimir" y "impresa, pendiente de juzgar" o "la
            // última se descartó" — todo se veía igual en el listado.
            'ultima_version_estado' => $ultimaVersion['estado'] ?? null,
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

        return array_map(fn($s) => $s + ['maquina' => $this->sync->nombreDeMaquina((int) $s['maquina_id'])], $sesiones);
    }

    /**
     * Cuánto trabajo hubo detrás de una versión, y cuánto de él ya se purgó.
     * Es la única ventana a las ramas cerradas: sus sesiones no se listan en
     * "trabajo en curso" (ya no lo son) pero su rastro es justo lo que da
     * sentido al historial dentro de tres meses.
     */
    private function sesionesQueLlevaronA(int $versionId): array
    {
        $rama = $this->ramaModel->where('cerrada_por_version_id', $versionId)->first();
        if (!$rama) {
            return ['total' => 0, 'purgadas' => 0];
        }

        $sesiones = $this->sesionModel->where('rama_id', $rama['id'])->findAll();

        return [
            'total'    => count($sesiones),
            'purgadas' => count(array_filter($sesiones, fn($s) => !empty($s['purgada']))),
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

        $variantes = $this->varianteModel->whereIn('familia_id', $activas)->where('id !=', $varianteExcluidaId)->findAll();
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

        if ($estado['sesion_abierta']) {
            $motivos['devolver'] = 'Hay una sesión sin cerrar. Ciérrala antes de cambiar de línea de trabajo.';
        }

        return [
            'puede_promocionar' => !isset($motivos['promocionar']),
            'puede_devolver'    => !isset($motivos['devolver']),
            'motivos'           => $motivos,
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
