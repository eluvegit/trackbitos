<?php

namespace App\Controllers\Silo;

use App\Controllers\BaseController;
use App\Models\SiloFicheroModel;
use App\Models\SiloPiezaAtributoModel;
use App\Models\SiloPiezaModel;
use App\Models\SiloProxyModel;
use App\Models\SiloTareaModel;
use App\Models\SiloUbicacionModel;
use App\Models\SiloUnidadBucketModel;
use App\Models\SiloUnidadModel;
use App\Models\SiloVocabularioModel;
use App\Services\SiloPropagacionService;
use App\Services\SiloService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Cara de navegador de Silo. Las piezas de Nivel 1 (Maestro) las da de
 * alta la ingesta (real o simulada, ver SiloIngestaService / `spark
 * silo:simular-ingesta`) a partir de lo que hay en la unidad física — el
 * formulario manual de aquí (`create`/`store`) es la vía secundaria para
 * cuando no hay nada que escanear todavía. No toca disco: los nombres de
 * carpeta y el `.silo_unit.json` de cada unidad se calculan y quedan
 * descargables, nunca se escriben solos en ningún sitio.
 */
class Web extends BaseController
{
    protected SiloPiezaModel $piezaModel;
    protected SiloPiezaAtributoModel $atributoModel;
    protected SiloUbicacionModel $ubicacionModel;
    protected SiloVocabularioModel $vocabularioModel;
    protected SiloUnidadModel $unidadModel;
    protected SiloFicheroModel $ficheroModel;
    protected SiloProxyModel $proxyModel;
    protected SiloTareaModel $tareaModel;
    protected SiloUnidadBucketModel $unidadBucketModel;
    protected SiloService $silo;
    protected SiloPropagacionService $propagacion;

    public function __construct()
    {
        helper('silo');

        $this->piezaModel        = new SiloPiezaModel();
        $this->atributoModel     = new SiloPiezaAtributoModel();
        $this->ubicacionModel    = new SiloUbicacionModel();
        $this->vocabularioModel  = new SiloVocabularioModel();
        $this->unidadModel       = new SiloUnidadModel();
        $this->ficheroModel      = new SiloFicheroModel();
        $this->proxyModel        = new SiloProxyModel();
        $this->tareaModel        = new SiloTareaModel();
        $this->unidadBucketModel = new SiloUnidadBucketModel();
        $this->silo              = new SiloService();
        $this->propagacion       = new SiloPropagacionService();
    }

    public function index()
    {
        $filtros = [
            'q'            => $this->request->getGet('q'),
            'categoria_id' => $this->request->getGet('categoria_id'),
        ];

        return view('silo/index', [
            'piezas'     => $this->piezaModel->buscar($filtros),
            'categorias' => $this->vocabularioModel->porTipo('categoria'),
            'filtros'    => $filtros,
            'vista'      => $this->vistaSolicitada(),
        ]);
    }

    /** Modo de presentación de las carpetas: 'galeria' o 'lista' (por defecto). */
    private function vistaSolicitada(): string
    {
        return $this->request->getGet('vista') === 'galeria' ? 'galeria' : 'lista';
    }

    /** "Mi PC": todas las unidades como discos del explorador, para entrar en cada una. */
    public function miPc()
    {
        $porNivel = [
            1 => $this->unidadModel->porNivel(1),
            2 => $this->unidadModel->porNivel(2),
            3 => $this->unidadModel->porNivel(3),
        ];

        // "Mi PC" es el mapa unidad definida -> disco físico real: solo
        // interesa poder reconocer cada unidad, no qué contiene.
        return view('silo/mi_pc', [
            'porNivel'        => $porNivel,
            'bucketsPorUnidad' => $this->bucketsPorNivel($porNivel),
        ]);
    }

    /**
     * Recalcula el reparto de Nivel 2 entre las unidades **ya dadas de
     * alta** (SiloPropagacionService::aplicarPlanNivel2()) — botón
     * "Recalcular reparto" en /silo/unidades. No borra ni crea unidades
     * (conserva identificación física/ruta de montaje/etiqueta puestas a
     * mano); solo reconstruye qué años tiene cada una y las ubicaciones de
     * copia 2. Vuelve a llamarse cuando das de alta una unidad nueva o
     * cuánto ha crecido el contenido.
     */
    public function recalcularNivel2()
    {
        $plan = $this->propagacion->aplicarPlanNivel2();

        if ($plan === []) {
            return redirect()->to(site_url('silo/unidades'))->with('error', 'No hay unidades de Nivel 2 con capacidad dada de alta todavía.');
        }

        $problemas = count(array_filter($plan, fn ($run) => $run['estado'] !== 'ok'));
        $aviso = $problemas > 0
            ? " ({$problemas} tramo(s) sin sitio — ver el aviso en la tarjeta o dar de alta más unidades)"
            : '';

        return redirect()->to(site_url('silo/unidades'))->with('success', count($plan) . ' tramo(s) de Nivel 2 recalculados.' . $aviso);
    }

