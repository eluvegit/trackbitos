<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-collection-play text-primary"></i>
    <a href="<?= site_url('gimnasio/catalogo') ?>" class="text-decoration-none text-muted fw-normal">Catálogo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold"><?= esc(ucfirst($c['nombre'])) ?></strong>
</h5>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="cat-ficha">
    <div class="cat-ficha-media">
        <img src="<?= esc(gim_catalogo_media($c['gif'])) ?>" alt="<?= esc($c['nombre']) ?>" width="180" height="180">
        <div class="cat-ficha-credito">© <a href="https://gymvisual.com/" target="_blank" rel="noopener">Gym visual</a></div>
    </div>

    <div class="cat-ficha-info">
        <div class="cat-chips mb-2">
            <span class="cat-chip"><i class="bi bi-bullseye"></i> <?= esc(gim_catalogo_etiqueta('objetivo', $c['objetivo'])) ?></span>
            <span class="cat-chip"><i class="bi bi-person"></i> <?= esc(gim_catalogo_etiqueta('parte', $c['parte'])) ?></span>
            <span class="cat-chip"><i class="bi bi-tools"></i> <?= esc(gim_catalogo_etiqueta('equipo', $c['equipo'])) ?></span>
        </div>
        <?php if ($c['secundarios']): ?>
            <p class="small text-muted mb-2">Secundarios: <?= esc($c['secundarios']) ?></p>
        <?php endif; ?>

        <?php if ($pasos): ?>
            <ol class="cat-pasos">
                <?php foreach ($pasos as $p): ?>
                    <li><?= esc($p) ?></li>
                <?php endforeach; ?>
            </ol>
        <?php elseif ($c['instrucciones_es']): ?>
            <p><?= esc($c['instrucciones_es']) ?></p>
        <?php endif; ?>

        <?php if ($c['instrucciones_en']): ?>
            <details class="small text-muted">
                <summary>Original en inglés</summary>
                <p class="mt-1 mb-0"><?= esc($c['instrucciones_en']) ?></p>
            </details>
        <?php endif; ?>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <h6 class="mb-2">En mis ejercicios</h6>

        <?php if ($vinculados): ?>
            <ul class="list-unstyled mb-3">
                <?php foreach ($vinculados as $v): ?>
                    <li class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <a href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $v['id']) ?>"><?= esc($v['nombre']) ?></a>
                        <span class="text-muted small"><?= esc(gim_grupo_nombre($v['grupo_muscular'])) ?></span>
                        <form action="<?= site_url('gimnasio/catalogo/desvincular/' . $v['id']) ?>" method="post" class="m-0 ms-auto">
                            <?= csrf_field() ?>
                            <button class="btn btn-link btn-sm text-muted p-0" title="Quitar vínculo (no borra tu ejercicio)">
                                <i class="bi bi-link-45deg"></i> desvincular
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted small">Todavía no está en tus ejercicios.</p>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <form action="<?= site_url('gimnasio/catalogo/' . $c['id'] . '/vincular') ?>" method="post">
                    <?= csrf_field() ?>
                    <label class="form-label small fw-semibold">Es uno que ya tengo</label>
                    <div class="input-group">
                        <select name="ejercicio_id" class="form-select" required>
                            <option value="">Elige tu ejercicio…</option>
                            <?php $grupoActual = null; ?>
                            <?php foreach ($misEjercicios as $e): ?>
                                <?php if ($e['grupo_muscular'] !== $grupoActual): ?>
                                    <?php if ($grupoActual !== null): ?></optgroup><?php endif; ?>
                                    <optgroup label="<?= esc(gim_grupo_nombre($e['grupo_muscular'])) ?>">
                                    <?php $grupoActual = $e['grupo_muscular']; ?>
                                <?php endif; ?>
                                <option value="<?= $e['id'] ?>">
                                    <?= esc($e['nombre']) ?><?= $e['catalogo_id'] ? ' 🎬' : '' ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($grupoActual !== null): ?></optgroup><?php endif; ?>
                        </select>
                        <button class="btn btn-outline-primary"><i class="bi bi-link"></i> Vincular</button>
                    </div>
                    <div class="form-text">Le añade la animación e instrucciones; no cambia nada más.</div>
                </form>
            </div>

            <div class="col-md-6">
                <form action="<?= site_url('gimnasio/catalogo/' . $c['id'] . '/adoptar') ?>" method="post">
                    <?= csrf_field() ?>
                    <label class="form-label small fw-semibold">Añadir como ejercicio nuevo</label>
                    <div class="input-group">
                        <input type="text" name="nombre" class="form-control" maxlength="100"
                               value="<?= esc(ucfirst($c['nombre'])) ?>" required>
                        <select name="grupo_muscular" class="form-select" style="max-width: 9.5rem">
                            <?php foreach ($grupos as $k => $v): ?>
                                <option value="<?= esc($k) ?>" <?= $k === $grupoSugerido ? 'selected' : '' ?>><?= esc($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="form-text">Ponle el nombre que usarías tú (p. ej. en español).</div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.cat-ficha { display: flex; gap: 16px; flex-wrap: wrap; }
.cat-ficha-media { flex: 0 0 auto; text-align: center; }
.cat-ficha-media img { width: 180px; height: 180px; border-radius: 12px; background: #fff; border: 1px solid var(--bs-border-color); }
.cat-ficha-credito { font-size: .7rem; color: var(--bs-secondary-color); margin-top: 2px; }
.cat-ficha-credito a { color: inherit; }
.cat-ficha-info { flex: 1 1 280px; min-width: 0; }
.cat-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.cat-chip {
    padding: .25rem .6rem; border-radius: 999px; font-size: .8rem;
    border: 1px solid var(--bs-border-color); background: var(--bs-tertiary-bg);
}
.cat-pasos { padding-left: 1.2rem; font-size: .92rem; }
.cat-pasos li { margin-bottom: .35rem; }
</style>

<?= $this->endSection() ?>
