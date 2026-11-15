<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<?php
/**
 * Enlaces — página de notas de un enlace (estilo fv) con CKEditor.
 * Ctrl+S guarda sin salir.
 *
 * @var array $item
 * @var array $cats
 * @var array $tags
 */
helper('enlaces');
$id = (int) $item['id'];
$dominio = enl_dominio($item['url']);
$rel = (int) $item['relevancia'];

$sub = [];
if ($dominio) $sub[] = '<a class="en-dominio" style="display:inline-flex;gap:6px;align-items:center" href="' . esc($item['url'], 'attr') . '" target="_blank" rel="noopener"><img src="https://www.google.com/s2/favicons?domain=' . urlencode($dominio) . '&sz=32" width="14" height="14" alt="">' . esc($dominio) . ' <i class="bi bi-box-arrow-up-right" style="font-size:10px"></i></a>';
$sub[] = '<span>' . esc(date('d/m/Y', strtotime($item['fecha']))) . '</span>';
if ($rel) $sub[] = '<span class="en-rel e-warn">' . str_repeat('★', $rel) . '</span>';
if (!empty($item['visto'])) $sub[] = '<span class="e-ok">Visto</span>';

ob_start(); ?>
<a class="fv-btn" href="<?= esc($item['url'], 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i>Abrir</a>
<a class="fv-ibtn" href="<?= site_url('enlaces/editar/' . $id) ?>" title="Editar enlace"><i class="bi bi-pencil"></i></a>
<?php $acciones = ob_get_clean(); ?>

<div class="fv rc">
    <?= view('enlaces/_cabecera', [
        'migas'    => [mb_strimwidth((string) ($item['titulo'] ?: $dominio), 0, 40, '…') => null],
        'titulo'   => esc($item['titulo'] ?: ($dominio ?: 'Enlace')),
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => $acciones,
    ]) ?>

    <?php if ($cats || $tags): ?>
        <div class="en-badges" style="margin-top:-8px">
            <?php foreach ($cats as $c): ?><span class="en-cat" style="--h:<?= crc32($c['nombre']) % 360 ?>"><?= esc($c['nombre']) ?></span><?php endforeach; ?>
            <?php foreach ($tags as $t): ?><span class="en-tag"><?= esc($t['nombre']) ?></span><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('enlaces/pagina/guardar/' . $id) ?>" id="enPagina" class="rc-form">
        <?= csrf_field() ?>
        <div class="en-editor">
            <?php // Sin escapar: es el HTML del propio editor. ?>
            <textarea id="editor" name="contenido_html"><?= $item['extra'] ?? '' ?></textarea>
        </div>
        <div class="rc-pie">
            <span class="en-guardado" id="enGuardado">Ctrl+S guarda sin salir</span>
            <div class="der">
                <button class="fv-btn" type="button" id="btnGuardar"><i class="bi bi-floppy"></i>Guardar</button>
                <button class="fv-btn fv-btn-pri" type="submit"><i class="bi bi-check-lg"></i>Guardar y salir</button>
            </div>
        </div>
    </form>
</div>

<style>
/* CKEditor en oscuro, con los colores de fv */
.en-editor {
  --ck-color-base-background: var(--fv-panel); --ck-color-base-foreground: var(--fv-raised); --ck-color-base-border: var(--fv-border-2);
  --ck-color-base-text: var(--fv-text); --ck-color-text: var(--fv-text); --ck-color-shadow-drop: rgb(0 0 0 / .4); --ck-color-shadow-inner: transparent;
  --ck-color-toolbar-background: var(--fv-raised); --ck-color-toolbar-border: var(--fv-border-2);
  --ck-color-button-default-hover-background: var(--fv-hover); --ck-color-button-default-active-background: var(--fv-hover);
  --ck-color-button-on-background: var(--fv-accent-weak); --ck-color-button-on-hover-background: var(--fv-accent-weak); --ck-color-button-on-color: var(--fv-accent-hover);
  --ck-color-dropdown-panel-background: var(--bs-secondary-bg); --ck-color-dropdown-panel-border: var(--fv-border-2);
  --ck-color-panel-background: var(--bs-secondary-bg); --ck-color-panel-border: var(--fv-border-2);
  --ck-color-input-background: var(--fv-inset); --ck-color-input-border: var(--fv-border-2); --ck-color-input-text: var(--fv-text);
  --ck-color-list-background: var(--bs-secondary-bg); --ck-color-list-button-hover-background: var(--fv-hover);
  --ck-color-list-button-on-background: var(--fv-accent); --ck-color-list-button-on-text: #fff;
  --ck-color-labeled-field-label-background: var(--bs-secondary-bg);
  --ck-color-focus-border: var(--fv-accent); --ck-focus-ring: 1px solid var(--fv-accent);
  --ck-color-link-default: var(--fv-accent-hover);
}
.en-editor .ck.ck-editor { color: var(--fv-text); }
.en-editor .ck-content a { color: var(--fv-accent-hover); }
.en-editor .ck-content blockquote { border-left-color: var(--fv-border-2); color: var(--fv-text-2); }
</style>

<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
(() => {
    const GUARDAR_URL = '<?= site_url('enlaces/pagina/guardar/' . $id) ?>';
    const estado = document.getElementById('enGuardado');
    let editor;

    ClassicEditor.create(document.getElementById('editor'), {
        toolbar: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'link', '|',
                  'bulletedList', 'numberedList', 'blockQuote', 'insertTable', '|', 'imageUpload', 'mediaEmbed', 'removeFormat'],
        simpleUpload: { uploadUrl: '<?= site_url('enlaces/editor-upload/' . $id) . '?' . csrf_token() . '=' . csrf_hash() ?>' },
    }).then(ed => {
        editor = ed;
        ed.model.document.on('change:data', () => { estado.textContent = 'Cambios sin guardar'; estado.className = 'en-guardado e-warn'; });
    }).catch(console.error);

    async function guardar() {
        if (!editor) return;
        const fd = new FormData();
        fd.append('contenido_html', editor.getData());
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        estado.textContent = 'Guardando…';
        estado.className = 'en-guardado';
        const r = await fetch(GUARDAR_URL, { method: 'POST', body: fd }).catch(() => null);
        estado.textContent = r && r.ok ? 'Guardado a las ' + new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) : 'No se pudo guardar';
        estado.className = 'en-guardado ' + (r && r.ok ? 'e-ok' : 'e-bad');
    }

    document.getElementById('btnGuardar').addEventListener('click', guardar);
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); guardar(); }
    });
    document.getElementById('enPagina').addEventListener('submit', () => {
        if (editor) document.getElementById('editor').value = editor.getData();
    });
})();
</script>
<?php $this->endSection(); ?>
