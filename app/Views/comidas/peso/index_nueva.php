<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$kg = static fn ($v, $d = 2) => number_format((float) $v, $d, ',', '');
$ultimo = $ultimos[0] ?? null;
$buscaHace = static function (int $dias) use ($ultimos, $ultimo) {
    if (!$ultimo) return null;
    $limite = date('Y-m-d', strtotime($ultimo['fecha'] . " -{$dias} days"));
    foreach ($ultimos as $r) if ($r['fecha'] <= $limite) return $r;
    return null;
};
$delta = static function (?array $antes) use ($ultimo, $kg) {
    if (!$ultimo || !$antes) return '';
    $d = (float) $ultimo['peso'] - (float) $antes['peso'];
    $cls = abs($d) < 0.05 ? 't3' : ($d < 0 ? 'e-ok' : 'e-warn');
    return '<span class="' . $cls . ' cm-num">' . ($d > 0 ? '+' : '') . $kg($d, 1) . '</span>';
};
$sub = [];
if ($ultimo) {
    $sub[] = '<span>Último <b class="cm-num t1">' . $kg($ultimo['peso']) . ' kg</b> · ' . esc(\CodeIgniter\I18n\Time::parse($ultimo['fecha'])->toLocalizedString('d MMM')) . '</span>';
    if ($h = $buscaHace(7)) $sub[] = '<span>7 días ' . $delta($h) . '</span>';
    if ($h = $buscaHace(30)) $sub[] = '<span>30 días ' . $delta($h) . '</span>';
}
$detalle = array_column(array_map(static fn ($r) => [
    'id' => (int) $r['id'], 'fecha' => $r['fecha'], 'peso' => $r['peso'], 'imc' => $r['imc'] ?? null,
    'grasa_corporal_pct' => $r['grasa_corporal_pct'] ?? null, 'grasa_visceral' => $r['grasa_visceral'] ?? null,
    'masa_muscular_kg' => $r['masa_muscular_kg'] ?? null, 'masa_osea_kg' => $r['masa_osea_kg'] ?? null,
    'metabolismo_basal_kcal' => $r['metabolismo_basal_kcal'] ?? null, 'edad_metabolica' => $r['edad_metabolica'] ?? null,
    'agua_corporal_pct' => $r['agua_corporal_pct'] ?? null, 'valoracion_fisica' => $r['valoracion_fisica'] ?? null,
], $ultimos ?? []), null, 'id');
?>
<div class="fv rc ancho">
    <?= view('comidas/_cabecera', [
        'activa'   => 'peso',
        'titulo'   => 'Peso',
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => '<button type="button" class="fv-btn" id="cmExportar"><i class="bi bi-clipboard"></i><span>Copiar 60 días</span></button><a class="fv-btn" href="' . site_url('comidas/peso/importar') . '"><i class="bi bi-upload"></i>Importar CSV</a>',
    ]) ?>

    <?php if (!empty($dup_id)): ?>
        <div class="fv-panel" style="flex-direction:row;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <span>Ya hay un registro el <b><?= esc($dup_fecha) ?></b> con <b class="cm-num"><?= esc($dup_peso) ?> kg</b>.</span>
            <a class="fv-btn fv-btn-sm fv-btn-peligro der" href="<?= site_url('comidas/peso/eliminar/' . (int) $dup_id) ?>" onclick="return confirm('¿Eliminar el registro existente de esa fecha?');"><i class="bi bi-trash"></i>Eliminar el existente</a>
        </div>
    <?php endif; ?>

    <div class="cm-peso">
        <form action="<?= site_url('comidas/peso/guardar') ?>" method="post" class="fv-panel rc-form">
            <?= csrf_field() ?>
            <div class="fv-ph"><h3>Registrar peso</h3></div>
            <label class="rc-campo"><span>Fecha</span><input type="date" name="fecha" value="<?= esc(old('fecha', $hoy)) ?>" class="fv-input" required></label>
            <label class="rc-campo"><span>Peso (kg)</span><input type="number" step="0.01" min="0" inputmode="decimal" name="peso" value="<?= esc(old('peso', '')) ?>" class="fv-input rc-grande cm-num" placeholder="<?= $ultimo ? esc(number_format((float) $ultimo['peso'], 2, '.', '')) : '82.65' ?>" required></label>
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
        </form>

        <section class="fv-panel cm-grafico-panel" id="cmGraficoPanel">
            <div class="fv-ph"><h3>Evolución</h3><span class="cuenta"><?= count($values ?? []) ?> pesadas</span>
                <button type="button" class="fv-btn fv-btn-sm der" id="cmAmpliar"><i class="bi bi-arrows-fullscreen"></i><span>Ampliar</span></button>
            </div>
            <div class="cm-grafico"><canvas id="cmGrafico"></canvas></div>
        </section>
    </div>

    <section class="rc-sec">
        <div class="fv-ph"><h3>Registros</h3><span class="cuenta"><?= count($ultimos) ?></span><span class="der t3" style="font-size:12px">toca uno para ver la báscula</span></div>
        <div class="rc-hist cm-pesos">
            <?php if (!$ultimos): ?><div class="rc-vacio">Sin registros todavía.</div><?php endif; ?>
            <?php foreach ($ultimos as $i => $r): ?>
                <?php
                $dia = $r['fecha'];
                $m = $macrosPorDia[$dia] ?? null;
                $prev = $ultimos[$i + 1] ?? null;
                $d = $prev ? (float) $r['peso'] - (float) $prev['peso'] : null;
                $tipos = $entrenosTiposPorDia[$dia] ?? [];
                ?>
                <div class="rc-h" data-id="<?= (int) $r['id'] ?>">
                    <span class="f"><?= esc(ucfirst(\CodeIgniter\I18n\Time::parse($dia)->toLocalizedString('EEE d MMM'))) ?></span>
                    <span><span class="p"><?= $kg($r['peso']) ?></span><?php if ($d !== null && abs($d) >= 0.05): ?><span class="d <?= $d < 0 ? 'e-ok' : 'e-warn' ?>"><?= ($d > 0 ? '+' : '') . $kg($d, 1) ?></span><?php endif; ?></span>
                    <span class="x">
                        <?php if ($tipos): ?><span class="e-info"><i class="bi bi-lightning-charge"></i> <?= esc(implode(' · ', $tipos)) ?></span><?php endif; ?>
                        <?php if ($m): ?><span class="cm-num"><?= (int) $m['kcal'] ?> kcal</span><?php endif; ?>
                        <?php if ($r['grasa_corporal_pct'] !== null): ?><span class="cm-num"><?= $kg($r['grasa_corporal_pct'], 1) ?>% gr</span><?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<textarea id="cmExport" hidden><?= esc($exportTexto60d ?? '') ?></textarea>

