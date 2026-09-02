<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaComposicionModel;
use App\Models\PiezaDescargaModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaMaquinaModel;
use App\Models\PiezaPlacaModel;
use App\Models\PiezaPlacaVersionModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaService;
use App\Services\PiezaSyncService;
use RuntimeException;
use Throwable;

/**
 * Endpoints que consume piezas-cli/trackbitos.py. Todo responde JSON, sin
 * vistas. Autenticación: Bearer token único (filtro 'piezasApi'), no
 * Myth\Auth — ver App\Filters\PiezasApiAuth.
 *
 * La identidad de máquina va aparte del token (spec 4.5): la declara el
 * cliente con su UUID, en la cabecera X-Maquina-Uuid (o en el cuerpo, para
 * /maquina/registrar). El token dice "eres tú"; el UUID dice "desde qué
 * disco", que es lo que de verdad importa para cuadrar los asientos.
 *
 * Casi todo lo que hay aquí es traducción HTTP: la lógica vive en
 * PiezaService (verbos) y PiezaSyncService (descarga/subida). Este
 * controlador no decide nada de dominio.
 */
class Api extends BaseController
{
    private PiezaVarianteModel $varianteModel;
    private PiezaVersionModel $versionModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;
    private PiezaDescargaModel $descargaModel;
    private PiezaMaquinaModel $maquinaModel;
    private PiezaPlacaModel $placaModel;
    private PiezaPlacaVersionModel $placaVersionModel;
    private PiezaComposicionModel $composicionModel;
    private PiezaService $servicio;
    private PiezaSyncService $sync;
    private PiezaAlmacen $almacen;

    public function __construct()
    {
        $this->varianteModel    = new PiezaVarianteModel();
        $this->versionModel     = new PiezaVersionModel();
        $this->ramaModel        = new PiezaRamaModel();
        $this->sesionModel      = new PiezaSesionModel();
        $this->descargaModel    = new PiezaDescargaModel();
        $this->maquinaModel     = new PiezaMaquinaModel();
        $this->placaModel       = new PiezaPlacaModel();
        $this->placaVersionModel = new PiezaPlacaVersionModel();
        $this->composicionModel = new PiezaComposicionModel();
        $this->servicio         = new PiezaService();
        $this->sync             = new PiezaSyncService();
        $this->almacen          = new PiezaAlmacen();
    }

    /**
     * Alta o "ping" de máquina. El uuid lo genera y guarda el cliente en su
     * primer arranque; hostname/so solo proponen un nombre por defecto.
     */
    public function registrarMaquina()
    {
        $uuid = trim((string) $this->request->getJsonVar('uuid'));
        if ($uuid === '') {
            return $this->response->setJSON(['error' => 'uuid es obligatorio'])->setStatusCode(422);
        }

        $hostname = $this->request->getJsonVar('hostname');
        $so       = $this->request->getJsonVar('so');

        $maquina = $this->maquinaModel->registrar(
            $uuid,
            $hostname !== null ? (string) $hostname : null,
            $so !== null ? (string) $so : null
        );

        return $this->response->setJSON($maquina);
    }

    /**
     * Lista de variantes con estado resumido (spec 7.1: de un vistazo, cuál
     * es la buena y dónde está el trabajo en curso). La consume tanto
     * "trackbitos estado" (indirectamente) como "trackbitos catalogo", que
     * es el catálogo visto desde la terminal — con categoría incluida para
     * que se pueda agrupar igual que el índice y la galería web.
     *
     * Las piezas en la papelera no aparecen: no son parte del catálogo de
     * trabajo mientras dura su plazo de gracia (invariante 6), igual que ya
     * pasa en /piezas y /piezas/galeria.
     */
    public function variantes()
    {
        $categoriasOrdenadas = (new PiezaCategoriaModel())->ordenadas();
        $categorias          = array_column($categoriasOrdenadas, 'nombre', 'id');

        $familias = [];
        foreach ((new PiezaFamiliaModel())->where('borrado_en', null)->findAll() as $f) {
            $categoriaId = $f['categoria_id'] !== null ? (int) $f['categoria_id'] : null;

            $familias[(int) $f['id']] = [
                'nombre'           => $f['nombre'],
                'categoria_id'     => $categoriaId,
                'categoria_nombre' => $categoriaId !== null ? ($categorias[$categoriaId] ?? null) : null,
            ];
        }

        $variantes = $familias === []
            ? []
            : $this->varianteModel->whereIn('familia_id', array_keys($familias))
                ->where('borrado_en', null)->orderBy('nombre', 'ASC')->findAll();

        return $this->response->setJSON([
            // El orden en que el usuario colocó sus categorías (spec 11.1),
            // no el alfabético: es el mismo criterio que ya usan el índice y
            // la galería, y "trackbitos catalogo" lo necesita para agrupar
            // igual sin tener que adivinar un orden por su cuenta.
            'categorias' => array_column($categoriasOrdenadas, 'nombre'),
            'variantes'  => array_map(fn($v) => $this->resumenVariante($v, $familias), $variantes),
        ]);
    }

