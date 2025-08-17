<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasAlimentosModel;
use App\Models\ComidasAlimentoUnidadesModel;

class Porciones extends BaseController
{
    private function currentUserId(): int
    {
        $uid = function_exists('user_id') ? (user_id() ?: 0) : 0;
        return $uid > 0 ? $uid : 1; // mono-usuario fallback
    }

    public function index($alimentoId)
    {
        $uid = $this->currentUserId();
        $alimentosM = new ComidasAlimentosModel();
        $porcionesM = new ComidasAlimentoUnidadesModel();

        $alimento = $alimentosM->where(['id' => $alimentoId, 'user_id' => $uid])->first();
        if (!$alimento) {
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['Alimento no encontrado.']);
        }

        $rows = $porcionesM->where(['user_id' => $uid, 'alimento_id' => $alimentoId])
            ->orderBy('id', 'ASC')->findAll();

        return view('comidas/porciones/index', [
            'title'    => 'Porciones habituales · ' . $alimento['nombre'],
            'alimento' => $alimento,
            'rows'     => $rows,
        ]);
    }

    public function create($alimentoId)
    {
        $uid = $this->currentUserId();
        $alimentosM = new ComidasAlimentosModel();
        $alimento = $alimentosM->where(['id' => $alimentoId, 'user_id' => $uid])->first();
        if (!$alimento) {
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['Alimento no encontrado.']);
        }
        // en Porciones::create($alimentoId) y Porciones::edit($id)
        $unidades = (new \App\Models\ComidasUnidadesModel())->orderBy('nombre', 'ASC')->findAll();
        return view('comidas/porciones/form', [
            'title'    => 'Nueva porción · ' . $alimento['nombre'],
            'alimento' => $alimento,
            'row'      => $row ?? null,
            'unidades' => $unidades,
        ]);
    }

    public function store()
{
    $uid  = $this->currentUserId();
    $data = $this->request->getPost();

    $payload = [
        'user_id'             => $uid,
        'alimento_id'         => (int) ($data['alimento_id'] ?? 0),
        'unidad_id'           => (int) ($data['unidad_id'] ?? 0),              // <--- NECESARIA
        'descripcion'         => trim($data['descripcion'] ?? ''),
        'gramos_equivalentes' => (float) ($data['gramos_equivalentes'] ?? 0),
        'es_predeterminada'   => isset($data['es_predeterminada']) ? 1 : 0,    // <--- OPCIONAL
    ];

    // Validación mínima
    $errors = [];
    if ($payload['alimento_id'] <= 0)                $errors[] = 'Alimento requerido.';
    if ($payload['unidad_id']   <= 0)                $errors[] = 'Unidad requerida.';
    if ($payload['gramos_equivalentes'] <= 0)        $errors[] = 'Equivalencia en gramos > 0.';
    if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

    $m = new ComidasAlimentoUnidadesModel();

    if (!$m->insert($payload)) {
        return redirect()->back()->withInput()->with('errors', $m->errors());
    }

    // Si marcaste predeterminada, desmarca las demás de ese alimento
    if ($payload['es_predeterminada'] === 1) {
        $newId = $m->getInsertID();
        $m->where('user_id', $uid)
          ->where('alimento_id', $payload['alimento_id'])
          ->where('id !=', $newId)
          ->set('es_predeterminada', 0)
          ->update();
    }

    return redirect()->to(site_url('comidas/porciones/alimento/'.$payload['alimento_id']))
                     ->with('msg', 'Porción creada');
}

public function edit($id)
{
    $uid = $this->currentUserId();
    $m   = new ComidasAlimentoUnidadesModel();
    $row = $m->find($id);
    if (!$row || $row['user_id'] != $uid) {
        return redirect()->to(site_url('comidas/alimentos'))->with('errors', ['Porción no encontrada.']);
    }

    $alimentosM = new ComidasAlimentosModel();
    $alimento   = $alimentosM->find($row['alimento_id']);

    $unidades = (new \App\Models\ComidasUnidadesModel())->orderBy('nombre','ASC')->findAll();

    return view('comidas/porciones/form', [
        'title'    => 'Editar porción · '.($alimento['nombre'] ?? ('#'.$row['alimento_id'])),
        'alimento' => $alimento,
        'row'      => $row,
        'unidades' => $unidades,
    ]);
}

public function update($id)
{
    $uid = $this->currentUserId();
    $m   = new ComidasAlimentoUnidadesModel();
    $row = $m->find($id);
    if (!$row || $row['user_id'] != $uid) {
        return redirect()->to(site_url('comidas/alimentos'))->with('errors', ['Porción no encontrada.']);
    }

    $data = $this->request->getPost();
    $payload = [
        'unidad_id'           => (int) ($data['unidad_id'] ?? 0),              // <--- NECESARIA
        'descripcion'         => trim($data['descripcion'] ?? ''),
        'gramos_equivalentes' => (float) ($data['gramos_equivalentes'] ?? 0),
        'es_predeterminada'   => isset($data['es_predeterminada']) ? 1 : 0,    // <--- OPCIONAL
    ];

    // Validación mínima
    $errors = [];
    if ($payload['unidad_id'] <= 0)                 $errors[] = 'Unidad requerida.';
    if ($payload['gramos_equivalentes'] <= 0)       $errors[] = 'Equivalencia en gramos > 0.';
    if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

    if (!$m->update($id, $payload)) {
        return redirect()->back()->withInput()->with('errors', $m->errors());
    }

    if ($payload['es_predeterminada'] === 1) {
        $m->where('user_id', $uid)
          ->where('alimento_id', $row['alimento_id'])
          ->where('id !=', $id)
          ->set('es_predeterminada', 0)
          ->update();
    }

    return redirect()->to(site_url('comidas/porciones/alimento/'.$row['alimento_id']))
                     ->with('msg', 'Porción actualizada');
}


    public function delete($id)
    {
        $uid = $this->currentUserId();
        $m = new ComidasAlimentoUnidadesModel();
        $row = $m->find($id);
        if (!$row || $row['user_id'] != $uid) {
            return redirect()->to(site_url('comidas/alimentos'))->with('errors', ['Porción no encontrada.']);
        }
        $alimentoId = $row['alimento_id'];
        $m->delete($id);
        return redirect()->to(site_url('comidas/porciones/alimento/' . $alimentoId))
            ->with('msg', 'Porción eliminada');
    }
}
