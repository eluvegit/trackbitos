<?php
$enlace = $enlace ?? ['titulo' => '', 'url' => '', 'visto' => 0, 'relevancia' => 0, 'fecha' => date('Y-m-d'), 'extra' => ''];
$action = $action ?? site_url('enlaces/guardar');
$selCats = $selCats ?? [];
$selTagNames = $selTagNames ?? [];
?>
<form method="post" action="<?= $action ?>">
    <div class="row g-2">
        <div class="col-12">
            <label class="form-label">Título</label>
            <input type="text" class="form-control" name="titulo" required value="<?= esc($enlace['titulo']) ?>">
        </div>
        <div class="col-12">
            <label class="form-label">URL</label>
            <input type="text" class="form-control" name="url" required value="<?= esc($enlace['url']) ?>">
        </div>


        <div class="col-6 col-md-3">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" name="fecha" value="<?= esc($enlace['fecha']) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label d-block">Relevancia</label>
            <div id="stars" class="fs-5" style="cursor:pointer">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span data-v="<?= $i ?>" class="star<?= $i <= (int)$enlace['relevancia'] ? ' text-warning' : '' ?>">★</span>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="relevancia" id="relevancia" value="<?= (int)$enlace['relevancia'] ?>">
        </div>
        <div class="col-12 col-md-6 d-flex align-items-end">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="visto" id="visto" <?= $enlace['visto'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="visto">Marcado como visto</label>
            </div>
        </div>


        <div class="col-12 col-md-6">
            <label class="form-label">Categorías</label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($categorias as $c): $checked = in_array($c['id'], $selCats) ? 'checked' : ''; ?>
                    <label class="btn btn-sm btn-outline-secondary">
                        <input class="form-check-input me-1" type="checkbox" name="categorias[]" value="<?= $c['id'] ?>" <?= $checked ?>>
                        <?= esc($c['nombre']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label">Etiquetas (separadas por coma)</label>
            <input type="text" class="form-control" name="etiquetas" id="etiquetas" value="<?= esc(implode(',', $selTagNames)) ?>" placeholder="p.ej.: dev, IA, diseño">
            <small class="text-muted">Escribe y separa con coma. Se crearán si no existen.</small>
        </div>


        <div class="col-12">
            <label class="form-label">Extra (texto/URLs/markdown)</label>
            <textarea class="form-control" name="extra" rows="5" placeholder="Notas, más enlaces, imágenes..."><?= esc($enlace['extra']) ?></textarea>
        </div>


        <div class="col-12 d-flex gap-2 justify-content-end">
            <a href="<?= site_url('enlaces') ?>" class="btn btn-light">Cancelar</a>
            <button class="btn btn-primary">Guardar</button>
        </div>
    </div>
</form>


<script>
    // Estrellas
    const stars = document.querySelectorAll('#stars .star');
    stars.forEach(s => {
        s.addEventListener('click', () => {
            const v = parseInt(s.dataset.v);
            document.getElementById('relevancia').value = v;
            stars.forEach((x, i) => {
                x.classList.toggle('text-warning', i < v);
            });
        });
    });
</script>