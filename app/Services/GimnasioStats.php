<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Cálculos de Gimnasio que comparten varias pantallas: progresión y récords
 * por ejercicio (1RM estimado con Epley), calendario de días entrenados,
 * rachas y volumen semanal por grupo muscular.
 *
 * Ojo con el modelo de datos: cada fila de gimnasio_series es un "registro"
 * que puede valer por varias series iguales (series × repeticiones × peso).
 */
class GimnasioStats
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper('gimnasio');
    }

    public static function e1rm(float $peso, int $reps): float
    {
        return $peso > 0 && $reps > 0 ? round($peso * (1 + $reps / 30), 1) : 0.0;
    }

    /**
     * Mejor serie por fecha (por 1RM estimado), récord, última sesión y
     * volumen por sesión de un ejercicio.
     */
    public function progresion(int $ejercicioId): array
    {
        $filas = $this->db->table('gimnasio_series s')
            ->select('en.fecha, s.series, s.repeticiones, s.peso')
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->where('ee.ejercicio_id', $ejercicioId)
            ->orderBy('en.fecha', 'ASC')
            ->get()->getResultArray();

        $porFecha = [];
        $volumen = [];
        foreach ($filas as $s) {
            $peso = (float) $s['peso'];
            $reps = (int) $s['repeticiones'];
            $f = $s['fecha'];
            $volumen[$f] = ($volumen[$f] ?? 0) + max(1, (int) $s['series']) * $reps * $peso;

            $e = self::e1rm($peso, $reps);
            if ($e <= 0) {
                continue;
            }
            if (!isset($porFecha[$f]) || $e > $porFecha[$f]['e1rm']) {
                $porFecha[$f] = ['fecha' => $f, 'peso' => $peso, 'reps' => $reps, 'e1rm' => $e];
            }
        }

        $progresion = array_values($porFecha);
        $pr = null;
        foreach ($progresion as $p) {
            if (!$pr || $p['e1rm'] > $pr['e1rm']) {
                $pr = $p;
            }
        }

        $vol = [];
        foreach ($volumen as $f => $v) {
            $vol[] = ['fecha' => $f, 'volumen' => round($v)];
        }

        return [
            'progresion' => $progresion,
            'pr'         => $pr,
            'ultimo'     => $progresion ? end($progresion) : null,
            'volumen'    => $vol,
            'sesiones'   => count($volumen),
        ];
    }

    /** Mejor 1RM estimado de un ejercicio en sesiones anteriores a $fecha (o en todas, si es null). */
    public function mejorAntes(int $ejercicioId, ?string $fecha = null, ?int $excluirSerieId = null): float
    {
        $b = $this->db->table('gimnasio_series s')
            ->select('MAX(s.peso * (1 + s.repeticiones / 30)) AS mejor', false)
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->where('ee.ejercicio_id', $ejercicioId)
            ->where('s.peso >', 0)
            ->where('s.repeticiones >', 0);
        if ($fecha !== null) {
            $b->where('en.fecha <', $fecha);
        }
        if ($excluirSerieId !== null) {
            $b->where('s.id !=', $excluirSerieId);
        }
        return round((float) ($b->get()->getRowArray()['mejor'] ?? 0), 1);
    }

    /** Ejercicios marcados con estrella (con progresión), en orden alfabético. */
    public function principales(): array
    {
        if (!$this->db->fieldExists('principal', 'gimnasio_ejercicios')) {
            return [];
        }
        $ejs = $this->db->table('gimnasio_ejercicios')
            ->where('principal', 1)->orderBy('nombre')
            ->get()->getResultArray();

        foreach ($ejs as &$e) {
            $e['prog'] = $this->progresion((int) $e['id']);
        }
        return $ejs;
    }

    /**
     * Días entrenados desde $desde: fecha => [id, tipo, ejercicios, series, volumen].
     */
    public function dias(string $desde): array
    {
        $filas = $this->db->table('gimnasio_entrenamientos en')
            ->select('en.id, en.fecha, en.tipo_sesion,
                COUNT(DISTINCT ee.id) AS ejercicios,
                COALESCE(SUM(s.series), 0) AS series,
                COALESCE(SUM(s.series * s.repeticiones * s.peso), 0) AS volumen', false)
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.entrenamiento_id = en.id', 'left')
            ->join('gimnasio_series s', 's.entrenamiento_ejercicio_id = ee.id', 'left')
            ->where('en.fecha >=', $desde)
            ->groupBy('en.id, en.fecha, en.tipo_sesion')
            ->get()->getResultArray();

        $dias = [];
        foreach ($filas as $f) {
            $dias[$f['fecha']] = [
                'id'         => (int) $f['id'],
                'tipo'       => $f['tipo_sesion'] ?: '',
                'ejercicios' => (int) $f['ejercicios'],
                'series'     => (int) $f['series'],
                'volumen'    => (float) $f['volumen'],
            ];
        }
        return $dias;
    }

    /**
     * Cifras de constancia: racha de semanas seguidas con al menos una sesión
     * (la semana en curso cuenta si ya tiene una, y si no, no rompe la racha),
     * sesiones esta semana, media de las últimas 8 semanas y total del año.
     */
    public function constancia(): array
    {
        $fechas = array_column(
            $this->db->table('gimnasio_entrenamientos')->select('fecha')->orderBy('fecha', 'DESC')->get()->getResultArray(),
            'fecha'
        );

        $semanas = [];
        foreach ($fechas as $f) {
            $semanas[date('o-W', strtotime($f))] = ($semanas[date('o-W', strtotime($f))] ?? 0) + 1;
        }

        $lunes = new \DateTime('monday this week');
        $estaSemana = $semanas[$lunes->format('o-W')] ?? 0;

        $racha = 0;
        $cursor = clone $lunes;
        if ($estaSemana === 0) {
            $cursor->modify('-1 week');
        }
        while (isset($semanas[$cursor->format('o-W')])) {
            $racha++;
            $cursor->modify('-1 week');
        }

        $suma8 = 0;
        $c = (clone $lunes)->modify('-1 week');
        for ($i = 0; $i < 8; $i++) {
            $suma8 += $semanas[$c->format('o-W')] ?? 0;
            $c->modify('-1 week');
        }

        $anio = date('Y');
        $esteAnio = count(array_filter($fechas, fn ($f) => str_starts_with($f, $anio)));

        return [
            'racha'       => $racha,
            'esta_semana' => $estaSemana,
            'media8'      => round($suma8 / 8, 1),
            'este_anio'   => $esteAnio,
            'ultima'      => $fechas[0] ?? null,
            'total'       => count($fechas),
        ];
    }

    /**
     * Series y volumen por grupo muscular: semana en curso frente a la media
     * de las 4 anteriores. Los grupos que tocas de normal y esta semana no,
     * salen marcados como "pendientes".
     */
    public function semanaPorGrupo(): array
    {
        $lunes = new \DateTime('monday this week');
        $desde = (clone $lunes)->modify('-4 weeks')->format('Y-m-d');

        $filas = $this->db->table('gimnasio_series s')
            ->select('en.fecha, ge.grupo_muscular AS grupo, SUM(s.series) AS series, SUM(s.series * s.repeticiones * s.peso) AS volumen', false)
            ->join('gimnasio_entrenamiento_ejercicios ee', 'ee.id = s.entrenamiento_ejercicio_id')
            ->join('gimnasio_entrenamientos en', 'en.id = ee.entrenamiento_id')
            ->join('gimnasio_ejercicios ge', 'ge.id = ee.ejercicio_id')
            ->where('en.fecha >=', $desde)
            ->groupBy('en.fecha, ge.grupo_muscular')
            ->get()->getResultArray();

        $inicioSemana = $lunes->format('Y-m-d');
        $grupos = [];
        foreach ($filas as $f) {
            $g = $f['grupo'] ?: 'otros';
            $grupos[$g] ??= ['grupo' => $g, 'nombre' => gim_grupo_nombre($g), 'series' => 0, 'volumen' => 0, 'previas' => 0];
            if ($f['fecha'] >= $inicioSemana) {
                $grupos[$g]['series'] += (int) $f['series'];
                $grupos[$g]['volumen'] += (float) $f['volumen'];
            } else {
                $grupos[$g]['previas'] += (int) $f['series'];
            }
        }

        foreach ($grupos as &$g) {
            $g['media'] = round($g['previas'] / 4, 1);
            $g['pendiente'] = $g['series'] === 0 && $g['media'] >= 1;
        }
        unset($g);

        usort($grupos, fn ($a, $b) => [$b['series'], $b['media']] <=> [$a['series'], $a['media']]);
        return $grupos;
    }
}
