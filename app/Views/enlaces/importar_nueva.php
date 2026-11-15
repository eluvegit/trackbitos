<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — importar desde el HTML exportado de Telegram (estilo fv).
 * El controlador deja en el flash «msg» un resumen de texto + una tabla HTML
 * (ya escapada) con las primeras filas: aquí se separan y se pintan aparte.
 */
$msg = (string) session()->getFlashdata('msg');
$corte = strpos($msg, '<');
$resumen = trim($corte === false ? $msg : substr($msg, 0, $corte));
$tabla = $corte === false ? '' : substr($msg, $corte);
$cifras = [];
if ($resumen !== '' && preg_match_all('/([\p{L} ]+):\s*(\d+)/u', $resumen, $m, PREG_SET_ORDER)) {
    foreach ($m as [, $k, $v]) $cifras[trim($k)] = (int) $v;
}
$simulacion = str_contains($resumen, 'Simulación');
?>
<div class="fv rc estrecho">
    <?= view('enlaces/_cabecera', [
        'activa' => 'importar',
        'titulo' => 'Importar',
        'sub'    => '<span>Enlaces desde un chat exportado de Telegram (los que te mandas a ti mismo).</span>',
        'sinMsg' => true,
    ]) ?>

    <?php if ($resumen !== ''): ?>
        <section class="rc-sec">
            <div class="fv-ph"><h3>Resultado</h3><?php if ($simulacion): ?><span class="fv-estado e-info">Simulación: no se guardó nada</span><?php endif; ?></div>
            <?php if ($cifras): ?>
                <div class="rc-cifras">
                    <?php foreach ($cifras as $k => $v): ?>
                        <div class="rc-cifra"><span class="k<?= $k === 'Insertados' && $v ? ' e-ok' : ($k === 'Errores' && $v ? ' e-bad' : '') ?>"><?= esc($k) ?></span><span class="v" style="font-size:22px"><?= $v ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="fv-flash fv-flash-ok en-flash-inf"><i class="bi bi-info-circle"></i><?= esc($resumen) ?></div>
            <?php endif; ?>
            <?php if ($tabla !== ''): ?>
                <?php // HTML generado por Enlaces::importarUpload() con los valores ya escapados. ?>
                <details class="fv-panel en-preview"<?= $simulacion ? ' open' : '' ?>><summary class="t2" style="cursor:pointer;font-size:13px">Primeras filas</summary><?= $tabla ?></details>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <form class="rc-form" action="<?= site_url('enlaces/importar') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="fv-panel">
            <label class="en-subir" id="enSubir">
                <i class="bi bi-filetype-html"></i>
                <span id="enSubirTxt"><b>Elige el archivo</b> o arrástralo aquí</span>
                <span class="t3" style="font-size:12px">Normalmente <code>messages.html</code></span>
                <input type="file" name="html_file" accept=".html,.htm,text/html" required>
            </label>
            <label class="rc-inter">
                <input type="checkbox" value="1" name="dry_run" checked>
                <span><b>Solo simular</b><small>No guarda nada: sirve para ver cuántos enlaces detecta antes de importar de verdad.</small></span>
            </label>
            <div class="rc-info"><i class="bi bi-info-circle"></i><span>Los que ya tienes se saltan, y los enlaces a imágenes no se importan.</span></div>
        </div>
        <div class="rc-pie"><div class="der"><button type="submit" class="fv-btn fv-btn-pri"><i class="bi bi-upload"></i>Procesar</button></div></div>
    </form>
</div>

<script>
(() => {
    const zona = document.getElementById('enSubir');
    const input = zona.querySelector('input');
    const txt = document.getElementById('enSubirTxt');
    const pintar = () => { if (input.files[0]) txt.innerHTML = '<b></b>'; if (input.files[0]) txt.firstChild.textContent = input.files[0].name; };
    input.addEventListener('change', pintar);
    ['dragenter', 'dragover'].forEach(ev => zona.addEventListener(ev, e => { e.preventDefault(); zona.classList.add('sobre'); }));
    ['dragleave', 'drop'].forEach(ev => zona.addEventListener(ev, () => zona.classList.remove('sobre')));
    zona.addEventListener('drop', e => { e.preventDefault(); if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; pintar(); } });
})();
</script>
<?php $this->endSection(); ?>
