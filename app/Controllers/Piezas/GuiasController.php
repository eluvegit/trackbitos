<?php

namespace App\Controllers\Piezas;

use App\Controllers\BaseController;
use App\Models\PiezaCategoriaModel;
use App\Models\PiezaFamiliaModel;
use App\Models\PiezaGuiaModel;
use App\Models\PiezaGuiaOpcionModel;
use App\Models\PiezaGuiaPasoModel;
use App\Models\PiezaRenderModel;
use App\Models\PiezaVarianteModel;
use App\Services\PiezaGuias;
use CodeIgniter\Model;

/**
 * Edición de las guías del TPV de inventario (ver App\Services\PiezaGuias):
 * pasos, opciones y qué pieza corresponde a cada combinación.
 */
class GuiasController extends BaseController
{
    private PiezaGuias $servicio;
    private PiezaGuiaModel $guias;
    private PiezaGuiaPasoModel $pasos;
    private PiezaGuiaOpcionModel $opciones;

    public function __construct()
    {
        helper('guias');
        $this->servicio = new PiezaGuias();
        $this->guias    = new PiezaGuiaModel();
        $this->pasos    = new PiezaGuiaPasoModel();
        $this->opciones = new PiezaGuiaOpcionModel();
    }

    public function index()
    {
        $filas = [];
        foreach ($this->servicio->todas() as $g) {
            $estructura = $this->servicio->estructura((int) $g['id']);
            $filas[] = [
                'guia'       => $g,
                'pasos'      => count($estructura['pasos']),
                'asignadas'  => $this->servicio->cuantasAsignadas((int) $g['id']),
            ];
        }

        return view('piezas/guias/index', ['filas' => $filas]);
    }