    /**
     * Estado completo de una variante: rama abierta, hash de la nube
     * (última sesión subida en esa rama), bloqueo de máquina y descargas
     * pendientes. Es lo que consume "trackbitos estado" para las cuatro
     * filas de la tabla 4.3 — hash_nube es el campo clave.
     */
    public function varianteEstado(int $id)
    {
        $variante = $this->varianteModel->find($id);
        if (!$variante) {
            return $this->response->setJSON(['error' => 'Variante no encontrada.'])->setStatusCode(404);
        }

        $estado         = $this->sync->estadoDeSincronizacion($id);
        $rama           = $estado['rama'];
        $ultimaSubida   = $estado['ultima_subida'];
        $sesionAbierta  = $estado['sesion_abierta'];
        $origenDescarga = $estado['origen_descarga'];

        return $this->response->setJSON([
            'variante_id' => (int) $variante['id'],
            'variante'    => $variante['nombre'],
            'rama'        => $rama ? [
                'id'         => (int) $rama['id'],
                'nombre'     => $this->ramaModel->nombre($rama),
                'abierta_en' => $rama['abierta_en'],
            ] : null,
            'hash_nube'            => $estado['hash_nube'],
            'origen_descarga'      => $origenDescarga,
            'ultima_sesion_subida' => $ultimaSubida ? [
                'id'         => (int) $ultimaSubida['id'],
                'numero'     => (int) $ultimaSubida['numero'],
                'subida_en'  => $ultimaSubida['subida_en'],
                'maquina_id' => (int) $ultimaSubida['maquina_id'],
            ] : null,
            'sesion_abierta' => $sesionAbierta ? [
                'id'             => (int) $sesionAbierta['id'],
                'numero'         => (int) $sesionAbierta['numero'],
                'maquina_id'     => (int) $sesionAbierta['maquina_id'],
                'maquina_nombre' => $this->nombreMaquina((int) $sesionAbierta['maquina_id']),
                'abierta_en'     => $sesionAbierta['abierta_en'],
            ] : null,
            'descargas_pendientes' => array_map(fn($d) => [
                'id'             => (int) $d['id'],
                'maquina_id'     => (int) $d['maquina_id'],
                'maquina_nombre' => $this->nombreMaquina((int) $d['maquina_id']),
                'motivo'         => $d['motivo'],
                'descargado_en'  => $d['descargado_en'],
            ], $estado['descargas_pendientes']),
        ]);
    }

    // ---- Sesiones de trabajo ------------------------------------------

    /**
     * Abrir sesión sin descargar nada: el caso de estrenar variante, cuando
     * todavía no hay ningún .blend del que partir. Lo normal es bajar, que
     * abre la sesión y entrega el fichero de una vez.
     */
    public function abrirSesion(int $varianteId)
    {
        return $this->responder(function () use ($varianteId) {
            $maquina = $this->exigirMaquina();
            $sesion  = $this->servicio->abrirSesion($varianteId, (int) $maquina['id']);

            return ['sesion' => $this->resumenSesion($sesion)];
        });
    }

    public function cerrarSesion(int $sesionId)
    {
        return $this->responder(function () use ($sesionId) {
            return ['sesion' => $this->resumenSesion($this->servicio->cerrarSesion($sesionId))];
        });
    }

    /**
     * Entrega el .blend y abre el asiento de descarga. Los datos del asiento
     * viajan en cabeceras porque el cuerpo es el fichero: el cliente los
     * necesita para escribir su .sesion.json y poder cuadrar después.
     */
    public function descargarSesion(int $sesionId)
    {
        $varianteId = $this->varianteDeLaPeticionOFallar();
        if ($varianteId instanceof \CodeIgniter\HTTP\Response) {
            return $varianteId;
        }

        return $this->entregarFichero(fn(int $maquinaId, string $motivo, bool $ignorar) => $this->sync->entregarSesion($sesionId, $varianteId, $maquinaId, $motivo, $ignorar));
    }

    public function descargarVersion(int $versionId)
    {
        $varianteId = $this->varianteDeLaPeticionOFallar();
        if ($varianteId instanceof \CodeIgniter\HTTP\Response) {
            return $varianteId;
        }

        return $this->entregarFichero(fn(int $maquinaId, string $motivo, bool $ignorar) => $this->sync->entregarVersion($versionId, $varianteId, $maquinaId, $motivo, $ignorar));
    }

