<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php helper('gimnasio'); $UMBRAL_CLARO = 0.8; // a partir de aquí la sugerencia viene marcada ?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'catalogo',
        'migas' => ['Catálogo' => site_url('gimnasio/catalogo'), 'Vincular' => null],
        'titulo' => 'Vincular mis ejercicios',
        'sub' => '<span>Sugerencias para tus ejercicios sin vincular. Solo añade animación e instrucciones; no cambia nombres ni historial.</span>']) ?>

    <?php if (!$sugerencias): ?>
        <div class="gm-vacio"><i class="bi bi-check2-all"></i>No hay sugerencias pendientes: o ya está todo vinculado o el catálogo no tiene equivalentes.</div>
    <?php else: ?>
        <div class="gm-chips" id="vlFiltro">
            <button type="button" class="gm-chip on" data-f="">Todas <small><?= count($sugerencias) ?></small></button>
            <button type="button" class="gm-chip" data-f="claras" style="--c:var(--fv-ok)">Coincidencias claras</button>
            <button type="button" class="gm-chip" data-f="dudosas" style="--c:var(--fv-warn)">Dudosas</button>
            <button type="button" class="gm-chip" id="vlNinguno"><i class="bi bi-x-lg"></i> Desmarcar todo</button>
        </div>

        <form action="<?= site_url('gimnasio/catalogo/vincular-lote') ?>" method="post" class="fv-panel" style="padding-top:4px;padding-bottom:0">
            <?php foreach ($sugerencias as $s): ?>
                <?php $e = $s['ejercicio']; $clara = $s['candidatos'][0]['score'] >= $UMBRAL_CLARO; ?>
                <div class="gm-vl" data-clara="<?= $clara ? 1 : 0 ?>">
                    <div><div style="font-weight:500"><?= esc($e['nombre']) ?></div><div class="gm-ayuda"><?= esc($grupos[$e['grupo_muscular']] ?? $e['grupo_muscular']) ?></div></div>
                    <div class="gm-opciones">
                        <?php foreach ($s['candidatos'] as $i => $c): ?>
                            <label class="gm-op">
                                <input type="radio" name="vincular[<?= $e['id'] ?>]" value="<?= $c['id'] ?>" <?= ($i === 0 && $clara) ? 'checked' : '' ?>>
                                <img src="<?= esc(gim_catalogo_media($c['imagen'])) ?>" alt="" loading="lazy">
                                <span><?= esc(ucfirst($c['nombre'])) ?></span>
                                <a href="<?= site_url('gimnasio/catalogo/' . $c['id']) ?>" target="_blank" class="fv-ibtn" title="Ver ficha" onclick="event.stopPropagation()"><i class="bi bi-box-arrow-up-right"></i></a>
                            </label>
                        <?php endforeach; ?>
                        <label class="gm-op"><input type="radio" name="vincular[<?= $e['id'] ?>]" value="" <?= $clara ? '' : 'checked' ?> data-ninguno><span>Ninguno</span></label>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="gm-pegado">
                <button class="fv-btn fv-btn-pri"><i class="bi bi-check2-all"></i> Guardar vínculos</button>
                <span class="gm-ayuda" id="vlN"></span>
            </div>
        </form>
        <script>
        (function () {
            const filas = document.querySelectorAll('.gm-vl');
            const n = document.getElementById('vlN');
            const cuenta = () => { const k = document.querySelectorAll('.gm-op input:checked:not([data-ninguno])').length; n.textContent = k + ' marcado' + (k === 1 ? '' : 's'); };
            document.querySelectorAll('#vlFiltro [data-f]').forEach(b => b.addEventListener('click', () => {
                document.querySelectorAll('#vlFiltro [data-f]').forEach(x => x.classList.toggle('on', x === b));
                const v = b.dataset.f;
                filas.forEach(f => { f.hidden = !!v && (v === 'claras') !== (f.dataset.clara === '1'); });
            }));
            document.getElementById('vlNinguno').addEventListener('click', () => { document.querySelectorAll('[data-ninguno]').forEach(i => { i.checked = true; }); cuenta(); });
            document.addEventListener('change', cuenta);
            cuenta();
        })();
        </script>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
