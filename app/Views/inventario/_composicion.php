<?php if (!empty($p['partes'])): ?>
<ul class="inv-composicion" title="Partes que componen la pieza">
    <li><i class="bi bi-puzzle"></i></li>
    <?php foreach ($p['partes'] as $nombre): ?><li><?= esc($nombre) ?></li><?php endforeach; ?>
</ul>
<?php endif; ?>
