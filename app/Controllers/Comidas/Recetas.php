<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasRecetasModel;
use App\Models\ComidasRecetaIngredientesModel;
use App\Models\ComidasAlimentosModel;
use App\Services\RecipeService; // sigue siendo importante

class Recetas extends BaseController
{
    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));

        $m = new \App\Models\ComidasRecetasModel();

        $m->select('comidas_recetas.*, a.kcal, a.proteina_g, a.carbohidratos_g, a.grasas_g')
            ->join('comidas_alimentos a', 'a.receta_id = comidas_recetas.id', 'left');

        if ($q !== '') {
            $m->like('comidas_recetas.nombre', $q);
        }

        $rows = $m->orderBy('comidas_recetas.nombre', 'ASC')->findAll();

        // Petición parcial (AJAX o ?partial=1): solo las filas, para el buscador.
        if ($this->request->isAJAX() || $this->request->getGet('partial') === '1') {
            return view('comidas/recetas/_rows', ['rows' => $rows]);
        }

        return view('comidas/recetas/index', [
            'rows'  => $rows,
            'q'     => $q,
            'title' => 'Recetas',
        ]);
    }

    public function create()
    {
        return view('comidas/recetas/form', [
            'title' => 'Nueva receta'
        ]);
    }

    public function store()
    {
        $m = new ComidasRecetasModel();

        $data = [
            'nombre'            => trim($this->request->getPost('nombre') ?? ''),
            'descripcion'       => $this->request->getPost('descripcion') ?? null,
            'raciones'          => $this->request->getPost('raciones') ?: null,
            'gramos_por_racion' => $this->request->getPost('gramos_por_racion') ?: null,
        ];

        if ($data['nombre'] === '') {
            return redirect()->back()->withInput()
                   ->with('errors',['El nombre es obligatorio.']);
        }

        $m->insert($data);
        $recetaId = (int) $m->getInsertID();

        // Recalcular alimento virtual
        (new RecipeService())->rebuildAlimentoFromReceta($recetaId);

        return redirect()->to(site_url('comidas/recetas/edit/'.$recetaId))
                         ->with('msg','Receta creada.');
    }

    public function edit($id)
    {
        $m    = new ComidasRecetasModel();
        $aliM = new ComidasAlimentosModel();

        $row = $m->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/recetas'))
                   ->with('errors',['Receta no encontrada.']);
        }

        $aliVirt   = $aliM->where('es_receta', 1)->where('receta_id', $id)->first();
        $aliVirtId = $aliVirt['id'] ?? null;

        return view('comidas/recetas/form', [
            'row'       => $row,
            'aliVirtId' => $aliVirtId,
            'title'     => 'Editar receta'
        ]);
    }

    public function update($id)
    {
        $m = new ComidasRecetasModel();

        // Actualizar metadatos
        $payload = [
            'nombre'            => trim($this->request->getPost('nombre') ?? ''),
            'descripcion'       => $this->request->getPost('descripcion') ?? null,
            'raciones'          => $this->request->getPost('raciones') ?: null,
            'gramos_por_racion' => $this->request->getPost('gramos_por_racion') ?: null,
        ];
        if ($payload['nombre'] === '') {
            return redirect()->back()->withInput()
                   ->with('errors',['El nombre es obligatorio.']);
        }

        // Verifica existencia
        if (!$m->find($id)) {
            return redirect()->to(site_url('comidas/recetas'))
                   ->with('errors',['Receta no encontrada.']);
        }

        $m->update($id, $payload);

        (new RecipeService())->rebuildAlimentoFromReceta((int)$id);

        return redirect()->to(site_url('comidas/recetas/edit/'.$id))
                         ->with('msg','Receta actualizada.');
    }

    // =================== AJAX ingredientes ======================

    public function ingredientesAjax($recetaId)
    {
        $ingM = new ComidasRecetaIngredientesModel();

        $rows = $ingM
            ->select('comidas_receta_ingredientes.id, comidas_receta_ingredientes.alimento_id, comidas_receta_ingredientes.gramos,
                      a.nombre, a.kcal, a.proteina_g, a.carbohidratos_g, a.grasas_g')
            ->join('comidas_alimentos a', 'a.id = comidas_receta_ingredientes.alimento_id', 'left')
            ->where('comidas_receta_ingredientes.receta_id', (int) $recetaId)
            ->orderBy('comidas_receta_ingredientes.id', 'ASC')
            ->findAll();

        return $this->response->setJSON($rows);
    }

    public function addIngredienteAjax($recetaId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $recetaId = (int) $recetaId;
        $alimId   = (int) $this->request->getPost('alimento_id');
        $g        = (float) str_replace(',', '.', (string) $this->request->getPost('gramos'));

        if (!(new ComidasRecetasModel())->find($recetaId)) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Receta no encontrada']);
        }
        if ($alimId <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Selecciona un alimento']);
        }
        if ($g <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Los gramos deben ser mayores que 0']);
        }

        $ingM = new ComidasRecetaIngredientesModel();
        $ingM->insert([
            'receta_id'   => $recetaId,
            'alimento_id' => $alimId,
            'gramos'      => $g,
        ]);

        (new RecipeService())->rebuildAlimentoFromReceta($recetaId);

        return $this->response->setJSON(['ok' => true]);
    }

    public function editIngredienteAjax($ingId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $ingM = new ComidasRecetaIngredientesModel();
        $row  = $ingM->find($ingId);
        if (!$row) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Ingrediente no encontrado']);
        }

        $g = (float) str_replace(',', '.', (string) $this->request->getPost('gramos'));
        if ($g <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Los gramos deben ser mayores que 0']);
        }

        $ingM->update($ingId, ['gramos' => $g]);

        (new RecipeService())->rebuildAlimentoFromReceta((int) $row['receta_id']);

        return $this->response->setJSON(['ok' => true]);
    }

    public function deleteIngredienteAjax($ingId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $ingM = new ComidasRecetaIngredientesModel();
        $row  = $ingM->find($ingId);
        if (!$row) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Ingrediente no encontrado']);
        }

        $recetaId = (int) $row['receta_id'];
        $ingM->delete($ingId);

        (new RecipeService())->rebuildAlimentoFromReceta($recetaId);

        return $this->response->setJSON(['ok' => true]);
    }

    public function delete($id)
    {
        $id = (int) $id;

        $recM  = new \App\Models\ComidasRecetasModel();
        $ingM  = new \App\Models\ComidasRecetaIngredientesModel();
        $aliM  = new \App\Models\ComidasAlimentosModel();
        $porM  = new \App\Models\ComidasAlimentoUnidadesModel();
        $ingI  = new \App\Models\ComidasIngestasModel();

        $rec = $recM->find($id);
        if (!$rec) {
            return redirect()->to(site_url('comidas/recetas'))
                ->with('errors', ['Receta no encontrada.']);
        }

        // Localiza el alimento “virtual” de la receta (si existe)
        $aliVirt = $aliM->where('es_receta', 1)->where('receta_id', $id)->first();
        $aliVirtId = $aliVirt['id'] ?? null;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1) Ingredientes de la receta
            $ingM->where('receta_id', $id)->delete();

            // 2) Si existe alimento virtual:
            if ($aliVirtId) {
                // 2.a) Ingestas que lo usen
                $ingI->where('item_tipo', 'alimento')->where('item_id', $aliVirtId)->delete();
                // 2.b) Porciones del alimento virtual
                $porM->where('alimento_id', $aliVirtId)->delete();
                // 2.c) El propio alimento virtual
                $aliM->delete($aliVirtId);
            }

            // 3) La receta
            $recM->delete($id);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Fallo al borrar receta.');
            }
            $db->transCommit();

            return redirect()->to(site_url('comidas/recetas'))
                ->with('ok', 'Receta eliminada.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->to(site_url('comidas/recetas'))
                ->with('errors', ['No se pudo eliminar la receta. ' . $e->getMessage()]);
        }
    }
}
