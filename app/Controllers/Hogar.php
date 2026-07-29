<?php

namespace App\Controllers;

use App\Models\HogarHabitacionModel;
use App\Models\HogarTareaModel;
use App\Models\HogarTareaLogModel;

class Hogar extends BaseController
{
    protected HogarHabitacionModel $habitacionModel;
    protected HogarTareaModel $tareaModel;
    protected HogarTareaLogModel $logModel;

    public function __construct()
    {
        helper('hogar');
        $this->habitacionModel = new HogarHabitacionModel();
        $this->tareaModel      = new HogarTareaModel();
        $this->logModel        = new HogarTareaLogModel();
    }

    /**
     * Grid de habitaciones con resumen de progreso.
     */
    public function index()
    {
        $habitaciones = $this->habitacionModel->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();

        $totalPendientes = 0;
        foreach ($habitaciones as &$hab) {
            $tareas = $this->tareaModel->porHabitacion($hab['id']);
            $total  = count($tareas);
            $hechas = count(array_filter($tareas, fn($t) => (int) $t['estado'] === 1));
            $atrasadas = count(array_filter(
                $tareas,
                fn($t) => hogar_esta_atrasada($t['frecuencia_dias'] ? (int) $t['frecuencia_dias'] : null, $t['ultima_vez'])
            ));

            $hab['total']      = $total;
            $hab['hechas']     = $hechas;
            $hab['atrasadas']  = $atrasadas;
            $hab['pct']        = $total ? round($hechas * 100 / $total) : 0;

            $totalPendientes += $total - $hechas;
        }
        unset($hab);

        return view('hogar/index', [
            'habitaciones'     => $habitaciones,
            'totalPendientes'  => $totalPendientes,
        ]);
    }

    /**
     * Listado lineal de todas las tareas pendientes (de todas las habitaciones),
     * ordenadas por prioridad: primero las nunca hechas y más atrasadas.
     */
    public function pendientes()
    {
        $tareas = $this->tareaModel->where('estado', 0)->findAll();

        $habitaciones = [];
        foreach ($this->habitacionModel->findAll() as $h) {
            $habitaciones[$h['id']] = $h;
        }

        foreach ($tareas as &$t) {
            $diasDesde = hogar_dias_desde($t['ultima_vez']);
            $nunca     = $t['ultima_vez'] === null;
            $frecuencia = $t['frecuencia_dias'] ? (int) $t['frecuencia_dias'] : null;

            $t['habitacion']       = $habitaciones[$t['habitacion_id']] ?? null;
            $t['dias_desde']       = $diasDesde;
            $t['nunca']            = $nunca;
            $t['tiempo_relativo']  = hogar_tiempo_relativo($t['ultima_vez']);
            $t['tiene_frecuencia'] = $frecuencia !== null;
            $t['diff_dias']        = ($frecuencia !== null && !$nunca) ? $diasDesde - $frecuencia : null;

            // Prioridad de orden: mayor = más urgente.
            // Con frecuencia: nunca hecha o muy atrasada sube arriba del todo.
            // Sin frecuencia: se quedan siempre por debajo, ordenadas por más tiempo sin hacerse.
            if ($frecuencia !== null) {
                $t['prioridad'] = ($diasDesde ?? 999999) - $frecuencia;
            } else {
                $t['prioridad'] = -1000000 + ($diasDesde ?? 0);
            }
        }
        unset($t);

        usort($tareas, fn($a, $b) => $b['prioridad'] <=> $a['prioridad']);

        return view('hogar/pendientes', ['tareas' => $tareas]);
    }

    /**
     * Checklist de tareas de una habitación.
     */
    public function habitacion(int $id)
    {
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Habitación no encontrada');
        }

        $tareas = $this->tareaModel->porHabitacion($id);
        foreach ($tareas as &$t) {
            $t['tiempo_relativo'] = hogar_tiempo_relativo($t['ultima_vez']);
            $t['atrasada'] = hogar_esta_atrasada($t['frecuencia_dias'] ? (int) $t['frecuencia_dias'] : null, $t['ultima_vez']);
        }
        unset($t);

