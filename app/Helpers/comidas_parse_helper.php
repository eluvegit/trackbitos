<?php
function comidas_parse_bulk(string $txt): array
{
    // Normaliza
    $raw = str_replace(["\r", "µ", "μ"], ["\n", "mcg", "mcg"], $txt);
    $lines = preg_split('/\n+/', $raw);

    // Mapa de sinónimos -> columna y unidad base de la columna
    $map = [
        // macros
        'calorías|calorias|energía|energia'      => ['kcal', 'kcal'],
        'proteína|proteina'                      => ['proteina_g', 'g'],
        'grasa|grasas'                           => ['grasas_g', 'g'],
        'carbohidratos'                          => ['carbohidratos_g', 'g'],
        'fibra'                                  => ['fibra_g', 'g'],
        'azúcares|azucares'                      => ['azucares_g', 'g'],

        // minerales
        'sodio|sodio na'                         => ['sodio_mg', 'mg'],
        'calcio|calcio ca'                       => ['calcio_mg', 'mg'],
        'hierro|hierro fe'                       => ['hierro_mg', 'mg'],
        'magnesio|magnesio mg'                   => ['magnesio_mg', 'mg'],
        'fósforo|fosforo|fósforo p|fosforo p'    => ['fosforo_mg', 'mg'],
        'potasio|potasio k'                      => ['potasio_mg', 'mg'],
        'zinc|zinc zn'                           => ['zinc_mg', 'mg'],
        'cobre|cobre cu'                          => ['cobre_mg', 'mg'],
        'manganeso'                               => ['manganeso_mg', 'mg'],
        'selenio|selenio se'                      => ['selenio_ug', 'mcg'],
        'yodo'                                    => ['yodo_ug', 'mcg'],

        // vitaminas
        'vitamina a(?!.*retinol)'                => ['vitamina_a_rae_ug', 'mcg'],
        'vitamina c'                             => ['vitamina_c_mg', 'mg'],
        'vitamina d'                             => ['vitamina_d_ug', 'mcg'],
        'vitamina e'                             => ['vitamina_e_mg', 'mg'],
        'vitamina k'                             => ['vitamina_k_ug', 'mcg'],

        // grasas específicas
        'ácidos? grasos? saturados?'             => ['grasas_saturadas_g', 'g'],
    ];

    $payload = [
        // inicializa por si faltan
        'kcal' => null, 'proteina_g' => null, 'grasas_g' => null, 'carbohidratos_g' => null,
        'fibra_g' => null, 'azucares_g' => null, 'sodio_mg' => null,
        'calcio_mg'=>null,'hierro_mg'=>null,'magnesio_mg'=>null,'fosforo_mg'=>null,'potasio_mg'=>null,'zinc_mg'=>null,'cobre_mg'=>null,
        'manganeso_mg'=>null,'selenio_ug'=>null,'yodo_ug'=>null,
        'vitamina_a_rae_ug'=>null,'vitamina_c_mg'=>null,'vitamina_d_ug'=>null,'vitamina_e_mg'=>null,'vitamina_k_ug'=>null,
        'grasas_saturadas_g'=>null,'omega3_mg'=>null,'omega6_mg'=>null,
    ];

    $omega3_g = 0.0;
    $omega6_g = 0.0;

    // Patrones para omega-3/6
    $pats3 = [
        'octadecatrienoico', 'estearidónico', 'estearidonico',
        'eicosapentaenoico', 'epa', 'docosapentaenoico', 'dpa', 'docosahexaenoico', 'dha', 'omega-3'
    ];
    $pats6 = [
        'octadecadienoico', 'linoleic', 'linoleico', 'araquidónico', 'araquidonico',
        'gamma-linolénico', 'gamma-linolenico', 'dihomo-gamma-linolénico', 'omega-6'
    ];

    foreach ($lines as $line) {
        $l = trim($line);
        if ($l === '') continue;

        // Extrae "nombre valor unidad"
        if (!preg_match('/^\s*([^\d%]+?)\s*([-+]?\d+(?:[.,]\d+)?)\s*(kcal|kc|cal|g|mg|mcg)\b/i', $l, $m)) {
            // También detecta solo el valor al final de línea aunque haya “%”
            if (!preg_match('/^\s*([^\d%]+?).*?([-+]?\d+(?:[.,]\d+)?)\s*(kcal|kc|cal|g|mg|mcg)\b/i', $l, $m)) {
                // Intenta capturar posibles grasas omega línea suelta
                if (preg_match('/('.implode('|',$pats3).')/i', $l) &&
                    preg_match('/([-+]?\d+(?:[.,]\d+)?)\s*g\b/i', $l, $mv)) {
                    $omega3_g += (float) str_replace(',', '.', $mv[1]);
                } elseif (preg_match('/('.implode('|',$pats6).')/i', $l) &&
                          preg_match('/([-+]?\d+(?:[.,]\d+)?)\s*g\b/i', $l, $mv)) {
                    $omega6_g += (float) str_replace(',', '.', $mv[1]);
                }
                continue;
            }
        }

        $name = mb_strtolower(trim($m[1]));
        $val  = (float) str_replace(',', '.', $m[2]);
        $unit = mb_strtolower($m[3]);
        if ($unit === 'kc' || $unit === 'cal') $unit = 'kcal';

        // ¿Es una línea de omega?
        $is3 = preg_match('/('.implode('|',$pats3).')/i', $name);
        $is6 = preg_match('/('.implode('|',$pats6).')/i', $name);
        if (($is3 || $is6) && $unit === 'g') {
            if ($is3) $omega3_g += $val;
            if ($is6) $omega6_g += $val;
            continue;
        }

        // Busca columna por el mapa
        foreach ($map as $rx => [$col, $baseUnit]) {
            if (preg_match('/^'.$rx.'\b/u', $name)) {
                $norm = $val;
                // Conversiones a la unidad de la columna
                if ($baseUnit === 'g') {
                    if ($unit === 'mg') $norm = $val / 1000.0;
                    elseif ($unit === 'mcg') $norm = $val / 1_000_000.0;
                } elseif ($baseUnit === 'mg') {
                    if ($unit === 'g') $norm = $val * 1000.0;
                    elseif ($unit === 'mcg') $norm = $val / 1000.0;
                } elseif ($baseUnit === 'mcg') {
                    if ($unit === 'g') $norm = $val * 1_000_000.0;
                    elseif ($unit === 'mg') $norm = $val * 1000.0;
                } // kcal queda igual

                // Guarda con 2 decimales
                $payload[$col] = round($norm, 2);
                break;
            }
        }
    }

    if ($omega3_g > 0) $payload['omega3_mg'] = round($omega3_g * 1000, 2);
    if ($omega6_g > 0) $payload['omega6_mg'] = round($omega6_g * 1000, 2);

    // Limpia nulls (no enviados)
    return array_filter($payload, fn($v) => $v !== null);
}
