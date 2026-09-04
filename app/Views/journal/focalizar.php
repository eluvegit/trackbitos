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
    // Todas las tareas con estrella, en orden del Journal.
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
    $enFoco = array_values(array_filter($filas, fn($f) => $f['en_foco']));
    ?>

    <!-- Pantalla 1: la lista del foco (solo lectura) -->
    <div id="focoView">
        <?php if (empty($enFoco)): ?>
            <p class="focalizar-empty" id="focoEmpty">Aún no has elegido ninguna tarea para el foco. Pulsa «Editar».</p>
        <?php endif; ?>
        <?php foreach ($enFoco as $f): ?>
            <div class="focalizar-row" data-task-id="<?= $f['id'] ?>">
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

        toggleBtn.addEventListener('click', function () {
            var editing = editView.hidden === false;
            if (editing) {
                rebuildFocoView();
                editView.hidden = true;
                focoView.hidden = false;
                toggleBtn.innerHTML = '<i class="bi bi-pencil"></i> Editar';
                toggleBtn.classList.remove('btn-primary');
                toggleBtn.classList.add('btn-outline-primary');
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

        function rebuildFocoView() {
            focoView.innerHTML = '';
            var checked = editView.querySelectorAll('.focalizar-check:checked');
            if (checked.length === 0) {
                var p = document.createElement('p');
                p.className = 'focalizar-empty';
                p.textContent = 'Aún no has elegido ninguna tarea para el foco. Pulsa «Editar».';
                focoView.appendChild(p);
                return;
            }
            checked.forEach(function (chk) {
                var row = chk.closest('.focalizar-row');
                var div = document.createElement('div');
                div.className = 'focalizar-row';
                div.dataset.taskId = row.dataset.taskId;

                var span = document.createElement('span');
                var cat = document.createElement('span');
                cat.className = 'cat';
                cat.textContent = row.dataset.cat;

                var a = document.createElement('a');
                a.href = row.dataset.url;
                a.textContent = row.dataset.title;

                span.appendChild(cat);
                span.appendChild(document.createTextNode(' — '));
                span.appendChild(a);
                div.appendChild(span);
                focoView.appendChild(div);
            });
        }
    })();
</script>

<?= $this->endSection() ?>
