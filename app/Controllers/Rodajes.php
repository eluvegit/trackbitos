<?php

namespace App\Controllers;


use App\Models\MoodboardItemModel;
use App\Models\RodajesEscenaImagenModel;
use App\Models\RodajesEscenaModel;
use App\Models\RodajesProyectoModel;
use App\Models\SesionModel;
use App\Models\SituacionModel;


class Rodajes extends BaseController
{
    protected $helpers = ['rodajes'];

    public function index()
    {
        $model = new RodajesProyectoModel();
        $data['proyectos'] = $model->orderBy('created_at', 'DESC')->findAll();
        $data['sesiones']  = $this->sesionesPorId();

        // Progreso de plató por proyecto: total / hechas.
        $data['progreso'] = [];
        $ids = array_column($data['proyectos'], 'id');
        if ($ids) {
            $filas = (new RodajesEscenaModel())
                ->select("proyecto_id, COUNT(*) AS total, SUM(toma_estado = 'hecha') AS hechas", false)
                ->whereIn('proyecto_id', $ids)
                ->groupBy('proyecto_id')
                ->findAll();
            foreach ($filas as $f) {
                $data['progreso'][$f['proyecto_id']] = ['total' => (int) $f['total'], 'hechas' => (int) $f['hechas']];
            }
        }

        // Portada de cada proyecto: la primera imagen subida a sus escenas.
        $data['portadas'] = [];
        if ($ids) {
            $filas = (new RodajesEscenaImagenModel())
                ->select('rodajes_escenas.proyecto_id, rodajes_escena_imagenes.ruta')
                ->join('rodajes_escenas', 'rodajes_escenas.id = rodajes_escena_imagenes.escena_id AND rodajes_escenas.deleted_at IS NULL')
                ->whereIn('rodajes_escenas.proyecto_id', $ids)
                ->orderBy('rodajes_escenas.orden', 'ASC')->orderBy('rodajes_escena_imagenes.id', 'ASC')
                ->findAll();
            foreach ($filas as $f) {
                if (!isset($data['portadas'][$f['proyecto_id']]) && !rod_es_video($f['ruta'])) {
                    $data['portadas'][$f['proyecto_id']] = $f['ruta'];
                }
            }
        }

        return view(rod_vista('rodajes/index'), $data);
    }


    public function create()
    {
        return view(rod_vista('rodajes/form'), ['proyecto' => null, 'sesiones' => $this->sesionesPorId()]);
    }


