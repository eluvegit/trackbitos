<!-- app/Views/enlaces/index.php -->
<?php $this->extend('layouts/default'); ?>
<?php $this->section('content'); ?>
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <?php $total = is_countable($enlaces) ? count($enlaces) : 0; ?>
        <h5 class="mb-0 d-flex align-items-center gap-2">
            Enlaces interesantes
            <span class="badge bg-light text-dark border">
                <?= $total ?> <?= $total === 1 ? 'resultado' : 'resultados' ?>
            </span>
        </h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/categorias') ?>">Categorías</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/etiquetas') ?>">Etiquetas</a>
            <a class="btn btn-sm btn-primary" href="<?= site_url('enlaces/crear') ?>">+ Agregar</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('enlaces/importar') ?>">Importar HTML</a>
            <a class="btn btn-sm btn-warning" href="<?= site_url('enlaces/revision') ?>">Revisar pendientes</a>

        </div>
    </div>

    <!-- Filtros -->
    <form class="row g-2 mb-3 align-items-end" method="get" action="<?= site_url('enlaces') ?>">

        <!-- Búsqueda -->
        <div class="col-12 col-md-4">
            <label class="form-label small text-muted mb-1">Buscar</label>
            <input type="text" name="q" value="<?= esc($q) ?>" class="form-control" placeholder="Texto, URL, notas…">
        </div>

        <!-- Visto -->
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">Estado</label>
            <select name="visto" class="form-select">
                <option value="">Todos</option>
                <option value="0" <?= $visto === '0' ? 'selected' : '' ?>>No vistos</option>
                <option value="1" <?= $visto === '1' ? 'selected' : '' ?>>Vistos</option>
            </select>
        </div>

        <!-- Match -->
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">Coincidencia</label>
            <select name="match" class="form-select" title="Modo de coincidencia">
                <option value="any" <?= ($match ?? 'any') === 'any' ? 'selected' : '' ?>>Cualquiera</option>
                <option value="all" <?= ($match ?? 'any') === 'all' ? 'selected' : '' ?>>Todas</option>
            </select>
        </div>

        <!-- Categorías (múltiple) -->
        <!-- Categorías (checkbox “guapetón”) -->
        <div class="col-12">
            <label class="form-label small text-muted mb-1 d-flex justify-content-between align-items-center">
                <span>Categorías</span>
                <span class="d-none d-md-inline small">
                    <a href="#" id="btnSelTodas" class="text-decoration-none me-2">Seleccionar todas</a> ·
                    <a href="#" id="btnSelNinguna" class="text-decoration-none">Ninguna</a>
                </span>
            </label>

            <div class="cat-grid">
                <?php foreach ($categorias as $c):
                    $checked = in_array($c['id'], $cats ?? []) ? 'checked' : '';
                    $n = $catCount[$c['id']] ?? 0; ?>
                    <label class="cat-chip">
                        <input type="checkbox" name="cats[]" value="<?= $c['id'] ?>" <?= $checked ?> />
                        <span class="cat-chip-text">
                            <?= esc($c['nombre']) ?>
                            <span class="cat-chip-count"><?= $n ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="small text-muted mt-1 d-md-none">
                <a href="#" id="btnSelTodasSm" class="text-decoration-none me-2">Seleccionar todas</a> ·
                <a href="#" id="btnSelNingunaSm" class="text-decoration-none">Ninguna</a>
            </div>
        </div>


        <!-- Etiquetas -->
        <div class="col-12 col-md-6">
            <label class="form-label small text-muted mb-1">Etiquetas</label>
            <input type="text" class="form-control" name="tags" value="<?= esc(implode(',', $tags ?? [])) ?>" placeholder="ej: ia, dev, lectura">
        </div>

        <!-- Etiquetas (checkbox “guapetón” con conteo) -->
        <div class="col-12">
            <label class="form-label small text-muted mb-1 d-flex justify-content-between align-items-center">
                <span>Etiquetas (solo de estos resultados)</span>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleTags">
                        Mostrar etiquetas
                    </button>
                    <span class="d-none d-md-inline small">
                        <a href="#" id="btnTagsTodas" class="text-decoration-none me-2">Seleccionar todas</a> ·
                        <a href="#" id="btnTagsNinguna" class="text-decoration-none">Ninguna</a>
                    </span>
                </div>
            </label>

            <!-- contenedor que se oculta por defecto -->
            <div class="tag-grid collapse" id="tagContainer">
                <?php foreach (($tagsDisp ?? []) as $tg):
                    $checked = in_array((int)$tg['etiqueta_id'], $tagIdsSel ?? []) ? 'checked' : ''; ?>
                    <label class="tag-chip">
                        <input type="checkbox" name="tag_ids[]" value="<?= (int)$tg['etiqueta_id'] ?>" <?= $checked ?> />
                        <span class="tag-chip-text">
                            <?= esc($tg['nombre']) ?>
                            <span class="tag-chip-count"><?= (int)$tg['total'] ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>

                <?php if (empty($tagsDisp)): ?>
                    <div class="text-muted small">No hay etiquetas en estos resultados.</div>
                <?php endif; ?>
            </div>

            <div class="small text-muted mt-1 d-md-none">
                <a href="#" id="btnTagsTodasSm" class="text-decoration-none me-2">Seleccionar todas</a> ·
                <a href="#" id="btnTagsNingunaSm" class="text-decoration-none">Ninguna</a>
            </div>
        </div>



        <!-- Acciones -->
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-secondary btn-sm">Filtrar</button>
            <a class="btn btn-light btn-sm" href="<?= site_url('enlaces') ?>">Limpiar</a>
        </div>
    </form>

    <!-- Lista -->
    <div class="row g-2">
        <?php foreach ($enlaces as $e): ?>
            <div class="col-12">
                <div class="border rounded p-2 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2">
                            <!-- icono a la URL externa -->
                            <a href="<?= esc($e['url']) ?>" target="_blank" class="ms-1" title="Abrir enlace externo">🔗</a>
                            <!-- título apunta a la página interna -->
                            <a href="<?= site_url('enlaces/pagina/' . $e['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= esc($e['titulo']) ?>
                            </a>


                            <?php if ($e['visto']): ?><span class="badge bg-success">visto</span><?php endif; ?>
                        </div>
                        <div class="small text-muted">
                            <?= date('d/m/Y', strtotime($e['fecha'])) ?> ·
                            <span title="relevancia"><?= str_repeat('★', (int)$e['relevancia']) ?><?= str_repeat('☆', 5 - (int)$e['relevancia']) ?></span>
                        </div>
                        <?php if (!empty($e['extra'])): ?>
                            <div class="small mt-1 text-break"><?= esc(mb_strimwidth(strip_tags($e['extra']), 0, 160, '…')) ?></div>
                        <?php endif; ?>
                        <div class="mt-1 d-flex flex-wrap gap-1">
                            <?php foreach (($catsPorEnlace[$e['id']] ?? []) as $c): if (!$c) continue; ?>
                                <span class="badge bg-light text-dark">#<?= esc($c['nombre']) ?></span>
                            <?php endforeach; ?>
                            <?php foreach (($tagsPorEnlace[$e['id']] ?? []) as $t): if (!$t) continue; ?>
                                <span class="badge bg-secondary"><?= esc($t['nombre']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-1 ms-auto">
                        <button data-id="<?= $e['id'] ?>" class="btn btn-outline-success btn-sm btn-toggle-visto" title="Marcar visto/no visto">
                            <i class="bi bi-check2-square"></i>
                        </button>
                        <a class="btn btn-outline-primary btn-sm" href="<?= site_url('enlaces/editar/' . $e['id']) ?>">Editar</a>
                        <a class="btn btn-outline-danger btn-sm" href="<?= site_url('enlaces/borrar/' . $e['id']) ?>" onclick="return confirm('¿Eliminar enlace?');">Borrar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<style>
    .cat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: .5rem;
    }

    .cat-chip {
        display: flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid #e5e7eb;
        /* gray-200 */
        border-radius: 9999px;
        padding: .35rem .6rem;
        background: #fff;
        cursor: pointer;
        user-select: none;
        transition: .15s ease-in-out;
        box-shadow: 0 0 0 rgba(0, 0, 0, 0);
    }

    .cat-chip:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    /* gray-300 / slate-50 */
    .cat-chip input[type="checkbox"] {
        accent-color: #0d6efd;
    }

    /* bootstrap primary */
    .cat-chip-text {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .9rem;
    }

    .cat-chip-count {
        font-size: .75rem;
        color: #475569;
        /* slate-600 */
        background: #f1f5f9;
        /* slate-100 */
        border-radius: 9999px;
        padding: .05rem .45rem;
        border: 1px solid #e2e8f0;
        /* slate-200 */
    }

    .tag-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .5rem;
    }

    .tag-chip {
        display: flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        padding: .3rem .55rem;
        background: #fff;
        cursor: pointer;
        transition: .15s ease-in-out;
    }

    .tag-chip:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .tag-chip input[type="checkbox"] {
        accent-color: #0d6efd;
    }

    .tag-chip-text {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .85rem;
    }

    .tag-chip-count {
        font-size: .7rem;
        color: #475569;
        background: #f1f5f9;
        border-radius: 9999px;
        padding: .05rem .45rem;
        border: 1px solid #e2e8f0;
    }

    .tag-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: .5rem;
    }

    .tag-chip {
        display: flex;
        align-items: center;
        gap: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 9999px;
        padding: .3rem .55rem;
        background: #fff;
        cursor: pointer;
        transition: .15s ease-in-out;
    }

    .tag-chip:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .tag-chip input[type="checkbox"] {
        accent-color: #0d6efd;
    }

    .tag-chip-text {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .85rem;
    }

    .tag-chip-count {
        font-size: .7rem;
        color: #475569;
        background: #f1f5f9;
        border-radius: 9999px;
        padding: .05rem .45rem;
        border: 1px solid #e2e8f0;
    }
