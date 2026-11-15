<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Recordatorios (estilo fv): la lista común, con los de Coche y Lentillas.
 *
 * @var array $recordatorios filas de RecordatorioServicio::lista()
 * @var array $categorias    [valor => [etiqueta, icono]]
 */
$total = count($recordatorios);
$cad   = count(array_filter($recordatorios, static fn ($r) => $r['nivel'] === 'caducado'));
$urg   = count(array_filter($recordatorios, static fn ($r) => $r['nivel'] === 'urgente'));
$porCat = array_count_values(array_column($recordatorios, 'categoria'));

$sub = ['<span>' . $total . ' ' . ($total === 1 ? 'recordatorio' : 'recordatorios') . '</span>'];
if ($cad) $sub[] = '<span class="e-bad">' . $cad . ' ' . ($cad === 1 ? 'caducado' : 'caducados') . '</span>';
if ($urg) $sub[] = '<span class="e-warn">' . $urg . ' en menos de un mes</span>';
?>
<div class="fv rc">
    <?= view('recordatorios/_cabecera', [
        'modulo'   => 'recordatorios',
        'titulo'   => 'Recordatorios',
        'sub'      => implode('<span class="fv-dot"></span>', $sub),
        'acciones' => '<a class="fv-btn fv-btn-pri" href="' . site_url('recordatorios/nuevo') . '"><i class="bi bi-plus-lg"></i>Nuevo</a>',
    ]) ?>

    <?php if (!$recordatorios): ?>
        <div class="fv-panel rc-vacio">
            <div>Todavía no hay recordatorios.</div>
            <div><a class="fv-btn fv-btn-pri" href="<?= site_url('recordatorios/nuevo') ?>"><i class="bi bi-plus-lg"></i>Crear el primero</a></div>
        </div>
    <?php else: ?>
        <div class="rc-herr">
            <input type="search" class="fv-input" id="rcBuscar" placeholder="Buscar…" autocomplete="off">
        </div>
        <nav class="rc-nav" id="rcCats">
            <button type="button" class="on" data-cat="">Todos <small><?= $total ?></small></button>
            <?php foreach ($categorias as $k => [$txt]): ?>
                <?php if (empty($porCat[$k])) continue; ?>
                <button type="button" data-cat="<?= $k ?>"><?= esc($txt) ?> <small><?= $porCat[$k] ?></small></button>
            <?php endforeach; ?>
        </nav>

        <?= view('recordatorios/_lista', ['recordatorios' => $recordatorios, 'conCategoria' => true]) ?>
        <div class="fv-vacio rc-vacio" id="rcNada" hidden>Ninguno coincide.</div>

        <p class="rc-info"><i class="bi bi-info-circle"></i><span>Los avisos de Coche y Lentillas son estos mismos: lo que cambies aquí cambia allí. <i class="bi bi-bell-slash"></i> = no avisa en el panel ni en Telegram.</span></p>
    <?php endif; ?>
</div>

<script>
(() => {
    const buscar = document.getElementById('rcBuscar');
    if (!buscar) return;
    const nav = document.getElementById('rcCats');
    let cat = '';
    const norm = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    function filtrar() {
        const q = norm(buscar.value.trim());
        let alguno = false;
        document.querySelectorAll('.rc-fila').forEach(f => {
            const ok = (!cat || f.dataset.categoria === cat) && (!q || norm(f.dataset.buscar).includes(q));
            f.hidden = !ok;
            if (ok) alguno = true;
        });
        document.getElementById('rcNada').hidden = alguno;
    }
    buscar.addEventListener('input', filtrar);
    nav.addEventListener('click', ev => {
        const b = ev.target.closest('button');
        if (!b) return;
        nav.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
        cat = b.dataset.cat;
        filtrar();
    });
})();
</script>
<?= $this->endSection() ?>
