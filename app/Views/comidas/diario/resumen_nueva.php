<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$limites = comidas_limites();
$fmt = static function ($n, $d = 0) {
    $s = number_format((float) $n, $d, '.', '');
    return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
};
$rango = static function (string $clave) use ($limites) {
    $min = isset($limites[$clave]['falta']) ? (float) $limites[$clave]['falta']['umbral'] : null;
    $max = isset($limites[$clave]['exceso']) ? (float) $limites[$clave]['exceso']['umbral'] : null;
    if ($min !== null && $max !== null && $min > $max) [$min, $max] = [$max, $min];
    return [$min, $max];
};
$barra = static function (float $v, ?float $min, ?float $max): array {
    $escala = max(($max ?? $min ?? 0) * 1.2, $v, 1);
    $estado = ($min !== null && $v < $min) ? 'low' : (($max !== null && $v > $max) ? 'high' : 'ok');
    if ($min === null && $max === null) $estado = '';
    return [
        'lleno'  => round(min(100, $v / $escala * 100), 1),
        'zona'   => $min !== null || $max !== null ? [round(($min ?? 0) / $escala * 100, 1), round(($max ?? $escala) / $escala * 100, 1)] : null,
        'estado' => $estado,
    ];
};
$quedan = static function (float $v, ?float $min, ?float $max) {
    $obj = ($min !== null && $max !== null) ? ($min + $max) / 2 : ($max ?? $min);
    return $obj === null ? null : $obj - $v;
};
$pintaBarra = static fn (array $b, string $extra = '') => '<div class="cm-barra ' . $extra . ($b['estado'] ? ' s-' . $b['estado'] : '') . '">'
    . ($b['zona'] ? '<span class="zona" style="left:' . $b['zona'][0] . '%;width:' . max(0, $b['zona'][1] - $b['zona'][0]) . '%"></span>' : '')
    . '<span class="lleno" style="width:' . $b['lleno'] . '%"></span></div>';

$f = $fechaSel->format('Y-m-d');
$ahora = new \DateTime('now');
$hoyComida = (clone $ahora)->modify((int) $ahora->format('H') < 7 ? '-1 day' : '+0 day')->format('Y-m-d');
$mins = (int) $ahora->format('H') * 60 + (int) $ahora->format('i');
$sugerido = match (true) {
    $mins >= 270 && $mins < 720  => 'desayuno',
    $mins >= 720 && $mins < 960  => 'almuerzo',
    $mins >= 960 && $mins < 1200 => 'merienda',
    $mins >= 1200                => 'cena',
    default                      => 'nocturna',
};
$esHoy = $f === $hoyComida;

$kcal = (float) ($resumen['kcal'] ?? 0);
[$kMin, $kMax] = $rango('kcal');
$bK = $barra($kcal, $kMin, $kMax);
$qK = $quedan($kcal, $kMin, $kMax);
$macros = [
    ['Proteínas', 'proteina_g'],
    ['Carbohidratos', 'carbohidratos_g'],
    ['Grasas', 'grasas_g'],
];

$nombreTipo = static fn (string $t) => ucfirst(str_replace('_', ' ', $t));
$titulo = ucfirst(\CodeIgniter\I18n\Time::parse($f)->toLocalizedString("EEEE d 'de' MMMM"));
$dia = static fn (string $mod) => site_url('comidas/diario/' . (new \DateTime($f))->modify($mod)->format('Y-m-d'));

$pesoValor = $pesoManiana['peso'] ?? null;
$sumaTotal = 0;
foreach ($ingPorTipo as $lista) foreach ($lista as $i) $sumaTotal += (float) ($i['cantidad_gramos'] ?? 0);
$nIng = array_sum(array_map('count', $ingPorTipo));