    public function store()
    {
        $model = new RodajesProyectoModel();
        $payload = $this->request->getPost(['titulo', 'codigo', 'descripcion']);
        $payload['sesion_id'] = $this->sesionIdDelPost();
        if (!$model->save($payload)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to(site_url('rodajes'));
    }


    public function edit($id)
    {
        $model = new RodajesProyectoModel();
        $proyecto = $model->find($id);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        return view(rod_vista('rodajes/form'), ['proyecto' => $proyecto, 'sesiones' => $this->sesionesPorId()]);
    }


    public function update($id)
    {
        $model = new RodajesProyectoModel();
        $payload = $this->request->getPost(['titulo', 'codigo', 'descripcion']);
        $payload['sesion_id'] = $this->sesionIdDelPost();
        $payload['id'] = $id;
        if (!$model->save($payload)) {
            return redirect()->back()->withInput()->with('errors', $model->errors());
        }
        return redirect()->to(site_url('rodajes'));
    }


    public function delete($id)
    {
        $model = new RodajesProyectoModel();
        $model->delete($id);
        return redirect()->to(site_url('rodajes'));
    }

    // ========== VÍNCULO CON SESIONES ==========

    /**
     * Crea un rodaje a partir de una sesión de stock: mismo título, notas
     * como descripción y una escena por cada situación de la sesión.
     */
    public function desdeSesion($sesionId)
    {
        $sesion = (new SesionModel())->find((int) $sesionId);
        if (!$sesion) {
            return redirect()->to(site_url('sesiones'))->with('error', 'Sesión no encontrada.');
        }

        $proyectos = new RodajesProyectoModel();
        $titulo = trim((string) $sesion['titulo']);
        if (mb_strlen($titulo) < 3) {
            $titulo = 'Sesión ' . $titulo;
        }
        $proyectos->insert([
            'titulo'      => mb_substr($titulo, 0, 150),
            'descripcion' => $sesion['notas'] ?? null,
            'sesion_id'   => (int) $sesion['id'],
        ]);
        $proyectoId = (int) $proyectos->getInsertID();

        $n = $this->traerSituaciones($proyectoId, (int) $sesion['id']);

        return redirect()->to(site_url("rodajes/$proyectoId/escenas"))
            ->with('success', "Rodaje creado desde la sesión con $n " . ($n === 1 ? 'escena' : 'escenas') . '.');
    }

    /**
     * Pasa información entre el rodaje y su sesión vinculada.
     * POST direccion=traer  → situaciones de la sesión sin escena → escenas nuevas.
     * POST direccion=enviar → bloques de escenas sin situación → situaciones
     *                         (reutiliza una situación existente si se llama igual).
     */
    public function sincronizar($id)
    {
        $proyecto = (new RodajesProyectoModel())->find((int) $id);
        $volver   = $this->request->getPost('volver') === 'sesion' && !empty($proyecto['sesion_id'])
            ? site_url('sesiones/' . $proyecto['sesion_id'])
            : site_url("rodajes/$id/escenas");

        if (!$proyecto || empty($proyecto['sesion_id'])) {
            return redirect()->to(site_url('rodajes'))->with('error', 'Este rodaje no está vinculado a ninguna sesión.');
        }
        if (!(new SesionModel())->find((int) $proyecto['sesion_id'])) {
            return redirect()->to($volver)->with('error', 'La sesión vinculada ya no existe.');
        }

        if ($this->request->getPost('direccion') === 'enviar') {
            [$creadas, $vinculadas] = $this->enviarBloques((int) $id, (int) $proyecto['sesion_id']);
            $msg = $vinculadas
                ? "$vinculadas " . ($vinculadas === 1 ? 'escena vinculada' : 'escenas vinculadas') . " a la sesión ($creadas " . ($creadas === 1 ? 'situación nueva' : 'situaciones nuevas') . ').'
                : 'Todas las escenas ya estaban vinculadas a una situación.';
        } else {
            $n   = $this->traerSituaciones((int) $id, (int) $proyecto['sesion_id']);
            $msg = $n ? "$n " . ($n === 1 ? 'escena nueva' : 'escenas nuevas') . ' desde las situaciones de la sesión.' : 'No había situaciones nuevas que traer.';
        }

        return redirect()->to($volver)->with('success', $msg);
    }

    /** Una escena por situación que todavía no tenga escena en este rodaje. */
    private function traerSituaciones(int $proyectoId, int $sesionId): int
    {
        $escenas = new RodajesEscenaModel();
        $yaVinculadas = array_map('intval', array_column(
            $escenas->select('situacion_id')->where('proyecto_id', $proyectoId)->where('situacion_id IS NOT NULL')->findAll(),
            'situacion_id'
        ));
        $orden = (int) ($escenas->select('orden')->where('proyecto_id', $proyectoId)->orderBy('orden', 'DESC')->first()['orden'] ?? 0);

        $situaciones = (new SituacionModel())->where('sesion_id', $sesionId)->orderBy('orden', 'ASC')->findAll();
        $n = 0;
        foreach ($situaciones as $sit) {
            if (in_array((int) $sit['id'], $yaVinculadas, true)) {
                continue;
            }
            $escenas->insert([
                'proyecto_id'   => $proyectoId,
                'situacion_id'  => (int) $sit['id'],
                'escena_bloque' => $sit['nombre'],
                'orden'         => ++$orden,
            ]);
            $n++;
        }
        return $n;
    }

    /** @return array{0:int,1:int} [situaciones creadas, escenas vinculadas] */
    private function enviarBloques(int $proyectoId, int $sesionId): array
    {
        $escenasM = new RodajesEscenaModel();
        $sitM     = new SituacionModel();

        $sueltas = $escenasM->where('proyecto_id', $proyectoId)->where('situacion_id', null)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        if (!$sueltas) {
            return [0, 0];
        }

        $porNombre = [];
        foreach ($sitM->where('sesion_id', $sesionId)->findAll() as $s) {
            $porNombre[mb_strtolower(trim($s['nombre']))] = (int) $s['id'];
        }
        $siguienteOrden = count($porNombre);

        $creadas = 0;
        foreach ($sueltas as $e) {
            $nombre = trim((string) ($e['escena_bloque'] ?? ''));
            if ($nombre === '') {
                $nombre = trim((string) ($e['escena_descripcion'] ?? '')) ?: 'Escena #' . (int) $e['orden'];
            }
            $nombre = mb_substr($nombre, 0, 150);
            $clave  = mb_strtolower($nombre);

            if (!isset($porNombre[$clave])) {
                $sitM->insert(['sesion_id' => $sesionId, 'nombre' => $nombre, 'orden' => $siguienteOrden++]);
                $porNombre[$clave] = (int) $sitM->getInsertID();
                $creadas++;
            }
            $escenasM->update((int) $e['id'], ['situacion_id' => $porNombre[$clave]]);
        }

        return [$creadas, count($sueltas)];
    }

    // ========== MODO PLATÓ ==========

    public function plato($id)
    {
        $proyecto = (new RodajesProyectoModel())->find((int) $id);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));

