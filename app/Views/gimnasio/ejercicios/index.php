<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-list-check text-primary"></i>
    <a href="<?= site_url('gimnasio') ?>" class="text-decoration-none text-muted fw-normal">Gimnasio</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Ejercicios</strong>

    <a href="<?= site_url('gimnasio/ejercicios/create') ?>" class="text-decoration-none ms-1 text-success" title="Nuevo ejercicio">
        <i class="bi bi-plus-circle fs-5"></i>
    </a>
</h5>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<?php
$grupos = [];
foreach ($ejercicios as $e) {
    $grupos[$e['grupo_muscular']][] = $e;
}
?>

<!-- Buscador -->
<div class="ej-search mb-2">
    <i class="bi bi-search"></i>
    <input type="text" id="ejBuscador" class="form-control" placeholder="Buscar ejercicio…">
</div>

<!-- Chips de acceso rápido -->
<div class="ej-chips mb-3">
    <?php foreach ($grupos as $grupo => $lista): ?>
        <a href="#grupo-<?= esc($grupo) ?>" class="ej-chip" data-target="grupo-<?= esc($grupo) ?>">
            <?= esc($grupoNombres[$grupo] ?? ucfirst($grupo)) ?> <span class="ej-chip-count"><?= count($lista) ?></span>
        </a>
    <?php endforeach; ?>
    <button type="button" class="ej-chip ej-chip-toggle" id="btnToggleTodos">Mostrar todo</button>
</div>

<div id="ejLista">
    <?php foreach ($grupos as $grupo => $ejerciciosDelGrupo): ?>
        <?php $grupoNombre = $grupoNombres[$grupo] ?? ucfirst($grupo); ?>
        <div class="ej-group" id="grupo-<?= esc($grupo) ?>">
            <div class="ej-group-header" data-bs-toggle="collapse" data-bs-target="#coll-<?= esc($grupo) ?>"
                 aria-expanded="false" aria-controls="coll-<?= esc($grupo) ?>">
                <i class="bi bi-chevron-right ej-group-chevron"></i>
                <span class="ej-group-title"><?= esc($grupoNombre) ?></span>
                <span class="ej-group-count"><?= count($ejerciciosDelGrupo) ?></span>
                <a href="<?= site_url('gimnasio/ejercicios/create?grupo=' . urlencode($grupo)) ?>"
                   class="ej-group-add" title="Añadir a <?= esc($grupoNombre) ?>" onclick="event.stopPropagation();">
                    <i class="bi bi-plus-lg"></i>
                </a>
            </div>

            <div class="collapse" id="coll-<?= esc($grupo) ?>">
                <div class="ej-item-list">
                    <?php foreach ($ejerciciosDelGrupo as $ejercicio): ?>
                        <div class="ej-item" data-nombre="<?= esc(mb_strtolower($ejercicio['nombre'])) ?>">
                            <span class="ej-item-nombre"><?= esc($ejercicio['nombre']) ?></span>
                            <div class="ej-item-actions">
                                <a class="ej-icon-btn" href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $ejercicio['id']) ?>" title="Ver estadísticas">
                                    <i class="bi bi-graph-up"></i>
                                </a>
                                <a class="ej-icon-btn" href="<?= site_url('gimnasio/ejercicios/edit/' . $ejercicio['id']) ?>" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?= site_url('gimnasio/ejercicios/delete/' . $ejercicio['id']) ?>" method="post" class="m-0"
                                      onsubmit="return confirm('¿Eliminar este ejercicio?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="ej-icon-btn ej-icon-btn-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<p class="text-muted small mt-3 d-none" id="ejSinResultados">No hay ejercicios que coincidan con la búsqueda.</p>

<style>
.ej-search { position: relative; display: flex; align-items: center; }
.ej-search i { position: absolute; left: 12px; color: var(--bs-secondary-color); }
.ej-search input { padding-left: 34px; }