    /**
     * Años/categorías de cada unidad de Nivel 2/3 ya comprimidos en
     * notación de rango ("2010-2018"), para mostrar en vez de `agrupador`
     * (que queda vacío en las unidades combinadas por
     * SiloPropagacionService::aplicarPlanNivel2() — una unidad ya puede
     * agrupar varios años consecutivos, ver docs/silo-ingesta-propagacion.md).
     *
     * @param array<int, array> $porNivel
     * @return array<int, string>
     */
    private function bucketsPorNivel(array $porNivel): array
    {
        $buckets = [];
        foreach ([2, 3] as $nivel) {
            foreach ($porNivel[$nivel] ?? [] as $u) {
                $lista               = $this->unidadBucketModel->bucketsDe((int) $u['id']);
                $buckets[$u['id']] = $lista !== [] ? $this->silo->comprimirAnios($lista) : '';
            }
        }

        return $buckets;
    }

    public function create()
    {
        return view('silo/create');
    }

    public function store()
    {
        $bloqueCrudo   = (string) $this->request->getPost('bloque_semantico');
        $fechaExtraida = $this->silo->extraerFecha($bloqueCrudo);

        $resuelto = $this->resolverBloque(
            (array) $this->request->getPost('elementos_texto'),
            (array) $this->request->getPost('elementos_tipo'),
            $fechaExtraida['resto']
        );

        $idNegocio = $this->silo->siguienteIdNegocio($fechaExtraida['fecha']);

        $nombreCarpeta = $this->silo->formatearNombreCarpeta(
            $idNegocio,
            $fechaExtraida['fecha'],
            $resuelto['categoria']['nombre'] ?? null,
            array_column($resuelto['atributos'], 'nombre')
        );

        $piezaId = $this->piezaModel->insert([
            'id_negocio'       => $idNegocio,
            'fecha'            => $fechaExtraida['fecha'],
            'tipo'             => $this->request->getPost('tipo') ?: null,
            'fuente'           => $this->request->getPost('fuente') ?: null,
            'categoria_id'     => $resuelto['categoria']['id'] ?? null,
            'bloque_semantico' => $bloqueCrudo,
            'nombre_carpeta'   => $nombreCarpeta,
            'notas'            => $this->request->getPost('notas') ?: null,
        ], true);

        if (!empty($resuelto['atributos'])) {
            $this->atributoModel->reemplazarDeLaPieza($piezaId, array_column($resuelto['atributos'], 'id'));
        }

        return redirect()->to(site_url('silo/' . $piezaId))->with('success', 'Pieza creada: ' . $nombreCarpeta);
    }

    /** AJAX: separa la fecha del principio (si la hay), trocea el resto por coma y sugiere tipo/coincidencia por trozo. */
    public function parsearBloque()
    {
        $texto = (string) ($this->request->getPost('texto') ?? '');
        $resto = $this->silo->extraerFecha($texto)['resto'];

        return $this->response->setJSON([
            'trozos' => $this->silo->parsearBloqueSemantico($resto),
        ]);
    }

    public function show(int $id)
    {
        $pieza = $this->piezaModel->find($id);
        if (!$pieza) {
            throw PageNotFoundException::forPageNotFound('Pieza no encontrada');
        }

        // De qué unidad venías (la vista de esa unidad pasa ?desde=ID en el
        // enlace): es a donde apunta el botón "Subir". Sin ello se sube al
        // listado del Silo, no a una unidad concreta.
        $desdeId = (int) $this->request->getGet('desde');
        $desde   = $desdeId ? $this->unidadModel->find($desdeId) : null;

        return view('silo/show', [
            'pieza'       => $pieza,
            'atributos'   => $this->atributoModel->deLaPieza($id),
            'ubicaciones' => $this->ubicacionModel->deLaPieza($id),
            'categoria'   => $pieza['categoria_id'] ? $this->vocabularioModel->find($pieza['categoria_id']) : null,
            'ficheros'    => $this->ficheroModel->deLaPieza($id),
            'proxies'     => $this->proxyModel->deLaPieza($id),
            'desde'       => $desde,
        ]);
    }