$sub = [];
$sub[] = $esHoy ? '<span class="fv-estado e-acc">Hoy</span>' : '<span>' . esc($fechaSel->format('d/m/Y')) . '</span>';
$sub[] = '<span>' . $nIng . ' ' . ($nIng === 1 ? 'registro' : 'registros') . '</span>';
if (!empty($huboEntreno) && !empty($tiposEntreno)) $sub[] = '<span class="fv-estado e-info">Entreno: ' . esc(implode(' · ', $tiposEntreno)) . '</span>';
if ($pesoValor) $sub[] = '<span><i class="bi bi-speedometer2"></i> ' . $fmt($pesoValor, 2) . ' kg al día siguiente</span>';
?>
<div class="fv rc ancho">
    <?= view('comidas/_cabecera', [
        'activa'   => 'diario',
        'titulo'   => esc($titulo),
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => '<div class="cm-fecha">'
            . '<a class="fv-ibtn" href="' . $dia('-1 day') . '" title="Día anterior"><i class="bi bi-chevron-left"></i></a>'
            . '<label class="cm-fecha-dia" title="Elegir día"><i class="bi bi-calendar3"></i><span class="cm-num">' . $fechaSel->format('d/m') . '</span><input type="date" id="cmIrFecha" value="' . $f . '"></label>'
            . '<a class="fv-ibtn" href="' . $dia('+1 day') . '" title="Día siguiente"><i class="bi bi-chevron-right"></i></a>'
            . ($esHoy ? '' : '<a class="fv-btn fv-btn-sm" href="' . site_url('comidas/diario/hoy') . '">Hoy</a>')
            . '</div>',
    ]) ?>

    <div class="cm-dia">
        <section class="fv-panel cm-kcal">
            <div class="fv-ph"><h3>Calorías</h3><a class="der fv-link" style="font-size:12px" href="<?= site_url('comidas/diario/' . $f . '/nutrientes') ?>">Todos los nutrientes <i class="bi bi-arrow-right"></i></a></div>
            <div class="cm-kcal-top">
                <span class="v"><?= $fmt($kcal) ?></span><span class="u">kcal</span>
                <div class="der">
                    <?php if ($kMin !== null || $kMax !== null): ?><span>objetivo <b class="cm-num"><?= $kMin !== null ? $fmt($kMin) : '—' ?>–<?= $kMax !== null ? $fmt($kMax) : '—' ?></b></span><?php endif; ?>
                    <?php if ($qK !== null): ?>
                        <span class="<?= $bK['estado'] === 'high' ? 'e-bad' : ($bK['estado'] === 'ok' ? 'e-ok' : '') ?>"><?= $qK >= 0 ? 'quedan <b class="cm-num">' . $fmt($qK) . '</b>' : 'te pasas <b class="cm-num">' . $fmt(-$qK) . '</b>' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?= $pintaBarra($bK, 'grande') ?>
            <div class="cm-macros" style="margin-top:6px">
                <?php foreach ($macros as [$nombre, $clave]): ?>
                    <?php
                    $v = (float) ($resumen[$clave] ?? 0);
                    [$mn, $mx] = $rango($clave);
                    $b = $barra($v, $mn, $mx);
                    $q = $quedan($v, $mn, $mx);
                    ?>
                    <div class="cm-macro">
                        <div class="cm-macro-t">
                            <span class="n"><?= $nombre ?></span>
                            <span class="v"><?= $fmt($v) ?> g</span>
                            <?php if ($q !== null): ?><span class="q <?= $b['estado'] === 'high' ? 'e-bad' : 't3' ?>"><?= $q >= 0 ? 'quedan ' . $fmt($q) : '+' . $fmt(-$q) ?></span><?php endif; ?>
                            <span class="r"><?= $mn !== null ? $fmt($mn) : '—' ?>–<?= $mx !== null ? $fmt($mx) : '—' ?> g</span>
                        </div>
                        <?= $pintaBarra($b) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rc-sec">
            <a class="fv-btn fv-btn-pri" style="height:48px;font-size:14px" href="<?= site_url('comidas/diario/' . $f . '/' . $sugerido) ?>"><i class="bi bi-plus-lg"></i>Añadir a <?= $nombreTipo($sugerido) ?></a>
            <div class="cm-herr">
                <button type="button" class="fv-btn" id="cmCopiarDia"><i class="bi bi-clipboard"></i><span>Copiar día</span></button>
                <a class="fv-btn" href="<?= site_url('comidas/diario/top-dias') ?>"><i class="bi bi-trophy"></i>Mejores días</a>
                <a class="fv-btn" href="<?= site_url('comidas/diario/' . $f . '/nutrientes') ?>"><i class="bi bi-list-columns-reverse"></i>Nutrientes</a>
            </div>
            <div class="cm-datos">
                <span>Total ingerido <b class="cm-num"><?= $fmt($sumaTotal) ?> g</b></span>
                <span>Densidad <b class="cm-num"><?= $sumaTotal > 0 ? $fmt($kcal / $sumaTotal, 2) : '0' ?></b> kcal/g</span>
            </div>
        </section>
    </div>

    <div class="cm-comidas">
        <?php foreach ($tiposLista as $tipo): ?>
            <?php
            $filas = $ingPorTipo[$tipo] ?? [];
            $t = $resumenTipos[$tipo] ?? [];
            ?>
            <section class="cm-comida<?= $filas ? '' : ' vacia' ?><?= $esHoy && $tipo === $sugerido ? ' sugerida' : '' ?>">
                <div class="cm-comida-cab">
                    <div style="display:grid;gap:2px;min-width:0">
                        <span class="t"><?= $nombreTipo($tipo) ?></span>
                        <?php if ($filas): ?>
                            <span class="m"><span class="k"><?= $fmt($t['kcal'] ?? 0) ?> kcal</span> · P <?= $fmt($t['proteina_g'] ?? 0) ?> · C <?= $fmt($t['carbohidratos_g'] ?? 0) ?> · G <?= $fmt($t['grasas_g'] ?? 0) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="der">
                        <a class="fv-btn fv-btn-sm" href="<?= site_url('comidas/diario/' . $f . '/' . $tipo) ?>"><i class="bi bi-plus-lg"></i>Añadir</a>
                    </div>
                </div>
                <?php foreach ($filas as $i): ?>
                    <button type="button" class="cm-ing" data-id="<?= (int) $i['id'] ?>" data-nombre="<?= esc($i['nombre']) ?>" data-cantidad="<?= $fmt($i['cantidad_gramos'] ?? 0, 2) ?>">
                        <span class="n"><?= esc($i['nombre']) ?></span>
                        <span class="k"><?= $fmt($i['macros']['kcal'] ?? 0) ?> <small class="t3">kcal</small></span>
                        <span class="c"><?= esc($i['cantidad_label']) ?></span>
                        <span class="m">P <?= $fmt($i['macros']['proteina_g'] ?? 0, 1) ?> · C <?= $fmt($i['macros']['carbohidratos_g'] ?? 0, 1) ?> · G <?= $fmt($i['macros']['grasas_g'] ?? 0, 1) ?></span>
                    </button>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</div>