    /**
     * El .blend inmutable de una versión concreta, de solo lectura — gemelo
     * API de Web::descargarBlend(). A diferencia de descargarVersion(), NO
     * abre asiento ni exige identidad de máquina (X-Maquina-Uuid): el
     * `.blend` de una versión ya promocionada no cambia, así que leerlo no
     * es "trabajar sobre una copia" que el sistema deba cuadrar luego. Sirve
     * tanto para la versión vigente de una variante como para versiones
     * viejas referenciadas desde una placa (stl.py `--como-anotado`).
     */
    public function descargarVersionBlend(int $versionId)
    {
        $version = $this->versionModel->find($versionId);
        if (!$version || !$this->almacen->existe($version['ruta_blend'] ?? null)) {
            return $this->response->setJSON(['error' => 'Versión no encontrada o sin .blend en el almacén.'])->setStatusCode(404);
        }

        $variante = $this->varianteModel->find($version['variante_id']);

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $this->nombreBlendVersion($variante, $version) . '"')
            ->setHeader('X-Hash-Blend', (string) $version['hash_blend'])
            ->setBody(file_get_contents($this->almacen->absoluta($version['ruta_blend'])));
    }

    /** Mismo esquema de nombre que Web::nombreArchivo(), sin sufijo: sku-familia-variante-vNNN.blend. */
    private function nombreBlendVersion(?array $variante, array $version): string
    {
        $familia = $variante ? (new PiezaFamiliaModel())->find($variante['familia_id']) : null;

        $partes = array_filter([
            $this->paraNombreDeArchivo($variante['sku'] ?? null),
            $this->paraNombreDeArchivo($familia['nombre'] ?? null),
            $this->paraNombreDeArchivo($variante['nombre'] ?? null) ?: 'variante-' . $version['variante_id'],
        ]);

        return sprintf('%s-v%03d.blend', implode('-', $partes), (int) $version['numero']);
    }

    /**
     * Histórico de placas (id, nombre, fecha) — hasta ahora eran solo-web
     * (PiezaPlacaModel/PiezaPlacaVersionModel). stl.py la usa para resolver
     * "placa <nombre>" a un id antes de pedir /placa/(:num).
     */
    public function placas()
    {
        $placas = $this->placaModel->orderBy('creado_en', 'DESC')->findAll();

        return $this->response->setJSON([
            'ok'     => true,
            'placas' => array_map(fn(array $p) => $this->resumenPlaca($p), $placas),
        ]);
    }

    /**
     * Qué versiones lleva una placa y con cuántas copias cada una, con lo
     * que stl.py necesita para reproducirla: familia/variante/categoría
     * (para el nombre de fichero), hash_blend (para pedir /version/(:num)/
     * blend) y qué STL ya tiene subidos y si siguen en el almacén. Mismo
     * dato que Web::piezasDeLaPlaca(), pero aplanado a JSON.
     */
    public function placa(int $placaId)
    {
        $placa = $this->placaModel->find($placaId);
        if (!$placa) {
            return $this->response->setJSON(['error' => 'Placa no encontrada.'])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'ok'         => true,
            'placa'      => $this->resumenPlaca($placa),
            'versiones'  => $this->versionesDePlaca($placaId),
        ]);
    }

    private function resumenPlaca(array $placa): array
    {
        return [
            'id'     => (int) $placa['id'],
            'nombre' => $placa['nombre'],
            'fecha'  => $placa['creado_en'],
        ];
    }

    /** @return array<int, array> una fila por versión de la placa, en el orden en que se añadieron */
    private function versionesDePlaca(int $placaId): array
    {
        $categorias   = array_column((new PiezaCategoriaModel())->findAll(), 'nombre', 'id');
        $familiaModel = new PiezaFamiliaModel();

        $lista = [];
        foreach ($this->placaVersionModel->where('placa_id', $placaId)->orderBy('id')->findAll() as $fila) {
            $version = $this->versionModel->find($fila['version_id']);
            if (!$version) {
                continue; // versión purgada (invariante 6): la fila ya no aporta nada al CLI
            }

            $variante    = $this->varianteModel->find($version['variante_id']);
            $familia     = $variante ? $familiaModel->find($variante['familia_id']) : null;
            $categoriaId = $familia['categoria_id'] ?? null;

            $stls = [];
            foreach ($this->servicio->stlsDe((int) $version['id']) as $stl) {
                $stls[] = [
                    'nombre'     => $stl['nombre'],
                    'hash'       => $stl['hash_stl'],
                    'disponible' => $this->almacen->existe($stl['ruta_stl']),
                ];
            }

            $lista[] = [
                'version_id'  => (int) $version['id'],
                // Añadido para stl.py `placa`: hace falta para pedir
                // /variante/(:num)/composicion y expandir recursivamente si
                // esta pieza es compuesta (decisión 11 del plan .blend->STL).
                'variante_id' => (int) $version['variante_id'],
                'familia'     => $familia['nombre'] ?? null,
                'variante'    => $variante['nombre'] ?? null,
                'categoria'   => $categoriaId !== null ? ($categorias[(int) $categoriaId] ?? null) : null,
                'numero'      => (int) $version['numero'],
                'estado'      => $version['estado'],
                'cantidad'    => (int) $fila['cantidad'],
                'hash_blend'  => $version['hash_blend'],
                'stls'        => $stls,
            ];
        }

        return $lista;
    }

    /**
     * "Compuesta de" (piezas_composiciones) traducido a JSON, mismo dato que
     * Web::componentesDe(): la pieza ES la suma de sus componentes (decisión
     * "caso 2 siempre" — sin geometría propia), así que stl.py expande
     * recursivamente llamando aquí por cada `variante_id` que devuelve, hasta
     * agotar componentes. Sin detección de ciclos aquí: hoy el servidor solo
     * impide que una variante se componga de sí misma, no ciclos
     * transitivos — stl.py es quien debe abortar si los encuentra.
     */
    public function composicion(int $varianteId)
    {
        if (!$this->varianteModel->find($varianteId)) {
            return $this->response->setJSON(['error' => 'Variante no encontrada.'])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'ok'          => true,
            'variante_id' => $varianteId,
            'componentes' => $this->componentesDeApi($varianteId),
        ]);
    }

    /**
     * Los STL ya subidos a mano (`piezas_version_stls`) de la versión para
     * imprimir de esta variante — para que `stl.py revisar` compare su hash
     * contra el que generaría ahora mismo desde el `.blend` y avise si han
     * divergido. Los dos sistemas conviven a propósito (decisión 6 del plan
     * .blend->STL: "el .blend genera, los STL subidos NO son autoridad") —
     * esto no es para fusionarlos, es para detectar cuándo se han
     * desincronizado.
     */
    public function stls(int $varianteId)
    {
        $variante = $this->varianteModel->find($varianteId);
        if (!$variante) {
            return $this->response->setJSON(['error' => 'Variante no encontrada.'])->setStatusCode(404);
        }

        $version = $this->versionParaImprimir($varianteId);
        if (!$version) {
            return $this->response->setJSON([
                'ok'          => true,
                'variante_id' => $varianteId,
                'version'     => null,
                'stls'        => [],
            ]);
        }

        $stls = [];
        foreach ($this->servicio->stlsDe((int) $version['id']) as $stl) {
            $stls[] = [
                'nombre'     => $stl['nombre'],
                'hash'       => $stl['hash_stl'],
                'disponible' => $this->almacen->existe($stl['ruta_stl']),
            ];
        }

        return $this->response->setJSON([
            'ok'          => true,
            'variante_id' => $varianteId,
            'version'     => $this->resumenVersion($version),
            'stls'        => $stls,
        ]);
    }

    /**
     * Réplica de Web::versionParaImprimir() (decisión 4 del plan
     * .blend->STL): la validada si la hay; si no, la última
     * borrador/impresa. Nunca descartada ni superada. Compartido por
     * resumenVariante() (campo `version_para_imprimir` del catálogo) y por
     * stls() — antes estaba duplicado en los dos sitios.
     */
    private function versionParaImprimir(int $varianteId): ?array
    {
        return $this->versionModel
            ->where('variante_id', $varianteId)
            ->whereIn('estado', ['validada', 'borrador', 'impresa'])
            ->orderBy('numero', 'DESC')
            ->first();
    }

    /** @return array<int, array> una fila por componente, en el orden en que se anotaron */
    private function componentesDeApi(int $varianteId): array
    {
        $categorias   = array_column((new PiezaCategoriaModel())->findAll(), 'nombre', 'id');
        $familiaModel = new PiezaFamiliaModel();

        $lista = [];
        foreach ($this->composicionModel->where('variante_id', $varianteId)->orderBy('creado_en', 'ASC')->findAll() as $fila) {
            $versionAnotada = $this->versionModel->find($fila['version_componente_id']);
            $componente     = $versionAnotada ? $this->varianteModel->find($versionAnotada['variante_id']) : null;
            $familia        = $componente ? $familiaModel->find($componente['familia_id']) : null;
            $categoriaId    = $familia['categoria_id'] ?? null;
            $versionVigente = $componente ? $this->versionVigenteDeVariante((int) $componente['id']) : null;

            $lista[] = [
                'variante_id'     => $componente ? (int) $componente['id'] : null,
                'variante'        => $componente['nombre'] ?? null,
                'familia'         => $familia['nombre'] ?? null,
                'categoria'       => $categoriaId !== null ? ($categorias[(int) $categoriaId] ?? null) : null,
                // Solo la nota histórica de "con qué versión se añadió" — la que
                // de verdad se usa para generar el STL es version_vigente.
                'notas'           => $fila['notas'],
                'version_anotada' => $versionAnotada ? $this->resumenVersion($versionAnotada) : null,
                'version_vigente' => $versionVigente ? $this->resumenVersion($versionVigente) : null,
            ];
        }

        return $lista;
    }

    /**
     * Réplica de Web::versionVigenteDeVariante(): la validada si la hay; si
     * no, la de número más alto (aunque sea descartada) — es el último
     * estado consolidado del modelo, no el "listo para imprimir" de
     * versionParaImprimir(). Es la que cuenta para "Compuesta de": una pieza
     * compuesta debe llevar la geometría más reciente de cada componente,
     * no quedarse en una validada vieja mientras hay trabajo posterior sin
     * promocionar a validada.
     */
    private function versionVigenteDeVariante(int $varianteId): ?array
    {
        $validada = $this->versionModel
            ->where('variante_id', $varianteId)
            ->where('estado', 'validada')
            ->first();
        if ($validada) {
            return $validada;
        }

        return $this->versionModel
            ->where('variante_id', $varianteId)
            ->orderBy('numero', 'DESC')
            ->first();
    }

    /** Deja solo lo que sobrevive intacto a cualquier sistema de ficheros. */
    private function paraNombreDeArchivo(?string $texto): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $texto), '-');
    }

    /**
     * Re-descarga de solo lectura de una sesión ya subida, para comparar a
     * ojo (fase 34). No crea ningún asiento ni sesión: `trackbitos subir` la
     * llama automáticamente justo después de subir, como comprobación en
     * paralelo, no como una mesa de trabajo nueva.
     */
    public function descargarVerificacion(int $sesionId)
    {
        $varianteId = $this->varianteDeLaPeticionOFallar();
        if ($varianteId instanceof \CodeIgniter\HTTP\Response) {
            return $varianteId;
        }

        try {
            $entrega = $this->sync->entregarParaVerificacion($sesionId, $varianteId);
        } catch (Throwable $e) {
            return $this->comoError($e);
        }

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $entrega['nombre_fichero'] . '"')
            ->setHeader('X-Hash-Blend', $entrega['hash'])
            ->setBody(file_get_contents($entrega['ruta_absoluta']));
    }

    /**
     * Lee 'variante' de la query string: la descarga siempre declara para qué
     * variante es (fase 34) — antes se deducía de la sesión/versión de
     * origen, que puede pertenecer a otra variante cuando viene de
     * `derivarVariante`. Devuelve el id, o una respuesta de error 422 ya
     * lista para retornar.
     */
    private function varianteDeLaPeticionOFallar()
    {
        $varianteId = (int) ($this->request->getGet('variante') ?? 0);
        if ($varianteId <= 0) {
            return $this->comoError(new RuntimeException(
                "Falta el parámetro 'variante': toda descarga debe declarar para qué variante es.",
                422
            ));
        }

        return $varianteId;
    }

    /**
     * Subida multipart: fichero en 'blend', y los campos 'hash' (lo que el
     * cliente dice que ha calculado) y 'hash_padre' (de qué copia parte).
     */
    public function subirSesion(int $sesionId)
    {
        return $this->responder(function () use ($sesionId) {
            $maquina = $this->exigirMaquina();

            $fichero = $this->request->getFile('blend');
            if (!$fichero || !$fichero->isValid()) {
                throw new RuntimeException(
                    'Falta el fichero en el campo "blend" de la petición multipart'
                    . ($fichero ? ' (' . $fichero->getErrorString() . ').' : '.'),
                    422
                );
            }

            $hash = trim((string) $this->request->getPost('hash'));
            if ($hash === '') {
                throw new RuntimeException('Falta el campo "hash": toda subida declara el sha256 de lo que envía.', 422);
            }

            $resultado = $this->sync->recibir(
                $sesionId,
                (int) $maquina['id'],
                $fichero->getTempName(),
                $hash,
                $this->request->getPost('hash_padre'),
                $this->request->getPost('log')
            );

            return [
                'sesion'   => $this->resumenSesion($resultado['sesion']),
                'descarga' => $resultado['descarga'] ? $this->resumenDescarga($resultado['descarga']) : null,
            ];
        });
    }

    // ---- Asientos de descarga ------------------------------------------

    public function cerrarSinCambios(int $descargaId)
    {
        return $this->responder(function () use ($descargaId) {
            $maquina    = $this->exigirMaquina();
            $hashLocal  = trim((string) $this->dato('hash_local'));

            if ($hashLocal === '') {
                throw new RuntimeException(
                    'Falta "hash_local": el servidor no se fía de que no hayas tocado nada, exige la prueba.',
                    422
                );
            }

            $resultado = $this->sync->cerrarSinCambios($descargaId, (int) $maquina['id'], $hashLocal);

            return [
                'descarga' => $this->resumenDescarga($resultado['descarga']),
                'sesion'   => $resultado['sesion'] ? $this->resumenSesion($resultado['sesion']) : null,
            ];
        });
    }

    /**
     * La válvula de escape para una copia que ya no existe (spec 4.4). Es de
     * uso web: el cliente no la expone como comando, precisamente para que
     * no se convierta en el atajo de cada noche.
     */
    public function forzarCierre(int $descargaId)
    {
        return $this->responder(function () use ($descargaId) {
            $resultado = $this->sync->forzarCierre($descargaId, (string) $this->dato('motivo'));

            return [
                'descarga' => $this->resumenDescarga($resultado['descarga']),
                'sesion'   => $resultado['sesion'] ? $this->resumenSesion($resultado['sesion']) : null,
            ];
        });
    }

    // ---- Verbos sobre versiones ----------------------------------------

    public function promocionar(int $varianteId)
    {
        return $this->responder(function () use ($varianteId) {
            $version = $this->servicio->promocionar(
                $varianteId,
                (string) $this->dato('cambio'),
                $this->dato('medidas')
            );

            $ramaNueva = $this->ramaModel->abiertaDe($varianteId);

            return [
                'version'    => $this->resumenVersion($version),
                'rama_nueva' => $ramaNueva ? [
                    'id'     => (int) $ramaNueva['id'],
                    'nombre' => $this->ramaModel->nombre($ramaNueva),
                ] : null,
            ];
        });
    }

    public function marcarImpresa(int $versionId)
    {
        return $this->responder(fn() => [
            'version' => $this->resumenVersion($this->servicio->marcarImpresa($versionId, $this->dato('params_impresion'))),
        ]);
    }

    public function validar(int $versionId)
    {
        return $this->responder(fn() => [
            'version' => $this->resumenVersion($this->servicio->validar($versionId, $this->dato('resultado'))),
        ]);
    }

    public function descartar(int $versionId)
    {
        return $this->responder(fn() => [
            'version' => $this->resumenVersion($this->servicio->descartar($versionId, (string) $this->dato('resultado'))),
        ]);
    }

    public function devolverATrabajo(int $versionId)
    {
        return $this->responder(function () use ($versionId) {
            $rama = $this->servicio->devolverATrabajo($versionId);

            return ['rama' => ['id' => (int) $rama['id'], 'nombre' => $this->ramaModel->nombre($rama)]];
        });
    }

    public function derivarVariante()
    {
        return $this->responder(function () {
            $variante = $this->servicio->derivarVariante(
                (int) $this->dato('origen_version_id'),
                (string) $this->dato('nombre'),
                $this->dato('notas')
            );

            return ['variante' => $this->resumenVariante($variante)];
        });
    }

    // ---- Cliente CLI (auto-actualización) --------------------------------

    /**
     * De dónde lee la versión "oficial" del cliente: el propio
     * piezas-cli/trackbitos.py desplegado junto al resto de la app, no una
     * copia aparte. Así solo hay un sitio que mantener — el mismo despliegue
     * que ya sube el resto del código sube también la versión nueva del
     * cliente, sin ningún paso extra.
     */
    private function rutaClienteCli(): string
    {
        return ROOTPATH . 'piezas-cli' . DIRECTORY_SEPARATOR . 'trackbitos.py';
    }

    /**
     * "¿Hay una versión más nueva?" — lo que consulta cada ejecución del
     * cliente (aviso automático, actualización manual: nunca se autoactualiza
     * sola). La versión se extrae por regex del propio fichero desplegado en
     * vez de guardarse aparte en la base de datos: un `VERSION = "…"` que se
     * pudiera desincronizar del fichero real sería peor que no tener versión.
     */
    public function clienteVersion()
    {
        $ruta = $this->rutaClienteCli();
        if (!is_file($ruta)) {
            return $this->response->setJSON(['error' => 'El cliente no está desplegado en este servidor.'])->setStatusCode(404);
        }

        if (!preg_match('/^VERSION\s*=\s*"([^"]+)"/m', file_get_contents($ruta), $m)) {
            return $this->response->setJSON(['error' => 'No se pudo leer la versión del cliente desplegado.'])->setStatusCode(500);
        }

        return $this->response->setJSON(['version' => $m[1]]);
    }

    /**
     * El propio fichero, para que "trackbitos actualizar" se reemplace a sí
     * mismo. Mismo token Bearer que el resto de la API — no hace falta
     * declarar máquina, esto no toca ningún asiento ni sesión.
     */
    public function clienteDescargar()
    {
        $ruta = $this->rutaClienteCli();
        if (!is_file($ruta)) {
            return $this->response->setJSON(['error' => 'El cliente no está desplegado en este servidor.'])->setStatusCode(404);
        }

        return $this->response->download($ruta, null, true)->setFileName('trackbitos.py');
    }

    // ---- Plomería -------------------------------------------------------

    /**
     * Cuerpo del fichero + asiento en cabeceras. No usa $this->responder
     * porque lo que devuelve no es JSON, pero traduce los errores igual.
     */
    private function entregarFichero(callable $entrega)
    {
        try {
            $maquina = $this->exigirMaquina();
            $entrega = $entrega(
                (int) $maquina['id'],
                (string) ($this->request->getGet('motivo') ?? 'consulta'),
                (bool) $this->request->getGet('ignorar_pendiente')
            );
        } catch (Throwable $e) {
            return $this->comoError($e);
        }

        return $this->response
            ->setHeader('Content-Type', 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $entrega['nombre_fichero'] . '"')
            ->setHeader('X-Hash-Blend', $entrega['hash'])
            ->setHeader('X-Descarga-Id', (string) $entrega['descarga']['id'])
            ->setHeader('X-Variante-Id', (string) $entrega['variante']['id'])
            ->setHeader('X-Variante-Nombre', rawurlencode($entrega['variante']['nombre']))
            ->setHeader('X-Familia-Nombre', rawurlencode($entrega['variante']['familia_nombre'] ?? ''))
            ->setHeader('X-Rama-Id', (string) $entrega['rama']['id'])
            ->setHeader('X-Rama-Nombre', rawurlencode($this->ramaModel->nombre($entrega['rama'])))
            ->setHeader('X-Sesion-Id', (string) ($entrega['sesion_trabajo']['id'] ?? ''))
            ->setHeader('X-Sesion-Numero', (string) ($entrega['sesion_trabajo']['numero'] ?? ''))
            ->setHeader('X-Origen-Tipo', $entrega['origen']['tipo'])
            ->setHeader('X-Origen-Numero', (string) $entrega['origen']['numero'])
            ->setBody(file_get_contents($entrega['ruta_absoluta']));
    }

    /**
     * Ejecuta la acción y traduce el resultado a JSON. Los errores de
     * dominio llevan el estado HTTP en el code de la excepción; sin code,
     * 409: que el sistema se niegue no es un fallo del servidor, es su
     * trabajo (spec 0, "se niega y explica").
     */
    private function responder(callable $accion)
    {
        try {
            return $this->response->setJSON(['ok' => true] + $accion());
        } catch (Throwable $e) {
            return $this->comoError($e);
        }
    }

    private function comoError(Throwable $e)
    {
        $codigo = $e->getCode();
        if (!is_int($codigo) || $codigo < 400 || $codigo > 499) {
            $codigo = $e instanceof RuntimeException ? 409 : 500;
        }

        if ($codigo === 500) {
            log_message('error', '[Piezas API] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        return $this->response->setJSON(['ok' => false, 'error' => $e->getMessage()])->setStatusCode($codigo);
    }

    /**
     * Identidad de máquina: cabecera X-Maquina-Uuid. No se da de alta aquí
     * — para eso está /maquina/registrar, que el cliente llama al arrancar:
     * si un UUID desconocido pudiera aparecer a mitad de una subida, el
     * registro de máquinas se llenaría de fantasmas.
     */
    private function exigirMaquina(): array
    {
        $uuid = trim($this->request->getHeaderLine('X-Maquina-Uuid'));
        if ($uuid === '') {
            $uuid = trim((string) $this->dato('uuid'));
        }
        if ($uuid === '') {
            throw new RuntimeException('Falta la cabecera X-Maquina-Uuid: toda escritura declara desde qué máquina viene.', 422);
        }

        $maquina = $this->maquinaModel->where('uuid', $uuid)->first();
        if (!$maquina) {
            throw new RuntimeException("Máquina desconocida ({$uuid}). Regístrala primero con POST /maquina/registrar.", 404);
        }

        return $maquina;
    }

    /**
     * El cliente manda JSON, pero las subidas van en multipart (no puede ir
     * un fichero dentro de un JSON), así que los campos se buscan en los dos
     * sitios.
     */
    private function dato(string $clave)
    {
        $valor = $this->request->getPost($clave);
        if ($valor !== null) {
            return $valor;
        }

        try {
            return $this->request->getJsonVar($clave);
        } catch (Throwable) {
            return null;
        }
    }

    private function resumenSesion(array $sesion): array
    {
        return [
            'id'           => (int) $sesion['id'],
            'numero'       => (int) $sesion['numero'],
            'rama_id'      => (int) $sesion['rama_id'],
            'maquina_id'   => (int) $sesion['maquina_id'],
            'abierta_en'   => $sesion['abierta_en'],
            'cerrada_en'   => $sesion['cerrada_en'],
            'hash_blend'   => $sesion['hash_blend'],
            'hash_padre'   => $sesion['hash_padre'],
            'tamano_bytes' => $sesion['tamano_bytes'] !== null ? (int) $sesion['tamano_bytes'] : null,
            'subida_en'    => $sesion['subida_en'],
        ];
    }

    private function resumenDescarga(array $descarga): array
    {
        return [
            'id'             => (int) $descarga['id'],
            'sesion_id'      => $descarga['sesion_id'] !== null ? (int) $descarga['sesion_id'] : null,
            'variante_id'    => (int) $descarga['variante_id'],
            'rama_id'        => (int) $descarga['rama_id'],
            'maquina_id'     => (int) $descarga['maquina_id'],
            'motivo'         => $descarga['motivo'],
            'descargado_en'  => $descarga['descargado_en'],
            'hash_entregado' => $descarga['hash_entregado'],
            'cerrada'        => (bool) $descarga['cerrada'],
            'cerrada_por'    => $descarga['cerrada_por'],
        ];
    }

    private function resumenVersion(array $version): array
    {
        return [
            'id'              => (int) $version['id'],
            'variante_id'     => (int) $version['variante_id'],
            'numero'          => (int) $version['numero'],
            'etiqueta'        => sprintf('v%03d', (int) $version['numero']),
            'estado'          => $version['estado'],
            'promocionada_en' => $version['promocionada_en'],
            'hash_blend'      => $version['hash_blend'],
            'cambio'          => $version['cambio'],
            'medidas'         => $version['medidas'],
            'resultado'       => $version['resultado'],
        ];
    }

    /**
     * @param array<int, string> $familias id => nombre, precargado por el llamador para no
     *                                     consultar la familia una vez por variante.
     */
    /**
     * $familias es familia_id => ['nombre', 'categoria_id', 'categoria_nombre']
     * (construido en variantes(), spec 11.1) — vacío en las llamadas que no
     * lo necesitan (p.ej. derivarVariante), y entonces estos campos salen
     * null, igual que antes de tener categoría.
     */
    private function resumenVariante(array $variante, array $familias = []): array
    {
        $validada = $this->versionModel
            ->where('variante_id', $variante['id'])
            ->where('estado', 'validada')
            ->first();

        // El listado de la web distingue "sin versión" / "versión sin
        // imprimir" / "sin validar" / "descartada"; el cliente no podía
        // porque este dato no salía de aquí y lo resumía todo como una sola
        // cosa. Es aditivo: un cliente anterior simplemente lo ignora.
        $ultimaVersion = $this->versionModel
            ->where('variante_id', $variante['id'])
            ->orderBy('numero', 'DESC')
            ->first();

        $paraImprimir = $this->versionParaImprimir((int) $variante['id']);

        $rama = $this->ramaModel->abiertaDe((int) $variante['id']);
        $sesionAbierta = $rama
            ? $this->sesionModel->where('rama_id', $rama['id'])->where('cerrada_en', null)->first()
            : null;

        // Trabajo subido a la rama abierta y todavía sin promocionar. Sin
        // esto el cliente no puede distinguir "cerrada y a medias" de "nada
        // en marcha": las dos se ven igual, sin sesión abierta que avisar.
        $ultimaSubida = $rama ? $this->sesionModel->ultimaSubida((int) $rama['id']) : null;

        $familia = $familias[(int) $variante['familia_id']] ?? [];

        return [
            'id'                    => (int) $variante['id'],
            'nombre'                => $variante['nombre'],
            'familia_id'            => (int) $variante['familia_id'],
            'familia_nombre'        => $familia['nombre'] ?? null,
            'categoria_id'          => $familia['categoria_id'] ?? null,
            'categoria_nombre'      => $familia['categoria_nombre'] ?? null,
            'version_validada'      => $validada ? [
                'id'     => (int) $validada['id'],
                'numero' => (int) $validada['numero'],
            ] : null,
            'version_para_imprimir' => $paraImprimir ? $this->resumenVersion($paraImprimir) : null,
            'versiones'             => $this->versionModel->where('variante_id', $variante['id'])->countAllResults(),
            // Solo las ramas abiertas a partir de la última versión consolidada:
            // el trabajo de ramas anteriores ya quedó congelado en versiones
            // previas y no cuenta como "lo que hay pendiente de esta".
            'sesiones'              => $this->sesionModel
                ->join('piezas_ramas', 'piezas_ramas.id = piezas_sesiones.rama_id')
                ->where('piezas_ramas.variante_id', $variante['id'])
                ->where('piezas_ramas.desde_version_id', $ultimaVersion['id'] ?? null)
                ->countAllResults(),
            'ultima_version_estado' => $ultimaVersion['estado'] ?? null,
            'rama_abierta'          => $rama !== null,
            'trabajo_en_curso'      => $sesionAbierta !== null || $ultimaSubida !== null,
            'sesion_abierta'        => $sesionAbierta !== null,
            'sesion_maquina'        => $sesionAbierta ? $this->nombreMaquina((int) $sesionAbierta['maquina_id']) : null,
            'descargas_pendientes'  => count($this->descargaModel->abiertasParaVariante((int) $variante['id'])),
        ];
    }

    private function nombreMaquina(int $maquinaId): ?string
    {
        $maquina = $this->maquinaModel->find($maquinaId);

        return $maquina['nombre'] ?? null;
    }
}
