<?php

namespace App\Controllers;


use App\Models\RodajesProyectoModel;
use CodeIgniter\HTTP\Files\UploadedFile;


class Rodajes extends BaseController
{
    public function index()
    {
        $model = new RodajesProyectoModel();
        $data['proyectos'] = $model->orderBy('created_at', 'DESC')->findAll();
        return view('rodajes/index', $data);
    }


    public function create()
    {
        return view('rodajes/form', ['proyecto' => null]);
    }


    public function store()
    {
        $model = new RodajesProyectoModel();
        $payload = $this->request->getPost(['titulo', 'codigo', 'descripcion']);
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
        return view('rodajes/form', ['proyecto' => $proyecto]);
    }


    public function update($id)
    {
        $model = new RodajesProyectoModel();
        $payload = $this->request->getPost(['titulo', 'codigo', 'descripcion']);
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
}
