<?php
    /**
     * Carta de colores de pistola del taller de sterclicks (solo lectura:
     * se edita en sterclicks, /admin/taller/colores). Ver Inventario::colores().
     */
    $fotoUrl = static fn (array $c): ?string => !empty($c['foto'])
        ? inventario_url('colores/' . (int) $c['id'] . '/foto') . '?v=' . rawurlencode($c['foto'])
        : null;
    // Texto claro u oscuro según lo claro que sea el color
    $tinta = static function (string $hex): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        [$r, $g, $b] = array_map('hexdec', str_split(str_pad(substr($hex, 0, 6), 6, '0'), 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#23242a' : '#ffffff';
    };
?>
<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Colores<?= $this->endSection() ?>

<?= $this->section('cabecera') ?>
<?php if ($colores !== []): ?>
<div class="inv-buscador" data-buscador-colores>
    <i class="bi bi-search"></i>
    <input type="search" placeholder="Alias, pintura o #hex…" value="<?= esc($q, 'attr') ?>"
        autocomplete="off" enterkeyhint="search" data-buscar>
    <button type="button" class="inv-limpiar" data-limpiar aria-label="Borrar" hidden><i class="bi bi-x-circle-fill"></i></button>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('contenido') ?>

<?php if ($desfasada): ?>
    <p class="inv-aviso"><i class="bi bi-cloud-slash"></i>
        No se ha podido conectar con sterclicks: es la última carta guardada<?= $generado ? ' (' . esc(date('d/m/Y H:i', strtotime($generado))) . ')' : '' ?>.</p>
<?php endif; ?>

<?php if ($sinCarta): ?>
    <div class="inv-vacio"><i class="bi bi-cloud-slash"></i><p>No se ha podido cargar la carta de colores de sterclicks.</p>
        <?php if (!empty($error)): ?><p><small>Motivo: <?= esc($error) ?></small></p><?php endif; ?></div>
<?php elseif ($colores === []): ?>
    <div class="inv-vacio"><i class="bi bi-palette"></i><p>La carta de colores está vacía.</p></div>
<?php else: ?>
    <p class="inv-contador" data-contador></p>

    <div class="inv-colores">
        <?php foreach ($colores as $c):
            $titulo = $c['alias'] !== '' ? $c['alias'] : $c['nombre'];
            $foto   = $fotoUrl($c);
        ?>
            <button type="button" class="inv-color" data-color
                data-buscable="<?= esc($c['alias'] . ' ' . $c['nombre'] . ' ' . $c['hex'], 'attr') ?>"
                data-hex="<?= esc($c['hex'], 'attr') ?>"
                data-tinta="<?= $tinta($c['hex']) ?>"
                data-titulo="<?= esc($titulo, 'attr') ?>"
                data-pintura="<?= esc($c['alias'] !== '' ? $c['nombre'] : '', 'attr') ?>"
                data-foto="<?= esc($foto ?? '', 'attr') ?>">
                <span class="inv-color-muestra" style="background: <?= esc($c['hex'], 'attr') ?>; color: <?= $tinta($c['hex']) ?>">
                    <?php if ($foto): ?>
                        <img src="<?= esc($foto, 'attr') ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <span class="inv-color-hex"><?= esc(strtoupper($c['hex'])) ?></span>
                </span>
                <strong><?= esc($titulo) ?></strong>
                <?php if ($c['alias'] !== ''): ?><span><?= esc($c['nombre']) ?></span><?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="inv-vacio" data-vacio hidden>
        <i class="bi bi-emoji-frown"></i>
        <p>No hay ningún color que coincida.</p>
    </div>

    <div class="inv-visor inv-visor-color" id="inv-visor-color" hidden>
        <button type="button" class="inv-visor-cerrar" data-color-cerrar aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
        <figure>
            <div class="inv-visor-color-muestra" data-color-muestra><span data-color-hex></span></div>
            <img src="" alt="" data-color-foto hidden>
            <figcaption>
                <strong data-color-titulo></strong>
                <span data-color-pintura></span>
            </figcaption>
        </figure>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    var colores = Array.prototype.slice.call(document.querySelectorAll('[data-color]'));
    if (!colores.length) return;

    // ---- Buscador: alias, nombre de la pintura o hex ----
    var buscador = document.querySelector('[data-buscador-colores]');
    var input    = buscador.querySelector('[data-buscar]');
    var limpiar  = buscador.querySelector('[data-limpiar]');
    var contador = document.querySelector('[data-contador]');
    var vacio    = document.querySelector('[data-vacio]');

    var normalizar = function (t) {
        return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    };
    colores.forEach(function (c) { c._texto = normalizar(c.dataset.buscable); });

    var aplicar = function () {
        var palabras = normalizar(input.value).split(/\s+/).filter(Boolean);
        var n = 0;
        colores.forEach(function (c) {
            var ver = palabras.every(function (w) { return c._texto.indexOf(w) !== -1; });
            c.hidden = !ver;
            if (ver) n++;
        });
        limpiar.hidden = input.value === '';
        vacio.hidden = n > 0;
        contador.textContent = n === 1 ? '1 color' : n + ' colores';

        var url = new URL(window.location.href);
        input.value ? url.searchParams.set('q', input.value) : url.searchParams.delete('q');
        history.replaceState(null, '', url);
    };
    input.addEventListener('input', aplicar);
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') input.blur(); });
    limpiar.addEventListener('click', function () { input.value = ''; aplicar(); input.focus(); });
    aplicar();

    // ---- Visor: el color en grande y la foto de ejemplo ----
    var visor   = document.getElementById('inv-visor-color');
    var muestra = visor.querySelector('[data-color-muestra]');
    var hex     = visor.querySelector('[data-color-hex]');
    var foto    = visor.querySelector('[data-color-foto]');
    var titulo  = visor.querySelector('[data-color-titulo]');
    var pintura = visor.querySelector('[data-color-pintura]');

    var cerrar = function () { visor.hidden = true; foto.src = ''; };

    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-color]');
        if (c) {
            muestra.style.background = c.dataset.hex;
            muestra.style.color = c.dataset.tinta;
            hex.textContent = c.dataset.hex.toUpperCase();
            titulo.textContent = c.dataset.titulo;
            pintura.textContent = c.dataset.pintura;
            pintura.hidden = c.dataset.pintura === '';
            foto.hidden = !c.dataset.foto;
            foto.src = c.dataset.foto || '';
            visor.hidden = false;
            return;
        }
        if (!visor.hidden && (e.target === visor || e.target.closest('[data-color-cerrar]'))) cerrar();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !visor.hidden) cerrar();
    });
})();
</script>
<?= $this->endSection() ?>
