<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<style>
    .editor-wrapper {
        max-width: 680px;
        /* 👈 limita el ancho */
        margin: 0 auto;
        /* centra en pantalla */
    }

    .ck-editor__editable {
        min-height: 400px;
        /* altura de edición cómoda */
    }
</style>
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0"><?= esc($item['titulo']) ?></h5>
            <div class="small text-muted">
                <a href="<?= esc($item['url']) ?>" target="_blank">Abrir enlace externo</a> ·
                <?= date('d/m/Y', strtotime($item['fecha'])) ?> ·
                <span><?= str_repeat('★', (int)$item['relevancia']) ?><?= str_repeat('☆', 5 - (int)$item['relevancia']) ?></span>
            </div>
            <div class="mt-1 d-flex flex-wrap gap-1">
                <?php foreach ($cats as $c): ?><span class="badge bg-light text-dark">#<?= esc($c['nombre']) ?></span><?php endforeach; ?>
                <?php foreach ($tags as $t): ?><span class="badge bg-secondary"><?= esc($t['nombre']) ?></span><?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces') ?>">← Volver</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces/editar/' . $item['id']) ?>">Editar enlace</a>
        </div>
    </div>

    <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
    <?php endif; ?>
    <?php if (session('mensaje')): ?>
        <div class="alert alert-success"><?= esc(session('mensaje')) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('enlaces/pagina/guardar/' . $item['id']) ?>">
        <?= csrf_field() ?>

        <!-- IMPORTANTE: no escapar el HTML inicial del editor -->
        <div class="editor-wrapper">
            <textarea id="editor" name="contenido_html"><?= $item['extra'] ?? '' ?></textarea>
        </div>
        <div class="d-flex justify-content-end mt-2 gap-2">
            <button class="btn btn-secondary btn-sm" type="button" id="btnGuardar">Guardar</button>
            <button class="btn btn-primary btn-sm">Guardar y salir</button>
        </div>
    </form>
</div>

<!-- CKEditor 5 (Classic build por CDN) -->
<script src="https://cdn.ckeditor.com/ckeditor5/41.1.0/classic/ckeditor.js"></script>
<script>
    // Sencillo: añadimos el token CSRF en la query del uploadUrl
    const uploadUrl = '<?= site_url('enlaces/editor-upload/' . $item['id']) . '?' . csrf_token() . '=' . csrf_hash() ?>';

    let editorInstance;

    ClassicEditor.create(document.querySelector('#editor'), {
            toolbar: [
                'undo', 'redo', '|',
                'heading', '|', 'bold', 'italic', 'underline', 'link', '|',
                'bulletedList', 'numberedList', 'blockQuote', 'insertTable', '|',
                'imageUpload', 'mediaEmbed', 'removeFormat'
            ],
            simpleUpload: {
                uploadUrl: uploadUrl, // pegar/arrastrar imágenes sube aquí
                withCredentials: false
                // Si prefieres enviar CSRF por cabecera en vez de query:
                // headers: { '<?= csrf_header() ?? 'X-CSRF-TOKEN' ?>': '<?= csrf_hash() ?>' }
            },
        })
        .then(ed => {
            editorInstance = ed;
        })
        .catch(console.error);

    // Ctrl/Cmd + S => guardar sin salir
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            document.getElementById('btnGuardar').click();
        }
    });

    // Guardado rápido (sin salir)
    document.getElementById('btnGuardar').addEventListener('click', async () => {
        const html = editorInstance.getData();
        const fd = new FormData();
        fd.append('contenido_html', html);
        fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        const res = await fetch('<?= site_url('enlaces/pagina/guardar/' . $item['id']) ?>', {
            method: 'POST',
            body: fd
        });
        alert(res.ok ? 'Guardado' : 'No se pudo guardar');
    });

    // Antes del submit normal, volcar el HTML al textarea
    document.querySelector('form').addEventListener('submit', () => {
        document.getElementById('editor').value = editorInstance.getData();
    });
</script>
<?php $this->endSection(); ?>