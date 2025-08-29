<?php namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasPesoModel;
use CodeIgniter\I18n\Time;

class Peso extends BaseController
{
    public function index()
    {
        helper(['form', 'url']);

        $model = new ComidasPesoModel();

        // Últimos 30 registros (tabla)
        $ultimos = $model->orderBy('fecha', 'DESC')->limit(30)->find();

        // Rango del último mes para el gráfico
        $hoy     = Time::now('Europe/Madrid')->toDateString(); // YYYY-MM-DD
        $desdeTs = strtotime('-30 days', strtotime($hoy));
        $desde   = date('Y-m-d', $desdeTs);

        $ultimoMes = $model->where('fecha >=', $desde)
                           ->where('fecha <=', $hoy)
                           ->orderBy('fecha', 'ASC')
                           ->find();

        // Preparar arrays para el gráfico
        $labels = [];
        $values = [];
        foreach ($ultimoMes as $row) {
            $labels[] = date('d/m', strtotime($row['fecha']));
            $values[] = (float)$row['peso'];
        }

        return view('comidas/peso/index', [
            'hoy'      => $hoy,
            'ultimos'  => $ultimos,
            'labels'   => $labels,
            'values'   => $values,
            // Por si venimos de un duplicado:
            'dup_fecha' => session()->getFlashdata('dup_fecha'),
            'dup_peso'  => session()->getFlashdata('dup_peso'),
            'dup_id'    => session()->getFlashdata('dup_id'),
        ]);
    }

    public function store()
    {
        $model = new ComidasPesoModel();

        // Sanitizar/validar rápido
        $fecha = trim((string)$this->request->getPost('fecha'));
        $peso  = str_replace(',', '.', trim((string)$this->request->getPost('peso')));

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return redirect()->back()->withInput()->with('error', 'Fecha inválida.');
        }
        if ($peso === '' || !is_numeric($peso)) {
            return redirect()->back()->withInput()->with('error', 'El peso debe ser numérico.');
        }

        // Comprobar si ya existe esa fecha
        $existente = $model->where('fecha', $fecha)->first();
        if ($existente) {
            // Informar y devolver al formulario sin romper nada
            session()->setFlashdata('warning',
                'Ya existe un registro para esa fecha.'
                . ' Valor guardado: ' . rtrim(rtrim((string)$existente['peso'], '0'), '.')
                . ' kg. Puedes borrar el registro existente o elegir otra fecha.'
            );
            // Enviar además datos del existente por si quieres mostrarlos en la vista
            session()->setFlashdata('dup_fecha', $existente['fecha']);
            session()->setFlashdata('dup_peso',  $existente['peso']);
            session()->setFlashdata('dup_id',    $existente['id']);

            return redirect()->back()->withInput();
        }

        // Guardar normalmente
        $data = [
            'fecha' => $fecha,
            'peso'  => $peso,
        ];

        if (!$model->save($data)) {
            // Capturar posible error 1062 si hay índice único en fecha (por si se coló la carrera)
            $err = implode(' ', $model->errors());
            return redirect()->back()->withInput()->with('error', $err ?: 'No se pudo guardar el peso.');
        }

        return redirect()->to(site_url('comidas/peso'))->with('success', 'Peso registrado correctamente.');
    }

    public function delete($id)
    {
        $model = new ComidasPesoModel();

        if (!$model->delete((int)$id)) {
            return redirect()->back()->with('error', 'No se pudo eliminar el registro.');
        }

        return redirect()->to(site_url('comidas/peso'))->with('success', 'Registro eliminado.');
    }

    // Opcional: JSON del último mes (por si quieres cargar el gráfico vía fetch)
    public function ultimoMesJson()
    {
        $model = new ComidasPesoModel();

        $hoy     = Time::now('Europe/Madrid')->toDateString();
        $desdeTs = strtotime('-30 days', strtotime($hoy));
        $desde   = date('Y-m-d', $desdeTs);

        $rows = $model->where('fecha >=', $desde)
                      ->where('fecha <=', $hoy)
                      ->orderBy('fecha', 'ASC')
                      ->find();

        return $this->response->setJSON($rows);
    }
}