    public function edit(int $id)
    {
        $pieza = $this->piezaModel->find($id);
        if (!$pieza) {
            throw PageNotFoundException::forPageNotFound('Pieza no encontrada');
        }

        return view('silo/edit', [
            'pieza'     => $pieza,
            'atributos' => $this->atributoModel->deLaPieza($id),
            'categoria' => $pieza['categoria_id'] ? $this->vocabularioModel->find($pieza['categoria_id']) : null,
        ]);
    }

    /**
     * Reclasificar: cambia categoría/atributos pero nunca `nombre_carpeta`
     * (histórico, congelado en el alta — plan Silo §4 "Categoría").
     */
    public function update(int $id)
    {
        $pieza = $this->piezaModel->find($id);
        if (!$pieza) {
            throw PageNotFoundException::forPageNotFound('Pieza no encontrada');
        }

        $resuelto = $this->resolverBloque(
            (array) $this->request->getPost('elementos_texto'),
            (array) $this->request->getPost('elementos_tipo'),
            ''
        );

        $this->piezaModel->update($id, [
            'tipo'         => $this->request->getPost('tipo') ?: null,
            'fuente'       => $this->request->getPost('fuente') ?: null,
            'categoria_id' => $resuelto['categoria']['id'] ?? null,
            'notas'        => $this->request->getPost('notas') ?: null,
        ]);

        $this->atributoModel->reemplazarDeLaPieza($id, array_column($resuelto['atributos'], 'id'));

        return redirect()->to(site_url('silo/' . $id))->with('success', 'Pieza actualizada.');
    }

    public function delete(int $id)
    {
        $this->piezaModel->delete($id);
        return redirect()->to(site_url('silo'))->with('success', 'Pieza eliminada.');
    }

    /**
     * Borra una ubicación puntual (limpieza de un ingesta equivocada). No
     * hay alta manual de ubicación desde la web — una pieza vive en una
     * unidad porque la ingesta la encontró ahí, no porque alguien la
     * asigne a mano (corrección de rumbo 2026-09-03: la web es escáner +
     * propagación + búsqueda/filtro, no asignación manual).
     */
    public function borrarUbicacion(int $id)
    {
        $ubicacion = $this->ubicacionModel->find($id);
        if (!$ubicacion) {
            throw PageNotFoundException::forPageNotFound('Ubicación no encontrada');
        }

        $this->ubicacionModel->delete($id);

        return redirect()->to(site_url('silo/' . $ubicacion['pieza_id']))->with('success', 'Ubicación eliminada.');
    }

    public function vocabulario()
    {
        $tipos = ['categoria', 'evento', 'lugar', 'persona', 'tema'];
        $porTipo = [];
        foreach ($tipos as $tipo) {
            $porTipo[$tipo] = $this->vocabularioModel->porTipo($tipo);
        }

        return view('silo/vocabulario', ['porTipo' => $porTipo]);
    }