.ej-chips { display: flex; flex-wrap: wrap; gap: 6px; }
.ej-chip {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .3rem .65rem;
    border-radius: 999px;
    border: 1px solid var(--bs-border-color);
    background: var(--bs-tertiary-bg);
    color: var(--bs-emphasis-color);
    font-size: .8rem;
    text-decoration: none;
    cursor: pointer;
}
.ej-chip:hover { background: var(--bs-body-bg); }
.ej-chip-count {
    font-size: .7rem;
    color: var(--bs-secondary-color);
    background: var(--bs-body-bg);
    border-radius: 999px;
    padding: 0 .4rem;
}
.ej-chip-toggle { border-style: dashed; }

.ej-group {
    border: 1px solid var(--bs-border-color);
    border-radius: 12px;
    margin-bottom: 8px;
    overflow: hidden;
    background: var(--bs-body-bg);
}
.ej-group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    cursor: pointer;
    background: var(--bs-tertiary-bg);
}
.ej-group-chevron { transition: transform .15s ease; color: var(--bs-secondary-color); }
.ej-group-header[aria-expanded="true"] .ej-group-chevron { transform: rotate(90deg); }
.ej-group-title { font-weight: 700; font-size: .92rem; color: var(--bs-emphasis-color); }
.ej-group-count {
    font-size: .72rem;
    color: var(--bs-secondary-color);
    background: var(--bs-body-bg);
    border-radius: 999px;
    padding: .05rem .5rem;
}
.ej-group-add {
    margin-left: auto;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    color: var(--bs-secondary-color);
    text-decoration: none;
    flex: 0 0 auto;
}
.ej-group-add:hover { background: var(--bs-body-bg); color: var(--bs-emphasis-color); }

.ej-item-list { display: flex; flex-direction: column; }
.ej-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 12px;
    border-top: 1px solid var(--bs-border-color);
}
.ej-item:hover { background: var(--bs-tertiary-bg); }
.ej-item-nombre { font-size: .88rem; color: var(--bs-emphasis-color); }

.ej-item-actions { display: flex; align-items: center; gap: 2px; flex: 0 0 auto; }
.ej-icon-btn {
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: var(--bs-secondary-color);
    text-decoration: none;
    cursor: pointer;
}
.ej-icon-btn:hover { background: var(--bs-body-bg); color: var(--bs-emphasis-color); }
.ej-icon-btn-danger:hover { color: #dc3545; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buscador = document.getElementById('ejBuscador');
    const grupos = document.querySelectorAll('.ej-group');
    const sinResultados = document.getElementById('ejSinResultados');

    function normaliza(s) {
        return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    buscador.addEventListener('input', () => {
        const q = normaliza(buscador.value.trim());
        let totalVisible = 0;

        grupos.forEach(grupo => {
            const items = grupo.querySelectorAll('.ej-item');
            let visiblesEnGrupo = 0;

            items.forEach(item => {
                const coincide = !q || normaliza(item.dataset.nombre).includes(q);
                item.style.display = coincide ? '' : 'none';
                if (coincide) visiblesEnGrupo++;
            });

            grupo.style.display = (q && visiblesEnGrupo === 0) ? 'none' : '';
            totalVisible += visiblesEnGrupo;

            const collapseEl = grupo.querySelector('.collapse');
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            if (q && visiblesEnGrupo > 0) {
                bsCollapse.show();
            } else if (!q) {
                bsCollapse.hide();
            }
        });

        sinResultados.classList.toggle('d-none', !(q && totalVisible === 0));
    });

    // Chips de acceso rápido: expanden el grupo y hacen scroll hasta él
    document.querySelectorAll('.ej-chip[data-target]').forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.getElementById(chip.dataset.target);
            if (!target) return;
            const collapseEl = target.querySelector('.collapse');
            bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // Mostrar/ocultar todos los grupos
    let expandido = false;
    const btnToggleTodos = document.getElementById('btnToggleTodos');
    btnToggleTodos.addEventListener('click', () => {
        grupos.forEach(grupo => {
            const collapseEl = grupo.querySelector('.collapse');
            const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            expandido ? bsCollapse.hide() : bsCollapse.show();
        });
        expandido = !expandido;
        btnToggleTodos.textContent = expandido ? 'Ocultar todo' : 'Mostrar todo';
    });
});
</script>

<?= $this->endSection() ?>
