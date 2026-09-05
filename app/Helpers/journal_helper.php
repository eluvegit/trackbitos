<?php
// app/Helpers/journal_helper.php

if (!function_exists('time_ago')) {
    /**
     * Formatea una fecha como "hace X" en español, para la actividad de
     * tareas/categorías del Journal. Se usa tanto en la vista completa
     * (journal/index) como en el partial de la rejilla servido por AJAX
     * (journal/_grid), así que vive en un helper compartido.
     */
    function time_ago(?string $datetime): string
    {
        if (!$datetime) {
            return 'sin actividad';
        }

        $time = strtotime($datetime);
        $diff = time() - $time;

        $days = floor($diff / 86400); // segundos en un día
        if ($days < 1) {
            return 'hoy';
        }

        if ($days < 7) {
            return "hace {$days} días";
        }

        $weeks = floor($days / 7);
        if ($weeks < 5) {
            return "hace {$weeks} sem";
        }

        $months = floor($days / 30);
        if ($months < 12) {
            return "hace {$months} meses";
        }

        $years = floor($days / 365);
        return "hace {$years} años";
    }
}
