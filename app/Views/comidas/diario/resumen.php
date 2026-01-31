<?= $this->extend('comidas/layout'); ?>
<?= $this->section('content') ?>

<?php
helper('comidas');

/** === NUEVO: límites globales, sin user_id === */
$limites = comidas_limites();

/** Helpers */
// Reemplaza tu $fmt por esto
$fmt = function ($n, $d = 0) {
    $s = number_format((float)$n, $d, '.', '');
    // Solo recorta ceros si hay punto decimal
    if (strpos($s, '.') !== false) {
        $s = rtrim(rtrim($s, '0'), '.');
    }
    return $s;
};

$val = fn($arr, $k) => (float)($arr[$k] ?? 0);

/** Lee min/max literalmente de comidas_limites */
$minMax = function (string $clave) use ($limites) {
    $min = isset($limites[$clave]['falta'])  ? (float)$limites[$clave]['falta']['umbral']  : null;
    $max = isset($limites[$clave]['exceso']) ? (float)$limites[$clave]['exceso']['umbral'] : null;
    // corrige si están invertidos
    if ($min !== null && $max !== null && $min > $max) [$min, $max] = [$max, $min];
    return ['min' => $min, 'max' => $max];
};

/** RANGOS */
$rK = $minMax('kcal');
$rP = $minMax('proteina_g');
$rC = $minMax('carbohidratos_g');
$rG = $minMax('grasas_g');

/** VALORES DEL DÍA */
$kcal = $val($resumen, 'kcal');
$prot = $val($resumen, 'proteina_g');
$carb = $val($resumen, 'carbohidratos_g');
$gras = $val($resumen, 'grasas_g');

/** % barra usando el máximo */
$bar = function (float $value, ?float $min, ?float $max) {
    $pct = $max && $max > 0 ? min(100, max(0, $value / $max * 100)) : 0;
    $state = 'ok';
    if ($min !== null && $value < $min) $state = 'low';
    if ($max !== null && $value > $max) $state = 'high';
    $class = $state === 'ok' ? 'bg-success' : ($state === 'high' ? 'bg-danger' : 'bg-warning');
    return [$pct, $class, $state];
};

[$pctK, $clsK] = $bar($kcal, $rK['min'], $rK['max']);
[$pctP, $clsP] = $bar($prot, $rP['min'], $rP['max']);
[$pctC, $clsC] = $bar($carb, $rC['min'], $rC['max']);
[$pctG, $clsG] = $bar($gras, $rG['min'], $rG['max']);

$rangotxt = fn($r, $u) => sprintf(
    '%s – %s %s',
    $r['min'] !== null ? $fmt($r['min'], 0) : '—',
    $r['max'] !== null ? $fmt($r['max'], 0) : '—',
    $u
);
?>

