<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaRamaModel;
use App\Models\PiezaSesionModel;
use App\Models\PiezaVarianteModel;
use App\Models\PiezaVersionModel;
use App\Services\PiezaService;
use App\Services\PiezaSyncService;
use Throwable;

/**
 * La cara de navegador del módulo Piezas (spec sección 7). Sobria y
 * orientada al estado: lo que debe responder de un vistazo es cuál es la
 * versión buena y dónde está el trabajo en curso.
 *
 * Lo que esta interfaz NO hace, a propósito: descargar ficheros. La
 * identidad de máquina la declara el script, nunca el navegador (spec 4.5)
 * — la web puede abrirse desde el móvil, donde no hay ningún disco que
 * registrar. Así que aquí se muestra el hash de la nube y el comando exacto
 * a ejecutar, y quien toca ficheros sigue siendo trackbitos.py.
 */
class Web extends BaseController
{
    /** Días en borrador/impresa a partir de los cuales se marca como olvidada (spec 7.2). */
    private const DIAS_PENDIENTE_DE_JUICIO = 14;

    private PiezaFamiliaModel $familiaModel;
    private PiezaVarianteModel $varianteModel;
    private PiezaVersionModel $versionModel;
    private PiezaRamaModel $ramaModel;
    private PiezaSesionModel $sesionModel;
    private PiezaService $servicio;
    private PiezaSyncService $sync;

    public function __construct()
    {
        $this->familiaModel  = new PiezaFamiliaModel();
        $this->varianteModel = new PiezaVarianteModel();
        $this->versionModel  = new PiezaVersionModel();
        $this->ramaModel     = new PiezaRamaModel();
        $this->sesionModel   = new PiezaSesionModel();
        $this->servicio      = new PiezaService();
        $this->sync          = new PiezaSyncService();
    }

    /**
     * Índice: familias con sus variantes, cada una resumida a lo único que
     * importa de lejos — cuál es la buena y si hay algo en marcha.
     */
    public function index()
    {
        $familias = $this->familiaModel->orderBy('nombre', 'ASC')->findAll();

        foreach ($familias as &$familia) {
            $familia['variantes'] = array_map(
                fn($v) => $this->resumen($v),
                $this->varianteModel->where('familia_id', $familia['id'])->orderBy('nombre', 'ASC')->findAll()
            );
        }
        unset($familia);

        return view('piezas/index', ['familias' => $familias]);
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

        foreach ($versiones as &$version) {
            $version['pendiente_de_juicio'] = $this->llevaDemasiadoPendiente($version);
            $version['sesiones']            = $this->sesionesQueLlevaronA((int) $version['id']);
        }
        unset($version);

        return view('piezas/variante', [
            'variante'  => $variante,
            'familia'   => $this->familiaModel->find($variante['familia_id']),
            'origen'    => $this->versionDeOrigen($variante),
            'versiones' => $versiones,
            'validada'  => $this->versionModel->where('variante_id', $id)->where('estado', 'validada')->first(),
            'rama'      => $estado['rama'],
            'ramaNombre' => $estado['rama'] ? $this->ramaModel->nombre($estado['rama']) : null,
            'sesiones'  => $this->sesionesDeRama($estado['rama']),
            'estado'    => $estado,
            'bloqueo'   => $this->descripcionBloqueo($estado['sesion_abierta']),
            'pendientes' => $this->descripcionPendientes($estado['descargas_pendientes']),
            'acciones'  => $this->accionesDisponibles($estado, $versiones),
            'familias'  => $this->familiaModel->orderBy('nombre', 'ASC')->findAll(),
        ]);
    }

    // ---- Alta de familias y variantes ----------------------------------

    public function crearFamilia()
    {
        return $this->ejecutar(
            fn() => $this->servicio->crearFamilia(
                trim((string) $this->request->getPost('nombre')),
                $this->request->getPost('notas') ?: null
            ),
            fn($familia) => site_url('piezas'),
            fn($familia) => 'Familia "' . $familia['nombre'] . '" creada.'
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
        $estado   = $this->sync->estadoDeSincronizacion((int) $variante['id']);
        $validada = $this->versionModel->where('variante_id', $variante['id'])->where('estado', 'validada')->first();

        return $variante + [
            'validada'      => $validada,
            'versiones'     => $this->versionModel->where('variante_id', $variante['id'])->countAllResults(),
            'bloqueo'       => $this->descripcionBloqueo($estado['sesion_abierta']),
            'pendientes'    => $this->descripcionPendientes($estado['descargas_pendientes']),
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

    private function descripcionBloqueo(?array $sesionAbierta): ?array
    {
        if (!$sesionAbierta) {
            return null;
        }

        return [
            'maquina' => $this->sync->nombreDeMaquina((int) $sesionAbierta['maquina_id']) ?? 'otra máquina',
            'numero'  => (int) $sesionAbierta['numero'],
            'desde'   => $sesionAbierta['abierta_en'],
            'dias'    => $this->diasDesde($sesionAbierta['abierta_en']),
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
