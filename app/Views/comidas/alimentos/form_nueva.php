<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$id = (int) ($row['id'] ?? 0);
$nombre = old('nombre', $row['nombre'] ?? '');
$marca = old('marca', $row['marca'] ?? '');
$val = static fn (string $c) => esc(old($c, $row[$c] ?? '0'));
$principales = [['kcal', 'kcal'], ['proteina_g', 'Proteína (g)'], ['carbohidratos_g', 'Carbohidratos (g)'], ['grasas_g', 'Grasas (g)']];
$bloques = [
    'Más macros' => [['azucares_g', 'Azúcares (g)'], ['fibra_g', 'Fibra (g)'], ['grasas_saturadas_g', 'Saturadas (g)'], ['omega3_mg', 'Omega-3 (mg)'], ['omega6_mg', 'Omega-6 (mg)'], ['sodio_mg', 'Sodio (mg)']],
    'Minerales'  => [['calcio_mg', 'Calcio (mg)'], ['hierro_mg', 'Hierro (mg)'], ['magnesio_mg', 'Magnesio (mg)'], ['fosforo_mg', 'Fósforo (mg)'], ['potasio_mg', 'Potasio (mg)'], ['zinc_mg', 'Zinc (mg)'], ['selenio_ug', 'Selenio (µg)'], ['cobre_mg', 'Cobre (mg)'], ['manganeso_mg', 'Manganeso (mg)'], ['yodo_ug', 'Yodo (µg)']],
    'Vitaminas'  => [['vitamina_a_rae_ug', 'Vit. A (µg RAE)'], ['vitamina_c_mg', 'Vit. C (mg)'], ['vitamina_d_ug', 'Vit. D (µg)'], ['vitamina_e_mg', 'Vit. E (mg)'], ['vitamina_k_ug', 'Vit. K (µg)']],
];
$rellenos = static fn (array $campos) => count(array_filter($campos, fn ($c) => (float) old($c[0], $row[$c[0]] ?? 0) != 0));
$acciones = $id
    ? '<button type="submit" form="cmBorrar" class="fv-btn fv-btn-peligro"><i class="bi bi-trash"></i>Eliminar</button>'
    : '';
?>
<div class="fv rc">
    <?= view('comidas/_cabecera', [
        'activa'   => 'alimentos',
        'migas'    => ['Alimentos' => site_url('comidas/alimentos'), ($id ? $nombre : 'Nuevo') => null],
        'titulo'   => $id ? esc($nombre) : 'Nuevo alimento',
        'sub'      => $id ? ($marca !== '' ? '<span>' . esc($marca) . '</span><span class="fv-dot"></span>' : '') . '<span>valores por 100 g</span>' : '<span>Los valores van siempre por 100 g</span>',
        'acciones' => $acciones,
    ]) ?>

    <?= form_open($action ?? current_url(), ['id' => 'cmAlimento', 'class' => 'rc-form']) ?>
        <?= csrf_field() ?>
        <details class="cm-plegable"<?= $id ? '' : ' open' ?>>
            <summary><i class="bi bi-tag t3"></i>Nombre y marca<?php if ($id): ?><small><?= esc($nombre) ?><?= $marca !== '' ? ' · ' . esc($marca) : '' ?></small><?php endif; ?><i class="bi bi-chevron-right ch"></i></summary>
            <div class="cuerpo">
                <div class="cm-campos dos">
                    <label class="rc-campo"><span>Nombre</span><input name="nombre" class="fv-input" required value="<?= esc($nombre) ?>"></label>
                    <label class="rc-campo"><span>Marca</span><input name="marca" class="fv-input" value="<?= esc($marca) ?>"></label>
                </div>
                <label class="rc-campo"><span>Descripción</span><textarea name="descripcion" class="fv-input" rows="2"><?= esc(old('descripcion', $row['descripcion'] ?? '')) ?></textarea></label>
            </div>
        </details>

        <section class="fv-panel">
            <div class="fv-ph"><h3>Macros principales</h3><span class="der t3" style="font-size:12px">por 100 g</span></div>
            <div class="cm-campos cm-principal">
                <?php foreach ($principales as [$c, $l]): ?>
                    <label class="rc-campo"><span><?= $l ?></span><input type="number" step="0.01" inputmode="decimal" name="<?= $c ?>" class="fv-input" value="<?= $val($c) ?>"></label>
                <?php endforeach; ?>
            </div>
        </section>

        <?php foreach ($bloques as $titulo => $campos): ?>
            <?php $n = $rellenos($campos); ?>
            <details class="cm-plegable">
                <summary><?= $titulo ?><small><?= $n ? $n . ' de ' . count($campos) . ' con valor' : 'vacío' ?></small><i class="bi bi-chevron-right ch"></i></summary>
                <div class="cuerpo">
                    <div class="cm-campos">
                        <?php foreach ($campos as [$c, $l]): ?>
                            <label class="rc-campo"><span><?= $l ?></span><input type="number" step="0.01" inputmode="decimal" name="<?= $c ?>" class="fv-input" value="<?= $val($c) ?>"></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </details>
        <?php endforeach; ?>

        <?php if ($id): ?>
            <details class="cm-plegable cm-pegar">
                <summary><i class="bi bi-clipboard-plus t3"></i>Pegado rápido<small>pega la tabla nutricional y elige qué aplicar</small><i class="bi bi-chevron-right ch"></i></summary>
                <div class="cuerpo">
                    <textarea name="bulk" class="fv-input" placeholder="Proteína 11.29 g&#10;Sodio 544.50 mg&#10;Ácido octadecatrienoico 8.49 g…"></textarea>
                    <p class="rc-ayuda">Acepta g, mg, µg y kcal; ignora los %. Omega-3/6 y saturadas se calculan solas.</p>
                    <div class="rc-pie">
                        <button type="button" class="fv-btn" id="cmSimular"><i class="bi bi-eye"></i><span>Simular</span></button>
                        <button type="button" class="fv-btn fv-btn-pri" id="cmAplicar" disabled><i class="bi bi-check2-all"></i>Aplicar y guardar</button>
                    </div>
                    <div class="cm-cambios" id="cmCambios" hidden>
                        <table class="cm-tabla">
                            <thead><tr><th><input type="checkbox" id="cmTodos" checked></th><th style="text-align:left">Campo</th><th>Actual</th><th>Nuevo</th></tr></thead>
                            <tbody id="cmCambiosFilas"></tbody>
                        </table>
                    </div>
                </div>
            </details>
        <?php endif; ?>

        <div class="cm-pie-fijo">
            <button class="fv-btn fv-btn-pri"><i class="bi bi-check-lg"></i>Guardar</button>
            <a href="<?= site_url('comidas/alimentos') ?>" class="fv-btn">Cancelar</a>
        </div>
    <?= form_close() ?>

    <?php if ($id): ?>
        <?= view('comidas/_proporciones_nueva', ['alimentoId' => $id]) ?>
        <form id="cmBorrar" action="<?= site_url('comidas/alimentos/delete/' . $id) ?>" method="post" onsubmit="return confirm('¿Eliminar «<?= esc($nombre, 'js') ?>»?');"><?= csrf_field() ?></form>
    <?php endif; ?>
