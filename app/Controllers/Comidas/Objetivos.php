<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasLimitesModel;
use App\Models\ComidasNutrientesModel;

class Objetivos extends BaseController
{
    // LISTADO
    public function index()
    {
        $db = \Config\Database::connect();
        $rows = $db->table('comidas_limites cl')
            ->select('cl.id, cl.tipo, cl.umbral, cl.nivel, cl.mensaje,
                      n.id AS nutriente_id, n.nombre AS nutriente_nombre,
                      n.clave AS nutriente_clave, n.unidad AS nutriente_unidad,
                      n.es_macro, n.orden, n.categoria')
            ->join('comidas_nutrientes n', 'n.id = cl.nutriente_id', 'inner')
            // (sin filtro por user_id)
            ->orderBy('n.es_macro', 'DESC')
            ->orderBy('n.orden', 'ASC')
            ->orderBy('n.nombre', 'ASC')
            ->get()->getResultArray();

        return view('comidas/objetivos/index', [
            'rows'  => $rows,
            'title' => 'Límites diarios por nutriente',
        ]);
    }

    // FORM CREAR
    public function create()
    {
        $nutM = new ComidasNutrientesModel();
        $nutrientes = $nutM->orderBy('es_macro', 'DESC')
                           ->orderBy('orden', 'ASC')
                           ->orderBy('nombre', 'ASC')
                           ->findAll();

        return view('comidas/objetivos/form', [
            'title'      => 'Nuevo límite',
            'nutrientes' => $nutrientes,
            'row'        => null,
        ]);
    }

    // GUARDAR NUEVO (UPSERT por (nutriente_id, tipo))
    public function store()
    {
        $validated = $this->validateLimitInput();
        if ($validated !== true) return $validated;

        $nutId   = (int) $this->request->getPost('nutriente_id');
        $tipo    = (string) $this->request->getPost('tipo');   // 'falta'|'exceso'
        $umbral  = (float) str_replace(',', '.', (string) $this->request->getPost('umbral'));
        $nivel   = (string) $this->request->getPost('nivel');  // 'info'|'warning'|'critical'
        $mensaje = (string) ($this->request->getPost('mensaje') ?? '');

        $m = new ComidasLimitesModel();

        // Upsert manual por clave única (nutriente_id, tipo)
        $existing = $m->where(['nutriente_id' => $nutId, 'tipo' => $tipo])->first();
        if ($existing) {
            $m->update($existing['id'], [
                'umbral'  => $umbral,
                'nivel'   => $nivel,
                'mensaje' => $mensaje,
            ]);
        } else {
            $m->insert([
                'nutriente_id' => $nutId,
                'tipo'         => $tipo,
                'umbral'       => $umbral,
                'nivel'        => $nivel,
                'mensaje'      => $mensaje,
            ]);
        }

        return redirect()->to(site_url('comidas/objetivos'))->with('ok', 'Límite guardado');
    }

    // FORM EDITAR
    public function edit($id)
    {
        $m   = new ComidasLimitesModel();
        $row = $m->find($id);
        if (!$row) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $nutM = new ComidasNutrientesModel();
        $nutrientes = $nutM->orderBy('es_macro', 'DESC')
                           ->orderBy('orden', 'ASC')
                           ->orderBy('nombre', 'ASC')
                           ->findAll();

        return view('comidas/objetivos/form', [
            'title'      => 'Editar límite',
            'nutrientes' => $nutrientes,
            'row'        => $row,
        ]);
    }

    // ACTUALIZAR
    public function update($id)
    {
        $validated = $this->validateLimitInput();
        if ($validated !== true) return $validated;

        $nutId   = (int) $this->request->getPost('nutriente_id');
        $tipo    = (string) $this->request->getPost('tipo');
        $umbral  = (float) str_replace(',', '.', (string) $this->request->getPost('umbral'));
        $nivel   = (string) $this->request->getPost('nivel');
        $mensaje = (string) ($this->request->getPost('mensaje') ?? '');

        $m = new ComidasLimitesModel();

        // Evita romper la UNIQUE si cambia a otro existente (por nutriente_id, tipo)
        $dup = $m->where(['nutriente_id' => $nutId, 'tipo' => $tipo])->first();
        if ($dup && (int)$dup['id'] !== (int)$id) {
            return redirect()->back()->withInput()->with('errors', [
                'unique' => 'Ya existe un límite para ese nutriente y tipo (falta/exceso).'
            ]);
        }

        $m->update($id, [
            'nutriente_id' => $nutId,
            'tipo'         => $tipo,
            'umbral'       => $umbral,
            'nivel'        => $nivel,
            'mensaje'      => $mensaje,
        ]);

        return redirect()->to(site_url('comidas/objetivos'))->with('ok', 'Límite actualizado');
    }

    // ELIMINAR
    public function delete($id)
    {
        $m = new ComidasLimitesModel();
        $m->delete($id);
        return redirect()->to(site_url('comidas/objetivos'))->with('ok','Límite eliminado');
    }

    // ================= Helpers =================

    private function validateLimitInput()
    {
        $rules = [
            'nutriente_id' => 'required|integer',
            'tipo'         => 'required|in_list[falta,exceso]',
            'umbral'       => 'required|numeric',
            'nivel'        => 'required|in_list[info,warning,critical]',
            'mensaje'      => 'permit_empty|string|max_length[140]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        return true;
    }
}
