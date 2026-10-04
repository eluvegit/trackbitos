<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php $UMBRAL_CLARO = 0.8; // a partir de aquí la sugerencia viene marcada ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-link-45deg text-primary"></i>
    <a href="<?= site_url('gimnasio/catalogo') ?>" class="text-decoration-none text-muted fw-normal">Catálogo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Vincular mis ejercicios</strong>
</h5>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<?php if (!$sugerencias): ?>
    <p class="text-muted">No hay sugerencias pendientes: o ya está todo vinculado o el catálogo no tiene equivalentes.</p>
<?php else: ?>

<p class="small text-muted mb-2">
    Sugerencias para tus ejercicios aún sin vincular. Las coincidencias claras vienen marcadas; revisa y guarda.
    Vincular solo añade la animación e instrucciones, no cambia nombres ni historial.
</p>

<div class="d-flex flex-wrap gap-2 mb-3">
    <select id="vlFiltro" class="form-select form-select-sm w-auto">
        <option value="">Todas (<?= count($sugerencias) ?>)</option>
        <option value="claras">Solo coincidencias claras</option>
        <option value="dudosas">Solo dudosas</option>
    </select>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="vlNinguno">Desmarcar todo</button>
    <span class="small text-muted align-self-center" id="vlMarcados"></span>
</div>

<form action="<?= site_url('gimnasio/catalogo/vincular-lote') ?>" method="post">
    <?= csrf_field() ?>

    <?php foreach ($sugerencias as $s): ?>
        <?php
        $e = $s['ejercicio'];
        $mejor = $s['candidatos'][0];
        $clara = $mejor['score'] >= $UMBRAL_CLARO;
        ?>
        <div class="vl-row" data-clara="<?= $clara ? 1 : 0 ?>">
            <div class="vl-mio">
                <strong><?= esc($e['nombre']) ?></strong>
                <span class="text-muted small"><?= esc($grupos[$e['grupo_muscular']] ?? $e['grupo_muscular']) ?></span>
            </div>
            <div class="vl-opciones">
                <?php foreach ($s['candidatos'] as $i => $c): ?>
                    <label class="vl-op">
                        <input type="radio" name="vincular[<?= $e['id'] ?>]" value="<?= $c['id'] ?>"
                               <?= ($i === 0 && $clara) ? 'checked' : '' ?>>
                        <img src="<?= esc(gim_catalogo_media($c['imagen'])) ?>" loading="lazy" alt="" width="44" height="44">
                        <span><?= esc(ucfirst($c['nombre'])) ?></span>
                        <a href="<?= site_url('gimnasio/catalogo/' . $c['id']) ?>" target="_blank" class="vl-ver" title="Ver ficha" onclick="event.stopPropagation()">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </label>
                <?php endforeach; ?>
                <label class="vl-op vl-op-ninguno">
                    <input type="radio" name="vincular[<?= $e['id'] ?>]" value="" <?= $clara ? '' : 'checked' ?>>
                    <span>Ninguno</span>
                </label>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="vl-guardar">
        <button class="btn btn-primary"><i class="bi bi-check2-all"></i> Guardar vínculos</button>
    </div>
</form>

<style>
.vl-row { border: 1px solid var(--bs-border-color); border-radius: 12px; padding: 8px 10px; margin-bottom: 8px; background: var(--bs-body-bg); }
.vl-mio { display: flex; gap: 8px; align-items: baseline; margin-bottom: 6px; }
.vl-opciones { display: flex; flex-wrap: wrap; gap: 6px; }
.vl-op {
    display: flex; align-items: center; gap: 6px; cursor: pointer;
    padding: 4px 8px 4px 6px; border: 1px solid var(--bs-border-color); border-radius: 10px;
    font-size: .82rem; background: var(--bs-tertiary-bg);
}
.vl-op:has(input:checked) { border-color: var(--bs-primary); background: var(--bs-primary-bg-subtle); }
.vl-op img { width: 44px; height: 44px; border-radius: 6px; background: #fff; }
.vl-op-ninguno { color: var(--bs-secondary-color); }
.vl-ver { color: var(--bs-secondary-color); font-size: .75rem; }
.vl-guardar { position: sticky; bottom: 0; padding: 10px 0; background: var(--bs-body-bg); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filas = document.querySelectorAll('.vl-row');
    const contador = document.getElementById('vlMarcados');

    function cuenta() {
        const n = document.querySelectorAll('.vl-op:not(.vl-op-ninguno) input:checked').length;
        contador.textContent = n + ' marcado' + (n === 1 ? '' : 's');
    }

    document.getElementById('vlFiltro').addEventListener('input', e => {
        filas.forEach(f => {
            const v = e.target.value;
            f.style.display = !v || (v === 'claras') === (f.dataset.clara === '1') ? '' : 'none';
        });
    });
    document.getElementById('vlNinguno').addEventListener('click', () => {
        document.querySelectorAll('.vl-op-ninguno input').forEach(i => i.checked = true);
        cuenta();
    });
    document.addEventListener('change', cuenta);
    cuenta();
});
</script>

<?php endif; ?>

<?= $this->endSection() ?>
