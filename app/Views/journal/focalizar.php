<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
    .focalizar-wrap {
        max-width: 640px;
        margin: 0 auto;
        font-size: .9rem;
    }

    .focalizar-row {
        display: flex;
        align-items: baseline;
        gap: .5rem;
        padding: .3rem 0;
        border-bottom: 1px solid #2b2b2b;
    }

    .focalizar-row input[type="checkbox"] {
        flex: 0 0 auto;
        width: 1rem;
        height: 1rem;
        cursor: pointer;
    }

    .focalizar-row .cat {
        color: #888;
    }

    .focalizar-row a {
        color: inherit;
        text-decoration: none;
    }

    .focalizar-row a:hover {
        text-decoration: underline;
    }

    /* En modo edición, las que no están en foco se atenúan */
    #editView .focalizar-row:not(.en-foco) {
        opacity: .55;
    }

    .focalizar-empty {
        color: #888;
        padding: .3rem 0;
    }

    #focoView .focalizar-row {
        cursor: grab;
    }

    #focoView .focalizar-row .drag-handle {
        color: #666;
        flex: 0 0 auto;
    }

    #focoView .focalizar-row.dragging {
        opacity: .4;
    }
</style>

<div class="focalizar-wrap">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-1">
        <h3 class="mb-0" style="line-height:1;">Focalizar</h3>
        <div class="d-flex gap-1">
            <button type="button" id="toggleEditBtn" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <a href="<?= site_url('journal') ?>" class="btn btn-outline-secondary btn-sm" title="Volver al Journal">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>

    <?php
    // Todas las tareas con estrella, en orden del Journal (para pantalla 2).
    $filas = [];
    foreach ($starredByCategory as $catName => $tareas) {
        foreach ($tareas as $t) {
            $filas[] = [
                'id'      => (int) $t['id'],
                'cat'     => $catName,
                'title'   => $t['title'],
                'en_foco' => !empty($t['en_foco']),
            ];
        }
    }
    ?>

    <!-- Pantalla 1: la lista del foco, en orden manual (drag & drop) -->
    <div id="focoView">
        <?php if (empty($enFocoOrdered)): ?>
            <p class="focalizar-empty" id="focoEmpty">Aún no has elegido ninguna tarea para el foco. Pulsa «Editar».</p>
        <?php endif; ?>
        <?php foreach ($enFocoOrdered as $f): ?>
            <div class="focalizar-row" data-task-id="<?= (int) $f['id'] ?>" draggable="true">
                <i class="bi bi-grip-vertical drag-handle"></i>
                <span>
                    <span class="cat"><?= esc($f['cat']) ?></span> &mdash;
                    <a href="<?= site_url('journal/edit/' . $f['id']) ?>"><?= esc($f['title']) ?></a>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pantalla 2: editar la selección sobre TODAS las estrelladas -->
    <div id="editView" hidden>
        <?php if (empty($filas)): ?>
            <p class="focalizar-empty">No hay ninguna tarea con estrella en el Journal.</p>
        <?php else: ?>
            <?php foreach ($filas as $f): ?>
                <div class="focalizar-row<?= $f['en_foco'] ? ' en-foco' : '' ?>"
                     data-task-id="<?= $f['id'] ?>"
                     data-cat="<?= esc($f['cat'], 'attr') ?>"
                     data-title="<?= esc($f['title'], 'attr') ?>"
                     data-url="<?= site_url('journal/edit/' . $f['id']) ?>">
                    <input type="checkbox" class="focalizar-check" <?= $f['en_foco'] ? 'checked' : '' ?>>
                    <span>
                        <span class="cat"><?= esc($f['cat']) ?></span> &mdash;
                        <a href="<?= site_url('journal/edit/' . $f['id']) ?>"><?= esc($f['title']) ?></a>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    (function () {
        var toggleBtn = document.getElementById('toggleEditBtn');
        var focoView  = document.getElementById('focoView');
        var editView  = document.getElementById('editView');
        var toggleUrl = '<?= site_url('journal/focalizar/toggle') ?>';
        var ordenUrl  = '<?= site_url('journal/focalizar/orden') ?>';

        toggleBtn.addEventListener('click', function () {
            var editing = editView.hidden === false;
            if (editing) {
                // La selección puede haber cambiado; recargamos para que la
                // lista del foco refleje el orden real guardado en servidor
                // (altas nuevas al final, bajas fuera) en vez de duplicar esa
                // lógica en JS.
                window.location.reload();
            } else {
                focoView.hidden = true;
                editView.hidden = false;
                toggleBtn.innerHTML = '<i class="bi bi-check-lg"></i> Hecho';
                toggleBtn.classList.remove('btn-outline-primary');
                toggleBtn.classList.add('btn-primary');
            }
        });

        editView.querySelectorAll('.focalizar-check').forEach(function (chk) {
            chk.addEventListener('change', function () {
                var row = chk.closest('.focalizar-row');
                chk.disabled = true;

                fetch(toggleUrl + '/' + row.dataset.taskId, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            chk.checked = !chk.checked;
                            alert(data.error || 'No se pudo actualizar.');
                            return;
                        }
                        row.classList.toggle('en-foco', data.en_foco === 1);
                    })
                    .catch(function () {
                        chk.checked = !chk.checked;
                        alert('Error de conexión.');
                    })
                    .finally(function () {
                        chk.disabled = false;
                    });
            });
        });

        // Drag & drop para reordenar la lista del foco a criterio del
        // usuario. Al soltar, se guarda el orden completo en el servidor
        // (foco_orden = posición en la lista).
        var dragEl = null;

        focoView.addEventListener('dragstart', function (e) {
            var row = e.target.closest('.focalizar-row');
            if (!row) return;
            dragEl = row;
            row.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        focoView.addEventListener('dragover', function (e) {
            e.preventDefault();
            var row = e.target.closest('.focalizar-row');
            if (!row || row === dragEl || !dragEl) return;
            var rect = row.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2;
            focoView.insertBefore(dragEl, before ? row : row.nextSibling);
        });

        focoView.addEventListener('dragend', function () {
            if (!dragEl) return;
            dragEl.classList.remove('dragging');
            dragEl = null;
            guardarOrden();
        });

        function guardarOrden() {
            var ids = Array.prototype.map.call(
                focoView.querySelectorAll('.focalizar-row'),
                function (row) { return parseInt(row.dataset.taskId, 10); }
            );
            fetch(ordenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ order: ids })
            });
        }
    })();
</script>

<?= $this->endSection() ?>
