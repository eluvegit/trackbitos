<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
helper('gimnasio');
$js = base_url('assets/js/gimnasio.js') . '?v=' . (@filemtime(FCPATH . 'assets/js/gimnasio.js') ?: 1);
$hoy = \CodeIgniter\I18n\Time::now('Europe/Madrid')->toDateString();
$porMes = [];
foreach ($entrenamientos as $e) {
    $porMes[substr($e['fecha'], 0, 7)][] = $e;
}
$meses = gim_meses();
$c = $constancia;
?>
<div class="fv gm">
    <?= view('gimnasio/_cabecera', ['activa' => 'entrenos', 'titulo' => 'Entrenos',
        'sub' => '<span>' . $c['total'] . ' sesiones</span><span class="fv-dot"></span><span>' . $c['este_anio'] . ' en ' . date('Y') . '</span>'
            . ($c['racha'] ? '<span class="fv-dot"></span><span><i class="bi bi-fire" style="color:var(--fv-accent)"></i> ' . $c['racha'] . ' sem seguidas</span>' : '')]) ?>

    <form method="post" action="<?= site_url('gimnasio/entrenamientos/crear') ?>" class="fv-panel" data-gm-nuevo style="grid-template-columns:minmax(0,1fr) auto;align-items:end;gap:10px">
        <div class="gm-campo">
            <label for="gmFechaNueva" data-gm-nuevo-txt>Nuevo entreno (o toca un día vacío del calendario)</label>
            <input type="date" id="gmFechaNueva" name="fecha" value="<?= $hoy ?>" class="fv-input" style="height:40px">
        </div>
        <button class="fv-btn fv-btn-pri" style="height:40px"><i class="bi bi-plus-lg"></i> Crear</button>
    </form>

    <form method="post" action="<?= site_url('gimnasio/entrenamientos/crear') ?>" class="fv-panel">
        <div class="fv-ph"><h3>Últimos dos meses</h3><span class="cuenta der">toca un día vacío para apuntarlo</span></div>
        <div class="gm-cals">
            <?= gim_calendario_mes($dias, date('Y-m', strtotime('first day of last month'))) ?>
            <?= gim_calendario_mes($dias, date('Y-m')) ?>
        </div>
    </form>

    <section class="fv-panel">
        <div class="fv-ph"><h3>Último año</h3></div>
        <?= gim_heatmap($dias, 53) ?>
        <div class="gm-leyenda">
            <?php foreach (gim_tipos() as $t => $col): ?><span><i style="--c:<?= $col ?>"></i><?= esc($t) ?></span><?php endforeach; ?>
        </div>
    </section>

    <section style="display:grid;gap:10px">
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <div class="gm-buscar" style="flex:1;min-width:200px"><i class="bi bi-search"></i><input type="search" id="gmBuscar" class="fv-input" placeholder="Buscar por ejercicio, tipo o nota…"></div>
            <div class="gm-chips" id="gmFiltroTipo">
                <button type="button" class="gm-chip on" data-tipo="">Todos</button>
                <?php foreach (gim_tipos() as $t => $col): ?>
                    <button type="button" class="gm-chip" style="--c:<?= $col ?>" data-tipo="<?= esc($t) ?>"><span class="pt"></span><?= esc($t) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!$entrenamientos): ?>
            <div class="gm-vacio"><i class="bi bi-calendar-plus"></i>Todavía no hay entrenamientos.</div>
        <?php endif; ?>

        <div id="gmLista">
            <?php foreach ($porMes as $ym => $lista): ?>
                <div class="gm-mesgrupo">
                    <div class="gm-mes"><?= $meses[(int) substr($ym, 5, 2)] ?> <?= substr($ym, 0, 4) ?> <span><?= count($lista) ?></span></div>
                    <div class="gm-filas">
                        <?php foreach ($lista as $e): ?>
                            <?php
                            $busqueda = gim_fecha_corta($e['fecha']) . ' ' . ($e['tipo_sesion'] ?? '') . ' ' . implode(' ', $e['_nombres']) . ' ' . ($e['notas_generales'] ?? '');
                            $resumen = $e['_nombres'] ? implode(' · ', $e['_nombres']) : 'Sin ejercicios';
                            ?>
                            <div class="gm-fila" data-q="<?= esc($busqueda) ?>" data-tipo="<?= esc($e['tipo_sesion'] ?? '') ?>">
                                <a class="gm-fecha" href="<?= site_url('gimnasio/entrenamientos/registro/' . $e['id']) ?>"><b><?= date('j', strtotime($e['fecha'])) ?></b><small><?= esc(mb_substr(gim_fecha_corta($e['fecha']), 0, 3)) ?></small></a>
                                <a class="pp" href="<?= site_url('gimnasio/entrenamientos/registro/' . $e['id']) ?>">
                                    <span class="t">
                                        <?= $e['fecha'] === $hoy ? 'Hoy' : esc(ucfirst(gim_hace($e['fecha']))) ?>
                                        <?php if (!empty($e['tipo_sesion'])): ?><span class="gm-tipo" style="--c:<?= gim_tipo_color($e['tipo_sesion']) ?>"><?= esc($e['tipo_sesion']) ?></span><?php endif; ?>
                                        <?php if (!empty($e['lesiones'])): ?><i class="bi bi-bandaid" style="color:var(--fv-warn);font-size:12px" title="<?= esc($e['lesiones']) ?>"></i><?php endif; ?>
                                    </span>
                                    <span class="d"><?= esc($resumen) ?></span>
                                    <?php if (!empty($e['notas_generales'])): ?><span class="d" style="font-style:italic">“<?= esc(mb_strimwidth($e['notas_generales'], 0, 140, '…')) ?>”</span><?php endif; ?>
                                </a>
                                <span class="n d-none d-sm-inline"><?= $e['_series'] ?> series<?= $e['_volumen'] > 0 ? '<br>' . gim_num($e['_volumen']) . ' kg' : '' ?></span>
                                <span class="acc">
                                    <button type="button" class="fv-ibtn" title="Resumen" data-gm-resumen="<?= site_url('gimnasio/entrenamientos/resumen/' . $e['id']) ?>?v=nueva" data-gm-abrir="<?= site_url('gimnasio/entrenamientos/registro/' . $e['id']) ?>" data-gm-titulo="<?= esc(ucfirst(gim_fecha_corta($e['fecha']))) ?>"><i class="bi bi-eye"></i></button>
                                    <form method="post" action="<?= site_url('gimnasio/entrenamientos/eliminar/' . $e['id']) ?>" data-confirm="¿Eliminar el entrenamiento del <?= esc(gim_fecha_corta($e['fecha'])) ?> con todas sus series?">
                                        <button class="fv-ibtn" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="fv-vacio" id="gmSinRes" hidden>Ningún entreno coincide.</p>
    </section>
