<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
$nombres = array_column($alimentos_todos, 'nombre', 'id');
$plural = static fn (int $x, string $s, string $p) => $x . ' ' . ($x === 1 ? $s : $p);
$nC = count($alimentos_controlados);
$mal = count(array_filter($alimentos_controlados, fn ($c) => $c['estado'] !== 'verde'));
?>
<div class="fv rc ancho">
    <?= view('comidas/_cabecera', [
        'activa'   => 'control',
        'titulo'   => 'Frecuencia',
        'sub'      => '<span>' . $plural($nC, 'alimento controlado', 'alimentos controlados') . '</span>' . ($nC ? '<span class="fv-dot"></span>' . ($mal ? '<span class="fv-estado e-bad">' . $mal . ' fuera de rango</span>' : '<span class="fv-estado e-ok">Todo en rango</span>') : ''),
        'acciones' => '<button type="button" class="fv-btn fv-btn-pri" data-controlar=""><i class="bi bi-plus-lg"></i>Controlar alimento</button>',
    ]) ?>

    <?php if ($alimentos_controlados): ?>
        <div class="cm-ctrl">
            <?php foreach ($alimentos_controlados as $aid => $c): ?>
                <?php
                $v = (int) $c['veces_en_periodo'];
                $ok = $c['estado'] === 'verde';
                $pct = $c['max_veces'] > 0 ? min(100, $v / $c['max_veces'] * 100) : 0;
                [$cls, $txt] = $ok ? ['e-ok', 'En rango'] : ($v < $c['min_veces'] ? ['e-warn', 'Te falta'] : ['e-bad', 'Te pasas']);
                ?>
                <div class="cm-ctrl-item<?= $ok ? '' : ' mal' ?>">
                    <div class="cab">
                        <span class="n"><?= esc($nombres[$aid] ?? '#' . $aid) ?></span>
                        <div class="rc-btns">
                            <a class="fv-ibtn" href="<?= site_url('comidas/alimentos-control/edit/' . $c['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                            <a class="fv-ibtn" href="<?= site_url('comidas/alimentos-control/delete/' . $c['id']) ?>" title="Dejar de controlar" onclick="return confirm('¿Dejar de controlar este alimento?');"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                    <div style="display:flex;align-items:baseline;gap:10px">
                        <span class="cm-veces"><?= $v ?> <small>/ <?= (int) $c['min_veces'] ?>–<?= (int) $c['max_veces'] ?></small></span>
                        <span class="fv-estado <?= $cls ?>" style="margin-left:auto"><?= $txt ?></span>
                    </div>
                    <div class="rc-barra <?= $ok ? '' : ($v < $c['min_veces'] ? 'warn' : 'bad') ?>" style="margin-top:0"><i style="width:<?= round($pct) ?>%"></i></div>
                    <div class="s"><span>en <?= $plural((int) $c['periodo_dias'], 'día', 'días') ?></span><span><?= $c['dias_desde_ultima'] !== null ? 'última hace ' . $plural((int) $c['dias_desde_ultima'], 'día', 'días') : 'nunca' ?></span></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="fv-panel rc-vacio">No controlas ningún alimento. Elige uno para saber cuántas veces lo comes.</div>
    <?php endif; ?>

    <?php if (!empty($ingredientes_no_controlados)): ?>
        <section class="rc-sec">
            <div class="fv-ph"><h3>Otros alimentos</h3><span class="cuenta"><?= count($ingredientes_no_controlados) ?></span>
                <div class="der cm-seg" style="padding:3px">
                    <button type="button" class="on" data-orden="nombre">A–Z</button>
                    <button type="button" data-orden="dias">Por días</button>
                </div>
            </div>
            <div class="rc-lista cm-lista" id="cmOtros">
                <?php foreach ($ingredientes_no_controlados as $ing): ?>
                    <div class="rc-fila" style="grid-template-columns:minmax(0,1fr) auto auto" data-nombre="<?= esc($ing['nombre']) ?>" data-dias="<?= $ing['dias_desde'] ?? 99999 ?>">
                        <span class="rc-txt"><span class="rc-t"><span class="t" style="font-weight:500"><?= esc($ing['nombre']) ?></span></span></span>
                        <span class="cm-k"><?= $ing['dias_desde'] !== null ? $ing['dias_desde'] . ' <small>días</small>' : '<small>nunca</small>' ?></span>
                        <div class="rc-btns"><button type="button" class="fv-ibtn" data-controlar="<?= (int) $ing['id'] ?>" title="Controlar"><i class="bi bi-plus-lg"></i></button></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<dialog class="rc-dialogo" id="cmCtrlDlg">
    <form action="<?= site_url('comidas/alimentos-control/add') ?>" method="post" class="fv">
        <?= csrf_field() ?>
        <h3>Controlar alimento</h3>
        <label class="rc-campo"><span>Alimento</span>
            <select name="alimento_id" id="cmCtrlAlimento" class="fv-input rc-sel" required>
                <?php foreach ($alimentos_todos as $al): ?>
                    <?php if (isset($alimentos_controlados[$al['id']])) continue; ?>
                    <option value="<?= $al['id'] ?>"><?= esc($al['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="rc-campo"><span>Periodo (días)</span><input type="number" name="periodo_dias" class="fv-input cm-num" min="1" value="7" required></label>
        <div class="rc-filas">
            <label class="rc-campo"><span>Mínimo de veces</span><input type="number" name="min_veces" class="fv-input cm-num" min="0" value="0" required></label>
            <label class="rc-campo"><span>Máximo de veces</span><input type="number" name="max_veces" class="fv-input cm-num" min="1" value="1" required></label>
        </div>
        <div class="rc-dialogo-pie">
            <button type="button" class="fv-btn" onclick="this.closest('dialog').close()">Cancelar</button>
            <button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i>Añadir</button>
        </div>
    </form>
</dialog>
<script>
(() => {
    const dlg = document.getElementById('cmCtrlDlg'), sel = document.getElementById('cmCtrlAlimento');
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-controlar]');
        if (!b) return;
        if (b.dataset.controlar) sel.value = b.dataset.controlar;
        dlg.showModal();
    });
    const lista = document.getElementById('cmOtros');
    document.querySelectorAll('[data-orden]').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('[data-orden]').forEach(x => x.classList.toggle('on', x === b));
        const porDias = b.dataset.orden === 'dias';
        [...lista.children].sort((a, z) => porDias ? (+a.dataset.dias - +z.dataset.dias) : a.dataset.nombre.localeCompare(z.dataset.nombre, 'es')).forEach(f => lista.appendChild(f));
    }));
})();
</script>
<?= $this->endSection() ?>
