<?php
function comidas_parse_bulk(string $txt): array
{
    // Normaliza líneas y unidades
    $raw = str_replace(["\r\n", "\r", "µ", "μ"], ["\n", "\n", "mcg", "mcg"], $txt);
    $lines = preg_split('/\n+/', $raw);

    // Mapa de sinónimos -> [columna, unidadBase]
    $map = [
        // macros
        'calorías|calorias|energía|energia'         => ['kcal', 'kcal'],
        'proteína|proteina'                          => ['proteina_g', 'g'],
        'grasa|grasas'                               => ['grasas_g', 'g'],
        'carbohidratos'                              => ['carbohidratos_g', 'g'],
        'fibra'                                      => ['fibra_g', 'g'],
        'azúcares|azucares|azucar'                   => ['azucares_g', 'g'],

        // minerales
        'sodio(\s+na)?'                              => ['sodio_mg', 'mg'],
        'calcio(\s+ca)?'                             => ['calcio_mg', 'mg'],
        'hierro(\s+fe)?'                             => ['hierro_mg', 'mg'],
        'magnesio'                                   => ['magnesio_mg', 'mg'],
        'fósforo(\s+p)?|fosforo(\s+p)?'              => ['fosforo_mg', 'mg'],
        'potasio(\s+k)?'                             => ['potasio_mg', 'mg'],
        'zinc(\s+zn)?'                               => ['zinc_mg', 'mg'],
        'cobre(\s+cu)?'                              => ['cobre_mg', 'mg'],
        'manganeso'                                  => ['manganeso_mg', 'mg'],
        'selenio(\s+se)?'                            => ['selenio_ug', 'mcg'],
        'yodo'                                       => ['yodo_ug', 'mcg'],

        // vitaminas (solo las que tienes en schema)
        'vitamina\s+a(?!.*retinol)'                  => ['vitamina_a_rae_ug', 'mcg'],
        'vitamina\s+c'                               => ['vitamina_c_mg', 'mg'],
        'vitamina\s+d'                               => ['vitamina_d_ug', 'mcg'],
        'vitamina\s+e'                               => ['vitamina_e_mg', 'mg'],
        'vitamina\s+k'                               => ['vitamina_k_ug', 'mcg'],

        // tipos de grasa
        'ácidos?\s*grasos?\s*saturados?|saturadas?|sat\.?' => ['grasas_saturadas_g', 'g'],

        // ⚠️ "sal" no es sodio directamente; lo tratamos aparte (salt→sodium)
        // 'sal' => ['sodio_mg', 'mg'], // NO usar aquí, lo manejamos como caso especial
    ];

    // Payload inicial
    $payload = [
        'kcal'=>null,'proteina_g'=>null,'grasas_g'=>null,'carbohidratos_g'=>null,
        'fibra_g'=>null,'azucares_g'=>null,
        'sodio_mg'=>null,'calcio_mg'=>null,'hierro_mg'=>null,'magnesio_mg'=>null,
        'fosforo_mg'=>null,'potasio_mg'=>null,'zinc_mg'=>null,'cobre_mg'=>null,
        'manganeso_mg'=>null,'selenio_ug'=>null,'yodo_ug'=>null,
        'vitamina_a_rae_ug'=>null,'vitamina_c_mg'=>null,'vitamina_d_ug'=>null,
        'vitamina_e_mg'=>null,'vitamina_k_ug'=>null,
        'grasas_saturadas_g'=>null,'omega3_mg'=>null,'omega6_mg'=>null,
    ];

    // Acumuladores de omega (en mg)
    $omega3_mg = 0.0;
    $omega6_mg = 0.0;

    // Patrones con los nombres de FA
    $pats3 = [
        'octadecatrien(o|ó)ico', 'estearid(ó|o)nico',
        'eicosapentaenoico|epa', 'docosapentaenoico|dpa', 'docosahexaenoico|dha',
        'omega[-\s]?3'
    ];
    $pats6 = [
        'octadecadien(o|ó)ico|linoleic|linoleico',
        'araquid(ó|o)nico', 'gamma[-\s]?linol(é|e)nico', 'dihomo[-\s]?gamma[-\s]?linol(é|e)nico',
        'omega[-\s]?6'
    ];

    foreach ($lines as $line) {
        $l = trim($line);
        if ($l === '') continue;

        // Quita “% VDR”, “% IR”, etc.
        $l = preg_replace('/\b\d{1,3}(?:[.,]\d+)?\s*%\b/u', '', $l);
        // Quita separadores tipo ":" o "-" entre nombre y cifra
        $l = preg_replace('/\s*[:\-–]\s*/u', ' ', $l);

        // name value unit (permite kcal|kj|g|mg|mcg)
        if (!preg_match('/^\s*([^\d%]+?)\s*([-+]?\d+(?:[.,]\d+)?)\s*(kcal|kj|kc|cal|g|mg|mcg)\b/iu', $l, $m)) {
            // Si no, intenta capturar omega por su nombre con "g" o "mg" en cualquier parte
            if (preg_match('/('.implode('|', $pats3).')/iu', $l) &&
                preg_match('/([-+]?\d+(?:[.,]\d+)?)\s*(mg|g)\b/iu', $l, $mv)) {
                $v = (float) str_replace(',', '.', $mv[1]);
                $omega3_mg += ($mv[2]==='g' || strtolower($mv[2])==='g') ? $v*1000.0 : $v;
            } elseif (preg_match('/('.implode('|', $pats6).')/iu', $l) &&
                      preg_match('/([-+]?\d+(?:[.,]\d+)?)\s*(mg|g)\b/iu', $l, $mv)) {
                $v = (float) str_replace(',', '.', $mv[1]);
                $omega6_mg += ($mv[2]==='g' || strtolower($mv[2])==='g') ? $v*1000.0 : $v;
            }
            continue;
        }

        $name = mb_strtolower(trim($m[1]));
        $val  = (float) str_replace(',', '.', $m[2]);
        $unit = mb_strtolower($m[3]);

        // Normaliza unidades de energía
        if ($unit === 'kc' || $unit === 'cal') $unit = 'kcal';
        if ($unit === 'kj') { // kJ → kcal
            $val  = $val / 4.184;
            $unit = 'kcal';
        }

        // Caso especial: “sal” ⇒ convertir a sodio
        // (etiquetado UE: SAL(g) = SODIO(g) × 2.5  ⇒ SODIO(mg) = SAL(g) / 2.5 × 1000 = SAL(g)*400)
        if (preg_match('/^sal(\s+equivalente)?\b/u', $name)) {
            // Si viene en mg, primero a g
            $sal_g = ($unit === 'mg') ? ($val/1000.0) : ($unit === 'g' ? $val : 0.0);
            if ($sal_g > 0) {
                $sodio_mg = $sal_g * 400.0;
                $payload['sodio_mg'] = round($sodio_mg, 2);
            }
            continue;
        }

        // ¿Es una línea de omega explícita?
        $is3 = preg_match('/('.implode('|', $pats3).')/iu', $name);
        $is6 = preg_match('/('.implode('|', $pats6).')/iu', $name);
        if ($is3 || $is6) {
            $mg = ($unit === 'g') ? $val*1000.0 : ($unit === 'mg' ? $val : 0.0);
            if ($mg > 0) {
                if ($is3) $omega3_mg += $mg;
                if ($is6) $omega6_mg += $mg;
            }
            continue;
        }

        // Busca columna por el mapa
        $matched = false;
        foreach ($map as $rx => [$col, $baseUnit]) {
            if (preg_match('/^'.$rx.'\b/u', $name)) {
                $norm = $val;

                // Conversiones a la unidad base
                if ($baseUnit === 'g') {
                    if ($unit === 'mg')   $norm = $val / 1000.0;
                    elseif ($unit === 'mcg') $norm = $val / 1_000_000.0;
                } elseif ($baseUnit === 'mg') {
                    if     ($unit === 'g')   $norm = $val * 1000.0;
                    elseif ($unit === 'mcg') $norm = $val / 1000.0;
                } elseif ($baseUnit === 'mcg') {
                    if     ($unit === 'g')   $norm = $val * 1_000_000.0;
                    elseif ($unit === 'mg')  $norm = $val * 1000.0;
                } // kcal queda igual

                $payload[$col] = round($norm, 2);
                $matched = true;
                break;
            }
        }

        // Si era "energía" en kcal pero no casó por el mapa (poco probable), guarda igualmente
        if (!$matched && $unit === 'kcal' && preg_match('/energ|calor/i', $name)) {
            $payload['kcal'] = round($val, 2);
        }
    }

    if ($omega3_mg > 0) $payload['omega3_mg'] = round($omega3_mg, 2);
    if ($omega6_mg > 0) $payload['omega6_mg'] = round($omega6_mg, 2);

    // Devuelve solo lo detectado
    return array_filter($payload, fn($v) => $v !== null);
}
