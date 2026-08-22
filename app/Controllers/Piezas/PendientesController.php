<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaConfigModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaReferenciaModel;
use App\Models\SubtaskModel;
use App\Models\TaskFileModel;
use App\Models\TaskModel;
use App\Services\PiezaAlmacen;
use App\Services\PiezaImagenesPublicas;

/**
 * "Pendientes de crear": qué piezas quedan por diseñar, tomado directamente
 * de las subtareas sin marcar de una tarea de Journal (spec: journal es el
 * punto de entrada de ideas, esto es solo una vista filtrada encima, sin
 * tabla ni sincronización propias — ver PiezaConfigModel).
 *
 * No hay endpoints propios para crear la pieza ni para marcar la subtarea
 * hecha: la vista llama por fetch() a los que ya existen (Web::crearFamilia
 * y Journal::subtaskToggle), así que no hay lógica duplicada que mantener
 * al día en dos sitios. La única lógica propia de este controlador es
 * copiarReferencias(): copiar (no enlazar) las imágenes que ya colgaban de
 * la subtarea en Journal, para que sigan existiendo si el día de mañana se
 * purga esa tarea — spec: "journal y las piezas son líneas de vida
 * distintas".
 */
class PendientesController extends BaseController
{
    protected $helpers = ['url', 'form'];

    /** Mismo criterio que Web::MIMES_IMAGEN — solo estos formatos entran como referencia. */
    private const MIMES_IMAGEN = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function index()
    {
        $configModel = new PiezaConfigModel();
        $taskModel   = new TaskModel();

        $tareaId = $configModel->tareaJournalId();
        $tarea   = $tareaId ? $taskModel->find($tareaId) : null;

        $pendientes = [];
        if ($tarea) {
            $pendientes = array_values(array_filter(
                (new SubtaskModel())->getForTask((int) $tarea['id']),
                static fn(array $s) => empty($s['is_done'])
            ));
        }

        // Cuántos materiales de Journal cuelgan de cada subtarea (todos los
        // tipos, no solo imagen — contar exige abrir cada fichero y eso es
        // caro para un simple aviso; el filtro real pasa en copiarReferencias()).
        // Sirve para que "Crear pieza" avise de que hay algo que copiar.
        $ficherosPorSubtarea = [];
        if ($pendientes !== []) {
            $ids = array_column($pendientes, 'id');
            foreach ((new TaskFileModel())->whereIn('subtask_id', $ids)->findAll() as $f) {
                $ficherosPorSubtarea[(int) $f['subtask_id']] = ($ficherosPorSubtarea[(int) $f['subtask_id']] ?? 0) + 1;
            }
        }
        foreach ($pendientes as &$p) {
            $p['ficheros'] = $ficherosPorSubtarea[(int) $p['id']] ?? 0;
        }
        unset($p);

        return view('piezas/pendientes/index', [
            'tarea'          => $tarea,
            'tareaEnlazada'  => $tareaId,
            'pendientes'     => $pendientes,
            // Para el selector de "enlazar tarea", agrupadas por categoría
            // tal cual las usa Journal.
            'tareasPorCategoria' => $taskModel->select('id, category, title')->orderBy('category', 'ASC')
                ->orderBy('title', 'ASC')->findAll(),
            'categorias' => (new PiezaCategoriaModel())->ordenadas(),
            // Para "es una variante de...": alguna idea de Journal no es una
            // pieza nueva, es otra línea de diseño de una que ya existe
            // (spec: hay que poder elegirlo al crear, no solo tras el hecho).
            'familias' => (new PiezaFamiliaModel())->where('borrado_en', null)
                ->orderBy('nombre', 'ASC')->findAll(),
        ]);
    }

    /** Enlaza (o cambia) la tarea de Journal de la que salen las piezas pendientes. */
    public function enlazar()
    {
        $tareaId = (int) $this->request->getPost('tarea_id');
        if (!$tareaId || !(new TaskModel())->find($tareaId)) {
            return redirect()->to(site_url('piezas/pendientes'))->with('error', 'Esa tarea no existe.');
        }

        (new PiezaConfigModel())->enlazarTarea($tareaId);

        return redirect()->to(site_url('piezas/pendientes'))->with('success', 'Tarea enlazada.');
    }

