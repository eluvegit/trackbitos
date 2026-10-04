<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <i class="bi bi-collection-play text-primary"></i>
    <a href="<?= site_url('gimnasio') ?>" class="text-decoration-none text-muted fw-normal">Gimnasio</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Catálogo</strong>
    <span class="text-muted small fw-normal">(<?= count($filas) ?>)</span>
    <?php if ($filas): ?>
        <a href="<?= site_url('gimnasio/catalogo/vincular-lote') ?>" class="ms-auto small fw-normal text-decoration-none">
            <i class="bi bi-link-45deg"></i> Vincular mis ejercicios
        </a>
    <?php endif; ?>
</h5>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (!$filas): ?>
    <div class="card card-body text-center">
        <p class="mb-2">El catálogo está vacío.</p>
        <p class="text-muted small">Descarga los ~1.300 ejercicios de
            <a href="https://github.com/hasaneyldrm/exercises-dataset" target="_blank" rel="noopener">exercises-dataset</a>.
            No toca tus ejercicios.</p>
        <form action="<?= site_url('gimnasio/catalogo/importar') ?>" method="post" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn-primary"><i class="bi bi-cloud-download"></i> Importar catálogo</button>
        </form>
    </div>
<?php else: ?>

<div class="cat-filtros mb-2">
    <div class="ej-search flex-grow-1">
        <i class="bi bi-search"></i>
        <input type="text" id="catBuscador" class="form-control" placeholder="Buscar (sentadilla, remo mancuerna, curl…)" autofocus>
    </div>
    <select id="catParte" class="form-select">
        <option value="">Zona</option>
        <?php foreach (gim_catalogo_partes() as $k => $v): ?>
            <option value="<?= esc($k) ?>"><?= esc($v) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="catEquipo" class="form-select">
        <option value="">Material</option>
        <?php foreach (gim_catalogo_equipos() as $k => $v): ?>
            <option value="<?= esc($k) ?>"><?= esc($v) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="catVinculo" class="form-select">
        <option value="">Todos</option>
        <option value="1">Ya en mis ejercicios</option>
        <option value="0">No usados</option>
    </select>
</div>
<p class="text-muted small mb-2" id="catContador"></p>

<div id="catLista">
    <?php foreach ($filas as $c): ?>
        <?php $mios = $vinculos[$c['id']] ?? []; ?>
        <a href="<?= site_url('gimnasio/catalogo/' . $c['id']) ?>" class="cat-item"
           data-q="<?= esc(gim_catalogo_terminos($c)) ?>"
           data-parte="<?= esc($c['parte']) ?>" data-equipo="<?= esc($c['equipo']) ?>"
           data-vinculo="<?= $mios ? 1 : 0 ?>">
            <img src="<?= esc(gim_catalogo_media($c['imagen'])) ?>" loading="lazy" alt="" width="56" height="56">
            <div class="cat-item-body">
                <div class="cat-item-nombre"><?= esc(ucfirst($c['nombre'])) ?></div>
                <div class="cat-item-meta">
                    <?= esc(gim_catalogo_etiqueta('objetivo', $c['objetivo'])) ?> ·
                    <?= esc(gim_catalogo_etiqueta('equipo', $c['equipo'])) ?>
                </div>
                <?php if ($mios): ?>
                    <div class="cat-item-mio"><i class="bi bi-check-circle-fill"></i> <?= esc(implode(', ', $mios)) ?></div>
                <?php endif; ?>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<p class="text-muted small mt-3">
    Datos: <a href="https://github.com/hasaneyldrm/exercises-dataset" target="_blank" rel="noopener">exercises-dataset</a> (MIT) ·
    Animaciones © <a href="https://gymvisual.com/" target="_blank" rel="noopener">Gym visual</a>
</p>
<form action="<?= site_url('gimnasio/catalogo/importar') ?>" method="post" class="m-0"
      onsubmit="return confirm('¿Volver a descargar el catálogo? Solo actualiza el catálogo, no tus ejercicios.');">
    <?= csrf_field() ?>
    <button class="btn btn-link btn-sm text-muted p-0"><i class="bi bi-arrow-repeat"></i> Actualizar catálogo</button>
</form>

<style>
.cat-filtros { display: flex; flex-wrap: wrap; gap: 6px; }
.cat-filtros .form-select { width: auto; flex: 1 1 140px; }
.ej-search { position: relative; display: flex; align-items: center; min-width: 220px; flex: 3 1 260px; }
.ej-search i { position: absolute; left: 12px; color: var(--bs-secondary-color); }
.ej-search input { padding-left: 34px; }

#catLista { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 6px; }
.cat-item {
    display: flex; gap: 10px; align-items: center;
    padding: 6px; border: 1px solid var(--bs-border-color); border-radius: 10px;
    background: var(--bs-body-bg); color: inherit; text-decoration: none;
}
.cat-item:hover { background: var(--bs-tertiary-bg); }
.cat-item img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; background: #fff; flex: 0 0 auto; }
.cat-item-body { min-width: 0; }
.cat-item-nombre { font-size: .88rem; font-weight: 600; color: var(--bs-emphasis-color); }
.cat-item-meta { font-size: .75rem; color: var(--bs-secondary-color); }
.cat-item-mio { font-size: .75rem; color: var(--bs-success); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buscador = document.getElementById('catBuscador');
    const selParte = document.getElementById('catParte');
    const selEquipo = document.getElementById('catEquipo');
    const selVinculo = document.getElementById('catVinculo');
    const contador = document.getElementById('catContador');
    const items = Array.from(document.querySelectorAll('.cat-item'));

    const normaliza = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    function filtra() {
        // Todas las palabras deben aparecer (en inglés o en su traducción)
        const palabras = normaliza(buscador.value.trim()).split(/\s+/).filter(Boolean);
        const parte = selParte.value, equipo = selEquipo.value, vinculo = selVinculo.value;
        let n = 0;
        items.forEach(it => {
            const ok = (!parte || it.dataset.parte === parte)
                && (!equipo || it.dataset.equipo === equipo)
                && (vinculo === '' || it.dataset.vinculo === vinculo)
                && palabras.every(p => it.dataset.q.includes(p)
                    // Tolerar plurales: "sentadillas", "pliometricos"
                    || (p.length > 4 && p.endsWith('s') && it.dataset.q.includes(p.slice(0, -1))));
            it.style.display = ok ? '' : 'none';
            if (ok) n++;
        });
        contador.textContent = n + ' ejercicio' + (n === 1 ? '' : 's');
    }

    [buscador, selParte, selEquipo, selVinculo].forEach(el => el.addEventListener('input', filtra));
    filtra();
});
</script>

<?php endif; ?>

<?= $this->endSection() ?>
