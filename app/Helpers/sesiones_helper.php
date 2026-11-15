<?php
// app/Helpers/sesiones_helper.php

if (!function_exists('ses_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica para todo el módulo de Sesiones, igual
     * que en Rodajes/Lectura/Gimnasio: ?nueva=0 / ?nueva=1 la fija en la
     * sesión y por defecto se ve la nueva.
     */
    function ses_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('sesiones_nueva', $nueva === '1');
        }
        return session('sesiones_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('ses_estados')) {
    /** Etapas del pipeline (SesionModel::ESTADOS) con texto, icono y color. */
    function ses_estados(): array
    {
        return [
            'idea'          => ['Idea', 'bi-lightbulb', '#8e8cff'],
            'planificacion' => ['Planificación', 'bi-pencil-square', '#4cc9f0'],
            'edicion'       => ['Edición', 'bi-sliders', '#f5a524'],
            'subiendo'      => ['Subiendo', 'bi-cloud-arrow-up', '#ff6a3d'],
            'completado'    => ['Completado', 'bi-check2-circle', '#3ecf8e'],
        ];
    }
}

if (!function_exists('ses_partes')) {
    /**
     * Partes de una sesión: [nombre, icono, color]. El color identifica la
     * parte en todo el módulo (tarjetas de "Por trabajo", tablero, ficha) y
     * está elegido para no confundirse con los colores de las etapas.
     */
    function ses_partes(): array
    {
        return [
            'foto'  => ['Fotografía', 'bi-camera-fill', '#f472b6'],
            'video' => ['Vídeo', 'bi-camera-video-fill', '#a78bfa'],
        ];
    }
}

if (!function_exists('ses_entrega')) {
    /** entrega_modelos: [texto, clase de color fv]. */
    function ses_entrega(): array
    {
        return [
            'no_aplica' => ['No aplica', 'e-neu'],
            'pendiente' => ['Pendiente de entregar', 'e-warn'],
            'entregado' => ['Entregado', 'e-ok'],
        ];
    }
}

if (!function_exists('ses_grupo')) {
    /**
     * En qué pestaña del tablero cae una sesión: 'idea' si todas sus partes
     * están en idea, 'completada' si todas están completadas, si no 'activa'.
     */
    function ses_grupo(array $s): string
    {
        $estados = array_values(array_filter([$s['estado_foto'] ?? null, $s['estado_video'] ?? null], static fn ($v) => $v !== null));
        if (!$estados) return 'activa';
        if (count(array_unique($estados)) === 1 && $estados[0] === 'idea') return 'idea';
        if (count(array_unique($estados)) === 1 && $estados[0] === 'completado') return 'completada';
        return 'activa';
    }
}

if (!function_exists('ses_fecha_relativa')) {
    /** «hoy», «mañana», «en 5 días», «hace 3 días», o la fecha corta si queda lejos. */
    function ses_fecha_relativa(?string $fecha): string
    {
        if (!$fecha) return '';
        $hoy = new DateTimeImmutable('today');
        $f   = new DateTimeImmutable($fecha);
        $d   = (int) $hoy->diff($f)->format('%r%a');
        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $corta = (int) $f->format('j') . ' ' . $meses[(int) $f->format('n') - 1] . ($f->format('Y') !== $hoy->format('Y') ? ' ' . $f->format('Y') : '');
        return match (true) {
            $d === 0  => 'hoy',
            $d === 1  => 'mañana',
            $d === -1 => 'ayer',
            $d > 1 && $d <= 14   => 'en ' . $d . ' días',
            $d < -1 && $d >= -14 => 'hace ' . -$d . ' días',
            default => $corta,
        };
    }
}
