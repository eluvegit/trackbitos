<?php
// app/Helpers/youtube_helper.php

if (!function_exists('youtube_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica, como en el resto de módulos:
     * ?nueva=0 / ?nueva=1 la fija en la sesión; por defecto, la nueva.
     */
    function youtube_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('youtube_nueva', $nueva === '1');
        }
        return session('youtube_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('youtube_id')) {
    /** ID de 11 caracteres de una URL de YouTube (watch?v=, youtu.be, shorts, embed) o null. */
    function youtube_id(?string $url): ?string
    {
        if ($url && preg_match('~(?:[?&]v=|youtu\.be/|/shorts/|/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
