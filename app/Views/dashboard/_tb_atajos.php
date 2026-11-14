<?php foreach ($enlacesRapidos as $e): ?>
    <a class="tb-fila" href="<?= site_url($e['ruta']) ?>"><i class="bi <?= esc($e['icono']) ?>"></i><span class="txt"><?= esc($e['titulo']) ?></span></a>
<?php endforeach; ?>
