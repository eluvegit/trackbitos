<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$acciones = $filas ? '<a class="fv-gbtn" href="' . site_url('gimnasio/catalogo/vincular-lote') . '"><i class="bi bi-link-45deg"></i> Vincular mis ejercicios</a>' : '';
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'catalogo', 'titulo' => 'Catálogo', 'acciones' => $acciones,
        'sub' => '<span>' . count($filas) . ' ejercicios con animación e instrucciones</span><span class="fv-dot"></span><span>biblioteca aparte: no aparece al registrar salvo que lo añadas</span>']) ?>

    <?php if (!$filas): ?>
        <section class="fv-panel" style="text-align:center;justify-items:center">
            <i class="bi bi-cloud-download" style="font-size:28px;color:var(--fv-text-3)"></i>
            <p>El catálogo está vacío.</p>
            <p class="gm-ayuda">Descarga los ~1.300 ejercicios de <a href="https://github.com/hasaneyldrm/exercises-dataset" target="_blank" rel="noopener" style="color:var(--fv-accent)">exercises-dataset</a>. No toca tus ejercicios.</p>
            <form action="<?= site_url('gimnasio/catalogo/importar') ?>" method="post"><button class="fv-btn fv-btn-pri"><i class="bi bi-cloud-download"></i> Importar catálogo</button></form>
        </section>
    <?php else: ?>
        <div class="gm-cat-filtros">
            <div class="gm-buscar"><i class="bi bi-search"></i><input type="search" id="catQ" class="fv-input" placeholder="Buscar (sentadilla, remo mancuerna, curl…)" autocomplete="off"></div>
            <select id="catParte" class="gm-sel"><option value="">Zona</option><?php foreach (gim_catalogo_partes() as $k => $v): ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select>
            <select id="catEquipo" class="gm-sel"><option value="">Material</option><?php foreach (gim_catalogo_equipos() as $k => $v): ?><option value="<?= esc($k) ?>"><?= esc($v) ?></option><?php endforeach; ?></select>
            <select id="catVinculo" class="gm-sel"><option value="">Todos</option><option value="1">En mis ejercicios</option><option value="0">No usados</option></select>
        </div>
        <p class="gm-ayuda" id="catN"></p>
        <div class="gm-cat" id="catLista">
            <?php foreach ($filas as $c): $mios = $vinculos[$c['id']] ?? []; ?>
                <a class="gm-cat-it" href="<?= site_url('gimnasio/catalogo/' . $c['id']) ?>"
                   data-q="<?= esc(gim_catalogo_terminos($c)) ?>" data-parte="<?= esc($c['parte']) ?>" data-equipo="<?= esc($c['equipo']) ?>" data-vinculo="<?= $mios ? 1 : 0 ?>">
                    <span class="img"><img src="<?= esc(gim_catalogo_media($c['imagen'])) ?>" alt="" loading="lazy"><?php if ($mios): ?><span class="mio" title="<?= esc(implode(', ', $mios)) ?>"><i class="bi bi-check-lg"></i></span><?php endif; ?></span>
                    <span class="tx">
                        <span class="t"><?= esc(ucfirst($c['nombre'])) ?></span>
                        <span class="d"><?= esc(gim_catalogo_etiqueta('objetivo', $c['objetivo'])) ?> · <?= esc(gim_catalogo_etiqueta('equipo', $c['equipo'])) ?></span>
                        <?php if ($mios): ?><span class="d" style="color:var(--fv-ok)"><?= esc(implode(', ', $mios)) ?></span><?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="gm-ayuda" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            <span>Datos: <a href="https://github.com/hasaneyldrm/exercises-dataset" target="_blank" rel="noopener">exercises-dataset</a> (MIT) · Animaciones © <a href="https://gymvisual.com/" target="_blank" rel="noopener">Gym visual</a></span>
            <form action="<?= site_url('gimnasio/catalogo/importar') ?>" method="post" data-confirm="¿Volver a descargar el catálogo? Solo actualiza el catálogo, no tus ejercicios." class="m-0">
                <button class="fv-gbtn"><i class="bi bi-arrow-repeat"></i> Actualizar catálogo</button>
            </form>
        </div>
    <?php endif; ?>
</div>
<script src="<?= $js ?>"></script>
<?php if ($filas): ?>
<script>
(function () {
    const q = document.getElementById('catQ'), parte = document.getElementById('catParte'), equipo = document.getElementById('catEquipo'), vinculo = document.getElementById('catVinculo');
    const n = document.getElementById('catN');
    const items = Array.from(document.querySelectorAll('.gm-cat-it'));
    // Pintar solo los primeros: con 1.300 tarjetas a la vez el móvil sufre
    const LOTE = 120;
    let visibles = [], mostrados = LOTE;
    function pinta() {
        items.forEach(it => { it.hidden = true; });
        visibles.slice(0, mostrados).forEach(it => { it.hidden = false; });
        n.textContent = visibles.length + ' ejercicio' + (visibles.length === 1 ? '' : 's') + (visibles.length > mostrados ? ' · mostrando ' + mostrados : '');
    }
    function filtra() {
        const palabras = gmNormaliza(q.value.trim()).split(/\s+/).filter(Boolean);
        visibles = items.filter(it => (!parte.value || it.dataset.parte === parte.value)
            && (!equipo.value || it.dataset.equipo === equipo.value)
            && (vinculo.value === '' || it.dataset.vinculo === vinculo.value)
            && palabras.every(p => it.dataset.q.includes(p) || (p.length > 4 && p.endsWith('s') && it.dataset.q.includes(p.slice(0, -1)))));
        mostrados = LOTE;
        pinta();
    }
    [q, parte, equipo, vinculo].forEach(el => el.addEventListener('input', filtra));
    window.addEventListener('scroll', () => {
        if (mostrados < visibles.length && window.innerHeight + window.scrollY > document.body.scrollHeight - 800) { mostrados += LOTE; pinta(); }
    }, { passive: true });
    filtra();
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
