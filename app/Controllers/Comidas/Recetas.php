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
        $m = new ComidasRecetasModel();
        $rows = $m->orderBy('nombre','ASC')->findAll(50);
        return view('comidas/recetas/index', ['rows'=>$rows, 'title'=>'Recetas']);
    }

    public function create()
    {
        $alimentos = (new ComidasAlimentosModel())
            ->orderBy('nombre','ASC')->findAll(200);

        return view('comidas/recetas/form', [
            'alimentos'   => $alimentos,
            'ingredientes'=> [],
            'title'       => 'Nueva receta'
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
        $ingM = new ComidasRecetaIngredientesModel();
        $aliM = new ComidasAlimentosModel();

        $row = $m->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/recetas'))
                   ->with('errors',['Receta no encontrada.']);
        }

        $alimentos = $aliM->orderBy('nombre','ASC')->findAll(400);

        $ingredientes = $ingM->where('receta_id',$id)->findAll();
        foreach ($ingredientes as &$ing) {
            $a = $aliM->find($ing['alimento_id']);
            $ing['alimento_nombre'] = $a['nombre'] ?? ('#'.$ing['alimento_id']);
        }

        return view('comidas/recetas/form', [
            'row'          => $row,
            'alimentos'    => $alimentos,
            'ingredientes' => $ingredientes,
            'title'        => 'Editar receta'
        ]);
    }

    public function update($id)
    {
        $m = new ComidasRecetasModel();

        // Añadir ingrediente inline
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
                'receta_id'   => (int)$id,
                'alimento_id' => $alimId,
                'gramos'      => $g,
                'notas'       => $this->request->getPost('notas') ?: null,
            ]);

            (new RecipeService())->rebuildAlimentoFromReceta((int)$id);

            return redirect()->to(site_url('comidas/recetas/edit/'.$id))
                             ->with('msg','Ingrediente añadido.');
        }

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

    public function removeIngrediente($ingId)
    {
        $ingM = new ComidasRecetaIngredientesModel();
        $row  = $ingM->find($ingId);

        if ($row) {
            $recetaId = (int) $row['receta_id'];
            $ingM->delete($ingId);

            (new RecipeService())->rebuildAlimentoFromReceta($recetaId);

            return redirect()->to(site_url('comidas/recetas/edit/'.$recetaId))
                             ->with('msg','Ingrediente eliminado.');
        }

        return redirect()->back()->with('errors',['Ingrediente no encontrado.']);
    }
}
