<?php if (empty($librosLeyendo)): ?>
    <div class="tb-vacio">Ningún libro en curso.</div>
<?php endif; ?>
<?php foreach ($librosLeyendo as $libro): ?>
    <a class="tb-fila" href="<?= site_url('reading/libro/' . $libro['id']) ?>">
        <span class="txt"><?= esc($libro['title']) ?><?php if ($libro['author'] !== ''): ?> <span class="sub">· <?= esc($libro['author']) ?></span><?php endif; ?></span>
        <?php if ($libro['progreso'] !== null): ?>
            <span class="tb-barrita"><i style="width:<?= (int) $libro['progreso'] ?>%"></i></span><span class="dato"><?= (int) $libro['progreso'] ?>%</span>
        <?php endif; ?>
    </a>
<?php endforeach; ?>