<div class="container my-3">

    <!-- Calendario -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= site_url('comidas/diario/' . (clone $fechaSel)->modify('-1 day')->format('Y-m-d')) ?>" class="btn btn-sm btn-outline-secondary">&lt;</a>
            <h6 class="m-0"><?= $fechaSel->format('d/m/Y') ?></h4>
            <a href="<?= site_url('comidas/diario/' . (clone $fechaSel)->modify('+1 day')->format('Y-m-d')) ?>" class="btn btn-sm btn-outline-secondary">&gt;</a>
        </div>
    </div>

    <!-- PESO DEL DIA SIGUIENTE -->

    <?php
    $tz        = new \DateTimeZone('Europe/Madrid');
    $manianaDt = (new \DateTimeImmutable($fechaSel->format('Y-m-d'), $tz))->modify('+1 day');
    $pesoValor = $pesoManiana['peso'] ?? null;
    $pesoNotas = $pesoManiana['notas'] ?? null;
    ?>
    <?php if ($pesoValor): ?>
        <div class="d-flex justify-content-between align-items-center my-2">
            <div>
                <strong>Peso dia siguiente</strong>
            </div>
            <small class="text-muted">
                <?= $pesoValor !== null ? number_format((float)$pesoValor, 2, '.', '') . ' kg' : '—' ?>
            </small>

        </div>
    <?php endif; ?>

    <!-- Resumen del día (con límites) -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Resumen del día</h5>
                <div class="text-end">
                    <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/nutrientes') ?>"
                        class="btn btn-sm btn-outline-secondary">Ver más</a>

                    <?php
                    // (Opcional) ajusta la zona horaria si hace falta
                    // $tz  = new \DateTimeZone('Europe/Madrid');
                    // $now = new \DateTime('now', $tz);
                    $now   = new \DateTime('now');                 // usa la TZ del servidor
                    $mins  = (int)$now->format('H') * 60 + (int)$now->format('i'); // minutos desde 00:00

                    // Franjas:
                    // 00:00–04:30  nocturna
                    // 04:30–12:00  desayuno
                    // 12:00–16:00  almuerzo
                    // 16:00–20:00  merienda
                    // 20:00–24:00  cena
                    if ($mins >=  270 && $mins <  720) $tipoSugerido = 'desayuno';
                    elseif ($mins >=  720 && $mins <  960) $tipoSugerido = 'almuerzo';
                    elseif ($mins >=  960 && $mins < 1200) $tipoSugerido = 'merienda';
                    elseif ($mins >= 1200 && $mins < 1440) $tipoSugerido = 'cena';
                    else                                    $tipoSugerido = 'nocturna';
                    ?>

                    <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $tipoSugerido) ?>"
                        class="btn btn-sm btn-outline-primary"
                        title="Añadir a <?= esc(ucfirst($tipoSugerido)) ?>">
                        +
                    </a>

                </div>
            </div>

            <!-- Kcal -->
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div><strong>Calorías</strong> <span style="font-size:0.7em;"><?= $fmt((($rK['max'] + $rK['min']) / 2) - $kcal, 0) ?> kcal restantes</span></div>
                <small class="text-muted"><?= $rangotxt($rK, 'kcal') ?></small>
            </div>
            <div class="progress mb-2" style="height: 12px;">
                <div class="progress-bar <?= $clsK ?>" role="progressbar" style="width: <?= $fmt($pctK, 0) ?>%">
                    <?= $fmt($pctK, 0) ?>%
                </div>
            </div>
            <small class="text-muted d-block mb-3">
                Ingeridas: <strong><?= $fmt($kcal, 0) ?> calorias</strong>
            </small>

            <?php
            $rows = [
                ['Proteínas', 'proteina_g', $prot, $rP, $pctP, $clsP, 'g'],
                ['Carbohidratos', 'carbohidratos_g', $carb, $rC, $pctC, $clsC, 'g'],
                ['Grasas', 'grasas_g', $gras, $rG, $pctG, $clsG, 'g'],
            ];
            ?>
            <?php foreach ($rows as [$label, $clave, $valNow, $range, $pct, $cls, $unit]): ?>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div><?= esc($label) ?> <span style="font-size:0.7em;"><?= $fmt((($range['max'] + $range['min']) / 2) - $valNow, 0) ?> g restantes</span></div>
                    <small class="text-muted">
                        <?= $range['min'] !== null ? $fmt($range['min'], 0) : '—' ?> – <?= $range['max'] !== null ? $fmt($range['max'], 0) : '—' ?> <?= $unit ?>
                    </small>
                </div>
                <div class="progress mb-1" style="height: 8px;">
                    <div class="progress-bar <?= $cls ?>" role="progressbar" style="width: <?= $fmt($pct, 0) ?>%"></div>
                </div>
                <small class="text-muted d-block mb-2">
                    Ingeridas: <strong><?= $fmt($valNow, 0) ?> <?= $unit ?></strong>
                </small>
            <?php endforeach; ?>

        </div>
    </div>

    <?php
    /** === NUEVO: sin user_id === */
    $alertasDia = comidas_alertas_eval_dia($resumen);

    $tot7d     = comidas_totales_7d($fechaSel->format('Y-m-d'));
    $alertas7d = comidas_alertas_eval_7d($tot7d);

    // helpers visuales
    $badgeNivel = function (string $nivel) {
        return $nivel === 'critical' ? 'danger' : ($nivel === 'warning' ? 'warning' : 'info');
    };
    $badgeTipo = function (string $tipo) {
        return $tipo === 'exceso' ? 'text-bg-danger' : 'text-bg-secondary';
    };
    $iconTipo = function (string $tipo) {
        return $tipo === 'exceso' ? 'bi-arrow-up-short' : 'bi-arrow-down-short';
    };
    ?>



    <!-- Botón nuevo: copiar resumen del día -->
    <button type="button" id="btnCopiarDia" class="btn btn-sm btn-outline-success">
        Copiar día
    </button>

    <!-- Hubo entrenamiento -->
    <div class="mb-3">
        <div class="d-flex justify-content-center">
            <?php if (!empty($huboEntreno) && !empty($tiposEntreno)): ?>
                <span class="badge bg-warning text-dark rounded-pill">
                    Entrenamiento: <?= esc(implode(' · ', $tiposEntreno)) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ingestas del día agrupadas -->
    <?php $suma_total = 0 ?>
    <?php foreach ($tiposLista as $tipo): ?>
        <?php
        $totK = $fmt($resumenTipos[$tipo]['kcal']            ?? 0, 0);
        $totP = $fmt($resumenTipos[$tipo]['proteina_g']      ?? 0, 0);
        $totC = $fmt($resumenTipos[$tipo]['carbohidratos_g'] ?? 0, 0);
        $totG = $fmt($resumenTipos[$tipo]['grasas_g']        ?? 0, 0);
        ?>
        <div class="card mb-2">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span><?= ucfirst(str_replace('_', ' ', $tipo)) ?></span>
                    <span class="bg-light text-dark"><?= $totK ?> kcal</span>
                    <span class="">P <?= $totP ?> g</span>
                    <span class="">C <?= $totC ?> g</span>
                    <span class="">G <?= $totG ?> g</span>
                </div>

                <a href="<?= site_url('comidas/diario/' . $fechaSel->format('Y-m-d') . '/' . $tipo) ?>"
                    class="btn btn-sm btn-outline-primary"
                    title="Añadir a <?= esc(ucfirst(str_replace('_', ' ', $tipo))) ?>">
                    <i class="bi bi-plus"></i>
                    <span class="visually-hidden">Añadir</span>
                </a>
            </div>

            <ul class="list-group list-group-flush">
                <?php if (!empty($ingPorTipo[$tipo])): ?>
                    <?php $suma = 0 ?>
                    <?php foreach ($ingPorTipo[$tipo] as $i): ?>
                        <?php
                        $k = $fmt($i['macros']['kcal']            ?? 0, 0);
                        $p = $fmt($i['macros']['proteina_g']      ?? 0, 1);
                        $c = $fmt($i['macros']['carbohidratos_g'] ?? 0, 1);
                        $g = $fmt($i['macros']['grasas_g']        ?? 0, 1);
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= esc($i['nombre']) ?></strong>
                                <br><small class="text-muted"><?= esc($i['cantidad_label']) ?></small>
                                <?php $suma += $i["cantidad_gramos"]; ?>

                            </div>

                            <div class="text-end">
                                <div class="fw-semibold"><?= $k ?> kcal</div>
                                <small class="text-muted">P <?= $p ?> g · C <?= $c ?> g · G <?= $g ?> g</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <small class="text-muted">Total: <?= $suma ?> g</small>
                        <?php $suma_total += $suma ?>
                    </li>
                <?php else: ?>
                    <li class="list-group-item text-muted">Sin registros</li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endforeach; ?>
    <div class="text-center">
        <small class="text-muted">
            Suma total: <?= $suma_total ?> g /
            Densidad = <?= $suma_total > 0 ? round($kcal / $suma_total, 2) : 0 ?>
        </small>
    </div>

    <!-- Calendario -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= site_url('comidas/diario/' . (clone $fechaSel)->modify('-1 day')->format('Y-m-d')) ?>" class="btn btn-outline-secondary">&lt;</a>
            <h4 class="m-0"><?= $fechaSel->format('d/m/Y') ?></h4>
            <a href="<?= site_url('comidas/diario/' . (clone $fechaSel)->modify('+1 day')->format('Y-m-d')) ?>" class="btn btn-outline-secondary">&gt;</a>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('btnCopiarDia');
        if (!btn) return;

        // Datos PHP -> JS
        const fecha = "<?= $fechaSel->format('Y-m-d') ?>";
        const tiposOrden = <?= json_encode(array_values($tiposLista), JSON_UNESCAPED_UNICODE) ?>;
        const ingPorTipo = <?= json_encode($ingPorTipo ?? [], JSON_UNESCAPED_UNICODE) ?>;
        const resumenTipos = <?= json_encode($resumenTipos ?? [], JSON_UNESCAPED_UNICODE) ?>;
        const resumenDia = <?= json_encode($resumen ?? [], JSON_UNESCAPED_UNICODE) ?>;

        const fmt = (n, d = 0) => {
            const num = Number(n || 0);
            let s = num.toFixed(d);
            if (s.indexOf('.') !== -1) s = s.replace(/\.?0+$/, ''); // quita ceros solo si hay decimales
            return s;
        };

        const tituloTipo = t => t.charAt(0).toUpperCase() + t.slice(1);

        const lineasTipo = (tipo) => {
            const rows = Array.isArray(ingPorTipo[tipo]) ? ingPorTipo[tipo] : [];
            const out = [];

            out.push(`== ${tituloTipo(tipo)} ==`);
            if (rows.length === 0) {
                out.push('  (sin registros)');
            } else {
                rows.forEach(r => {
                    const nombre = r?.nombre || '—';
                    const cant = r?.cantidad_label || '—';
                    const k = fmt(r?.macros?.kcal, 0);
                    const p = fmt(r?.macros?.proteina_g, 1);
                    const c = fmt(r?.macros?.carbohidratos_g, 1);
                    const g = fmt(r?.macros?.grasas_g, 1);
                    out.push(`- ${nombre} · ${cant} · ${k} kcal | P ${p} g · C ${c} g · G ${g} g`);
                });
            }

            const tot = resumenTipos?.[tipo] || {};
            const tk = fmt(tot?.kcal, 0);
            const tp = fmt(tot?.proteina_g, 0);
            const tc = fmt(tot?.carbohidratos_g, 0);
            const tg = fmt(tot?.grasas_g, 0);
            out.push(`> Totales ${tituloTipo(tipo)}: ${tk} kcal | P ${tp} g · C ${tc} g · G ${tg} g`);
            out.push(''); // línea en blanco
            return out;
        };

        const buildTexto = () => {
            const out = [];
            out.push(`Día: ${fecha}`);
            out.push('');

            tiposOrden.forEach(t => out.push(...lineasTipo(t)));

            const dk = fmt(resumenDia?.kcal, 0);
            const dp = fmt(resumenDia?.proteina_g, 0);
            const dc = fmt(resumenDia?.carbohidratos_g, 0);
            const dg = fmt(resumenDia?.grasas_g, 0);

            out.push(`== Totales del día ==`);
            out.push(`${dk} kcal | P ${dp} g · C ${dc} g · G ${dg} g`);
            return out.join('\n');
        };

        const copiar = async (texto) => {
            try {
                await navigator.clipboard.writeText(texto);
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success');
                btn.textContent = '¡Copiado!';
                setTimeout(() => {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-success');
                    btn.textContent = 'Copiar día';
                }, 1500);
            } catch (e) {
                // fallback
                const ta = document.createElement('textarea');
                ta.value = texto;
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    btn.classList.remove('btn-outline-success');
                    btn.classList.add('btn-success');
                    btn.textContent = '¡Copiado!';
                    setTimeout(() => {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-success');
                        btn.textContent = 'Copiar día';
                    }, 1500);
                } catch (err) {
                    alert('No se pudo copiar automáticamente. Selecciona y copia manualmente.');
                    console.error(err);
                } finally {
                    document.body.removeChild(ta);
                }
            }
        };

        btn.addEventListener('click', () => {
            const texto = buildTexto();
            copiar(texto);
        });
    });
</script>

<?= $this->endSection() ?>