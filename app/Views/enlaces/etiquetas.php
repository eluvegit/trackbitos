<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <h5 class="mb-0">
            Etiquetas
            <span class="text-muted small">
                <?= count($etiquetas) ?> · <?= (int) $sinUso ?> sin uso · <?= (int) $unaVez ?> usadas una vez
            </span>
        </h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/categorias') ?>">Categorías</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces') ?>">Volver</a>
        </div>
    </div>

    <?php if (session()->getFlashdata('mensaje')): ?>
        <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('mensaje')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-warning py-2"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <form class="d-flex gap-2 flex-grow-1" method="post" action="<?= site_url('enlaces/etiquetas/guardar') ?>">
            <input class="form-control form-control-sm" name="nombre" placeholder="Nueva etiqueta" autocomplete="off">
            <button class="btn btn-primary btn-sm">Agregar</button>
        </form>
        <?php if ($sinUso > 0): ?>
            <form method="post" action="<?= site_url('enlaces/etiquetas/borrar-sin-uso') ?>"
                onsubmit="return confirm('¿Borrar las <?= (int) $sinUso ?> etiquetas que no usa ningún enlace?');">
                <button class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i> Borrar las <?= (int) $sinUso ?> sin uso
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Filtro -->
    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
        <div class="etq-seg" id="etqSeg">
            <button type="button" data-f="all" class="active">Todas</button>
            <button type="button" data-f="0">Sin uso</button>
            <button type="button" data-f="1">Usadas 1 vez</button>
        </div>
        <input type="search" class="form-control form-control-sm" id="etqBuscar" placeholder="Filtrar por nombre…" style="max-width: 240px;">
        <span class="text-muted small" id="etqCount"></span>
    </div>

    <ul class="list-group" id="etqList">
        <?php foreach ($etiquetas as $t): ?>
            <li class="list-group-item d-flex align-items-center gap-2 etq-row"
                data-uso="<?= (int) $t['uso'] ?>" data-nombre="<?= esc(mb_strtolower($t['nombre']), 'attr') ?>">
                <input type="checkbox" class="form-check-input mt-0 etq-check" value="<?= (int) $t['id'] ?>"
                    data-nombre="<?= esc($t['nombre'], 'attr') ?>">
                <form method="post" action="<?= site_url('enlaces/etiquetas/renombrar/' . (int) $t['id']) ?>"
                    class="d-flex gap-1 flex-grow-1 etq-rename">
                    <input type="text" name="nombre" value="<?= esc($t['nombre'], 'attr') ?>"
                        class="form-control form-control-sm border-0 bg-transparent px-1" maxlength="120">
                    <button class="btn btn-sm btn-outline-primary py-0 px-1 etq-save d-none" title="Guardar nombre">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>
                <span class="badge <?= $t['uso'] === 0 ? 'text-bg-secondary' : 'text-bg-light border' ?>"><?= (int) $t['uso'] ?></span>
                <a class="btn btn-sm btn-outline-danger py-0 px-1" title="Borrar"
                    href="<?= site_url('enlaces/etiquetas/borrar/' . (int) $t['id']) ?>"
                    onclick="return confirm('¿Borrar «<?= esc($t['nombre'], 'attr') ?>»?<?= $t['uso'] > 0 ? ' Se quitará de ' . (int) $t['uso'] . ' enlace(s).' : '' ?>');">
                    <i class="bi bi-trash"></i>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <p class="text-muted small d-none mt-2" id="etqVacio">Ninguna etiqueta coincide.</p>

    <!-- Barra de fusión (aparece al marcar 2+) -->
    <form method="post" action="<?= site_url('enlaces/etiquetas/fusionar') ?>" class="etq-merge d-none" id="etqMerge">
        <span><i class="bi bi-signpost-split"></i> Fusionar <strong id="etqMergeN">0</strong> en:</span>
        <select name="destino" class="form-select form-select-sm" id="etqMergeDestino" style="max-width: 220px;"></select>
        <button class="btn btn-sm btn-primary" onclick="return confirm('Fusionar las etiquetas seleccionadas en una sola?');">
            Fusionar
        </button>
        <button type="button" class="btn btn-sm btn-link text-decoration-none" id="etqMergeCancel">Cancelar</button>
        <span id="etqMergeInputs"></span>
    </form>
