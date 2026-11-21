<?php
// app/Helpers/rodajes_helper.php

if (!function_exists('rod_vista')) {
    /**
     * Vista nueva (estilo fv) o clásica para todo el módulo de Rodajes, igual
     * que en Lectura/Gimnasio/Journal/Piezas: ?nueva=0 / ?nueva=1 la fija en
     * la sesión y por defecto se ve la nueva.
     */
    function rod_vista(string $vista): string
    {
        $nueva = service('request')->getGet('nueva');
        if ($nueva !== null) {
            session()->set('rodajes_nueva', $nueva === '1');
        }
        return session('rodajes_nueva') !== false ? $vista . '_nueva' : $vista;
    }
}

if (!function_exists('rod_es_video')) {
    function rod_es_video(string $ruta): bool
    {
        return in_array(strtolower(pathinfo($ruta, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'mov'], true);
    }
}

if (!function_exists('rod_toma_estados')) {
    /** Estados de toma en el plató: [clave => [texto, icono, clase de color fv]]. */
    function rod_toma_estados(): array
    {
        return [
            'pendiente'  => ['Pendiente', 'bi-circle', 'e-neu'],
            'hecha'      => ['Hecha', 'bi-check-lg', 'e-ok'],
            'repetir'    => ['Repetir', 'bi-arrow-repeat', 'e-warn'],
            'descartada' => ['Descartada', 'bi-x-lg', 'e-neu'],
        ];
    }
}

if (!function_exists('rod_formatos')) {
    /** Formatos de escena: [clave => [texto, icono]]. NULL en BD = sin definir. */
    function rod_formatos(): array
    {
        return [
            'foto'  => ['Foto', 'bi-camera'],
            'video' => ['Vídeo', 'bi-camera-video'],
            'ambas' => ['Foto y vídeo', 'bi-collection-play'],
        ];
    }
}

if (!function_exists('rod_formato_chip')) {
    /** Icono + texto del formato para listados ('' si no tiene). */
    function rod_formato_chip(array $e, bool $conTexto = true): string
    {
        $f = rod_formatos()[$e['formato'] ?? ''] ?? null;
        if (!$f) return '';
        return '<span class="rd-formato ' . esc($e['formato'], 'attr') . '" title="' . $f[0] . '"><i class="bi ' . $f[1] . '"></i>' . ($conTexto ? $f[0] : '') . '</span>';
    }
}

if (!function_exists('rod_partes')) {
    /**
     * Partes con estado de toma propio: [parte => columna del estado].
     * Una escena «ambas» tiene dos (foto en toma_estado_foto, vídeo en
     * toma_estado); el resto, una sola en toma_estado. Parte '' = sin formato.
     */
    function rod_partes(array $e): array
    {
        return match ($e['formato'] ?? null) {
            'ambas' => ['foto' => 'toma_estado_foto', 'video' => 'toma_estado'],
            'foto'  => ['foto' => 'toma_estado'],
            'video' => ['video' => 'toma_estado'],
            default => ['' => 'toma_estado'],
        };
    }
}

if (!function_exists('rod_estados_partes')) {
    /** [parte => estado] ('pendiente' cuando la columna es NULL). */
    function rod_estados_partes(array $e): array
    {
        return array_map(static fn ($col) => ($e[$col] ?? null) ?: 'pendiente', rod_partes($e));
    }
}

if (!function_exists('rod_estado_escena')) {
    /**
     * Estado de la escena entera a partir de sus partes: si coinciden, ese;
     * si alguna es «repetir», repetir; si todas están cerradas (hecha o
     * descartada), hecha; si no, pendiente.
     */
    function rod_estado_escena(array $e): string
    {
        $estados = array_values(rod_estados_partes($e));
        if (count(array_unique($estados)) === 1) return $estados[0];
        if (in_array('repetir', $estados, true)) return 'repetir';
        if (!array_diff($estados, ['hecha', 'descartada'])) return 'hecha';
        return 'pendiente';
    }
}

if (!function_exists('rod_cambio_formato')) {
    /**
     * Al cambiar el formato de una escena, mueve el estado de toma para que
     * no se pierda: foto → ambas lleva el estado a la parte foto; ambas → foto
     * lo trae de vuelta; dejar de ser «ambas» por otro lado limpia la foto.
     */
    function rod_cambio_formato(array $e, ?string $nuevo): array
    {
        $viejo = ($e['formato'] ?? null) ?: null;
        if ($viejo === $nuevo) return [];
        if ($viejo === 'foto' && $nuevo === 'ambas') {
            return ['toma_estado_foto' => $e['toma_estado'], 'toma_estado_foto_at' => $e['toma_estado_at'], 'toma_estado' => null, 'toma_estado_at' => null];
        }
        if ($viejo === 'ambas' && $nuevo === 'foto') {
            return ['toma_estado' => $e['toma_estado_foto'], 'toma_estado_at' => $e['toma_estado_foto_at'], 'toma_estado_foto' => null, 'toma_estado_foto_at' => null];
        }
        if ($viejo === 'ambas') {
            return ['toma_estado_foto' => null, 'toma_estado_foto_at' => null];
        }
        return [];
    }
}

if (!function_exists('rod_sql_hechas')) {
    /** Expresión SQL «escena hecha» (en las «ambas», foto y vídeo hechos). $a = alias con punto. */
    function rod_sql_hechas(string $a = ''): string
    {
        return "SUM({$a}toma_estado = 'hecha' AND ({$a}formato IS NULL OR {$a}formato <> 'ambas' OR {$a}toma_estado_foto = 'hecha'))";
    }
}

if (!function_exists('rod_normalizar')) {
    /** Mayúsculas y sin tildes, para agrupar «Cocina», «cocina » y «COCINA» juntos. */
    function rod_normalizar(string $s): string
    {
        static $mapa = null;
        $mapa ??= array_combine(
            mb_str_split('ÁÀÂÄÃÅÉÈÊËÍÌÎÏÓÒÔÖÕÚÙÛÜÑáàâäãåéèêëíìîïóòôöõúùûüñ'),
            str_split('AAAAAAEEEEIIIIOOOOOUUUUNaaaaaaeeeeiiiiooooouuuun')
        );
        return mb_strtoupper(strtr(trim($s), $mapa));
    }
}

if (!function_exists('rod_colores')) {
    /**
     * Color de cada valor de un campo (bloque, ubicación, soporte…) dentro de
     * un proyecto: [valor normalizado => color]. Se reparten por orden de
     * primera aparición en la historia, así que los grupos cercanos no repiten
     * color y cada uno conserva el suyo en pizarra, storyboard y plató.
     *
     * @param array $escenas en orden narrativo (rodajes_escenas.orden)
     */
    function rod_colores(array $escenas, string $campo): array
    {
        $paleta = ['#f97316', '#38bdf8', '#a3e635', '#f472b6', '#facc15', '#a78bfa', '#2dd4bf', '#fb7185', '#60a5fa', '#4ade80', '#e879f9', '#fdba74'];
        $mapa = [];
        foreach ($escenas as $e) {
            $n = rod_normalizar((string) ($e[$campo] ?? ''));
            if ($n !== '' && !isset($mapa[$n])) $mapa[$n] = $paleta[count($mapa) % count($paleta)];
        }
        return $mapa;
    }
}

if (!function_exists('rod_color')) {
    /** Color de un valor según rod_colores(); gris neutro si no tiene (vacío, «Sin …»). */
    function rod_color(array $mapa, ?string $valor): string
    {
        return $mapa[rod_normalizar((string) $valor)] ?? '#71717a';
    }
}

if (!function_exists('rod_titulo_escena')) {
    /** Texto principal de una escena: descripción, si no bloque, si no «Escena #n». */
    function rod_titulo_escena(array $e): string
    {
        return trim((string) ($e['escena_descripcion'] ?? ''))
            ?: (trim((string) ($e['escena_bloque'] ?? '')) ?: 'Escena #' . (int) ($e['orden'] ?? 0));
    }
}

if (!function_exists('rod_ficha_tecnica')) {
    /**
     * Ficha técnica compacta en cuadrícula de 4 columnas con posiciones FIJAS
     * (cada dato siempre en el mismo sitio, para leerla de un vistazo en el
     * plató). Fila 1 encuadre, fila 2 exposición, fila 3 resto. Una fila sin
     * ningún dato no se pinta; dentro de una fila, los huecos salen como «—».
     */
    function rod_ficha_tecnica(array $e, bool $conCamara = false): string
    {
        $filas = [
            ['Plano' => 'camara_tipo_plano', 'Ángulo' => 'camara_angulo', 'Movim.' => 'camara_movimiento', 'Soporte' => 'camara_soporte'],
            ['Óptica' => 'camara_optica', 'Diafr.' => 'camara_apertura', 'Obtur.' => 'camara_velocidad', 'ISO' => 'camara_iso'],
            ['FPS' => 'camara_fps', 'Balance' => 'camara_wb', 'ND' => 'camara_nd'] + ($conCamara ? ['Cámara' => 'camara_modelo'] : []),
        ];
        $html = '';
        foreach ($filas as $fila) {
            $valores = array_map(static fn ($c) => trim((string) ($e[$c] ?? '')), $fila);
            if (implode('', $valores) === '') continue;
            foreach ($valores as $k => $v) {
                $html .= '<div' . ($v === '' ? ' class="vacio"' : '') . '><span class="k">' . $k . '</span><span class="v">'
                    . ($v === '' ? '—' : esc($v)) . '</span></div>';
            }
            // Fila 3 sin cámara tiene 3 celdas: se rellena para no descuadrar.
            for ($i = count($valores); $i < 4; $i++) $html .= '<div class="vacio"></div>';
        }
        return $html === '' ? '' : '<div class="rd-tec">' . $html . '</div>';
    }
}

if (!function_exists('rod_campos')) {
    /**
     * Pares etiqueta => texto en rejilla de 2 columnas: los cortos van de dos
     * en dos y los largos (o con saltos de línea) ocupan toda la fila.
     */
    function rod_campos(array $pares, array $siempreAnchos = []): string
    {
        $html = '';
        foreach ($pares as $k => $v) {
            $v = trim((string) $v);
            if ($v === '') continue;
            $ancho = in_array($k, $siempreAnchos, true) || mb_strlen($v) > 34 || str_contains($v, "\n");
            $html .= '<div class="rd-c' . ($ancho ? ' ancho' : '') . '"><span class="k">' . esc($k) . '</span><div class="v">' . nl2br(esc($v)) . '</div></div>';
        }
        return $html === '' ? '' : '<div class="rd-campos">' . $html . '</div>';
    }
}

if (!function_exists('rod_resumen_plano')) {
    /** Una línea: «Medio · Frontal · Dolly · 35mm · f/2.8» (para listados). */
    function rod_resumen_plano(array $e): string
    {
        $partes = [];
        foreach (['camara_tipo_plano', 'camara_angulo', 'camara_movimiento', 'camara_optica', 'camara_apertura'] as $c) {
            $v = trim((string) ($e[$c] ?? ''));
            if ($v !== '') $partes[] = esc($v);
        }
        return implode('<i>·</i>', $partes);
    }
}

if (!function_exists('rod_chips_camara')) {
    /** Chips monoespaciados con los datos técnicos que tenga la escena. */
    function rod_chips_camara(array $e, bool $soloPlano = false): string
    {
        $campos = $soloPlano
            ? ['camara_tipo_plano' => '', 'camara_angulo' => '', 'camara_movimiento' => '']
            : [
                'camara_tipo_plano' => '', 'camara_angulo' => '', 'camara_movimiento' => '',
                'camara_optica' => '', 'camara_apertura' => '', 'camara_iso' => 'ISO ',
                'camara_velocidad' => '', 'camara_fps' => 'fps', 'camara_wb' => '', 'camara_nd' => '',
                'camara_soporte' => '',
            ];
        $out = '';
        foreach ($campos as $campo => $extra) {
            $v = trim((string) ($e[$campo] ?? ''));
            if ($v === '') continue;
            $txt = $extra === 'fps' ? $v . ' fps' : $extra . $v;
            $out .= '<span class="rd-chip">' . esc($txt) . '</span>';
        }
        return $out;
    }
}

if (!function_exists('rod_props')) {
    function rod_props(array $e): array
    {
        $p = json_decode((string) ($e['props'] ?? ''), true);
        return is_array($p) ? array_values(array_filter(array_map('strval', $p), static fn ($x) => trim($x) !== '')) : [];
    }
}

if (!function_exists('rod_dia_noche')) {
    /** Lado de la jornada de una franja: 'noche', 'dia' o '' (sin franja, sale en los dos). */
    function rod_dia_noche(?string $franja): string
    {
        if ($franja === null || $franja === '') return '';
        return $franja === 'noche' ? 'noche' : 'dia';
    }
}

if (!function_exists('rod_props_variante')) {
    /** Lo que lleva una variante: los props de su toma + los suyos propios. */
    function rod_props_variante(array $e, array $v): array
    {
        $out = [];
        foreach (array_merge(rod_props($e), rod_props($v)) as $p) $out[rod_normalizar($p)] ??= $p;
        return array_values($out);
    }
}

if (!function_exists('rod_props_extra')) {
    /** Solo lo que la variante lleva además de su toma. */
    function rod_props_extra(array $e, array $v): array
    {
        $t = array_map('rod_normalizar', rod_props($e));
        return array_values(array_filter(rod_props($v), static fn ($p) => !in_array(rod_normalizar($p), $t, true)));
    }
}

if (!function_exists('rod_props_diff')) {
    function rod_props_diff(?array $prev, array $cur): string
    {
        // Primera toma del grupo: no hay con qué comparar y sus props ya se ven al lado.
        if ($prev === null) return '';
        $n = array_map('rod_normalizar', $prev);
        $c = array_map('rod_normalizar', $cur);
        $mas = array_filter($cur, static fn ($p) => !in_array(rod_normalizar($p), $n, true));
        $men = array_filter($prev, static fn ($p) => !in_array(rod_normalizar($p), $c, true));
        if (!$mas && !$men) return $cur ? 'Mismos props' : '';
        return implode(' · ', array_filter([
            $mas ? '<span class="rf-mas">+ ' . esc(implode(', ', $mas)) . '</span>' : '',
            $men ? '<span class="rf-menos">− ' . esc(implode(', ', $men)) . '</span>' : '',
        ]));
    }
}

if (!function_exists('rod_texto_idea')) {
    function rod_texto_idea(array $i): string
    {
        $t = trim((string) ($i['nota'] ?? ''));
        if ($t === '' && $i['origen'] === 'enlace') $t = preg_replace('/^www\./', '', (string) (parse_url((string) $i['url_externa'], PHP_URL_HOST) ?: $i['url_externa']));
        return $t;
    }
}

if (!function_exists('rod_setup_linea')) {
    function rod_setup_linea(?array $s): string
    {
        if (!$s) return '';
        $tec = implode(' · ', array_filter([trim((string) $s['optica']), trim((string) $s['soporte'])]));
        if (rod_normalizar($tec) === rod_normalizar((string) $s['nombre'])) $tec = '';
        return esc(implode(' · ', array_filter([$tec, trim((string) $s['luz'])])));
    }
}

if (!function_exists('rod_setups_toma')) {
    /** ['foto' => setup|null, 'video' => setup|null] según el formato; solo las claves que la toma usa. */
    function rod_setups_toma(array $e, array $setups): array
    {
        $p = $setups[(int) ($e['setup_id'] ?? 0)] ?? null;
        $v = $setups[(int) ($e['setup_video_id'] ?? 0)] ?? null;
        return match ($e['formato'] ?? null) {
            'ambas' => ['foto' => $p, 'video' => $v],
            'video' => ['video' => $p],
            'foto'  => ['foto' => $p],
            default => [($p['tipo'] ?? '') === 'video' ? 'video' : 'foto' => $p],
        };
    }
}

if (!function_exists('rod_cambio_formato_setup')) {
    /** Al cambiar el formato, el setup de vídeo pasa a principal o al revés. */
    function rod_cambio_formato_setup(array $e, ?string $nuevo): array
    {
        $viejo = ($e['formato'] ?? null) ?: null;
        if ($viejo === $nuevo) return [];
        if ($viejo === 'ambas' && $nuevo === 'video') return ['setup_id' => $e['setup_video_id'] ?? null, 'setup_video_id' => null];
        if ($viejo === 'ambas') return ['setup_video_id' => null];
        if ($viejo === 'video' && $nuevo === 'ambas') return ['setup_id' => null, 'setup_video_id' => $e['setup_id'] ?? null];
        return [];
    }
}

if (!function_exists('rod_pre')) {
    /** Prefijo/sufijo solo si el valor es un número («2.8» → «f/2.8»; «f/2.8» se queda igual). */
    function rod_pre(string $v, string $pref = '', string $suf = ''): string
    {
        $v = trim($v);
        return $v === '' ? '' : (is_numeric(str_replace(',', '.', $v)) ? $pref . $v . $suf : $v);
    }
}

if (!function_exists('rod_tecnica_parte')) {
    /**
     * Parámetros de cámara de una parte (foto / vídeo) tal como se ruedan: lo
     * que tenga la toma y, si no, lo de su setup. $deToma: true = la toma
     * manda en todo; array = solo en esas columnas (el setup de vídeo de una
     * toma «ambas» solo coge de la toma los fps y el ND).
     */
    function rod_tecnica_parte(string $parte, ?array $st, array $e, bool|array $deToma = true): array
    {
        $v = static function (string $ec, string $sc) use ($st, $e, $deToma): string {
            $t = ($deToma === true || in_array($ec, (array) $deToma, true)) ? trim((string) ($e[$ec] ?? '')) : '';
            return $t !== '' ? $t : trim((string) ($st[$sc] ?? ''));
        };
        $video = $parte === 'video';
        return array_filter([
            'Lente'                                  => $v('camara_optica', 'optica'),
            'Soporte'                                => $v('camara_soporte', 'soporte'),
            'Diafragma'                              => rod_pre($v('camara_apertura', 'apertura'), 'f/'),
            ($video ? 'Obturación' : 'Velocidad')    => $v('camara_velocidad', 'velocidad'),
            'ISO'                                    => rod_pre($v('camara_iso', 'iso'), 'ISO '),
            'FPS'                                    => $video ? rod_pre($v('camara_fps', 'fps'), '', ' fps') : '',
            'ND'                                     => $video ? rod_pre($v('camara_nd', 'nd'), 'ND ') : '',
            ($video ? 'Foco de luz' : 'Flash / luz') => $v('plano_esquema_iluminacion', 'luz'),
        ], static fn ($x) => $x !== '');
    }
}

if (!function_exists('rod_cajas_toma')) {
    /**
     * Una caja por setup que usa la toma: [parte => ['st' => setup|null, 'tec' => [...], 'luz' => '']].
     * Mismo cálculo que el plató, para que todas las vistas digan lo mismo.
     */
    function rod_cajas_toma(array $e, array $setups): array
    {
        $out = [];
        foreach (rod_setups_toma($e, $setups) as $parte => $st) {
            $parte = (string) $parte;
            $principal = !($parte === 'video' && ($e['formato'] ?? '') === 'ambas');
            $tec = rod_tecnica_parte($parte, $st, $e, $principal ? true : ['camara_fps', 'camara_nd']);
            $out[$parte] = ['st' => $st, 'tec' => $tec, 'luz' => $tec[$parte === 'video' ? 'Foco de luz' : 'Flash / luz'] ?? ''];
        }
        return $out;
    }
}

if (!function_exists('rod_cuenta_tomas')) {
    /**
     * Recuento del plató: cada toma y cada variante cuentan una vez por parte
     * (foto / vídeo). ['pendiente','hecha','repetir','descartada','total','utiles'].
     */
    function rod_cuenta_tomas(array $escenas): array
    {
        $c = ['pendiente' => 0, 'hecha' => 0, 'repetir' => 0, 'descartada' => 0];
        foreach ($escenas as $e) {
            foreach (rod_estados_partes($e) as $est) $c[$est]++;
            foreach ($e['variantes'] ?? [] as $va) foreach (rod_partes($e) as $col) $c[($va[$col] ?? null) ?: 'pendiente']++;
        }
        $c['total']  = $c['pendiente'] + $c['hecha'] + $c['repetir'] + $c['descartada'];
        $c['utiles'] = $c['total'] - $c['descartada'];
        return $c;
    }
}

if (!function_exists('rod_estados_variante')) {
    /** [parte => estado] de una variante (usa las mismas partes que su toma). */
    function rod_estados_variante(array $e, array $v): array
    {
        return array_map(static fn ($col) => ($v[$col] ?? null) ?: 'pendiente', rod_partes($e));
    }
}

if (!function_exists('rod_momento')) {
    /** Chip del momento del día (sol / luna + nombre); '' si no tiene. */
    function rod_momento(array $e, string $clase = ''): string
    {
        $f = (string) ($e['franja'] ?? '');
        $txt = \App\Models\RodajesEscenaModel::FRANJAS[$f] ?? '';
        if ($txt === '') return '';
        return '<span class="rd-momento ' . rod_dia_noche($f) . ($clase !== '' ? ' ' . $clase : '') . '"><i class="bi ' . ($f === 'noche' ? 'bi-moon-stars' : 'bi-sun') . '"></i>' . $txt . '</span>';
    }
}

if (!function_exists('rod_chips_setups')) {
    /** Chips «📷 Setup · lente» por parte; en gris lo que falta. */
    function rod_chips_setups(array $cajas): string
    {
        $ico = ['foto' => 'bi-camera', 'video' => 'bi-camera-video', '' => 'bi-camera'];
        $h = '';
        foreach ($cajas as $parte => $c) {
            $nombre = $c['st'] ? esc($c['st']['nombre']) : 'sin setup';
            $lente  = $c['tec']['Lente'] ?? '';
            $h .= '<span class="rd-stp' . ($c['st'] ? '' : ' falta') . '"><i class="bi ' . ($ico[$parte] ?? 'bi-camera') . '"></i>' . $nombre
                . ($lente !== '' && !str_contains(rod_normalizar((string) ($c['st']['nombre'] ?? '')), rod_normalizar($lente)) ? ' <b>' . esc($lente) . '</b>' : '')
                . '</span>';
        }
        return $h;
    }
}