<dialog class="rc-dialogo" id="cmPesoDlg">
    <form method="dialog" class="fv">
        <h3 id="cmPesoDlgTit">Detalle</h3>
        <div class="cm-detalle">
            <div><div class="v" data-c="peso">—</div><div class="k">kg</div></div>
            <div><div class="v" data-c="imc">—</div><div class="k">IMC</div></div>
            <div><div class="v" data-c="grasa_corporal_pct" data-s="%">—</div><div class="k">grasa</div></div>
        </div>
        <dl class="fv-kv">
            <div><dt>Grasa visceral</dt><dd class="cm-num" data-c="grasa_visceral">—</dd></div>
            <div><dt>Masa muscular</dt><dd class="cm-num" data-c="masa_muscular_kg" data-s=" kg">—</dd></div>
            <div><dt>Masa ósea</dt><dd class="cm-num" data-c="masa_osea_kg" data-s=" kg">—</dd></div>
            <div><dt>Metabolismo basal</dt><dd class="cm-num" data-c="metabolismo_basal_kcal" data-s=" kcal">—</dd></div>
            <div><dt>Edad metabólica</dt><dd class="cm-num" data-c="edad_metabolica" data-s=" años">—</dd></div>
            <div><dt>Agua corporal</dt><dd class="cm-num" data-c="agua_corporal_pct" data-s="%">—</dd></div>
            <div><dt>Valoración física</dt><dd class="cm-num" data-c="valoracion_fisica">—</dd></div>
        </dl>
        <div class="rc-dialogo-pie">
            <a href="#" class="fv-btn fv-btn-peligro" id="cmPesoBorrar" style="margin-right:auto" onclick="return confirm('¿Eliminar este registro?');"><i class="bi bi-trash"></i>Eliminar</a>
            <button type="submit" class="fv-btn">Cerrar</button>
        </div>
    </form>
