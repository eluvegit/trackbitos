<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasAlimentosModel;

class Alimentos extends BaseController
{
    private function currentUserId(): int
    {
        // Fallback a 1 si no hay sesión (como comentaste que es mono-usuario)
        $uid = function_exists('user_id') ? (user_id() ?: 0) : 0;
        return $uid > 0 ? $uid : 1;
    }

    public function index()
    {
        $q = trim((string) $this->request->getGet('q'));
        $m = new ComidasAlimentosModel();

        $uid = $this->currentUserId();

        $query = $m->where('user_id', $uid);
        if ($q !== '') {
            $query = $query->like('nombre', $q);
        }

        // OPCIÓN A: sin límite (mostrar todo)
        // $rows = $query->orderBy('nombre','ASC')->findAll();

        // OPCIÓN B: paginación (recomendado)
        $rows = $query->orderBy('nombre','ASC')->paginate(100); // 100 por página
        $pager = $m->pager;

        return view('comidas/alimentos/index', [
            'rows'  => $rows,
            'q'     => $q,
            'title' => 'Alimentos',
            'pager' => $pager,  // si usas paginate()
        ]);
    }

    public function create()
    {
        return view('comidas/alimentos/form', ['title'=>'Nuevo alimento']);
    }

    public function store()
    {
        $m = new ComidasAlimentosModel();
        $data = $this->request->getPost();

        // Asegura user_id válido siempre
        $data['user_id'] = $this->currentUserId();

        // Normaliza checkbox/numéricos si hace falta (evita strings vacíos)
        $data['es_liquido'] = isset($data['es_liquido']) ? 1 : 0;

        $m->insert($data);
        return redirect()->to(site_url('comidas/alimentos'));
    }

    public function edit($id)
    {
        $m = new ComidasAlimentosModel();
        $row = $m->find($id);
        return view('comidas/alimentos/form', ['row'=>$row, 'title'=>'Editar alimento']);
    }

    public function update($id)
    {
        $m = new ComidasAlimentosModel();
        $data = $this->request->getPost();

        // Normaliza checkbox
        $data['es_liquido'] = isset($data['es_liquido']) ? 1 : 0;

        $m->update($id, $data);
        return redirect()->to(site_url('comidas/alimentos'));
    }
}