    /** Suelta la tarea enlazada, sin tocar nada en Journal. */
    public function desenlazar()
    {
        (new PiezaConfigModel())->enlazarTarea(null);

        return redirect()->to(site_url('piezas/pendientes'))->with('success', 'Tarea desenlazada.');
    }

    /**
     * Copia (no enlaza) como referencias de la pieza recién creada las
     * imágenes que colgaban de esta subtarea en Journal. Se llama justo
     * después de crear la pieza (ver el fetch en pendientes/index.php), con
     * `familia_id`/`variante_id` de la respuesta de Web::crearFamilia.
     *
     * Copia y no enlace a propósito (spec del diseño): si algún día se purga
     * la tarea de Journal, o simplemente deja de usarse, la referencia de la
     * pieza tiene que seguir existiendo — son dos cuadernos con vidas
     * distintas, y la pieza no puede depender de que el otro siga vivo.
     *
     * Los ficheros que no sean imagen (un PDF de referencia, por ejemplo) se
     * saltan sin más: una referencia de pieza solo admite foto.
     */
    public function copiarReferencias(int $subtaskId)
    {
        $familiaId  = (int) $this->request->getPost('familia_id');
        $varianteId = (int) $this->request->getPost('variante_id');
        if (!$familiaId || !$varianteId) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'mensaje' => 'Falta la pieza destino.']);
        }

        $archivos = (new TaskFileModel())->where('subtask_id', $subtaskId)->findAll();

        $almacen         = new PiezaAlmacen();
        $publicas        = new PiezaImagenesPublicas();
        $referenciaModel = new PiezaReferenciaModel();

        $copiadas = 0;
        foreach ($archivos as $archivo) {
            $origen = FCPATH . $archivo['ruta_archivo'];
            $extension = $this->extensionDeImagen($origen);
            if (!$extension) {
                continue;
            }

            $id = $referenciaModel->insert([
                'familia_id'  => $familiaId,
                'variante_id' => $varianteId,
                'ruta_imagen' => '',
                'notas'       => 'Desde Journal: ' . ($archivo['descripcion'] ?: $archivo['nombre_original']),
                'subida_en'   => date('Y-m-d H:i:s'),
            ], true);
            if (!$id) {
                continue;
            }

            // Copia de verdad, no el mismo fichero: PiezaAlmacen::guardar()
            // mueve su origen (pensado para un temporal de subida), así que
            // aquí se le da una copia de usar y tirar, nunca el original de
            // Journal.
            $tmp = tempnam(sys_get_temp_dir(), 'jref_');
            copy($origen, $tmp);

            $destino = $almacen->rutaReferencia($familiaId, $id, $extension);
            $almacen->guardar($tmp, $destino);
            $hash = $almacen->hash($destino);

            $referenciaModel->update($id, [
                'ruta_imagen'  => $destino,
                'hash_imagen'  => $hash,
                'tamano_bytes' => filesize($almacen->absoluta($destino)),
            ]);

            try {
                $publicas->publicar($almacen->absoluta($destino), $hash);
            } catch (\Throwable $e) {
                log_message('error', '[Pendientes] no se pudieron publicar las copias de ' . $destino . ': ' . $e->getMessage());
            }

            $copiadas++;
        }

        return $this->response->setJSON(['ok' => true, 'copiadas' => $copiadas]);
    }

    /** Mismo espíritu que Web::validarImagen(), pero sobre un fichero ya en disco, no una subida. */
    private function extensionDeImagen(string $rutaAbsoluta): ?string
    {
        if (!is_file($rutaAbsoluta)) {
            return null;
        }

        $mime = @mime_content_type($rutaAbsoluta) ?: null;

        return $mime ? (self::MIMES_IMAGEN[$mime] ?? null) : null;
    }
}