</div>

<style>
    .etq-seg {
        display: inline-flex;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 999px;
        padding: 3px;
        gap: 3px;
    }
    .etq-seg button {
        border: none; background: transparent; color: var(--bs-emphasis-color);
        font-size: .8rem; padding: .3rem .7rem; border-radius: 999px; cursor: pointer;
    }
    .etq-seg button.active { background: #7c3aed; color: #fff; }
    .etq-row.d-none { display: none; }
    .etq-rename input:focus { background: var(--bs-body-bg) !important; }
    .etq-merge {
        position: sticky;
        bottom: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        padding: 10px 14px;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        background: var(--bs-tertiary-bg);
        box-shadow: 0 -4px 16px rgba(0,0,0,.08);
    }
</style>

<script>
(() => {
    const list = document.getElementById('etqList');
    const rows = Array.from(list.querySelectorAll('.etq-row'));
    const seg = document.getElementById('etqSeg');
    const buscar = document.getElementById('etqBuscar');
    const count = document.getElementById('etqCount');
    const vacio = document.getElementById('etqVacio');
    let filtro = 'all';

    function aplicar() {
        const q = buscar.value.trim().toLowerCase();
        let visibles = 0;
        rows.forEach(row => {
            const uso = row.dataset.uso;
            let ok = filtro === 'all' || uso === filtro;
            if (ok && q) ok = row.dataset.nombre.includes(q);
            row.classList.toggle('d-none', !ok);
            if (ok) visibles++;
        });
        count.textContent = visibles + ' / ' + rows.length;
        vacio.classList.toggle('d-none', visibles > 0);
    }

    seg.addEventListener('click', e => {
        const b = e.target.closest('button');
        if (!b) return;
        seg.querySelectorAll('button').forEach(x => x.classList.remove('active'));
        b.classList.add('active');
        filtro = b.dataset.f;
        aplicar();
    });
    buscar.addEventListener('input', aplicar);
    aplicar();

    // ---- Renombrar: mostrar el botón guardar solo si cambia el valor ----
    list.addEventListener('input', e => {
        const inp = e.target.closest('.etq-rename input');
        if (!inp) return;
        const save = inp.closest('.etq-rename').querySelector('.etq-save');
        save.classList.toggle('d-none', inp.value.trim() === inp.defaultValue.trim() || inp.value.trim() === '');
    });
    list.addEventListener('keydown', e => {
        if (e.key === 'Escape' && e.target.closest('.etq-rename input')) {
            e.target.value = e.target.defaultValue;
            e.target.closest('.etq-rename').querySelector('.etq-save').classList.add('d-none');
            e.target.blur();
        }
    });

    // ---- Selección para fusionar ----
    const merge = document.getElementById('etqMerge');
    const mergeN = document.getElementById('etqMergeN');
    const mergeSel = document.getElementById('etqMergeDestino');
    const mergeInputs = document.getElementById('etqMergeInputs');

    function checked() {
        return Array.from(list.querySelectorAll('.etq-check:checked'));
    }
    function refrescarMerge() {
        const sel = checked();
        if (sel.length < 2) {
            merge.classList.add('d-none');
            return;
        }
        merge.classList.remove('d-none');
        mergeN.textContent = sel.length;
        const prev = mergeSel.value;
        mergeSel.innerHTML = sel.map(c =>
            `<option value="${c.value}">${c.dataset.nombre.replace(/</g, '&lt;')}</option>`
        ).join('');
        if (sel.some(c => c.value === prev)) mergeSel.value = prev;
        mergeInputs.innerHTML = sel.map(c => `<input type="hidden" name="origen[]" value="${c.value}">`).join('');
    }
    list.addEventListener('change', e => {
        if (e.target.classList.contains('etq-check')) refrescarMerge();
    });
    document.getElementById('etqMergeCancel').addEventListener('click', () => {
        checked().forEach(c => { c.checked = false; });
        refrescarMerge();
    });
})();
</script>
<?php $this->endSection(); ?>
