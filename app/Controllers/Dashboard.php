<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\LentillasSustitucionesModel;
use App\Models\RecordatorioModel;

class Dashboard extends BaseController
{
    public function index()
    {
        helper('recordatorio');

        $model = new LentillasSustitucionesModel();
        $ultima = $model->whereIn('elemento', ['lentilla izquierda', 'lentilla derecha', 'lentillas'])
                        ->orderBy('fecha', 'DESC')
                        ->first();

        $dias = 0;
        if ($ultima) {
            $dias = (new \DateTime($ultima['fecha']))->diff(new \DateTime())->days;
        }

        $mostrarAlerta = $dias >= 45;

        // Recordatorios que vencen dentro de 1 mes (o ya caducados)
        $recordatorioModel = new RecordatorioModel();
        $recordatoriosUrgentes = [];
        foreach ($recordatorioModel->findAll() as $r) {
            $periodo = $r['periodo_meses'] ? (int) $r['periodo_meses'] : null;
            $fechaEfectiva = recordatorio_fecha_efectiva($r['fecha_evento'], $periodo);
            $estado = recordatorio_estado($fechaEfectiva);

            if (in_array($estado['nivel'], ['caducado', 'urgente'], true)) {
                $r['dias']  = $estado['dias'];
                $r['texto'] = $estado['texto'];
                $r['nivel'] = $estado['nivel'];
                $recordatoriosUrgentes[] = $r;
            }
        }
        usort($recordatoriosUrgentes, fn($a, $b) => $a['dias'] <=> $b['dias']);

        return view('dashboard/index', [
            'dias' => $dias,
            'mostrarAlerta' => $mostrarAlerta,
            'recordatoriosUrgentes' => $recordatoriosUrgentes,
            'secciones' => $this->secciones(),
        ]);
    }

    private function secciones(): array
    {
        return [
            ['ruta' => 'comidas/diario/hoy', 'icono' => '🍽️', 'titulo' => 'Comida', 'texto' => 'Planifica tus menús, dieta y seguimiento alimenticio.'],
            ['ruta' => 'gimnasio', 'icono' => '🏋️', 'titulo' => 'Gimnasio', 'texto' => 'Registra tus entrenamientos, progresos y objetivos físicos.'],
            ['ruta' => 'compras', 'icono' => '🛒', 'titulo' => 'Compras', 'texto' => 'Lleva control de tus compras, listas y gastos.'],
            ['ruta' => 'lentillas', 'icono' => '👁️', 'titulo' => 'Lentillas', 'texto' => 'Lleva un registro de cambios, limpieza y reemplazos.'],
            ['ruta' => 'coche', 'icono' => '🚗', 'titulo' => 'Coche', 'texto' => 'Controla cambios de aceite, revisiones, neumáticos y más.'],
            ['ruta' => 'youtube', 'icono' => '▶️', 'titulo' => 'YouTube', 'texto' => 'Permite revisar los vídeos guardados como interesantes.'],
            ['ruta' => 'enlaces', 'icono' => '📒', 'titulo' => 'Enlaces', 'texto' => 'Permite revisar los enlaces registrados interesantes.'],
            ['ruta' => 'journal', 'icono' => '📨', 'titulo' => 'Journal', 'texto' => 'Permite hacer y seguir tareas y bullet journal.'],
            ['ruta' => 'reading', 'icono' => '📖', 'titulo' => 'Lectura', 'texto' => 'Sigue tus libros a tu ritmo, sin presión ni rachas.'],
            ['ruta' => 'hogar', 'icono' => '🏠', 'titulo' => 'Hogar', 'texto' => 'Checklist rutinario de limpieza y tareas del hogar por habitación.'],
            ['ruta' => 'recordatorios', 'icono' => '📅', 'titulo' => 'Recordatorios', 'texto' => 'ITV, revisiones médicas, vacunas, DNI, carnet... y cuándo tocan.'],
            ['ruta' => 'braintogram', 'icono' => '🧠', 'titulo' => 'Braintogram', 'texto' => 'Log de ingesta del bot de Telegram: segundo cerebro en construcción.'],
            ['ruta' => 'buscapp', 'icono' => '📟', 'titulo' => 'Buscapp', 'texto' => 'Avisos de llamada estilo busca/telegrama: usuarios y envíos de la app.'],
            ['ruta' => 'rodajes', 'icono' => '🎬', 'titulo' => 'Rodajes', 'texto' => 'Permite gestionar las escenas de un rodaje.'],
            ['ruta' => 'sesiones', 'icono' => '📸', 'titulo' => 'Sesiones', 'texto' => 'Kanban de sesiones de foto/vídeo: moodboard, equipo y model releases.'],
        ];
    }
}