    public function renombrarVocabulario(int $id)
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre !== '') {
            $descripcion = trim((string) $this->request->getPost('descripcion'));
            $this->vocabularioModel->update($id, [
                'nombre'      => $nombre,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
            ]);
        }

        return redirect()->to(site_url('silo/vocabulario'))->with('success', 'Vocabulario actualizado.');
    }

    public function unidades()
    {
        $porNivel = [
            1 => $this->unidadModel->porNivel(1),
            2 => $this->unidadModel->porNivel(2),
            3 => $this->unidadModel->porNivel(3),
        ];

        // Solo para reforzar la confirmación de borrado si la unidad tiene
        // contenido registrado; `excedePorUnidad` avisa cuando lo que ya
        // pesa la unidad supera su capacidad declarada — con años que no
        // se pueden fragmentar (SiloPropagacionService::calcularPlanNivel2()),
        // "cabe un año entero o no cabe" es una posibilidad real que hay que
        // ver en la propia tarjeta, no solo en la salida del comando.
        $piezasPorUnidad = [];
        $excedePorUnidad = [];
        foreach ($porNivel as $unidadesNivel) {
            foreach ($unidadesNivel as $u) {
                $piezasPorUnidad[$u['id']] = $this->ubicacionModel->contarPorUnidad($u['id']);

                $usado = $this->ubicacionModel->sumaTamanoPorUnidad($u['id']);
                $excedePorUnidad[$u['id']] = $u['capacidad_bytes'] !== null && $usado > (int) $u['capacidad_bytes'];
            }
        }

        // Estado del último escaneo pedido a cada unidad Maestro (nivel 1):
        // el resto de niveles no se escanean (no son Maestro, plan Silo §2).
        $tareasPorUnidad = [];
        foreach ($porNivel[1] as $u) {
            $tareasPorUnidad[$u['id']] = $this->tareaModel->ultimaDeUnidad((int) $u['id'], 'escaneo_maestro');
        }

        return view('silo/unidades', [
            'porNivel'         => $porNivel,
            'piezasPorUnidad'  => $piezasPorUnidad,
            'excedePorUnidad'  => $excedePorUnidad,
            'tareasPorUnidad'  => $tareasPorUnidad,
            'bucketsPorUnidad' => $this->bucketsPorNivel($porNivel),
        ]);
    }

    /**
     * Encola un escaneo_maestro para que el agente `.py` de esa máquina lo
     * recoja (modo `--daemon`, sondeo periódico, o la próxima vez que se
     * lance a mano — ver silo-agente/agente.py). La web nunca toca disco:
     * solo dejar la petición en `silo_tareas`, aprobada porque quien la pide
     * es el dueño de la unidad, no un tercero.
     */
    public function solicitarEscaneo(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        if ((int) $unidad['nivel'] !== 1) {
            return redirect()->to(site_url('silo/unidades'))->with('error', 'Solo las unidades Maestro (nivel 1) se escanean.');
        }

        if ($this->tareaModel->pendienteDeUnidad($id, 'escaneo_maestro')) {
            return redirect()->to(site_url('silo/unidades'))->with('success', 'Ya había un escaneo pendiente para esta unidad: se ejecutará en cuanto el agente de esa máquina conecte.');
        }

        $this->tareaModel->crear($id, 'escaneo_maestro');

        return redirect()->to(site_url('silo/unidades'))->with('success', 'Escaneo solicitado: se ejecutará en cuanto el agente de esa máquina conecte.');
    }

    /** Contenido de una unidad, estilo "abrir esta carpeta en el explorador". */
    public function verUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        $orden = $this->request->getGet('orden') === 'fecha' ? 'fecha' : 'nombre';

        return view('silo/unidad', [
            'unidad' => $unidad,
            'piezas' => $this->piezaModel->deLaUnidad($id, $orden),
            'vista'  => $this->vistaSolicitada(),
            'orden'  => $orden,
        ]);
    }

    /**
     * Borra la unidad. Sin contenido es una baja directa; con contenido la
     * confirmación reforzada la hace la vista (JS) antes de enviar — al
     * borrar, sus ubicaciones caen con ella (ON DELETE CASCADE), las
     * piezas en sí no se tocan (pueden seguir vivas en otras unidades).
     */
    public function borrarUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        $this->unidadModel->delete($id);

        return redirect()->to(site_url('silo/unidades'))->with('success', 'Unidad eliminada.');
    }

    public function crearUnidad()
    {
        $nivel = (int) $this->request->getPost('nivel');
        if (!in_array($nivel, [1, 2, 3], true)) {
            return redirect()->to(site_url('silo/unidades'))->with('error', 'Nivel inválido.');
        }

        $datos = $this->datosUnidadDesdePost($nivel);

        $unidad = $this->silo->crearUnidad(
            $nivel,
            $datos['etiqueta'],
            $datos['agrupador'],
            $datos['capacidad_bytes'],
            $datos['ruta_montaje'],
            $datos['tipo_fisico']
        );

        return redirect()->to(site_url('silo/unidades'))->with('success', "Unidad creada: nivel {$unidad['nivel']} #{$unidad['numero']}.");
    }

    /**
     * Guarda de una sola vez todos los campos editables de la tarjeta
     * (etiqueta, identificación física, tipo físico, ruta de montaje,
     * capacidad, agrupador) — un único endpoint para el modal de edición
     * en vez de un POST suelto por campo.
     */
    public function actualizarUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        $datos = $this->datosUnidadDesdePost((int) $unidad['nivel']);
        $datos['identificacion_fisica'] = trim((string) $this->request->getPost('identificacion_fisica')) ?: null;

        $this->unidadModel->update($id, $datos);

        return redirect()->to(site_url('silo/unidades'))->with('success', 'Unidad actualizada.');
    }

    /**
     * Campos comunes a alta y edición, leídos del POST.
     * `identificacion_fisica` no vive aquí: crearUnidad() no la usa (unidad
     * recién nacida) y actualizarUnidad() la sobrescribe después de
     * llamar a este método.
     *
     * @return array{etiqueta: ?string, identificacion_fisica: null, tipo_fisico: ?string, agrupador: ?string, capacidad_bytes: ?int, ruta_montaje: ?string}
     */
    private function datosUnidadDesdePost(int $nivel): array
    {
        $etiqueta = trim((string) $this->request->getPost('etiqueta'));

        $tipoFisico = (string) $this->request->getPost('tipo_fisico');
        if (!in_array($tipoFisico, ['usb', 'hdd_interno', 'hdd_externo'], true)) {
            $tipoFisico = null;
        }

        // Agrupador: solo tiene sentido en nivel 2/3 (año o categoría),
        // para que la propagación reutilice esta unidad si la das de alta
        // tú antes de que le toque su turno (plan Silo §2).
        $agrupador = trim((string) $this->request->getPost('agrupador'));
        if ($nivel === 3 && $agrupador !== '') {
            $agrupador = $this->silo->slugify($agrupador);
        }

        // GB o TB (no solo TB, plan Silo §7: un USB de 64GB en TB sería
        // 0.064 — inutilizable con el mínimo de 0.5 que tenía el campo
        // antes de la corrección 2026-09-05).
        $capacidadValor  = $this->request->getPost('capacidad_valor');
        $capacidadUnidad = $this->request->getPost('capacidad_unidad') === 'tb' ? 'tb' : 'gb';
        $capacidadBytes  = ($capacidadValor !== null && $capacidadValor !== '')
            ? (int) round((float) str_replace(',', '.', $capacidadValor) * ($capacidadUnidad === 'tb' ? 1_000_000_000_000 : 1_000_000_000))
            : null;

        $rutaMontaje = trim((string) $this->request->getPost('ruta_montaje'));

        return [
            'etiqueta'               => $etiqueta !== '' ? $etiqueta : null,
            'identificacion_fisica'  => null, // lo rellena actualizarUnidad(); crearUnidad() no lo usa (unidad recién nacida, sin datos aún)
            'tipo_fisico'            => $tipoFisico,
            'agrupador'              => $nivel !== 1 && $agrupador !== '' ? $agrupador : null,
            'capacidad_bytes'        => $capacidadBytes,
            'ruta_montaje'           => $rutaMontaje !== '' ? $rutaMontaje : null,
        ];
    }

    /** Descarga el .silo_unit.json que se copiaría en la raíz de la unidad física (plan Silo §7.1). */
    public function ficheroControlUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        return $this->response
            ->setContentType('application/json')
            ->setHeader('Content-Disposition', 'attachment; filename=".silo_unit.json"')
            ->setBody($unidad['fichero_control']);
    }

    /**
     * Resuelve el bloque semántico a categoría + atributos. Si el usuario
     * clasificó cada trozo a mano con "Analizar" (elementos_texto/tipo ya
     * rellenos), se respeta esa clasificación. Si no, se parsea el texto
     * crudo del textarea directamente: primer trozo = categoría, el resto
     * = tema por defecto — el diseño de Silo nunca bloquea el alta por
     * falta de clasificación (regla de "bloqueo cero", plan Silo §4).
     */
    private function resolverBloque(array $textos, array $tipos, string $bloqueCrudo): array
    {
        if (empty($textos)) {
            $trozos = array_values(array_filter(array_map('trim', explode(',', $bloqueCrudo)), fn ($t) => $t !== ''));
            $textos = $trozos;
            $tipos  = [];
            foreach ($textos as $i => $t) {
                $tipos[$i] = $i === 0 ? 'categoria' : 'tema';
            }
        }

        $categoria = null;
        $atributos = [];

        foreach ($textos as $i => $texto) {
            $texto = trim((string) $texto);
            if ($texto === '') {
                continue;
            }
            $tipo = $tipos[$i] ?? 'tema';

            if ($tipo === 'categoria') {
                if ($categoria === null) {
                    $categoria = $this->silo->getOrCreateVocabulario('categoria', $texto);
                } else {
                    // Ya hay una categoría (solo cabe una): esta segunda no
                    // se descarta, cae a tema en vez de perderse.
                    $atributos[] = $this->silo->getOrCreateVocabulario('tema', $texto);
                }
                continue;
            }

            if (!in_array($tipo, ['evento', 'lugar', 'persona', 'tema'], true)) {
                $tipo = 'tema';
            }
            $atributos[] = $this->silo->getOrCreateVocabulario($tipo, $texto);
        }

        return ['categoria' => $categoria, 'atributos' => $atributos];
    }
}
