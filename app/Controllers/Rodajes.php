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
                ->select('proyecto_id, COUNT(*) AS total, ' . rod_sql_hechas() . ' AS hechas', false)
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
        $payload['codigo'] = trim((string) $payload['codigo']) ?: null;
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
        $payload['codigo'] = trim((string) $payload['codigo']) ?: null;
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
     * Crea un rodaje a partir de una sesión de stock: mismo título y notas
     * como descripción. Las situaciones de la sesión son las temáticas
     * (= bloques) del rodaje; las tomas salen después al revisar sus ideas,
     * así que no se crea ninguna escena aquí.
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

        return redirect()->to(site_url("rodajes/$proyectoId/ideas"))
            ->with('success', 'Rodaje creado desde la sesión: sus situaciones son las temáticas.');
    }

    /**
     * Antes había dos botones (situaciones → escenas, bloques → situaciones).
     * Ahora la temática ES el bloque y se mantiene sola; esto solo fuerza la
     * sincronización (por si algún enlace antiguo sigue apuntando aquí).
     */
    public function sincronizar($id)
    {
        $proyecto = (new RodajesProyectoModel())->find((int) $id);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));
        RodajesFlujo::sincronizarBloques((int) $id);
        $volver = $this->request->getPost('volver') === 'sesion' && !empty($proyecto['sesion_id'])
            ? site_url('sesiones/' . $proyecto['sesion_id'])
            : site_url("rodajes/$id/escenas");
        return redirect()->to($volver)->with('success', 'Bloques y temáticas al día.');
    }

    // ========== MODO PLATÓ ==========

    public function plato($id)
    {
        $proyecto = (new RodajesProyectoModel())->find((int) $id);
        if (!$proyecto) return redirect()->to(site_url('rodajes'));

        $escenas = RodajesFlujo::conVariantes((new RodajesEscenaModel())->where('proyecto_id', $id)
            ->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll());

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

        // Orden del plató: el de la historia o el de la pizarra por ubicación / montaje (se recuerda).
        $orden = $this->request->getGet('orden');
        if (in_array($orden, ['plan', 'narrativa', 'ubicacion', 'montaje'], true)) {
            session()->set('rodajes_plato_orden', $orden);
        }
        $hayPlan = (bool) array_filter($escenas, static fn ($e) => $e['franja'] || $e['setup_id'] || $e['variantes']);
        $orden = session('rodajes_plato_orden') ?? ($hayPlan ? 'plan' : 'narrativa');

        $puntos = [];
        if ($orden === 'plan') {
            $grupos = [];
            foreach (RodajesFlujo::construirPlan((int) $id, $escenas) as $f) {
                foreach ($f['zonas'] as $z) {
                    foreach ($z['setups'] as $st) {
                        $grupos[] = ['nombre' => $z['zona']['nombre'] ?? '', 'franja' => $f['nombre'], 'zona' => $z['zona'], 'n' => $z['n'], 'setup' => $st['setup'], 'escenas' => $st['escenas']];
                    }
                }
            }
            $zids = array_filter(array_column($escenas, 'ubicacion_id'));
            if ($zids) $puntos = array_column((new \App\Models\RodajesPuntoModel())->whereIn('ubicacion_id', array_unique($zids))->findAll(), 'nombre', 'id');
            $colores = rod_colores($escenas, 'escena_ubicacion');
        } else {
            $grupos  = RodajesPizarra::grupos($escenas, $orden);
            $colores = rod_colores($escenas, RodajesPizarra::VISTAS[$orden][2]);
        }

        return view('rodajes/plato', [
            'proyecto'  => $proyecto,
            'escenas'   => $escenas,
            'grupos'    => $grupos,
            'colores'   => $colores,
            'puntos'    => $puntos,
            'fotos'     => RodajesFlujo::fotosDeTomas(array_column($escenas, 'id')),
            'orden'     => $orden,
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
            // Parte (foto/vídeo) en las escenas «ambas»; si no se indica o no existe, la principal.
            $partes = rod_partes($escena);
            $col    = $partes[(string) ($input['parte'] ?? '')] ?? 'toma_estado';
            $datos[$col]         = $estado === '' ? null : $estado;
            $datos[$col . '_at'] = $estado === '' ? null : date('Y-m-d H:i:s');
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
            ->set(['toma_estado' => null, 'toma_estado_at' => null, 'toma_estado_foto' => null, 'toma_estado_foto_at' => null])
            ->update();
        $ids = array_column((new RodajesEscenaModel())->select('id')->where('proyecto_id', (int) $id)->findAll(), 'id');
        if ($ids) {
            (new \App\Models\RodajesVarianteModel())->whereIn('escena_id', $ids)
                ->set(['toma_estado' => null, 'toma_estado_at' => null, 'toma_estado_foto' => null, 'toma_estado_foto_at' => null])->update();
        }

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
