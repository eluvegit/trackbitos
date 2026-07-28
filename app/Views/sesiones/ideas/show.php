<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?php $ideaId = (int) $idea['id']; ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-lightbulb text-primary"></i>
    <a href="<?= site_url('sesiones') ?>" class="text-decoration-none text-muted fw-normal">Sesiones</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc($idea['titulo']) ?></strong>
</h5>

<?php if (session('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session('success')) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-2">
            <div>
                <h4 class="mb-1"><?= esc($idea['titulo']) ?></h4>
                <div class="d-flex flex-wrap gap-1">
                    <?php if ((int) $idea['tiene_foto'] === 1): ?>
                        <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera"></i> Fotografía</span>
                    <?php endif; ?>
                    <?php if ((int) $idea['tiene_video'] === 1): ?>
                        <span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-camera-video"></i> Vídeo</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= site_url('sesiones/ideas/' . $ideaId . '/editar') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <form method="post" action="<?= site_url('sesiones/ideas/' . $ideaId . '/promover') ?>"
                      onsubmit="return confirm('¿Promover esta idea a sesión activa? Empezará en «Planificación» y esta idea desaparecerá de la lista de ideas.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-arrow-up-right-circle"></i> Promover a sesión</button>
                </form>
                <form method="post" action="<?= site_url('sesiones/ideas/' . $ideaId . '/borrar') ?>"
                      onsubmit="return confirm('¿Borrar esta idea y su moodboard? No se puede deshacer.');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Borrar</button>
                </form>
            </div>
        </div>

        <form method="post" action="<?= site_url('sesiones/ideas/' . $ideaId . '/actualizar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="titulo" value="<?= esc($idea['titulo']) ?>">
            <label class="form-label small mb-1">Notas</label>
            <textarea name="notas" class="form-control mb-2" rows="3"><?= esc($idea['notas'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-sm btn-outline-primary">Guardar notas</button>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3"><i class="bi bi-images me-1"></i>Moodboard</h6>
        <div class="gallery-grid moodboard-grid mb-2" id="moodboard">
            <?php foreach ($moodboard as $item): ?>
                <?= view('sesiones/_moodboard_item', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>

        <div class="d-flex flex-wrap gap-3 mt-2">
            <form id="moodboardArchivoForm" class="d-flex align-items-center gap-2" enctype="multipart/form-data">
                <input type="file" name="archivo" accept="image/*" multiple class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-upload"></i></button>
            </form>

            <form id="moodboardEnlaceForm" class="d-flex align-items-center gap-2">
                <input type="url" name="url_externa" placeholder="https://..." class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-link-45deg"></i></button>
            </form>
        </div>
    </div>
</div>

<style>
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 12px;
}
.gallery-item {
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bs-tertiary-bg);
    display: block;
    border: 1px solid var(--bs-border-color);
    position: relative;
}
.gallery-item img { width: 100%; height: 100%; object-fit: cover; }
.gallery-item .item-borrar {
    position: absolute;
    top: 4px;
    right: 4px;
    background: rgba(0,0,0,.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
}
</style>

<script>
(() => {
    const ideaId = <?= $ideaId ?>;
    const base = '<?= site_url('sesiones/ideas') ?>/' + ideaId;
    const csrf = '<?= csrf_hash() ?>';

    async function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/json',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    }

    async function postForm(url, formData) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: formData,
        });
    }

    document.getElementById('moodboardArchivoForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await postForm(`${base}/moodboard/subir`, new FormData(e.target));
        if (!res.ok) { console.error('No se pudo añadir al moodboard'); return; }
        location.reload();
    });

    document.getElementById('moodboardEnlaceForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await post(`${base}/moodboard/enlace`, Object.fromEntries(new FormData(e.target).entries()));
        if (!res.ok) { console.error('No se pudo añadir al moodboard'); return; }
        location.reload();
    });

    document.getElementById('moodboard').addEventListener('click', async (e) => {
        const btn = e.target.closest('.item-borrar');
        if (!btn) return;
        const el = btn.closest('.gallery-item');
        const res = await post(`${base}/moodboard/${el.dataset.id}/borrar`);
        if (res.ok) el.remove();
    });
})();
</script>

<?= $this->endSection() ?>
