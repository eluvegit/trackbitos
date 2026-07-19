<?php
// app/Helpers/hogar_helper.php

if (!function_exists('hogar_tiempo_relativo')) {
    /**
     * Formatea una fecha como tiempo transcurrido en español, ej. "Hace 3 días".
     */
    function hogar_tiempo_relativo(?string $datetime): string
    {
        if (!$datetime) {
            return 'Nunca';
        }

        $entonces = strtotime($datetime);
        $diff     = max(0, time() - $entonces);

        if ($diff < 60) {
            return 'Hace un momento';
        }

        $minutos = intdiv($diff, 60);
        if ($minutos < 60) {
            return 'Hace ' . $minutos . ($minutos === 1 ? ' minuto' : ' minutos');
        }

        $horas = intdiv($minutos, 60);
        if ($horas < 24) {
            return 'Hace ' . $horas . ($horas === 1 ? ' hora' : ' horas');
        }

        $dias = intdiv($horas, 24);
        if ($dias < 7) {
            return 'Hace ' . $dias . ($dias === 1 ? ' día' : ' días');
        }

        $semanas = intdiv($dias, 7);
        if ($dias < 30) {
            return 'Hace ' . $semanas . ($semanas === 1 ? ' semana' : ' semanas');
        }

        $meses = intdiv($dias, 30);
        if ($dias < 365) {
            return 'Hace ' . $meses . ($meses === 1 ? ' mes' : ' meses');
        }

        $anios = intdiv($dias, 365);
        return 'Hace ' . $anios . ($anios === 1 ? ' año' : ' años');
    }
}

if (!function_exists('hogar_dias_desde')) {
    function hogar_dias_desde(?string $datetime): ?int
    {
        if (!$datetime) {
            return null;
        }

        return (int) floor((time() - strtotime($datetime)) / 86400);
    }
}

if (!function_exists('hogar_esta_atrasada')) {
    /**
     * Una tarea está "atrasada" si tiene frecuencia definida y ha pasado más
     * tiempo del esperado desde la última vez que se hizo (o nunca se hizo).
     */
    function hogar_esta_atrasada(?int $frecuenciaDias, ?string $ultimaVez): bool
    {
        if (!$frecuenciaDias) {
            return false;
        }

        if (!$ultimaVez) {
            return true;
        }

        return hogar_dias_desde($ultimaVez) >= $frecuenciaDias;
    }
}