        return view('hogar/habitacion', [
            'habitacion' => $habitacion,
            'tareas'     => $tareas,
        ]);
    }

    // ================= Habitaciones =================

    public function gestionar()
    {
        $habitaciones = $this->habitacionModel->orderBy('orden', 'ASC')->orderBy('id', 'ASC')->findAll();
        return view('hogar/gestionar', ['habitaciones' => $habitaciones]);
    }

    public function nuevaHabitacion()
    {
        return view('hogar/habitacion_form');
    }

    public function crearHabitacion()
    {
        $this->habitacionModel->insert([
            'nombre' => $this->request->getPost('nombre'),
            'icono'  => $this->request->getPost('icono') ?: 'house',
            'orden'  => $this->habitacionModel->siguienteOrden(),
        ]);

        return redirect()->to(site_url('hogar'))->with('success', 'Habitación creada.');
    }

    public function editarHabitacion(int $id)
    {
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Habitación no encontrada');
        }

        return view('hogar/habitacion_form', ['habitacion' => $habitacion]);
    }

    public function actualizarHabitacion(int $id)
    {
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Habitación no encontrada');
        }

        $this->habitacionModel->update($id, [
            'nombre' => $this->request->getPost('nombre'),
            'icono'  => $this->request->getPost('icono') ?: 'house',
        ]);

        return redirect()->to(site_url('hogar/gestionar'))->with('success', 'Habitación actualizada.');
    }

    public function borrarHabitacion(int $id)
    {
        $this->habitacionModel->delete($id);
        return redirect()->to(site_url('hogar/gestionar'))->with('success', 'Habitación eliminada.');
    }

    public function reordenarHabitaciones()
    {
        $ids = $this->request->getJSON(true)['orden'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        foreach ($ids as $index => $id) {
            $this->habitacionModel->skipValidation(true)->update((int) $id, ['orden' => $index + 1]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    // ================= Tareas =================

    public function crearTarea()
    {
        $habitacionId = (int) $this->request->getPost('habitacion_id');
        $habitacion = $this->habitacionModel->find($habitacionId);
        if (!$habitacion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Habitación no encontrada');
        }

        $frecuencia = $this->request->getPost('frecuencia_dias');

        $this->tareaModel->insert([
            'habitacion_id'   => $habitacionId,
            'nombre'          => $this->request->getPost('nombre'),
            'orden'           => $this->tareaModel->siguienteOrden($habitacionId),
            'frecuencia_dias' => $frecuencia !== '' && $frecuencia !== null ? (int) $frecuencia : null,
        ]);

        return redirect()->to(site_url('hogar/' . $habitacionId))->with('success', 'Tarea añadida.');
    }

    public function editarTarea(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tarea no encontrada');
        }

        return view('hogar/tarea_form', ['tarea' => $tarea]);
    }

    /**
     * Historial completo de una tarea: todas las veces que se hizo y la
     * media de días transcurridos entre una vez y la siguiente.
     */
    public function historialTarea(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tarea no encontrada');
        }

        $habitacion = $this->habitacionModel->find($tarea['habitacion_id']);

        $logs = $this->logModel
            ->where('tarea_id', $id)
            ->orderBy('completada_at', 'ASC')
            ->findAll();

        $timestamps = array_map(fn($l) => strtotime($l['completada_at']), $logs);

        $intervalos = [];
        for ($i = 1; $i < count($timestamps); $i++) {
            $intervalos[] = ($timestamps[$i] - $timestamps[$i - 1]) / 86400;
        }
        $media = count($intervalos) ? round(array_sum($intervalos) / count($intervalos), 1) : null;
        $minIntervalo = count($intervalos) ? round(min($intervalos), 1) : null;
        $maxIntervalo = count($intervalos) ? round(max($intervalos), 1) : null;

        // Timeline para la vista: más reciente primero, con el intervalo respecto a la vez anterior
        $timeline = [];
        for ($i = count($logs) - 1; $i >= 0; $i--) {
            $timeline[] = [
                'id'        => $logs[$i]['id'],
                'fecha'     => $logs[$i]['completada_at'],
                'intervalo' => $i > 0 ? round(($timestamps[$i] - $timestamps[$i - 1]) / 86400, 1) : null,
            ];
        }

        // Calendario de los últimos dos meses (mes anterior + mes actual)
        $logDates = [];
        foreach ($logs as $l) {
            $d = date('Y-m-d', strtotime($l['completada_at']));
            $logDates[$d] = ($logDates[$d] ?? 0) + 1;
        }

        $hoy = new \DateTime();
        $mesAnteriorDt = (clone $hoy)->modify('-1 month');

        $calendario = [
            $this->construirMesCalendario((int) $mesAnteriorDt->format('Y'), (int) $mesAnteriorDt->format('n'), $logDates),
            $this->construirMesCalendario((int) $hoy->format('Y'), (int) $hoy->format('n'), $logDates),
        ];

        return view('hogar/tarea_historial', [
            'tarea'        => $tarea,
            'habitacion'   => $habitacion,
            'timeline'     => $timeline,
            'calendario'   => $calendario,
            'totalVeces'   => count($logs),
            'media'        => $media,
            'minIntervalo' => $minIntervalo,
            'maxIntervalo' => $maxIntervalo,
            'primeraVez'   => $logs[0]['completada_at'] ?? null,
            'ultimaVez'    => $logs ? $logs[count($logs) - 1]['completada_at'] : null,
        ]);
    }

    /**
     * Elimina un registro concreto del historial (por si te equivocas al marcar
     * una tarea como hecha). Recalcula "última vez" y, si borrabas el registro
     * más reciente, también desmarca la tarea si estaba en estado "hecha".
     */
    public function borrarLog(int $logId)
    {
        $log = $this->logModel->find($logId);
        if (!$log) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Registro no encontrado');
        }

        $tareaId = $log['tarea_id'];
        $tarea = $this->tareaModel->find($tareaId);

        $eraLaUltima = $tarea && $tarea['ultima_vez'] === $log['completada_at'];

        $this->logModel->delete($logId);

        $nuevaUltima = $this->logModel
            ->where('tarea_id', $tareaId)
            ->orderBy('completada_at', 'DESC')
            ->first();

        $data = ['ultima_vez' => $nuevaUltima['completada_at'] ?? null];
        if ($eraLaUltima && $tarea && (int) $tarea['estado'] === 1) {
            $data['estado'] = 0;
        }

        $this->tareaModel->skipValidation(true)->update($tareaId, $data);

        return redirect()->to(site_url('hogar/tareas/' . $tareaId . '/historial'))->with('success', 'Registro eliminado.');
    }

    /**
     * Construye la cuadrícula (semanas de lunes a domingo) de un mes,
     * marcando los días en los que hay registro en $logDates ['Y-m-d' => veces].
     */
    private function construirMesCalendario(int $anio, int $mes, array $logDates): array
    {
        $nombresMes = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $primerDia = new \DateTime(sprintf('%04d-%02d-01', $anio, $mes));
        $diasEnMes = (int) $primerDia->format('t');
        $diaSemanaInicio = (int) $primerDia->format('N'); // 1 = lunes ... 7 = domingo

        $celdas = array_fill(0, $diaSemanaInicio - 1, null);

        $hoyStr = date('Y-m-d');
        for ($d = 1; $d <= $diasEnMes; $d++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $d);
            $celdas[] = [
                'dia'   => $d,
                'veces' => $logDates[$fecha] ?? 0,
                'esHoy' => $fecha === $hoyStr,
            ];
        }

        while (count($celdas) % 7 !== 0) {
            $celdas[] = null;
        }

        return [
            'etiqueta' => $nombresMes[$mes] . ' ' . $anio,
            'semanas'  => array_chunk($celdas, 7),
        ];
    }

    public function actualizarTarea(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tarea no encontrada');
        }

        $frecuencia = $this->request->getPost('frecuencia_dias');

        $this->tareaModel->update($id, [
            'nombre'          => $this->request->getPost('nombre'),
            'frecuencia_dias' => $frecuencia !== '' && $frecuencia !== null ? (int) $frecuencia : null,
        ]);

        return redirect()->to(site_url('hogar/' . $tarea['habitacion_id']))->with('success', 'Tarea actualizada.');
    }

    public function borrarTarea(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Tarea no encontrada');
        }

        $habitacionId = $tarea['habitacion_id'];
        $this->tareaModel->delete($id);

        return redirect()->to(site_url('hogar/' . $habitacionId))->with('success', 'Tarea eliminada.');
    }

    public function reordenarTareas()
    {
        $ids = $this->request->getJSON(true)['orden'] ?? null;
        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false]);
        }

        foreach ($ids as $index => $id) {
            $this->tareaModel->skipValidation(true)->update((int) $id, ['orden' => $index + 1]);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    public function marcarTarea(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $ahora = date('Y-m-d H:i:s');

        $this->tareaModel->skipValidation(true)->update($id, [
            'estado'     => 1,
            'ultima_vez' => $ahora,
        ]);

        $this->logModel->insert([
            'tarea_id'      => $id,
            'completada_at' => $ahora,
        ]);

        return $this->response->setJSON([
            'ok'              => true,
            'estado'          => 1,
            'tiempo_relativo' => hogar_tiempo_relativo($ahora),
            'atrasada'        => false,
        ]);
    }

    public function renovarTarea(int $id)
    {
        $tarea = $this->tareaModel->find($id);
        if (!$tarea) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        // Renovar significa "vuelvo a empezar el ciclo desde hoy": además de
        // desmarcar la casilla, hay que refrescar ultima_vez (y dejar
        // constancia en el historial) o el cálculo de atrasada seguiría
        // leyendo la fecha vieja y la tarea aparecería pendiente al momento.
        $ahora = date('Y-m-d H:i:s');

        $this->tareaModel->skipValidation(true)->update($id, [
            'estado'     => 0,
            'ultima_vez' => $ahora,
        ]);

        $this->logModel->insert([
            'tarea_id'      => $id,
            'completada_at' => $ahora,
        ]);

        return $this->response->setJSON([
            'ok'              => true,
            'estado'          => 0,
            'tiempo_relativo' => hogar_tiempo_relativo($ahora),
            'atrasada'        => false,
        ]);
    }

    public function renovarTodo(int $habitacionId)
    {
        $habitacion = $this->habitacionModel->find($habitacionId);
        if (!$habitacion) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false]);
        }

        $ahora = date('Y-m-d H:i:s');

        $hechas = $this->tareaModel
            ->where('habitacion_id', $habitacionId)
            ->where('estado', 1)
            ->findAll();

        foreach ($hechas as $t) {
            $this->tareaModel->skipValidation(true)->update($t['id'], [
                'estado'     => 0,
                'ultima_vez' => $ahora,
            ]);
            $this->logModel->insert([
                'tarea_id'      => $t['id'],
                'completada_at' => $ahora,
            ]);
        }

        return $this->response->setJSON([
            'ok'              => true,
            'renovadas'       => count($hechas),
            'tiempo_relativo' => hogar_tiempo_relativo($ahora),
        ]);
    }
}
