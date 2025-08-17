<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasRecetasModel;
use App\Models\ComidasRecetaIngredientesModel;
use App\Models\ComidasAlimentosModel;
use App\Services\RecipeService; // <-- IMPORTANTE

class Recetas extends BaseController
{
    private function uid(): int
    {
        $u = function_exists('user_id') ? (user_id() ?: 0) : 0;
        return $u > 0 ? $u : 1;
    }

    public function index()
    {
        $m = new ComidasRecetasModel();
        $rows = $m->where('user_id', $this->uid())
                  ->orderBy('nombre','ASC')->findAll(50);
        return view('comidas/recetas/index', ['rows'=>$rows, 'title'=>'Recetas']);
    }

    public function create()
    {
        $alimentos = (new ComidasAlimentosModel())
            ->where('user_id', $this->uid())
            ->orderBy('nombre','ASC')->findAll(200);
        return view('comidas/recetas/form', [
            'alimentos'=>$alimentos,
            'ingredientes'=>[],
            'title'=>'Nueva receta'
        ]);
    }

    public function store()
    {
        $uid = $this->uid();
        $m   = new ComidasRecetasModel();

        $data = [
            'user_id' => $uid,
            'nombre'  => trim($this->request->getPost('nombre') ?? ''),
            'descripcion' => $this->request->getPost('descripcion') ?? null,
            // estos campos existen en tu tabla pero NO se usan para el cálculo por 100g
            'raciones'         => $this->request->getPost('raciones') ?: null,
            'gramos_por_racion'=> $this->request->getPost('gramos_por_racion') ?: null,
        ];

        if ($data['nombre'] === '') {
            return redirect()->back()->withInput()
                   ->with('errors',['El nombre es obligatorio.']);
        }

        $m->insert($data);
        $recetaId = (int)$m->getInsertID();

        // Recalcular alimento virtual (por si añades ingredientes inmediatamente no habrá suma,
        // pero así garantizas su existencia y metadatos al día)
        $svc = new RecipeService();
        $svc->rebuildAlimentoFromReceta($recetaId, $uid);

        return redirect()->to(site_url('comidas/recetas/edit/'.$recetaId))
                         ->with('msg','Receta creada.');
    }

    public function edit($id)
    {
        $uid  = $this->uid();
        $m    = new ComidasRecetasModel();
        $ingM = new ComidasRecetaIngredientesModel();
        $aliM = new ComidasAlimentosModel();

        $row = $m->where('user_id',$uid)->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/recetas'))
                   ->with('errors',['Receta no encontrada.']);
        }

        $alimentos = $aliM->where('user_id', $uid)
                          ->orderBy('nombre','ASC')->findAll(400);

        $ingredientes = $ingM->where('receta_id',$id)->findAll();
        foreach($ingredientes as &$ing){
            $a = $aliM->find($ing['alimento_id']);
            $ing['alimento_nombre'] = $a['nombre'] ?? ('#'.$ing['alimento_id']);
        }

        return view('comidas/recetas/form', [
            'row'=>$row,
            'alimentos'=>$alimentos,
            'ingredientes'=>$ingredientes,
            'title'=>'Editar receta'
        ]);
    }

    public function update($id)
    {
        $uid = $this->uid();
        $m   = new ComidasRecetasModel();

        // Añadir ingrediente inline desde el formulario de edición
        if ($this->request->getPost('action') === 'add_ingrediente') {
            $alimId = (int) $this->request->getPost('alimento_id');
            $g      = (float) $this->request->getPost('gramos');

            $errs = [];
            if ($alimId <= 0) $errs[] = 'Selecciona un alimento.';
            if ($g <= 0)      $errs[] = 'Los gramos deben ser > 0.';
            if ($errs) {
                return redirect()->back()->withInput()->with('errors', $errs);
            }

            $ingM = new ComidasRecetaIngredientesModel();
            $ingM->insert([
                'receta_id'  => (int)$id,
                'alimento_id'=> $alimId,
                'gramos'     => $g,
                'notas'      => $this->request->getPost('notas') ?: null,
            ]);

            // Recalcular alimento virtual tras tocar ingredientes
            $svc = new RecipeService();
            $svc->rebuildAlimentoFromReceta((int)$id, $uid);

            return redirect()->to(site_url('comidas/recetas/edit/'.$id))
                             ->with('msg','Ingrediente añadido.');
        }

        // Actualizar metadatos de la receta
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

        // Verifica ownership
        $exists = $m->where('user_id',$uid)->find($id);
        if (!$exists) {
            return redirect()->to(site_url('comidas/recetas'))
                   ->with('errors',['Receta no encontrada.']);
        }

        $m->update($id, $payload);

        // Recalcular alimento virtual (por si cambió el nombre u otros metadatos)
        $svc = new RecipeService();
        $svc->rebuildAlimentoFromReceta((int)$id, $uid);

        return redirect()->to(site_url('comidas/recetas/edit/'.$id))
                         ->with('msg','Receta actualizada.');
    }

    public function removeIngrediente($ingId)
    {
        $uid  = $this->uid();
        $ingM = new ComidasRecetaIngredientesModel();
        $row  = $ingM->find($ingId);

        if ($row) {
            $recetaId = (int)$row['receta_id'];
            $ingM->delete($ingId);

            // Recalcular alimento virtual tras eliminar ingrediente
            $svc = new RecipeService();
            $svc->rebuildAlimentoFromReceta($recetaId, $uid);

            return redirect()->to(site_url('comidas/recetas/edit/'.$recetaId))
                             ->with('msg','Ingrediente eliminado.');
        }

        return redirect()->back()->with('errors',['Ingrediente no encontrado.']);
    }
}