</div>

<?php if ($id): ?>
<script>
(() => {
    const form = document.getElementById('cmAlimento');
    const btnSim = document.getElementById('cmSimular'), btnApl = document.getElementById('cmAplicar');
    const caja = document.getElementById('cmCambios'), filas = document.getElementById('cmCambiosFilas'), todos = document.getElementById('cmTodos');
    const csrf = form.querySelector('input[name="<?= csrf_token() ?>"]');
    let parsed = {};
    btnSim.addEventListener('click', async () => {
        const fd = new URLSearchParams({ id: '<?= $id ?>', bulk: form.querySelector('[name="bulk"]').value, url: '' });
        if (csrf) fd.set(csrf.name, csrf.value);
        const s = btnSim.querySelector('span'); s.textContent = 'Simulando…'; btnSim.disabled = true;
        try {
            const j = await (await fetch('<?= site_url('comidas/alimentos/preview') ?>', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, credentials: 'same-origin', body: fd })).json();
            parsed = j.parsed || {};
            const ch = j.changes || [];
            filas.innerHTML = ch.length
                ? ch.map(c => `<tr><td><input type="checkbox" class="cm-ch" value="${c.field}" checked></td><td style="text-align:left;font-family:inherit">${c.label}</td><td>${c.old}</td><td style="color:var(--fv-text)">${c.new}</td></tr>`).join('')
                : `<tr><td colspan="4" class="t3">${j.error || 'No hay cambios.'}</td></tr>`;
            btnApl.disabled = !ch.length; todos.checked = true; caja.hidden = false;
        } catch (e) {
            filas.innerHTML = '<tr><td colspan="4" class="e-bad">Error en la simulación.</td></tr>'; caja.hidden = false; btnApl.disabled = true;
        } finally { s.textContent = 'Simular'; btnSim.disabled = false; }
    });
    todos.addEventListener('change', () => filas.querySelectorAll('.cm-ch').forEach(c => c.checked = todos.checked));
    btnApl.addEventListener('click', () => {
        form.querySelectorAll('input[name="apply_fields[]"]').forEach(el => el.remove());
        filas.querySelectorAll('.cm-ch:checked').forEach(ch => {
            const campo = form.querySelector(`[name="${ch.value}"]`);
            if (campo) campo.value = parsed[ch.value];
            const h = document.createElement('input'); h.type = 'hidden'; h.name = 'apply_fields[]'; h.value = ch.value; form.appendChild(h);
        });
        form.submit();
    });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
