<?php
/**
 * El comportamiento del formulario de bitácora, en un solo sitio: lo carga
 * la pantalla /bitacora/editar (el modal de Placas es de solo lectura desde
 * la fase 48, sin formulario que iniciar). Va embebido en el HTML y no en
 * public/assets a propósito — el Hostinger sirve los
 * assets con una semana de caché y un arreglo aquí tardaría días en llegar
 * al navegador; el HTML no se cachea.
 *
 * Expone `window.bitacoraIniciar(form)`, que hay que llamar a mano cuando el
 * formulario llega por fetch (los <script> de un innerHTML no se ejecutan).
 * Los formularios que ya están en la página al cargar se enganchan solos.
 */
?>
<script>
(function () {
    // Espejo en JS de Web::aMinutos(): "2h 35", "2:35", "155" o "155 min".
    // Está duplicado a propósito —el servidor no puede fiarse de lo que
    // calcule el navegador— pero las dos versiones tienen que entender lo
    // mismo, o el número que se ve al teclear no será el que se guarde.
    function aMinutos(texto) {
        texto = (texto || '').toString().toLowerCase().trim();
        if (texto === '') return null;

        var conHoras = texto.match(/^(\d+)\s*(?::|h)\s*(\d*)/);
        if (conHoras) return parseInt(conHoras[1], 10) * 60 + (conHoras[2] === '' ? 0 : parseInt(conHoras[2], 10));

        var suelto = texto.match(/(\d+)/);
        return suelto ? parseInt(suelto[1], 10) : null;
    }

    function duracion(minutos) {
        var h = Math.floor(minutos / 60), m = minutos % 60;
        return h ? (m ? h + ' h ' + m + ' min' : h + ' h') : m + ' min';
    }

    function valor(form, nombre) {
        var campo = form.querySelector('[name="' + nombre + '"]');
        return campo ? campo.value : '';
    }

    /**
     * Lo que se deduce de lo tecleado, sin esperar al guardado: cuánto se
     * pasó de lo prometido, en los tres relojes.
     */
    function recalcular(form) {
        var salida = form.querySelector('[data-calculado]');
        if (!salida) return;

        var partes = [];

        var reales = aMinutos(valor(form, 'minutos_reales'));
        if (reales !== null) {
            [['minutos_estimados', 'programa'], ['minutos_previstos', 'máquina']].forEach(function (par) {
                var referencia = aMinutos(valor(form, par[0]));
                if (referencia === null) return;
                var dif = reales - referencia;
                partes.push('<i class="bi bi-clock"></i> ' + par[1] + ': '
                    + (dif === 0 ? 'clavado' : (dif > 0 ? '+' : '−') + duracion(Math.abs(dif))));
            });
        }

        salida.innerHTML = partes.join(' · ');
        salida.hidden = partes.length === 0;
    }

    /**
     * Estimación en vivo del tiempo de la placa a partir del número de
     * capas, con el mismo criterio que la calculadora del índice: el
     * min/capa sale de la referencia medida (minutos ÷ capas), nunca es una
     * constante suelta, y siempre se suman los minutos fijos de preparación.
     * Los tres números de calibración llegan en los data-* del form.
     */
    function estimarPorCapas(form) {
        var salida = form.querySelector('[data-estimado-capas]');
        if (!salida) return;

        var capas    = parseInt((valor(form, 'numero_capas') || '').replace(/[^\d]/g, ''), 10);
        var capasRef = parseFloat(form.dataset.calcCapasRef);
        var minRef   = parseFloat(form.dataset.calcMinutosRef);
        var minPrep  = parseFloat(form.dataset.calcMinutosPrep) || 0;

        if (!(capas > 0) || !(capasRef > 0) || !(minRef >= 0)) {
            salida.hidden = true;
            return;
        }

        var impresion = capas * (minRef / capasRef);
        var total     = impresion + minPrep;

        var ayuda = 'El laminador muestra el número de capas al abrir el archivo de la placa. '
            + 'La estimación usa el mismo criterio que la calculadora del índice: '
            + 'capas × minutos por capa (de la referencia medida) + minutos fijos de preparación.';

        salida.innerHTML = '<i class="bi bi-stopwatch"></i> Estimado por capas: ≈ <strong>'
            + duracion(Math.round(total)) + '</strong> ('
            + duracion(Math.round(impresion)) + ' de impresión + '
            + duracion(Math.round(minPrep)) + ' de preparación) '
            + '<i class="bi bi-info-circle" title="' + ayuda + '" style="cursor: help;"></i>';
        salida.hidden = false;
    }

    /**
     * Clonar la última fila en vez de construir el HTML aquí: así el marcado
     * vive solo en el PHP y no hay dos versiones que se desincronicen.
     */
    function anadirFila(lista, selector) {
        var filas = lista.querySelectorAll(selector);
        var nueva = filas[filas.length - 1].cloneNode(true);
        nueva.querySelectorAll('input, textarea').forEach(function (campo) { campo.value = ''; });
        nueva.querySelectorAll('[data-abrir-enlace]').forEach(function (boton) {
            boton.classList.add('disabled');
            boton.setAttribute('href', '#');
        });
        lista.appendChild(nueva);
        var primero = nueva.querySelector('input, textarea');
        if (primero) primero.focus();
        return nueva;
    }

    window.bitacoraIniciar = function (form) {
        if (!form || form.dataset.iniciado === '1') return;
        form.dataset.iniciado = '1';

        // A pantalla completa, piezas/pruebas/enlaces viven fuera de la
        // etiqueta <form> (asociados por el atributo `form="..."` de cada
        // campo, para poder meter la foto de la placa entre medias sin
        // anidar formularios) — pero sus botones siguen necesitando algo
        // que buscarlos. `raiz` es ese algo: el envoltorio que los junta a
        // todos, o el propio form si no existe (el modal, que no lo tiene).
        var raiz = form.closest('[data-bitacora-raiz]') || form;

        // El estado (Cómo salió) se ve como un único botón de color; al
        // pincharlo se abre el desplegable con las demás opciones.
        var veredictoBoton = form.querySelector('[data-veredicto-boton]');
        var veredictoInput = form.querySelector('[data-veredicto-input]');
        if (veredictoBoton && veredictoInput) {
            form.querySelectorAll('[data-veredicto-opcion]').forEach(function (opcion) {
                opcion.addEventListener('click', function () {
                    veredictoInput.value = opcion.dataset.valor;
                    veredictoBoton.textContent = opcion.textContent.trim();
                    veredictoBoton.className = 'btn btn-sm btn-' + opcion.dataset.color + ' dropdown-toggle';
                    veredictoInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        // Las notas de "soportes y pruebas" de cada pieza ocupan una sola
        // línea en reposo (no abultan la tabla) y se abren a varias líneas
        // en cuanto se pincha, para escribir sin apretarse — se vuelven a
        // encoger al salir del campo.
        raiz.querySelectorAll('[data-nota-expandible]').forEach(function (campo) {
            campo.addEventListener('focus', function () { campo.rows = 3; });
            campo.addEventListener('blur', function () { campo.rows = 1; });
        });

        // "Ahora" en la fecha de impresión: casi siempre se apunta al rato de
        // que la máquina pare, y escribir un datetime a mano es lo más
        // pesado de todo el formulario.
        form.querySelectorAll('[data-ahora]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var campo = form.querySelector('[name="impresa_en"]');
                if (!campo) return;
                var ahora = new Date();
                ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset());
                campo.value = ahora.toISOString().slice(0, 16);
                campo.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        var listaPruebas = raiz.querySelector('[data-lista-pruebas]');
        var botonPrueba = raiz.querySelector('[data-anadir-prueba]');
        if (listaPruebas && botonPrueba) {
            botonPrueba.addEventListener('click', function () { anadirFila(listaPruebas, '[data-prueba]'); });

            // Quitar es vaciar: una fila en blanco no se guarda. La última no
            // se va, para no dejar la sección sin sitio donde escribir.
            listaPruebas.addEventListener('click', function (e) {
                var boton = e.target.closest('[data-quitar-prueba]');
                if (!boton) return;
                var fila = boton.closest('[data-prueba]');
                if (listaPruebas.querySelectorAll('[data-prueba]').length > 1) {
                    fila.remove();
                } else {
                    fila.querySelectorAll('input, textarea').forEach(function (campo) { campo.value = ''; });
                }
            });
        }

        var listaEnlaces = raiz.querySelector('[data-lista-enlaces]');
        var botonEnlace = raiz.querySelector('[data-anadir-enlace]');
        if (listaEnlaces && botonEnlace) {
            botonEnlace.addEventListener('click', function () { anadirFila(listaEnlaces, '[data-enlace]'); });

            // El botón de abrir sigue a lo que se teclea: sin esto habría que
            // guardar y recargar para poder pinchar el enlace recién pegado.
            listaEnlaces.addEventListener('input', function (e) {
                var campo = e.target.closest('[name="enlace_url[]"]');
                if (!campo) return;
                var fila = campo.closest('[data-enlace]');
                var abrir = fila ? fila.querySelector('[data-abrir-enlace]') : null;
                if (!abrir) return;

                var url = campo.value.trim();
                // Se pega lo que haya en el portapapeles, y a veces viene sin
                // esquema; el servidor hace lo mismo al guardar.
                if (url !== '' && !/^https?:\/\//i.test(url)) url = 'https://' + url;
                abrir.setAttribute('href', url || '#');
                abrir.classList.toggle('disabled', url === '');
            });
        }

        // ---- Añadir/quitar piezas sueltas, sin borrar la placa (fase 44) --
        // El resto del formulario se guarda todo junto al pulsar "Guardar",
        // pero esto va contra el servidor al momento: son filas de verdad de
        // piezas_placas_versiones, no texto suelto que se pueda reescribir
        // entero como pruebas o enlaces.
        var placaId = form.dataset.placa;

        function cuerpoConToken(extra) {
            var body = new FormData();
            body.append(form.dataset.csrfName, form.dataset.csrfHash);
            Object.keys(extra || {}).forEach(function (clave) { body.append(clave, extra[clave]); });
            return body;
        }

        function llamada(url, extra) {
            return fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: cuerpoConToken(extra)
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok && d.ok, datos: d }; }); });
        }

        // Este formulario solo vive a pantalla completa (fase 48: el modal
        // de Placas es de solo lectura, sin edición), así que recargar tras
        // añadir/quitar una pieza es simplemente recargar la página — más
        // simple que reconstruir la tabla a mano, y sigue funcionando sin
        // JavaScript.
        function recargarFormulario() {
            window.location.reload();
        }

        var cajaBuscarPieza = raiz.querySelector('[data-buscar-pieza]');
        var resultadosPieza = raiz.querySelector('[data-resultados-pieza]');
        if (cajaBuscarPieza && resultadosPieza) {
            var esperaBusqueda = null;

            cajaBuscarPieza.addEventListener('input', function () {
                clearTimeout(esperaBusqueda);
                var q = cajaBuscarPieza.value.trim();
                if (q.length < 2) {
                    resultadosPieza.style.display = 'none';
                    resultadosPieza.innerHTML = '';
                    return;
                }

                esperaBusqueda = setTimeout(function () {
                    fetch('<?= site_url('piezas/pieza-buscar') ?>?q=' + encodeURIComponent(q), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            var lista = d.resultados || [];
                            resultadosPieza.innerHTML = lista.length
                                ? lista.map(function (p) {
                                    var texto = p.texto.replace(/&/g, '&amp;').replace(/</g, '&lt;');
                                    return '<button type="button" class="list-group-item list-group-item-action py-1 small" '
                                        + 'data-elegir-pieza data-version-id="' + p.version_id + '">' + texto + '</button>';
                                }).join('')
                                : '<div class="list-group-item py-1 small text-muted">Sin resultados</div>';
                            resultadosPieza.style.display = 'block';
                        });
                }, 250);
            });

            // Se cierra al perder el foco, con un respiro para que el click
            // sobre un resultado (que quita el foco del campo) llegue a
            // disparar su propio evento antes de que la lista desaparezca.
            cajaBuscarPieza.addEventListener('blur', function () {
                setTimeout(function () { resultadosPieza.style.display = 'none'; }, 200);
            });

            resultadosPieza.addEventListener('click', function (e) {
                var boton = e.target.closest('[data-elegir-pieza]');
                if (!boton) return;

                cajaBuscarPieza.disabled = true;
                llamada(
                    '<?= site_url('piezas/placa') ?>/' + placaId + '/pieza/agregar',
                    { version_id: boton.getAttribute('data-version-id'), cantidad: 1 }
                ).then(function (r) {
                    if (!r.ok) { cajaBuscarPieza.disabled = false; return; }
                    recargarFormulario();
                });
            });
        }

        raiz.addEventListener('click', function (e) {
            var boton = e.target.closest('[data-quitar-pieza]');
            if (!boton) return;
            if (!confirm('¿Quitar esta pieza de la placa?')) return;

            boton.disabled = true;
            llamada('<?= site_url('piezas/placa') ?>/' + placaId + '/pieza/' + boton.getAttribute('data-fila') + '/quitar', {})
                .then(function (r) {
                    if (!r.ok) { boton.disabled = false; return; }
                    recargarFormulario();
                });
        });

        form.addEventListener('input', function () { recalcular(form); estimarPorCapas(form); });
        recalcular(form);
        estimarPorCapas(form);
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bitacora-form]').forEach(window.bitacoraIniciar);
    });
})();
</script>
