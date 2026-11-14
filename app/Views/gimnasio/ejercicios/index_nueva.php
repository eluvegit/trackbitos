<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$porGrupo = [];
foreach ($ejercicios as $e) {
    $porGrupo[$e['grupo_muscular']][] = $e;
}
// Grupos en el orden de gim_grupos(); los desconocidos al final
$orden = array_flip(array_keys($grupoNombres));
uksort($porGrupo, fn ($a, $b) => ($orden[$a] ?? 99) <=> ($orden[$b] ?? 99));
$nPrincipales = count(array_filter($ejercicios, fn ($e) => !empty($e['principal'])));
$acciones = '<a class="fv-btn fv-btn-pri" href="' . site_url('gimnasio/ejercicios/create') . '"><i class="bi bi-plus-lg"></i> Nuevo</a>';
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'ejercicios', 'titulo' => 'Ejercicios', 'acciones' => $acciones,
        'sub' => '<span>' . count($ejercicios) . ' ejercicios</span><span class="fv-dot"></span><span><i class="bi bi-star-fill" style="color:var(--fv-warn)"></i> ' . $nPrincipales . ' principales (salen en Estadísticas)</span>']) ?>

    <div style="display:grid;gap:10px">
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <div class="gm-buscar" style="flex:1;min-width:220px"><i class="bi bi-search"></i><input type="search" id="gmBuscar" class="fv-input" placeholder="Buscar ejercicio…"></div>
            <div class="gm-chips">
                <button type="button" class="gm-chip" id="gmSoloUsados" title="Ocultar los que nunca has entrenado"><i class="bi bi-activity"></i> Solo usados</button>
                <button type="button" class="gm-chip" id="gmSoloPrinc" style="--c:var(--fv-warn)"><i class="bi bi-star-fill"></i> Principales</button>
            </div>
        </div>
        <div class="gm-chips" id="gmGrupos">
            <button type="button" class="gm-chip on" data-grupo="">Todos</button>
            <?php foreach ($porGrupo as $g => $lista): ?>
                <button type="button" class="gm-chip" data-grupo="<?= esc($g) ?>"><?= esc($grupoNombres[$g] ?? ucfirst((string) $g)) ?> <small><?= count($lista) ?></small></button>
            <?php endforeach; ?>
        </div>
    </div>

    <section class="fv-panel" style="padding-top:8px">
        <?php foreach ($porGrupo as $g => $lista): ?>
            <div class="gm-ejgrupo" data-grupo="<?= esc($g) ?>">
                <div class="gm-grupo-h">
                    <span class="nm"><?= esc($grupoNombres[$g] ?? ucfirst((string) $g)) ?></span>
                    <span class="ct"><?= count($lista) ?></span>
                    <span class="der"><a class="fv-ibtn" href="<?= site_url('gimnasio/ejercicios/create?grupo=' . urlencode((string) $g)) ?>" title="Añadir a <?= esc($grupoNombres[$g] ?? $g) ?>"><i class="bi bi-plus-lg"></i></a></span>
                </div>
                <div class="gm-filas">
                    <?php foreach ($lista as $e): $u = $uso[$e['id']] ?? null; ?>
                        <div class="gm-fila" data-q="<?= esc($e['nombre'] . ' ' . ($e['catalogo_nombre'] ?? '')) ?>" data-usado="<?= $u ? 1 : 0 ?>" data-princ="<?= !empty($e['principal']) ? 1 : 0 ?>">
                            <button type="button" class="gm-star<?= !empty($e['principal']) ? ' on' : '' ?>" data-gm-star="<?= site_url('gimnasio/ejercicios/principal/' . $e['id']) ?>" title="<?= !empty($e['principal']) ? 'Quitar de principales' : 'Marcar como principal (sale en Estadísticas)' ?>"><i class="bi <?= !empty($e['principal']) ? 'bi-star-fill' : 'bi-star' ?>"></i></button>
                            <?php if (!empty($e['catalogo_imagen'])): ?>
                                <img class="gm-mini" src="<?= esc(gim_catalogo_media($e['catalogo_imagen'])) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="gm-mini vacio"><i class="bi bi-activity"></i></span>
                            <?php endif; ?>
                            <a class="pp" href="<?= site_url('gimnasio/ejercicios/estadisticas/' . $e['id']) ?>">
                                <span class="t"><?= esc($e['nombre']) ?></span>
                                <span class="d"><?= $u ? $u['veces'] . ($u['veces'] == 1 ? ' vez' : ' veces') . ' · última ' . esc(gim_hace($u['ultima'])) : 'Sin usar' ?></span>
                            </a>
                            <span class="acc">
                                <?php if (!empty($e['catalogo_id'])): ?><a class="fv-ibtn" href="<?= site_url('gimnasio/catalogo/' . $e['catalogo_id']) ?>" title="Cómo se hace: <?= esc(ucfirst((string) $e['catalogo_nombre'])) ?>"><i class="bi bi-play-circle"></i></a><?php endif; ?>
                                <a class="fv-ibtn" href="<?= site_url('gimnasio/ejercicios/edit/' . $e['id']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= site_url('gimnasio/ejercicios/delete/' . $e['id']) ?>" data-confirm="¿Eliminar «<?= esc($e['nombre']) ?>»?<?= $u ? ' Se usa en ' . $u['veces'] . ' entrenos.' : '' ?>">
                                    <button class="fv-ibtn" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <p class="fv-vacio" id="gmSinRes" hidden>Ningún ejercicio coincide.</p>
    </section>
</div>
<script src="<?= $js ?>"></script>
<script>
(function () {
    const q = document.getElementById('gmBuscar');
    const filas = Array.from(document.querySelectorAll('.gm-ejgrupo .gm-fila'));
    const grupos = Array.from(document.querySelectorAll('.gm-ejgrupo'));
    const bUsados = document.getElementById('gmSoloUsados'), bPrinc = document.getElementById('gmSoloPrinc');
    filas.forEach(f => { f._q = gmNormaliza(f.dataset.q); });
    let grupo = '';
    try { bUsados.classList.toggle('on', localStorage.getItem('gm_solo_usados') === '1'); } catch (e) {}
    function filtrar() {
        const palabras = gmNormaliza(q.value.trim()).split(/\s+/).filter(Boolean);
        const soloUsados = bUsados.classList.contains('on') && !palabras.length;
        const soloPrinc = bPrinc.classList.contains('on');
        let total = 0;
        grupos.forEach(g => {
            let n = 0;
            const grupoOk = !grupo || g.dataset.grupo === grupo;
            g.querySelectorAll('.gm-fila').forEach(f => {
                const ok = grupoOk && (!soloUsados || f.dataset.usado === '1') && (!soloPrinc || f.querySelector('.gm-star.on'))
                    && palabras.every(p => f._q.includes(p));
                f.hidden = !ok;
                if (ok) n++;
            });
            g.hidden = n === 0;
            total += n;
        });
        document.getElementById('gmSinRes').hidden = total > 0;
    }
    q.addEventListener('input', filtrar);
    bUsados.addEventListener('click', () => { bUsados.classList.toggle('on'); try { localStorage.setItem('gm_solo_usados', bUsados.classList.contains('on') ? '1' : '0'); } catch (e) {} filtrar(); });
    bPrinc.addEventListener('click', () => { bPrinc.classList.toggle('on'); filtrar(); });
    document.querySelectorAll('#gmGrupos [data-grupo]').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#gmGrupos [data-grupo]').forEach(x => x.classList.toggle('on', x === b));
        grupo = b.dataset.grupo;
        filtrar();
    }));
    filtrar();
})();
</script>
<?= $this->endSection() ?>