    public function crear()
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return redirect()->to(site_url('piezas/guias'))->with('error', 'Ponle un nombre a la guía.');
        }

        $id = $this->guias->insert([
            'nombre' => $nombre,
            'icono'  => $this->icono((string) $this->request->getPost('icono')),
            'orden'  => $this->siguienteOrden($this->guias),
            'activa' => 0,
        ]);

        return redirect()->to(site_url('piezas/guias/' . $id))
            ->with('success', 'Guía creada. Añade sus pasos y opciones, asigna las piezas y actívala cuando esté lista.');
    }

    public function editar(int $id)
    {
        $estructura = $this->servicio->estructura($id);
        if (!$estructura) {
            return redirect()->to(site_url('piezas/guias'))->with('error', 'Esa guía no existe.');
        }

        // Una fila por pieza asignada, con la opción elegida en cada paso.
        $pasoDeOpcion = [];
        foreach ($estructura['pasos'] as $p) {
            foreach ($p['opciones'] as $o) {
                $pasoDeOpcion[(int) $o['id']] = (int) $p['id'];
            }
        }

        $variantes = $this->variantesAgrupadas();
        $nombres   = array_replace([], ...array_values(array_column($variantes, 'variantes')));

        $filas = [];
        foreach ($this->servicio->resultados($id) as $clave => $varianteId) {
            $elegidas = [];
            foreach (explode('-', $clave) as $oid) {
                if (isset($pasoDeOpcion[(int) $oid])) {
                    $elegidas[$pasoDeOpcion[(int) $oid]] = (int) $oid;
                }
            }
            $filas[] = ['variante' => $varianteId, 'opciones' => $elegidas];
        }
        usort($filas, static fn (array $a, array $b) => strnatcasecmp($nombres[$a['variante']] ?? '', $nombres[$b['variante']] ?? ''));

        $old = session('filas_guia');

        return view('piezas/guias/editar', [
            'guia'      => $estructura['guia'],
            'pasos'     => $estructura['pasos'],
            'filas'     => is_array($old) ? $old : $filas,
            'variantes' => $variantes,
            'nombres'   => $nombres,
            'fotos'     => $this->fotos(array_keys($nombres)),
        ]);
    }

    public function actualizar(int $id)
    {
        if (!$this->guias->find($id)) {
            return redirect()->to(site_url('piezas/guias'))->with('error', 'Esa guía no existe.');
        }

        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '') {
            return $this->volver($id)->with('error', 'El nombre no puede quedar vacío.');
        }

        $this->guias->update($id, [
            'nombre' => $nombre,
            'icono'  => $this->icono((string) $this->request->getPost('icono')),
            'activa' => $this->request->getPost('activa') ? 1 : 0,
        ]);

        return $this->volver($id)->with('success', 'Guía guardada.');
    }

    public function borrar(int $id)
    {
        $guia = $this->guias->find($id);
        if ($guia) {
            $this->guias->delete($id);
        }

        return redirect()->to(site_url('piezas/guias'))->with('success', $guia ? 'Guía «' . $guia['nombre'] . '» borrada.' : 'Esa guía ya no existía.');
    }

    public function crearPaso(int $guiaId)
    {
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($nombre === '' || !$this->guias->find($guiaId)) {
            return $this->volver($guiaId)->with('error', 'Pon un nombre al paso.');
        }

        $porDefecto = trim((string) $this->request->getPost('por_defecto')) ?: 'Estándar';

        $pasoId = $this->pasos->insert([
            'guia_id' => $guiaId,
            'nombre'  => $nombre,
            'orden'   => $this->siguienteOrden($this->pasos, 'guia_id', $guiaId),
        ]);
        $opcionId = $this->opciones->insert(['paso_id' => $pasoId, 'nombre' => $porDefecto, 'orden' => 1]);
        $movidas  = $this->servicio->anadirOpcionATodas($guiaId, (int) $opcionId);

        return $this->volver($guiaId)->with('success', 'Paso añadido con la opción «' . $porDefecto . '».'
            . ($movidas > 0 ? ' Las ' . $movidas . ' piezas ya asignadas pasan a esa opción.' : ''));
    }

    public function crearOpcion(int $pasoId)
    {
        $paso   = $this->pasos->find($pasoId);
        $nombre = trim((string) $this->request->getPost('nombre'));
        if (!$paso) {
            return redirect()->to(site_url('piezas/guias'));
        }
        if ($nombre === '') {
            return $this->volver((int) $paso['guia_id'])->with('error', 'Pon un nombre a la opción.');
        }

        $this->opciones->insert([
            'paso_id' => $pasoId,
            'nombre'  => $nombre,
            'orden'   => $this->siguienteOrden($this->opciones, 'paso_id', $pasoId),
        ]);

        return $this->volver((int) $paso['guia_id'])->with('success', 'Opción añadida.');
    }

    public function renombrar(string $tipo, int $id)
    {
        [$model, $fila, $guiaId] = $this->elemento($tipo, $id);
        $nombre = trim((string) $this->request->getPost('nombre'));
        if ($fila && $nombre !== '') {
            $model->update($id, ['nombre' => $nombre]);
        }

        return $this->volver($guiaId);
    }

    public function mover(string $tipo, int $id, string $sentido)
    {
        [$model, $fila, $guiaId] = $this->elemento($tipo, $id);
        if (!$fila) {
            return $this->volver($guiaId);
        }

        $campo    = $tipo === 'paso' ? 'guia_id' : 'paso_id';
        $hermanos = $model->where($campo, $fila[$campo])->orderBy('orden')->orderBy('id')->findAll();
        $ids      = array_map('intval', array_column($hermanos, 'id'));
        $pos      = array_search($id, $ids, true);
        $otro     = $sentido === 'subir' ? $pos - 1 : $pos + 1;

        if ($pos !== false && isset($ids[$otro])) {
            [$ids[$pos], $ids[$otro]] = [$ids[$otro], $ids[$pos]];
            foreach ($ids as $i => $hid) {
                $model->update($hid, ['orden' => $i + 1]);
            }
        }

        return $this->volver($guiaId);
    }

    public function borrarElemento(string $tipo, int $id)
    {
        [$model, $fila, $guiaId] = $this->elemento($tipo, $id);
        if ($fila) {
            $model->delete($id);
            $this->servicio->limpiar($guiaId);
        }

        return $this->volver($guiaId)->with('success', $tipo === 'paso' ? 'Paso borrado con sus opciones.' : 'Opción borrada.');
    }

    /**
     * Guarda la lista de piezas de la guía entera (sustituye a la anterior).
     * Cada fila: una variante y una opción por paso. No deja guardar filas a
     * medias, dos piezas en la misma combinación ni la misma pieza dos veces.
     */
    public function resultados(int $guiaId)
    {
        $estructura = $this->servicio->estructura($guiaId);
        if (!$estructura) {
            return redirect()->to(site_url('piezas/guias'));
        }

        $nombres = array_replace([], ...array_values(array_column($this->variantesAgrupadas(), 'variantes')));
        $filas   = [];
        $porClave = [];
        $errores = [];
        $vistas  = [];

        foreach ((array) $this->request->getPost('filas') as $f) {
            $vid = (int) ($f['variante'] ?? 0);
            if ($vid <= 0) {
                continue;
            }
            if (isset($vistas[$vid])) {
                $errores[] = '«' . ($nombres[$vid] ?? '#' . $vid) . '» está más de una vez: cada pieza solo puede estar una vez en la guía.';
            }
            $vistas[$vid] = true;
            $elegidas = [];
            $ids      = [];
            foreach ($estructura['pasos'] as $p) {
                $oid    = (int) ($f['opciones'][$p['id']] ?? 0);
                $valida = in_array($oid, array_map('intval', array_column($p['opciones'], 'id')), true);
                if (!$valida) {
                    $errores[] = '«' . ($nombres[$vid] ?? '#' . $vid) . '»: falta elegir ' . mb_strtolower($p['nombre']) . '.';
                    continue;
                }
                $elegidas[(int) $p['id']] = $oid;
                $ids[] = $oid;
            }
            $filas[] = ['variante' => $vid, 'opciones' => $elegidas];

            if (count($ids) === count($estructura['pasos'])) {
                $clave = PiezaGuias::clave($ids);
                if (isset($porClave[$clave])) {
                    $errores[] = '«' . ($nombres[$porClave[$clave]] ?? '') . '» y «' . ($nombres[$vid] ?? '') . '» tienen la misma combinación: solo puede haber una pieza por combinación.';
                }
                $porClave[$clave] = $vid;
            }
        }

        if ($errores !== []) {
            return $this->volver($guiaId)->with('errores_guia', $errores)->with('filas_guia', $filas);
        }

        $this->servicio->reemplazarResultados($guiaId, $porClave);

        return $this->volver($guiaId)->with('success', count($porClave) . ' piezas guardadas en la guía.');
    }

    /** La foto más reciente de cada variante, en miniatura. */
    private function fotos(array $ids): array
    {
        helper('piezas_imagenes');
        $fotos = [];
        if ($ids === []) {
            return $fotos;
        }
        foreach ((new PiezaRenderModel())->whereIn('variante_id', $ids)->orderBy('subida_en', 'DESC')->findAll() as $r) {
            $vid = (int) $r['variante_id'];
            if (isset($fotos[$vid]) || (empty($r['ruta_imagen']) && empty($r['hash_imagen']))) {
                continue;
            }
            $fotos[$vid] = imagen_pieza($r, 'render', 't');
        }

        return $fotos;
    }

    /** @return array{0: Model, 1: ?array, 2: int} */
    private function elemento(string $tipo, int $id): array
    {
        if ($tipo === 'paso') {
            $fila = $this->pasos->find($id);

            return [$this->pasos, $fila, (int) ($fila['guia_id'] ?? 0)];
        }

        $fila = $this->opciones->find($id);
        $paso = $fila ? $this->pasos->find($fila['paso_id']) : null;

        return [$this->opciones, $fila, (int) ($paso['guia_id'] ?? 0)];
    }

    /** Todas las variantes vivas, agrupadas por categoría para el <select>. */
    private function variantesAgrupadas(): array
    {
        $categorias = [];
        foreach ((new PiezaCategoriaModel())->ordenadas() as $c) {
            $categorias[(int) $c['id']] = ['nombre' => $c['nombre'], 'variantes' => []];
        }
        $categorias[0] = ['nombre' => 'Sin categoría', 'variantes' => []];

        $familias = [];
        foreach ((new PiezaFamiliaModel())->where('borrado_en', null)->findAll() as $f) {
            $familias[(int) $f['id']] = $f;
        }

        foreach ((new PiezaVarianteModel())->where('borrado_en', null)->findAll() as $v) {
            $f = $familias[(int) $v['familia_id']] ?? null;
            if (!$f) {
                continue;
            }
            $cat = (int) ($f['categoria_id'] ?? 0);
            $categorias[isset($categorias[$cat]) ? $cat : 0]['variantes'][(int) $v['id']] = trim($f['nombre'] . ' ' . $v['nombre']);
        }

        foreach ($categorias as &$c) {
            natcasesort($c['variantes']);
        }
        unset($c);

        return array_filter($categorias, static fn (array $c) => $c['variantes'] !== []);
    }

    private function siguienteOrden(Model $model, ?string $campo = null, ?int $valor = null): int
    {
        $q = $model->builder()->selectMax('orden', 'maximo');
        if ($campo !== null) {
            $q->where($campo, $valor);
        }

        return (int) ($q->get()->getRow()->maximo ?? 0) + 1;
    }

    private function icono(string $icono): string
    {
        $icono = trim($icono);

        return preg_match('/^(bi|svg)-[a-z0-9-]+$/', $icono) ? $icono : 'bi-signpost-split';
    }

    private function volver(int $guiaId)
    {
        return redirect()->to(site_url($guiaId > 0 ? 'piezas/guias/' . $guiaId : 'piezas/guias'));
    }
}
