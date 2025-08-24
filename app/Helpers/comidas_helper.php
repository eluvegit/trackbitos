<?php
/**
 * Helpers de límites/alertas SIN user_id.
 *  - Dinámico: usa nutrientes activos y columnas reales de comidas_alimentos
 *  - Compatible con firmas antiguas (parámetros $userId)
 */

use Config\Database;
use App\Models\ComidasNutrientesModel;

/** Nutrientes activos (macro/micro) ordenados */
function _comidas_nutrientes_activos(): array
{
    $m = new ComidasNutrientesModel();
    return $m->where('activo', 1)
             ->orderBy('es_macro','DESC')
             ->orderBy('orden','ASC')
             ->orderBy('nombre','ASC')
             ->findAll();
}

/** Columnas reales de la tabla comidas_alimentos */
function _comidas_alimento_fields(): array
{
    $db = Database::connect();
    return $db->getFieldNames('comidas_alimentos') ?: [];
}

/**
 * Carga TODOS los límites y los devuelve indexados por CLAVE de nutriente:
 *   $out['proteina_g'] = ['falta'=>..., 'exceso'=>...]
 * Solo para nutrientes activos (evita ruido).
 */
function comidas_limites(): array
{
    $db = Database::connect();

    $rows = $db->table('comidas_limites cl')
        ->select('cl.*, n.id AS n_id, n.nombre AS n_nombre, n.clave AS n_clave, n.unidad AS n_unidad')
        ->join('comidas_nutrientes n', 'n.id = cl.nutriente_id', 'inner')
        ->where('n.activo', 1)
        ->get()->getResultArray();

    $out = [];
    foreach ($rows as $r) {
        $clave = $r['n_clave'];
        $sent  = $r['tipo']; // 'falta' | 'exceso'
        $out[$clave][$sent] = [
            'nutriente_id' => (int)$r['n_id'],
            'clave'        => $clave,
            'nombre'       => $r['n_nombre'],
            'unidad'       => $r['n_unidad'],
            'tipo'         => $sent,
            'umbral'       => (float)$r['umbral'],
            'nivel'        => $r['nivel'], // 'info'|'warning'|'critical'
            'mensaje'      => $r['mensaje'] ?? null,
        ];
    }
    return $out;
}

/** COMPAT: firma antigua que incluía $userId */
function comidas_limites_by_user(int $userId): array
{
    return comidas_limites();
}

/**
 * Evalúa límites vs totales.
 * IMPORTANTE: si un nutriente no está en $totales (no podemos calcularlo), lo **omitimos**.
 */
function comidas_eval_limits(array $limites, array $totales, int $escala = 1): array
{
    $alerts = [];
    foreach ($limites as $clave => $byDir) {
        // si no está en totales, saltamos (evita "faltas" ficticias)
        if (!array_key_exists($clave, $totales)) continue;

        $valor = (float)$totales[$clave];
        foreach (['falta','exceso'] as $dir) {
            if (empty($byDir[$dir])) continue;
            $L = $byDir[$dir];
            $umbral = (float)$L['umbral'] * $escala;

            $dispara = ($dir === 'falta') ? ($valor < $umbral) : ($valor > $umbral);
            if (!$dispara) continue;

            $diff = $valor - $umbral;
            $pct  = $umbral > 0 ? ($valor / $umbral * 100.0) : null;

            $alerts[] = [
                'tipo'         => $dir,                // 'falta'|'exceso'
                'nivel'        => $L['nivel'],         // 'info'|'warning'|'critical'
                'nutriente_id' => $L['nutriente_id'],
                'clave'        => $clave,
                'nombre'       => $L['nombre'],
                'unidad'       => $L['unidad'],
                'umbral'       => round($umbral, 2),
                'valor'        => round($valor, 2),
                'diff'         => round($diff, 2),
                'pct'          => $pct !== null ? round($pct, 1) : null,
                'mensaje'      => $L['mensaje'] ?? null,
            ];
        }
    }

    // Orden: critical > warning > info, y luego mayor desviación relativa
    $sev = ['critical'=>0,'warning'=>1,'info'=>2];
    usort($alerts, function($a,$b) use ($sev) {
        $sa = $sev[$a['nivel']] ?? 9;
        $sb = $sev[$b['nivel']] ?? 9;
        if ($sa !== $sb) return $sa <=> $sb;
        $da = $a['umbral'] > 0 ? abs($a['valor'] - $a['umbral']) / $a['umbral'] : 0;
        $db = $b['umbral'] > 0 ? abs($b['valor'] - $b['umbral']) / $b['umbral'] : 0;
        return $db <=> $da;
    });

    return $alerts;
}

/**
 * Totales de los últimos 7 días (incluido $endDate) **DINÁMICOS**:
 * suma todos los nutrientes activos cuya clave exista como columna en comidas_alimentos.
 *
 * COMPAT de firma:
 *  - Nuevo uso: comidas_totales_7d('2025-08-24')
 *  - Antiguo:   comidas_totales_7d($userId, '2025-08-24')  // $userId se ignora
 */
function comidas_totales_7d($arg1 = null, ?string $arg2 = null): array
{
    // Resolver parámetros (compat)
    $endDate = is_int($arg1) ? $arg2 : $arg1;

    $end   = $endDate ? new \DateTime($endDate) : new \DateTime('today');
    $start = (clone $end)->modify('-6 days');

    $nutrs  = _comidas_nutrientes_activos();
    $fields = _comidas_alimento_fields();

    // construir SELECT dinámico
    $selects = [];
    $claves  = [];
    foreach ($nutrs as $n) {
        $c = $n['clave'];
        if (!in_array($c, $fields, true)) continue; // sólo columnas reales
        $selects[] = "SUM(ci.cantidad_gramos * ca.`$c` / 100) AS `$c`";
        $claves[]  = $c;
    }
    if (empty($selects)) return []; // nada que sumar = no evaluamos límites

    $db  = Database::connect();
    $row = $db->table('comidas_ingestas ci')
        ->select(implode(",\n", $selects), false)
        ->join('comidas_dias cd', 'cd.id = ci.dia_id', 'inner')
        ->join('comidas_alimentos ca', "ca.id = ci.item_id AND ci.item_tipo = 'alimento'", 'left')
        ->where('cd.fecha >=', $start->format('Y-m-d'))
        ->where('cd.fecha <=', $end->format('Y-m-d'))
        ->get()->getRowArray() ?? [];

    // Normaliza a float SOLO para las claves calculadas
    $out = [];
    foreach ($claves as $c) {
        $out[$c] = isset($row[$c]) ? (float)$row[$c] : 0.0;
    }
    return $out;
}

/** COMPAT: antes: comidas_alertas_eval_dia($userId, $totalesDia) */
function comidas_alertas_eval_dia($arg1, ?array $arg2 = null): array
{
    $totalesDia = is_array($arg1) && $arg2 === null ? $arg1 : ($arg2 ?? []);
    $limits = comidas_limites(); // global
    return comidas_eval_limits($limits, $totalesDia, 1);
}

/** COMPAT: antes: comidas_alertas_eval_7d($userId, $totales7d) */
function comidas_alertas_eval_7d($arg1, ?array $arg2 = null): array
{
    $totales7d = is_array($arg1) && $arg2 === null ? $arg1 : ($arg2 ?? []);
    $limits = comidas_limites(); // global
    return comidas_eval_limits($limits, $totales7d, 7);
}
