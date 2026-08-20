<?php
/**
 * El comportamiento del formulario de bitácora, en un solo sitio: lo cargan
 * la pantalla /bitacora/editar y la de Placas (para el modal). Va embebido
 * en el HTML y no en public/assets a propósito — el Hostinger sirve los
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

    // Un peso tal y como se teclea aquí: con coma o con punto.
    function aPeso(texto) {
        texto = (texto || '').toString().replace(',', '.').trim();
        if (texto === '') return null;
        var n = parseFloat(texto);
        return isNaN(n) ? null : n;
    }

    function duracion(minutos) {
        var h = Math.floor(minutos / 60), m = minutos % 60;
        return h ? (m ? h + ' h ' + m + ' min' : h + ' h') : m + ' min';
    }

    function gramos(n) {
        return (Math.round(n * 100) / 100).toString().replace('.', ',') + ' g';
    }

    function valor(form, nombre) {
        var campo = form.querySelector('[name="' + nombre + '"]');
        return campo ? campo.value : '';
    }

    /**
     * Lo que se deduce de lo tecleado, sin esperar al guardado: cuánta resina
     * se fue (los dos pesos), cuánto se pasó de lo prometido (los tres
     * relojes) y si el laminador acertó con la resina.
     */
    function recalcular(form) {
        var salida = form.querySelector('[data-calculado]');
        if (!salida) return;

        var partes = [];

        var antes = aPeso(valor(form, 'peso_antes'));
        var despues = aPeso(valor(form, 'peso_despues'));
        var estimadaResina = aPeso(valor(form, 'resina_estimada'));
        if (antes !== null && despues !== null) {
            var gastado = antes - despues;
            if (gastado > 0) {
                var texto = '<i class="bi bi-droplet"></i> Se fueron <strong>' + gramos(gastado) + '</strong>';
                if (estimadaResina !== null) {
                    var difR = gastado - estimadaResina;
                    texto += ' (el programa decía ' + gramos(estimadaResina) + ': '
                        + (difR >= 0 ? '+' : '−') + gramos(Math.abs(difR)) + ')';
                }
                partes.push(texto);
            } else {
                // Al revés no es un dato raro, es una errata al teclear — y
                // más vale decirlo ahora que guardar un número imposible.
                partes.push('<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> '
                    + 'El peso de después no puede ser mayor que el de antes</span>');
            }
        }

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

        var listaPruebas = form.querySelector('[data-lista-pruebas]');
        var botonPrueba = form.querySelector('[data-anadir-prueba]');
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

        var listaEnlaces = form.querySelector('[data-lista-enlaces]');
        var botonEnlace = form.querySelector('[data-anadir-enlace]');
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

        form.addEventListener('input', function () { recalcular(form); });
        recalcular(form);
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bitacora-form]').forEach(window.bitacoraIniciar);
    });
})();
</script>