<?= view('comidas/diario/_editar_ingesta') ?>

<script>
(() => {
    const ir = document.getElementById('cmIrFecha');
    ir.addEventListener('change', () => { if (ir.value) location.href = '<?= site_url('comidas/diario') ?>/' + ir.value; });

    const btn = document.getElementById('cmCopiarDia');
    const fecha = <?= json_encode($f) ?>;
    const tipos = <?= json_encode(array_values($tiposLista), JSON_UNESCAPED_UNICODE) ?>;
    const ing = <?= json_encode($ingPorTipo ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const resTipos = <?= json_encode($resumenTipos ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const resDia = <?= json_encode($resumen ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const fmt = (n, d = 0) => { let s = Number(n || 0).toFixed(d); return s.includes('.') ? s.replace(/\.?0+$/, '') : s; };
    const tit = t => t.charAt(0).toUpperCase() + t.slice(1);
    const texto = () => {
        const out = [`Día: ${fecha}`, ''];
        tipos.forEach(t => {
            const rows = Array.isArray(ing[t]) ? ing[t] : [];
            out.push(`== ${tit(t)} ==`);
            if (!rows.length) out.push('  (sin registros)');
            rows.forEach(r => out.push(`- ${r.nombre || '—'} · ${r.cantidad_label || '—'} · ${fmt(r.macros?.kcal)} kcal | P ${fmt(r.macros?.proteina_g, 1)} g · C ${fmt(r.macros?.carbohidratos_g, 1)} g · G ${fmt(r.macros?.grasas_g, 1)} g`));
            const x = resTipos[t] || {};
            out.push(`> Totales ${tit(t)}: ${fmt(x.kcal)} kcal | P ${fmt(x.proteina_g)} g · C ${fmt(x.carbohidratos_g)} g · G ${fmt(x.grasas_g)} g`, '');
        });
        out.push('== Totales del día ==', `${fmt(resDia.kcal)} kcal | P ${fmt(resDia.proteina_g)} g · C ${fmt(resDia.carbohidratos_g)} g · G ${fmt(resDia.grasas_g)} g`);
        return out.join('\n');
    };
    btn.addEventListener('click', async () => {
        const t = texto();
        try { await navigator.clipboard.writeText(t); }
        catch (e) { const ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
        const s = btn.querySelector('span'); s.textContent = '¡Copiado!';
        setTimeout(() => { s.textContent = 'Copiar día'; }, 1500);
    });

    document.addEventListener('click', ev => {
        const b = ev.target.closest('.cm-ing');
        if (b) window.cmEditarIngesta(b.dataset, () => location.reload());
    });
})();
</script>
<?= $this->endSection() ?>
