<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasObjetivosModel;

class Objetivos extends BaseController
{
    public function index()
    {
        $m = new ComidasObjetivosModel();
        $rows = $m->where('user_id', user_id())->orderBy('fecha_inicio','DESC')->findAll(50);
        return view('comidas/objetivos/index', ['rows'=>$rows, 'title'=>'Objetivos']);
    }

    public function create()
    {
        return view('comidas/objetivos/form', ['title'=>'Nuevo objetivo']);
    }

    public function store()
    {
        $m = new ComidasObjetivosModel();
        $data = $this->request->getPost();
        $data['user_id'] = user_id();
        $m->insert($data);
        return redirect()->to(site_url('comidas/objetivos'));
    }

    public function edit($id)
    {
        $m = new ComidasObjetivosModel();
        $row = $m->find($id);
        return view('comidas/objetivos/form', ['row'=>$row, 'title'=>'Editar objetivo']);
    }

    public function update($id)
    {
        $m = new ComidasObjetivosModel();
        $data = $this->request->getPost();
        $m->update($id, $data);
        return redirect()->to(site_url('comidas/objetivos'));
    }
}
