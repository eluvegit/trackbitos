<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php $pid = (int) $proyecto['id']; ?>
<div class="fv rd">
    <?= view('rodajes/flujo/_cab', array_merge(get_defined_vars(), ['intro' => 'Echa todo de golpe: imágenes, capturas e ideas escritas. Entra junto en la bandeja, sin ordenar ni decidir nada.'])) ?>

    <?php if (!$sesion): ?>
        <div class="fv-panel rf-aviso-sesion">
            <p>Las ideas se guardan en el moodboard de una sesión, y este rodaje no tiene ninguna vinculada.</p>
            <div class="rd-herr">
                <form method="post" action="<?= site_url("rodajes/$pid/ideas/sesion") ?>"><?= csrf_field() ?>
                    <button class="fv-btn fv-btn-pri"><i class="bi bi-plus-lg"></i>Crear sesión «<?= esc($proyecto['titulo']) ?>»</button>
                </form>
                <?php if ($sesiones): ?>
                    <form method="post" action="<?= site_url("rodajes/$pid/ideas/sesion") ?>" class="rd-herr"><?= csrf_field() ?>
                        <select name="sesion_id" class="form-select rd-sel" required>
                            <option value="">Vincular una existente…</option>
                            <?php foreach ($sesiones as $s): ?><option value="<?= (int) $s['id'] ?>"><?= esc($s['titulo']) ?></option><?php endforeach; ?>
                        </select>
                        <button class="fv-btn">Vincular</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="rf-ingesta">
            <form id="rfSubir" class="rf-drop" method="post" action="<?= site_url("rodajes/$pid/ideas/subir") ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <label>
                    <input type="file" name="archivo[]" id="rfFiles" accept="image/*" multiple hidden>
                    <i class="bi bi-images"></i>
                    <b>Suelta aquí las imágenes</b>
                    <span>O pulsa para elegir varias, o pégalas con Ctrl+V. Entran todas juntas en la bandeja.</span>
                    <em class="subiendo">Subiendo…</em>
                </label>
            </form>
            <form class="fv-panel rf-texto rd-form" method="post" action="<?= site_url("rodajes/$pid/ideas/texto") ?>">
                <?= csrf_field() ?>
                <label class="rd-lbl" for="rfTexto">Ideas escritas</label>
                <textarea class="fv-input" id="rfTexto" name="texto" rows="5" placeholder="Una idea por línea. Los enlaces (https://…) entran como enlace."></textarea>
                <div class="rf-fila">
                    <label class="rf-chk"><input type="checkbox" name="buscar_ref" value="1">Buscar referencia</label>
                    <button class="fv-btn fv-btn-pri der"><i class="bi bi-plus-lg"></i>Añadir a la bandeja</button>
                </div>
            </form>
        </div>

        <section class="rf-sec">
            <div class="rf-sec-h">
                <h2>Bandeja</h2>
                <span><?= count($bandeja) ?> sin temática · <?= $agrupadas ?> ya agrupadas</span>
                <a class="fv-btn fv-btn-sm der" href="<?= site_url("rodajes/$pid/tematicas") ?>">Agrupar en temáticas<i class="bi bi-arrow-right"></i></a>
            </div>
            <?php if ($bandeja): ?>
                <div class="rf-ideas">
                    <?php foreach ($bandeja as $i): ?><?= view('rodajes/flujo/_idea', ['i' => $i, 'href' => site_url("rodajes/$pid/revisar?idea=" . $i['id'])]) ?><?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="rf-vacio">La bandeja está vacía.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<script>
(() => {
    const f = document.getElementById('rfSubir');
    if (!f) return;
    const inp = document.getElementById('rfFiles');
    const subir = () => { f.classList.add('subiendo'); f.submit(); };
    const enviar = files => {
        const im = [...files].filter(x => x.type.startsWith('image/'));
        if (!im.length) return;
        const dt = new DataTransfer();
        im.forEach(x => dt.items.add(x));
        inp.files = dt.files;
        subir();
    };
    inp.addEventListener('change', () => { if (inp.files.length) subir(); });
    ['dragenter', 'dragover'].forEach(t => f.addEventListener(t, e => { e.preventDefault(); f.classList.add('sobre'); }));
    ['dragleave', 'drop'].forEach(t => f.addEventListener(t, e => { e.preventDefault(); f.classList.remove('sobre'); }));
    f.addEventListener('drop', e => enviar(e.dataTransfer.files));
    document.addEventListener('paste', e => {
        if (e.target.closest && e.target.closest('textarea, input')) return;
        const fs = e.clipboardData && e.clipboardData.files;
        if (fs && fs.length) { e.preventDefault(); enviar(fs); }
    });
})();
</script>
<?= $this->endSection() ?>