</dialog>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const DATOS = <?= json_encode($detalle) ?>;
    const dlg = document.getElementById('cmPesoDlg');
    document.querySelector('.cm-pesos').addEventListener('click', e => {
        const f = e.target.closest('.rc-h[data-id]');
        const d = f && DATOS[f.dataset.id];
        if (!d) return;
        document.getElementById('cmPesoDlgTit').textContent = new Date(d.fecha + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        dlg.querySelectorAll('[data-c]').forEach(el => { const v = d[el.dataset.c]; el.textContent = (v === null || v === undefined || v === '') ? '—' : v + (el.dataset.s || ''); });
        document.getElementById('cmPesoBorrar').href = '<?= site_url('comidas/peso/eliminar') ?>/' + d.id;
        dlg.showModal();
    });

    const btnExp = document.getElementById('cmExportar');
    btnExp.addEventListener('click', async () => {
        const t = document.getElementById('cmExport').value;
        try { await navigator.clipboard.writeText(t); }
        catch (e) { const ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }
        const s = btnExp.querySelector('span'); s.textContent = '¡Copiado!';
        setTimeout(() => { s.textContent = 'Copiar 60 días'; }, 1600);
    });

    const labels = <?= json_encode($labels ?? []) ?>;
    const values = <?= json_encode($values ?? []) ?>;
    const grasa = <?= json_encode($grasaValues ?? []) ?>;
    const agua = <?= json_encode($aguaValues ?? []) ?>;
    const flags = <?= json_encode($flagsEntreno ?? []) ?>;
    if (!values.length || typeof Chart === 'undefined') return;
    const sma = (arr, w = 7) => { const out = Array(arr.length).fill(null); let s = 0, q = []; arr.forEach((v, i) => { v = Number(v); if (!Number.isFinite(v)) { q = []; s = 0; return; } q.push(v); s += v; if (q.length > w) s -= q.shift(); if (q.length === w) out[i] = +(s / w).toFixed(2); }); return out; };
    const nums = values.filter(Number.isFinite).slice().sort((a, b) => a - b);
    const med = nums.length ? (nums.length % 2 ? nums[nums.length >> 1] : (nums[nums.length / 2 - 1] + nums[nums.length / 2]) / 2) : null;
    const vmin = Math.min(...values), vmax = Math.max(...values), pad = Math.max(0.5, (vmax - vmin) * 0.05);
    Chart.defaults.color = 'rgba(255,255,255,.45)';
    Chart.defaults.borderColor = 'rgba(255,255,255,.06)';
    Chart.defaults.font.family = '"Geist", ui-sans-serif, system-ui, sans-serif';
    Chart.defaults.font.size = 11;
    const graf = new Chart(document.getElementById('cmGrafico'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Peso', data: values, borderColor: '#8e8cff', backgroundColor: 'rgba(142,140,255,.10)', fill: true, borderWidth: 2, tension: .2, pointRadius: 2, order: 3 },
                { label: 'Media 7 días', data: sma(values), borderColor: '#3ecf8e', borderWidth: 2.5, tension: .25, pointRadius: 0, spanGaps: true, order: 2 },
                ...(med !== null ? [{ label: `Mediana ${med.toFixed(2)}`, data: values.map(() => +med.toFixed(2)), borderColor: 'rgba(255,255,255,.3)', borderDash: [6, 6], borderWidth: 1.5, pointRadius: 0, order: 1 }] : []),
                { label: 'Entreno', data: values.map((v, i) => flags[i] ? v : null), showLine: false, pointRadius: 5, pointHoverRadius: 7, pointBackgroundColor: '#f5a524', pointBorderColor: '#f5a524', order: 4 },
                { label: '% grasa', data: grasa, yAxisID: 'y1', borderColor: '#ff6a3d', borderWidth: 1.5, tension: .2, pointRadius: 1.5, spanGaps: true, hidden: true, order: 5 },
                { label: '% agua', data: agua, yAxisID: 'y1', borderColor: '#5ec8ff', borderWidth: 1.5, tension: .2, pointRadius: 1.5, spanGaps: true, hidden: true, order: 6 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                y: { min: vmin - pad, max: vmax + pad, ticks: { padding: 6 } },
                y1: { position: 'right', display: 'auto', ticks: { callback: v => v + '%' }, grid: { drawOnChartArea: false } },
                x: { ticks: { maxRotation: 0, autoSkipPadding: 12 }, grid: { display: false } },
            },
            plugins: { legend: { labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } }, tooltip: { filter: it => it.raw !== null } },
        },
    });
    const panel = document.getElementById('cmGraficoPanel'), amp = document.getElementById('cmAmpliar');
    const poner = on => { panel.classList.toggle('lleno', on); document.body.style.overflow = on ? 'hidden' : ''; amp.querySelector('span').textContent = on ? 'Cerrar' : 'Ampliar'; amp.querySelector('i').className = 'bi ' + (on ? 'bi-fullscreen-exit' : 'bi-arrows-fullscreen'); graf.resize(); };
    amp.addEventListener('click', () => poner(!panel.classList.contains('lleno')));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && panel.classList.contains('lleno')) poner(false); });
})();
</script>
<?= $this->endSection() ?>
