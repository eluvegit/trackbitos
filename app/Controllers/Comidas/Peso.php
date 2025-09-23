<?php

namespace App\Controllers\Comidas;

use App\Controllers\BaseController;
use App\Models\ComidasPesoModel;
use App\Models\GimnasioEntrenamientosModel;
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

        // ===== Entrenamientos =====
        $entrenaday = new GimnasioEntrenamientosModel();

        // Fecha seleccionada para "entrenos del día" (query param ?fecha=YYYY-MM-DD) o hoy
        $fecha = $this->request->getGet('fecha') ?? $hoy;

        // Entrenamientos del día
        $entrenosDia = $entrenaday
            ->select('id, fecha, tipo_sesion, notas_generales, lesiones, sin_molestias')
            // Si tu columna es DATETIME, usa DATE(fecha)
            // ->where('DATE(fecha)', $fecha)
            ->where('fecha', $fecha)  // si 'fecha' es DATE
            ->orderBy('id', 'ASC')
            ->findAll();

        $tiposEntreno = array_values(array_filter(array_unique(array_map(
            static fn($e) => trim((string)($e['tipo_sesion'] ?? '')),
            $entrenosDia
        ))));

        $huboEntreno = !empty($entrenosDia);

        // Fechas (distintas) con entrenamiento en el rango del gráfico
        // Si 'fecha' es DATETIME, cambia a: ->select('DATE(fecha) AS dia')
        $entrenosPorDia = $entrenaday
            ->select('fecha AS dia, COUNT(*) AS n')
            ->where('fecha >=', $desde)
            ->where('fecha <=', $hoy)
            ->groupBy('dia')
            ->orderBy('dia', 'ASC')
            ->find();

        // Array simple de días con entreno: ['2025-08-28', '2025-08-30', ...]
        $diasConEntreno = array_map(static fn($r) => $r['dia'], $entrenosPorDia);

        // Mapa rápido para marcar en el gráfico/calendario: ['2025-08-28' => 2, ...]
        $mapEntrenos = [];
        foreach ($entrenosPorDia as $r) {
            $mapEntrenos[$r['dia']] = (int) $r['n'];
        }

        // ===== Preparar arrays para el gráfico de peso =====
        $labels = [];
        $values = [];
        $flagsEntreno = []; // true/false para cada punto del gráfico
        foreach ($ultimoMes as $row) {
            $dia = $row['fecha']; // YYYY-MM-DD
            $labels[] = date('d/m', strtotime($dia));
            $values[] = (float) $row['peso'];
            $flagsEntreno[] = isset($mapEntrenos[$dia]); // marca si hubo entreno ese día
        }

        // === Tipos de entrenamiento por día (para la tabla) ===
        // Si tu columna 'fecha' es DATETIME, usa DATE(fecha) AS dia y whereBetween por DATE(fecha)
        $entrenosTodos = $entrenaday
            ->select('fecha, tipo_sesion') // si es DATETIME: ->select('DATE(fecha) AS fecha, tipo_sesion')
            ->where('fecha >=', $desde)
            ->where('fecha <=', $hoy)
            ->orderBy('fecha', 'ASC')
            ->findAll();

        $entrenosTiposPorDia = []; // ['YYYY-MM-DD' => ['Fuerza', 'Cardio', ...]]
        foreach ($entrenosTodos as $e) {
            $dia  = $e['fecha']; // si usas DATE(fecha) AS fecha, sigue siendo 'YYYY-MM-DD'
            $tipo = trim((string)($e['tipo_sesion'] ?? ''));
            if ($tipo === '') continue;
            $entrenosTiposPorDia[$dia][] = $tipo;
        }

        // Quita duplicados por día y ordena bonito
        foreach ($entrenosTiposPorDia as $dia => $lista) {
            $lista = array_values(array_filter(array_unique($lista)));
            sort($lista, SORT_NATURAL | SORT_FLAG_CASE);
            $entrenosTiposPorDia[$dia] = $lista;
        }


        return view('comidas/peso/index', [
            'hoy'           => $hoy,
            'desde'         => $desde,
            'ultimos'       => $ultimos,
            'labels'        => $labels,
            'values'        => $values,
            'flagsEntreno'  => $flagsEntreno,   // para pintar el gráfico (p.ej. color/marker distinto)
            'diasConEntreno' => $diasConEntreno, // para un calendario/listado
            'mapEntrenos'   => $mapEntrenos,    // si quieres mostrar conteos por día
            'entrenosDia'   => $entrenosDia,
            'entrenosTiposPorDia' => $entrenosTiposPorDia,
            'tiposEntreno'  => $tiposEntreno,
            'huboEntreno'   => $huboEntreno,

            // Por si venimos de un duplicado:
            'dup_fecha'     => session()->getFlashdata('dup_fecha'),
            'dup_peso'      => session()->getFlashdata('dup_peso'),
            'dup_id'        => session()->getFlashdata('dup_id'),
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
            session()->setFlashdata(
                'warning',
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