</style>


<script>
    // Toggle visto
    for (const b of document.querySelectorAll('.btn-toggle-visto')) {
        b.addEventListener('click', async () => {
            const id = b.getAttribute('data-id');
            const res = await fetch('<?= site_url('enlaces/toggle-visto') ?>/' + id, {
                method: 'POST'
            });
            if (res.ok) location.reload();
        });
    }

    function setAllCats(checked) {
        document.querySelectorAll('input[name="cats[]"]').forEach(cb => cb.checked = checked);
    }
    const btnAll = document.getElementById('btnSelTodas');
    const btnNone = document.getElementById('btnSelNinguna');
    const btnAllSm = document.getElementById('btnSelTodasSm');
    const btnNoneSm = document.getElementById('btnSelNingunaSm');

    [btnAll, btnAllSm].forEach(b => b && b.addEventListener('click', e => {
        e.preventDefault();
        setAllCats(true);
    }));
    [btnNone, btnNoneSm].forEach(b => b && b.addEventListener('click', e => {
        e.preventDefault();
        setAllCats(false);
    }));

    function setAll(name, checked) {
        document.querySelectorAll(`input[name="${name}"]`).forEach(cb => cb.checked = checked);
    }
    // Tags
    const TALL = document.getElementById('btnTagsTodas');
    const TNONE = document.getElementById('btnTagsNinguna');
    const TALLs = document.getElementById('btnTagsTodasSm');
    const TNONEs = document.getElementById('btnTagsNingunaSm');

    [TALL, TALLs].forEach(b => b && b.addEventListener('click', e => {
        e.preventDefault();
        setAll('tag_ids[]', true);
    }));
    [TNONE, TNONEs].forEach(b => b && b.addEventListener('click', e => {
        e.preventDefault();
        setAll('tag_ids[]', false);
    }));

    // Toggle mostrar/ocultar etiquetas
    const btnToggleTags = document.getElementById('btnToggleTags');
    const tagContainer = document.getElementById('tagContainer');
    btnToggleTags.addEventListener('click', () => {
        const isHidden = tagContainer.classList.contains('collapse');
        if (isHidden) {
            tagContainer.classList.remove('collapse');
            btnToggleTags.textContent = 'Ocultar etiquetas';
        } else {
            tagContainer.classList.add('collapse');
            btnToggleTags.textContent = 'Mostrar etiquetas';
        }
    });


    // --- LIMPIAR FILTRO DE ETIQUETAS CUANDO CAMBIAN LAS CATEGORÍAS ---
    function clearTagFilters() {
        // Desmarcar todos los checkboxes de etiquetas
        document.querySelectorAll('input[name="tag_ids[]"]').forEach(cb => cb.checked = false);
        // Vaciar el input de etiquetas libres (csv)
        const tagsInput = document.querySelector('input[name="tags"]');
        if (tagsInput) tagsInput.value = '';
    }

    // Cuando cambie cualquier categoría, limpiamos etiquetas
    document.querySelectorAll('input[name="cats[]"]').forEach(cb => {
        cb.addEventListener('change', () => {
            clearTagFilters();
        });
    });

    // Si usas los botones "Seleccionar todas / Ninguna", también limpiamos etiquetas
    [btnAll, btnNone, btnAllSm, btnNoneSm].forEach(b => b && b.addEventListener('click', () => {
        clearTagFilters();
    }));

    // (Opcional) si quieres limpiar etiquetas al cambiar el "Estado" o "Coincidencia":
    document.querySelector('select[name="visto"]').addEventListener('change', clearTagFilters);
    document.querySelector('select[name="match"]').addEventListener('change', clearTagFilters);
</script>
<?php $this->endSection(); ?>