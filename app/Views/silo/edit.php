<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-archive text-primary"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <a href="<?= site_url('silo/' . $pieza['id']) ?>" class="text-decoration-none text-muted fw-normal"><?= esc($pieza['id_negocio']) ?></a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Reclasificar</strong>
</h5>

<a href="<?= site_url('silo/' . $pieza['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<div class="alert alert-light border">
    <small class="text-muted d-block mb-1">Nombre de carpeta (histórico, no cambia al reclasificar):</small>
    <code><?= esc($pieza['nombre_carpeta']) ?></code>
</div>

<form method="post" action="<?= site_url('silo/' . $pieza['id'] . '/actualizar') ?>" style="max-width: 640px;" id="form-silo">
    <?= csrf_field() ?>

    <div class="mb-2">
        <label class="form-label">Categoría, evento, lugar, personas, tema...</label>
        <div id="trozos-lista" class="mb-2"></div>

        <div class="d-flex gap-2">
            <input type="text" class="form-control" id="nuevo-elemento" placeholder="añadir otro, separado por comas">
            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" id="btn-analizar">
                <i class="bi bi-magic"></i> Añadir
            </button>
        </div>
        <div class="form-text">Cada fila lleva un desplegable para decir qué es (incluida "Categoría", que solo puede haber una).</div>
    </div>

    <details class="mb-3">
        <summary class="text-muted small">Más detalles (opcional)</summary>
        <div class="row mt-2">
            <div class="col-sm-6 mb-3">
                <label for="tipo" class="form-label">Tipo</label>
                <input type="text" class="form-control" name="tipo" id="tipo" value="<?= esc($pieza['tipo'] ?? '') ?>">
            </div>
            <div class="col-sm-6 mb-3">
                <label for="fuente" class="form-label">Fuente</label>
                <input type="text" class="form-control" name="fuente" id="fuente" value="<?= esc($pieza['fuente'] ?? '') ?>">
            </div>
        </div>
        <div class="mb-3">
            <label for="notas" class="form-label">Notas</label>
            <textarea class="form-control" name="notas" id="notas" rows="2"><?= esc($pieza['notas'] ?? '') ?></textarea>
        </div>
    </details>

    <button type="submit" class="btn btn-primary">Guardar cambios</button>
</form>

<template id="tpl-trozo">
    <div class="trozo-row d-flex gap-2 align-items-center mb-1">
        <input type="hidden" name="elementos_texto[]" value="">
        <span class="flex-grow-1 texto-trozo"></span>
        <select name="elementos_tipo[]" class="form-select form-select-sm w-auto">
            <option value="categoria">Categoría</option>
            <option value="evento">Evento</option>
            <option value="lugar">Lugar</option>
            <option value="persona">Persona</option>
            <option value="tema" selected>Tema</option>
        </select>
        <button type="button" class="btn btn-sm btn-outline-danger btn-quitar-trozo">&times;</button>
    </div>
</template>

<script>
(function () {
    const lista = document.getElementById('trozos-lista');
    const tpl = document.getElementById('tpl-trozo');
    const elementosExistentes = <?= json_encode(array_merge(
        $categoria ? [['texto' => $categoria['nombre'], 'tipo' => 'categoria']] : [],
        array_map(fn ($a) => ['texto' => $a['nombre'], 'tipo' => $a['tipo']], $atributos)
    )) ?>;

    function trozosExistentes() {
        return Array.from(lista.querySelectorAll('.trozo-row input[name="elementos_texto[]"]')).map(i => i.value.toLowerCase());
    }

    function agregarTrozo(texto, tipo) {
        if (trozosExistentes().includes(texto.toLowerCase())) return;

        const nodo = tpl.content.cloneNode(true);
        const fila = nodo.querySelector('.trozo-row');
        fila.querySelector('input[name="elementos_texto[]"]').value = texto;
        fila.querySelector('.texto-trozo').textContent = texto;
        if (tipo) fila.querySelector('select').value = tipo;
        fila.querySelector('.btn-quitar-trozo').addEventListener('click', () => fila.remove());
        lista.appendChild(fila);
    }

    elementosExistentes.forEach(a => agregarTrozo(a.texto, a.tipo));

    document.getElementById('btn-analizar').addEventListener('click', () => {
        const input = document.getElementById('nuevo-elemento');
        const texto = input.value.trim();
        if (!texto) return;

        fetch('<?= site_url('silo/parsear-bloque') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>',
            },
            body: 'texto=' + encodeURIComponent(texto),
        })
            .then(r => r.json())
            .then(data => {
                (data.trozos || []).forEach(t => agregarTrozo(t.texto, t.sugerencia_tipo));
                input.value = '';
            });
    });
})();
</script>

<?= $this->endSection() ?>