</div>

<div class="modal fade" id="gmResumen" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Sesión</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" class="btn btn-primary" data-gm-abrir>Abrir sesión</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= $js ?>"></script>
<script>
(function () {
    const q = document.getElementById('gmBuscar');
    const filas = Array.from(document.querySelectorAll('#gmLista .gm-fila'));
    const grupos = Array.from(document.querySelectorAll('.gm-mesgrupo'));
    const sinRes = document.getElementById('gmSinRes');
    filas.forEach(f => { f._q = gmNormaliza(f.dataset.q); });
    let tipo = '';
    function filtrar() {
        const palabras = gmNormaliza(q.value.trim()).split(/\s+/).filter(Boolean);
        let total = 0;
        filas.forEach(f => {
            const ok = (!tipo || f.dataset.tipo === tipo) && palabras.every(p => f._q.includes(p));
            f.hidden = !ok;
            if (ok) total++;
        });
        grupos.forEach(g => { g.hidden = !g.querySelector('.gm-fila:not([hidden])'); });
        sinRes.hidden = total > 0;
    }
    q.addEventListener('input', filtrar);
    document.querySelectorAll('#gmFiltroTipo [data-tipo]').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#gmFiltroTipo [data-tipo]').forEach(x => x.classList.toggle('on', x === b));
        tipo = b.dataset.tipo;
        filtrar();
    }));
})();
</script>
<?= $this->endSection() ?>
