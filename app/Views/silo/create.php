<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<h5 class="mb-3 d-flex align-items-center gap-2">
    <i class="bi bi-archive text-primary"></i>
    <a href="<?= site_url('silo') ?>" class="text-decoration-none text-muted fw-normal">Silo</a>
    <span class="text-muted">/</span>
    <strong class="fw-semibold">Nueva pieza</strong>
</h5>

<a href="<?= site_url('silo') ?>" class="btn btn-sm btn-outline-secondary rounded-pill mb-3">
    <i class="bi bi-chevron-left"></i> Volver
</a>

<form method="post" action="<?= site_url('silo/crear') ?>" style="max-width: 640px;" id="form-silo">
    <?= csrf_field() ?>

    <div class="mb-2">
        <label for="bloque_semantico" class="form-label">Fecha + categoría, evento, lugar, personas, tema...</label>
        <textarea class="form-control" name="bloque_semantico" id="bloque_semantico" rows="2"
                  placeholder="20260714 stock, sesion danza, arte por danza, lucia, marta"></textarea>
        <div class="form-text">
            Si empieza por una fecha (<code>AAAAMMDD</code>, <code>AAAAMM</code>, <code>AAAA</code> o <code>sinfecha</code>)
            se separa sola; si no, se asume sin fecha. Resto separado por comas: el primer trozo es la categoría,
            el resto tema por defecto — puedes afinarlo abajo. Si ya creaste la carpeta a mano, pega aquí el nombre tal
            cual (sin el ID).
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="btn-analizar">
        <i class="bi bi-magic"></i> Afinar clasificación (opcional)
    </button>

    <div id="trozos-lista" class="mb-3"></div>

    <details class="mb-3">
        <summary class="text-muted small">Más detalles (opcional)</summary>
        <div class="row mt-2">
            <div class="col-sm-6 mb-3">
                <label for="tipo" class="form-label">Tipo</label>
                <input type="text" class="form-control" name="tipo" id="tipo" placeholder="foto, vídeo, mixto, stock...">
            </div>
            <div class="col-sm-6 mb-3">
                <label for="fuente" class="form-label">Fuente</label>
                <input type="text" class="form-control" name="fuente" id="fuente">
            </div>
        </div>
        <div class="mb-3">
            <label for="notas" class="form-label">Notas</label>
            <textarea class="form-control" name="notas" id="notas" rows="2"></textarea>
        </div>
    </details>

    <div class="alert alert-light border">
        <small class="text-muted d-block mb-1">Nombre de carpeta (se crea a mano en disco):</small>
        <code id="preview-carpeta">(ID asignado al guardar) sinfecha sin_clasificar</code>
    </div>

    <button type="submit" class="btn btn-primary">Crear pieza</button>
</form>

<template id="tpl-trozo">
    <div class="trozo-row d-flex gap-2 align-items-center mb-1">
        <input type="hidden" name="elementos_texto[]" value="">
        <span class="flex-grow-1 texto-trozo"></span>
        <span class="badge text-bg-secondary badge-existe" hidden>ya existe</span>
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
    const btnAnalizar = document.getElementById('btn-analizar');
    const textarea = document.getElementById('bloque_semantico');
    const lista = document.getElementById('trozos-lista');
    const tpl = document.getElementById('tpl-trozo');

    /** Mismo criterio que SiloService::extraerFecha(): separa el token de fecha del principio, si lo hay. */
    function extraerFecha(texto) {
        texto = texto.replace(/^\s+/, '');
        const m = texto.match(/^(\d{8}|\d{6}|\d{4}|sinfecha)\b[\s,]*/i);
        if (!m) return { fechaTexto: 'sinfecha', resto: texto };

        const token = m[1].toLowerCase();
        const resto = texto.slice(m[0].length);
        return { fechaTexto: token === 'sinfecha' ? 'sinfecha' : token, resto };
    }

    function trozosExistentes() {
        return Array.from(lista.querySelectorAll('.trozo-row input[name="elementos_texto[]"]')).map(i => i.value.toLowerCase());
    }

    function hayCategoria() {
        return Array.from(lista.querySelectorAll('.trozo-row select')).some(s => s.value === 'categoria');
    }

    function agregarTrozo(texto, sugerenciaTipo, existe) {
        if (trozosExistentes().includes(texto.toLowerCase())) return;

        const esPrimero = lista.children.length === 0;
        const nodo = tpl.content.cloneNode(true);
        const fila = nodo.querySelector('.trozo-row');
        fila.querySelector('input[name="elementos_texto[]"]').value = texto;
        fila.querySelector('.texto-trozo').textContent = texto;
        if (existe) fila.querySelector('.badge-existe').hidden = false;

        const select = fila.querySelector('select');
        if (sugerenciaTipo) {
            select.value = sugerenciaTipo;
        } else if (esPrimero && !hayCategoria()) {
            select.value = 'categoria';
        }

        fila.querySelector('.btn-quitar-trozo').addEventListener('click', () => {
            fila.remove();
            actualizarPreview();
        });
        select.addEventListener('change', actualizarPreview);
        lista.appendChild(fila);
    }

    btnAnalizar.addEventListener('click', () => {
        const texto = textarea.value.trim();
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
                (data.trozos || []).forEach(t => agregarTrozo(t.texto, t.sugerencia_tipo, !!t.vocabulario_id));
                actualizarPreview();
            });
    });

    function actualizarPreview() {
        const { fechaTexto, resto } = extraerFecha(textarea.value);

        let categoria = 'sin_clasificar';
        const elementos = [];

        if (lista.children.length > 0) {
            // Ya se ha analizado: usar las filas confirmadas (con su tipo elegido).
            Array.from(lista.querySelectorAll('.trozo-row')).forEach(fila => {
                const texto = fila.querySelector('.texto-trozo').textContent;
                const tipo = fila.querySelector('select').value;
                if (tipo === 'categoria') categoria = texto;
                else elementos.push(texto);
            });
        } else {
            // Sin analizar todavía: mismo criterio que el fallback del servidor
            // (primer trozo tras la fecha = categoría, resto tal cual) para que
            // la vista previa sea fiel a lo que se guardaría si se envía ya.
            const trozos = resto.split(',').map(t => t.trim()).filter(t => t !== '');
            if (trozos.length) categoria = trozos[0];
            elementos.push(...trozos.slice(1));
        }

        let nombre = '(ID asignado al guardar) ' + fechaTexto + ' ' + categoria;
        if (elementos.length) nombre += ', ' + elementos.join(', ');

        document.getElementById('preview-carpeta').textContent = nombre;
    }

    textarea.addEventListener('input', actualizarPreview);
})();
</script>

<?= $this->endSection() ?>