        $escenas = (new RodajesEscenaModel())->where('proyecto_id', $id)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();

        // Referencias propias de cada escena (lugar + inspiración).
        $media = [];
        if ($escenas) {
            $files = (new RodajesEscenaImagenModel())->whereIn('escena_id', array_column($escenas, 'id'))
                ->orderBy('id', 'ASC')->findAll();
            foreach ($files as $f) {
                $media[$f['escena_id']][] = [
                    'src'   => base_url($f['ruta']),
                    'video' => in_array(strtolower(pathinfo($f['ruta'], PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov'], true),
                ];
            }
        }

        // Moodboard de la sesión vinculada, por situación (solo lectura).
        $sesion = null;
        $moodboard = [];
        if (!empty($proyecto['sesion_id'])) {
            $sesion = (new SesionModel())->find((int) $proyecto['sesion_id']);
            if ($sesion) {
                $items = (new MoodboardItemModel())->where('sesion_id', $sesion['id'])->orderBy('orden', 'ASC')->findAll();
                foreach ($items as $it) {
                    $moodboard[$it['situacion_id'] ?? 'general'][] = $it;
                }
            }
        }

        return view('rodajes/plato', [
            'proyecto'  => $proyecto,
            'escenas'   => $escenas,
            'media'     => $media,
            'sesion'    => $sesion,
            'moodboard' => $moodboard,
        ]);
    }

    /** POST JSON {estado?: ''|hecha|repetir|descartada, nota?: string} */
    public function toma($proyectoId, $escenaId)
    {
        $model  = new RodajesEscenaModel();
        $escena = $model->where('proyecto_id', (int) $proyectoId)->find((int) $escenaId);
        if (!$escena) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $datos = [];

        if (array_key_exists('estado', $input)) {
            $estado = (string) $input['estado'];
            if ($estado !== '' && !in_array($estado, RodajesEscenaModel::TOMA_ESTADOS, true)) {
                return $this->response->setStatusCode(422)->setJSON(['ok' => false]);
            }
            $datos['toma_estado']    = $estado === '' ? null : $estado;
            $datos['toma_estado_at'] = $estado === '' ? null : date('Y-m-d H:i:s');
        }
        if (array_key_exists('nota', $input)) {
            $nota = trim((string) $input['nota']);
            $datos['toma_nota'] = $nota === '' ? null : mb_substr($nota, 0, 255);
        }

        if ($datos) {
            $model->skipValidation(true)->update((int) $escenaId, $datos);
        }

        return $this->response->setJSON(['ok' => true] + $datos);
    }

    /** Vuelve a dejar todas las tomas del rodaje como pendientes (las notas se conservan). */
    public function reiniciarPlato($id)
    {
        (new RodajesEscenaModel())->skipValidation(true)
            ->where('proyecto_id', (int) $id)
            ->set(['toma_estado' => null, 'toma_estado_at' => null])
            ->update();

        return redirect()->to(site_url("rodajes/$id/plato"))->with('success', 'Todas las tomas vuelven a estar pendientes.');
    }

    // ========== AUXILIARES ==========

    /** [id => fila] de sesiones, las más recientes primero. */
    private function sesionesPorId(): array
    {
        $out = [];
        foreach ((new SesionModel())->orderBy('actualizada_at', 'DESC')->findAll() as $s) {
            $out[(int) $s['id']] = $s;
        }
        return $out;
    }

    private function sesionIdDelPost(): ?int
    {
        $v = (int) $this->request->getPost('sesion_id');
        return $v > 0 && (new SesionModel())->find($v) ? $v : null;
    }
}
