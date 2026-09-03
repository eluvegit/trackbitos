<?php

namespace App\Controllers\Silo;

use App\Controllers\BaseController;
use App\Models\SiloFicheroModel;
use App\Models\SiloPiezaAtributoModel;
use App\Models\SiloPiezaModel;
use App\Models\SiloProxyModel;
use App\Models\SiloUbicacionModel;
use App\Models\SiloUnidadModel;
use App\Models\SiloVocabularioModel;
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
    protected SiloService $silo;

    public function __construct()
    {
        helper('silo');

        $this->piezaModel       = new SiloPiezaModel();
        $this->atributoModel    = new SiloPiezaAtributoModel();
        $this->ubicacionModel   = new SiloUbicacionModel();
        $this->vocabularioModel = new SiloVocabularioModel();
        $this->unidadModel      = new SiloUnidadModel();
        $this->ficheroModel     = new SiloFicheroModel();
        $this->proxyModel       = new SiloProxyModel();
        $this->silo             = new SiloService();
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
        // "Mi PC" es el mapa unidad definida -> disco físico real: solo
        // interesa poder reconocer cada unidad, no qué contiene.
        return view('silo/mi_pc', [
            'porNivel' => [
                1 => $this->unidadModel->porNivel(1),
                2 => $this->unidadModel->porNivel(2),
                3 => $this->unidadModel->porNivel(3),
            ],
        ]);
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
            $this->vocabularioModel->update($id, ['nombre' => $nombre]);
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
        // contenido registrado.
        $piezasPorUnidad = [];
        foreach ($porNivel as $unidadesNivel) {
            foreach ($unidadesNivel as $u) {
                $piezasPorUnidad[$u['id']] = $this->ubicacionModel->contarPorUnidad($u['id']);
            }
        }

        return view('silo/unidades', [
            'porNivel'        => $porNivel,
            'piezasPorUnidad' => $piezasPorUnidad,
        ]);
    }

    /** Contenido de una unidad, estilo "abrir esta carpeta en el explorador". */
    public function verUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        return view('silo/unidad', [
            'unidad' => $unidad,
            'piezas' => $this->piezaModel->deLaUnidad($id),
            'vista'  => $this->vistaSolicitada(),
        ]);
    }

    public function renombrarUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        $etiqueta = trim((string) $this->request->getPost('etiqueta'));
        $this->unidadModel->update($id, ['etiqueta' => $etiqueta !== '' ? $etiqueta : null]);

        return redirect()->to(site_url('silo/unidades'))->with('success', 'Etiqueta actualizada.');
    }

    /** Texto libre para reconocer el disco/USB físico real de esta unidad. */
    public function identificacionFisicaUnidad(int $id)
    {
        $unidad = $this->unidadModel->find($id);
        if (!$unidad) {
            throw PageNotFoundException::forPageNotFound('Unidad no encontrada');
        }

        $texto = trim((string) $this->request->getPost('identificacion_fisica'));
        $this->unidadModel->update($id, ['identificacion_fisica' => $texto !== '' ? $texto : null]);

        return redirect()->to(site_url('silo/unidades'))->with('success', 'Identificación física actualizada.');
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

        $etiqueta = trim((string) $this->request->getPost('etiqueta'));

        // Agrupador: solo tiene sentido en nivel 2/3 (año o categoría),
        // para que la propagación reutilice esta unidad si la das de alta
        // tú antes de que le toque su turno (plan Silo §2).
        $agrupador = trim((string) $this->request->getPost('agrupador'));
        if ($nivel === 3 && $agrupador !== '') {
            $agrupador = $this->silo->slugify($agrupador);
        }

        $capacidadTb = $this->request->getPost('capacidad_tb');
        $capacidadBytes = ($capacidadTb !== null && $capacidadTb !== '')
            ? (int) round((float) str_replace(',', '.', $capacidadTb) * 1_000_000_000_000)
            : null;

        $unidad = $this->silo->crearUnidad(
            $nivel,
            $etiqueta !== '' ? $etiqueta : null,
            $nivel !== 1 && $agrupador !== '' ? $agrupador : null,
            $capacidadBytes
        );

        return redirect()->to(site_url('silo/unidades'))->with('success', "Unidad creada: nivel {$unidad['nivel']} #{$unidad['numero']}.");
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
