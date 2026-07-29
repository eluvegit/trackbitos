<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasAlimentosModel;
use App\Models\ComidasAlimentoUnidadesModel;

class Porciones extends BaseController
{
    public function index($alimentoId)
    {
        $alimentosM = new ComidasAlimentosModel();
        $porcionesM = new ComidasAlimentoUnidadesModel();

        $alimento = $alimentosM->where('id', $alimentoId)->first();
        if (!$alimento) {
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['Alimento no encontrado.']);
        }

        $rows = $porcionesM->where('alimento_id', $alimentoId)
            ->orderBy('id', 'ASC')->findAll();

        return view('comidas/porciones/index', [
            'title'    => 'Porciones habituales · ' . $alimento['nombre'],
            'alimento' => $alimento,
            'rows'     => $rows,
        ]);
    }

    public function create($alimentoId)
    {
        $alimentosM = new ComidasAlimentosModel();
        $alimento   = $alimentosM->where('id', $alimentoId)->first();
        if (!$alimento) {
            return redirect()->to(site_url('comidas/alimentos'))
                ->with('errors', ['Alimento no encontrado.']);
        }


        return view('comidas/porciones/form', [
            'title'    => 'Nueva porción · ' . $alimento['nombre'],
            'alimento' => $alimento,
            'row'      => null,
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost();

        // Normaliza número (admite coma)
        $toFloat = static function ($v): float {
            if ($v === null || $v === '') return 0.0;
            if (is_string($v)) $v = str_replace(',', '.', $v);
            return (float)$v;
        };

        $payload = [
            'alimento_id'         => (int) ($data['alimento_id'] ?? 0),
            'descripcion'         => trim($data['descripcion'] ?? ''),
            'gramos_equivalentes' => $toFloat($data['gramos_equivalentes'] ?? 0),
            'es_predeterminada'   => isset($data['es_predeterminada']) ? 1 : 0,
        ];

        // Validación mínima
        $errors = [];
        if ($payload['alimento_id'] <= 0)                $errors[] = 'Alimento requerido.';
        if ($payload['gramos_equivalentes'] <= 0)        $errors[] = 'Equivalencia en gramos > 0.';
        if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

        $m = new ComidasAlimentoUnidadesModel();

        if (!$m->insert($payload)) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        // Si marcaste predeterminada, desmarca las demás de ese alimento
        if ($payload['es_predeterminada'] === 1) {
            $newId = $m->getInsertID();
            $m->where('alimento_id', $payload['alimento_id'])
              ->where('id !=', $newId)
              ->set('es_predeterminada', 0)
              ->update();
        }

        return redirect()->to(site_url('comidas/porciones/alimento/'.$payload['alimento_id']))
                         ->with('msg', 'Porción creada');
    }

    public function edit($id)
    {
        $m   = new ComidasAlimentoUnidadesModel();
        $row = $m->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/alimentos'))->with('errors', ['Porción no encontrada.']);
        }

        $alimento = (new ComidasAlimentosModel())->find($row['alimento_id']);

        return view('comidas/porciones/form', [
            'title'    => 'Editar porción · '.($alimento['nombre'] ?? ('#'.$row['alimento_id'])),
            'alimento' => $alimento,
            'row'      => $row,
        ]);
    }

    public function update($id)
    {
        $m   = new ComidasAlimentoUnidadesModel();
        $row = $m->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/alimentos'))->with('errors', ['Porción no encontrada.']);
        }

        $data = $this->request->getPost();

        // Normaliza número (admite coma)
        $toFloat = static function ($v): float {
            if ($v === null || $v === '') return 0.0;
            if (is_string($v)) $v = str_replace(',', '.', $v);
            return (float)$v;
        };

        $payload = [
            'descripcion'         => trim($data['descripcion'] ?? ''),
            'gramos_equivalentes' => $toFloat($data['gramos_equivalentes'] ?? 0),
            'es_predeterminada'   => isset($data['es_predeterminada']) ? 1 : 0,
        ];

        // Validación mínima
        $errors = [];
        if ($payload['gramos_equivalentes'] <= 0)       $errors[] = 'Equivalencia en gramos > 0.';
        if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

        if (!$m->update($id, $payload)) {
            return redirect()->back()->withInput()->with('errors', $m->errors());
        }

        if ($payload['es_predeterminada'] === 1) {
            $m->where('alimento_id', $row['alimento_id'])
              ->where('id !=', $id)
              ->set('es_predeterminada', 0)
              ->update();
        }

        return redirect()->to(site_url('comidas/porciones/alimento/'.$row['alimento_id']))
                         ->with('msg', 'Porción actualizada');
    }

    public function delete($id)
    {
        $m   = new ComidasAlimentoUnidadesModel();
        $row = $m->find($id);
        if (!$row) {
            return redirect()->to(site_url('comidas/alimentos'))->with('errors', ['Porción no encontrada.']);
        }

        $alimentoId = $row['alimento_id'];
        $m->delete($id);

        return redirect()->to(site_url('comidas/porciones/alimento/' . $alimentoId))
            ->with('msg', 'Porción eliminada');
    }

    // =================== AJAX (edición inline, p.ej. desde el formulario de receta) ===================

    private function toFloatComa($v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_string($v)) {
            $v = str_replace(',', '.', $v);
        }
        return (float) $v;
    }

    public function listAjax($alimentoId)
    {
        $rows = (new ComidasAlimentoUnidadesModel())
            ->where('alimento_id', $alimentoId)
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON($rows);
    }

    public function storeAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $data = $this->request->getPost();

        $payload = [
            'alimento_id'         => (int) ($data['alimento_id'] ?? 0),
            'descripcion'         => trim($data['descripcion'] ?? ''),
            'gramos_equivalentes' => $this->toFloatComa($data['gramos_equivalentes'] ?? 0),
            'es_predeterminada'   => !empty($data['es_predeterminada']) ? 1 : 0,
        ];

        if ($payload['alimento_id'] <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Alimento requerido.']);
        }
        if ($payload['descripcion'] === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Indica un nombre para la proporción.']);
        }
        if ($payload['gramos_equivalentes'] <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'La equivalencia en gramos debe ser mayor que 0.']);
        }

        $m = new ComidasAlimentoUnidadesModel();
        if (!$m->insert($payload)) {
            return $this->response->setJSON(['ok' => false, 'error' => implode(' ', $m->errors())]);
        }

        if ($payload['es_predeterminada'] === 1) {
            $newId = $m->getInsertID();
            $m->where('alimento_id', $payload['alimento_id'])
              ->where('id !=', $newId)
              ->set('es_predeterminada', 0)
              ->update();
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function updateAjax($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $m   = new ComidasAlimentoUnidadesModel();
        $row = $m->find($id);
        if (!$row) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Porción no encontrada.']);
        }

        $data = $this->request->getPost();

        $payload = [
            'descripcion'         => trim($data['descripcion'] ?? ''),
            'gramos_equivalentes' => $this->toFloatComa($data['gramos_equivalentes'] ?? 0),
            'es_predeterminada'   => !empty($data['es_predeterminada']) ? 1 : 0,
        ];

        if ($payload['descripcion'] === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Indica un nombre para la proporción.']);
        }
        if ($payload['gramos_equivalentes'] <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'La equivalencia en gramos debe ser mayor que 0.']);
        }

        if (!$m->update($id, $payload)) {
            return $this->response->setJSON(['ok' => false, 'error' => implode(' ', $m->errors())]);
        }

        if ($payload['es_predeterminada'] === 1) {
            $m->where('alimento_id', $row['alimento_id'])
              ->where('id !=', $id)
              ->set('es_predeterminada', 0)
              ->update();
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function deleteAjax($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Método no permitido']);
        }

        $m   = new ComidasAlimentoUnidadesModel();
        $row = $m->find($id);
        if (!$row) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Porción no encontrada.']);
        }

        $m->delete($id);

        return $this->response->setJSON(['ok' => true]);
    }
}
