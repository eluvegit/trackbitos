<?php
// app/Helpers/recordatorio_helper.php

if (!function_exists('recordatorio_fecha_efectiva')) {
    /**
     * Si el recordatorio tiene período (se repite cada N meses) y la fecha
     * guardada ya pasó hace más de $mesesGracia, avanza en bucle sumando el
     * período completo hasta quedar dentro de esa ventana de gracia.
     *
     * Así, un recordatorio olvidado no se queda "caducado" indefinidamente:
     * se muestra caducado como mucho $mesesGracia, y luego salta solo al
     * siguiente ciclo (sin escribir nada en la base de datos, es solo para
     * calcular qué mostrar).
     */
    function recordatorio_fecha_efectiva(string $fechaEvento, ?int $periodoMeses, int $mesesGracia = 1): string
    {
        if (!$periodoMeses) {
            return $fechaEvento;
        }

        $hoy   = new DateTime('today');
        $fecha = new DateTime($fechaEvento);

        $maxIteraciones = 2000; // salvaguarda anti bucle infinito
        while ($maxIteraciones-- > 0) {
            $finGracia = (clone $fecha)->modify('+' . $mesesGracia . ' months');
            if ($finGracia >= $hoy) {
                break;
            }
            $fecha->modify('+' . $periodoMeses . ' months');
        }

        return $fecha->format('Y-m-d');
    }
}

if (!function_exists('recordatorio_estado')) {
    /**
     * Calcula cuántos días quedan (o han pasado) hasta la fecha de un recordatorio,
     * junto con un texto CORTO en español (días si está cerca, meses si falta
     * más de un mes) y un nivel de urgencia para la UI.
     *
     * Niveles: caducado | urgente (<=30 días) | proximo (<=90 días) | lejano
     */
    function recordatorio_estado(string $fechaEvento): array
    {
        $hoy   = new DateTime('today');
        $fecha = new DateTime($fechaEvento);
        $dias  = (int) $hoy->diff($fecha)->format('%r%a');

        if ($dias < 0) {
            $nivel = 'caducado';
            $absDias = abs($dias);
            $texto = 'Caducado hace ' . ($absDias < 31 ? $absDias . 'd' : round($absDias / 30) . 'm');
        } elseif ($dias === 0) {
            $nivel = 'caducado';
            $texto = 'Vence hoy';
        } elseif ($dias <= 30) {
            $nivel = 'urgente';
            $texto = 'Quedan ' . $dias . 'd';
        } elseif ($dias <= 90) {
            $nivel = 'proximo';
            $texto = 'Quedan ' . round($dias / 30) . 'm';
        } else {
            $nivel = 'lejano';
            $texto = 'Quedan ' . round($dias / 30) . 'm';
        }

        return [
            'dias'  => $dias,
            'texto' => $texto,
            'nivel' => $nivel,
        ];
    }
}

if (!function_exists('recordatorio_es_icono_bootstrap')) {
    /**
     * El campo "icono" admite tanto un slug de Bootstrap Icons (car-front,
     * heart-pulse...) como un emoji literal (🐶). Esto distingue cuál es.
     */
    function recordatorio_es_icono_bootstrap(string $icono): bool
    {
        return (bool) preg_match('/^[a-z0-9-]+$/', $icono);
    }
}
