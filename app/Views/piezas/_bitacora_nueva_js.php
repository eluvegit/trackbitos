<script>
(function () {
    var raiz = document.getElementById('bt');
    var form = document.getElementById('<?= $idForm ?>');
    if (!raiz || !form) return;
    var placaId = '<?= (int) $idPlaca ?>';
    var URL_PLACA = '<?= site_url('piezas/placa') ?>/' + placaId;
    var indicador = document.getElementById('btGuardado');

    function campo(nombre) { return form.elements.namedItem(nombre); }
    function valor(nombre) { var c = campo(nombre); return c && c.value !== undefined ? c.value : ''; }

    function aMinutos(texto) {
        texto = (texto || '').toString().toLowerCase().trim();
        if (texto === '') return null;
        var conHoras = texto.match(/^(\d+)\s*(?::|h)\s*(\d*)/);
        if (conHoras) return parseInt(conHoras[1], 10) * 60 + (conHoras[2] === '' ? 0 : parseInt(conHoras[2], 10));
        var suelto = texto.match(/(\d+)/);
        return suelto ? parseInt(suelto[1], 10) : null;
    }
    function duracion(m) { var h = Math.floor(m / 60), r = m % 60; return h ? (r ? h + ' h ' + r : h + ' h') : r + ' min'; }

    function pintarIndicador(estado, texto) {
        if (!indicador) return;
        indicador.className = 'fv-estado ' + ({ ok: 'e-ok', guardando: 'e-warn', error: 'e-bad', pendiente: 'e-neu' }[estado] || 'e-neu');
        indicador.textContent = texto;
    }

    var guardando = false, otraVez = false, trasGuardar = null;
    function guardar(alTerminar) {
        if (alTerminar) trasGuardar = alTerminar;
        if (guardando) { otraVez = true; return; }
        guardando = true;
        pintarIndicador('guardando', 'Guardando…');
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: new FormData(form)
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok && d.ok, datos: d }; }); })
            .then(function (r) {
                if (!r.ok) {
                    pintarIndicador('error', (r.datos && r.datos.mensaje) || 'No se pudo guardar');
                    return;
                }
                var hora = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                pintarIndicador('ok', 'Guardado a las ' + hora);
                if (r.datos.nombre && campo('nombre') && document.activeElement !== campo('nombre')) campo('nombre').value = r.datos.nombre;
                var panelReparto = raiz.querySelector('[data-panel-reparto]');
                if (panelReparto && r.datos.repartoHtml !== undefined) panelReparto.outerHTML = r.datos.repartoHtml;
                if (trasGuardar && !otraVez) { var f = trasGuardar; trasGuardar = null; f(); }
            })
            .catch(function () { pintarIndicador('error', 'Sin conexión: no se ha guardado'); })
            .finally(function () {
                guardando = false;
                if (otraVez) { otraVez = false; guardar(); }
            });
    }

    function esDelFormulario(el) { return el && el.form === form; }
    document.addEventListener('change', function (e) {
        if (!esDelFormulario(e.target)) return;
        if (e.target.matches('[data-resina-select]') && e.target.value === 'nueva') return;
        guardar();
    });
    document.addEventListener('input', function (e) {
        if (!esDelFormulario(e.target)) return;
        pintarIndicador('pendiente', 'Cambios sin guardar');
        recalcularTodo();
    });
    window.addEventListener('beforeunload', function (e) {
        if (indicador && /sin guardar|Guardando/.test(indicador.textContent)) { e.preventDefault(); e.returnValue = ''; }
    });

    raiz.addEventListener('click', function (e) {
        var b = e.target.closest('[data-stp]');
        if (!b) return;
        var input = b.parentNode.querySelector('input');
        var min = parseInt(input.min || '0', 10), max = parseInt(input.max || '99', 10);
        input.value = Math.min(max, Math.max(min, (parseInt(input.value, 10) || 0) + parseInt(b.getAttribute('data-stp'), 10)));
        recalcularTodo();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    function recalcularServibles() {
        var copias = 0, fallidas = 0;
        raiz.querySelectorAll('[data-fila-pieza]').forEach(function (fila) {
            var c = Math.max(0, parseInt(fila.querySelector('[data-fila-copias]').value, 10) || 0);
            var f = Math.min(c, Math.max(0, parseInt(fila.querySelector('[data-fila-fallidas]').value, 10) || 0));
            fila.querySelector('[data-fila-servibles]').textContent = c - f;
            copias += c; fallidas += f;
        });
        var serv = Math.max(0, copias - fallidas);
        raiz.querySelectorAll('[data-total-copias]').forEach(function (el) { el.textContent = copias; });
        raiz.querySelectorAll('[data-total-servibles], [data-eco-servibles]').forEach(function (el) { el.textContent = serv; });
        raiz.querySelectorAll('[data-total-fallidas], [data-eco-fallidas]').forEach(function (el) { el.textContent = fallidas; });
        raiz.querySelectorAll('[data-barra-serv]').forEach(function (b) {
            b.children[0].style.width = (copias ? serv * 100 / copias : 0) + '%';
            b.children[1].style.width = (copias ? fallidas * 100 / copias : 0) + '%';
        });
    }

    function estimarPorCapas() {
        var salida = raiz.querySelector('[data-estimado-capas]');
        if (!salida) return null;
        var capas = parseInt((valor('numero_capas') || '').replace(/[^\d]/g, ''), 10);
        var capasRef = parseFloat(form.dataset.calcCapasRef), minRef = parseFloat(form.dataset.calcMinutosRef);
        var minPrep = parseFloat(form.dataset.calcMinutosPrep) || 0;
        if (!(capas > 0) || !(capasRef > 0) || !(minRef >= 0)) { salida.textContent = ''; return null; }
        var total = Math.round(capas * (minRef / capasRef) + minPrep);
        salida.textContent = '· ≈ ' + duracion(total);
        salida.title = 'Capas × minutos por capa de la calculadora + ' + duracion(Math.round(minPrep)) + ' de preparación';
        return total;
    }

    function diferencias(porCapas) {
        var salida = raiz.querySelector('[data-calculado]');
        if (!salida) return;
        var reales = aMinutos(valor('minutos_reales'));
        if (reales === null) { salida.textContent = ''; return; }
        var partes = [];
        [['minutos_estimados', 'programa'], ['minutos_previstos', 'máquina']].forEach(function (par) {
            var ref = aMinutos(valor(par[0]));
            if (ref === null) return;
            var d = reales - ref;
            partes.push((d === 0 ? 'clavado' : (d > 0 ? '+' : '−') + duracion(Math.abs(d))) + ' sobre ' + par[1]);
        });
        if (!partes.length && porCapas) {
            var d2 = reales - porCapas;
            partes.push((d2 === 0 ? 'clavado' : (d2 > 0 ? '+' : '−') + duracion(Math.abs(d2))) + ' sobre capas');
        }
        salida.textContent = partes.length ? '· ' + partes.join(' · ') : '';
    }

    function recalcularTodo() { recalcularServibles(); diferencias(estimarPorCapas()); }

    var momentoActual = raiz.getAttribute('data-vista-momento');
    function ponerMomento(m) {
        momentoActual = m;
        raiz.setAttribute('data-vista-momento', m);
        raiz.querySelectorAll('.bt-momentos [data-momento]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-momento') === m ? 'true' : 'false'); });
    }
    raiz.querySelectorAll('.bt-momentos [data-momento]').forEach(function (b) {
        b.addEventListener('click', function () { ponerMomento(b.getAttribute('data-momento')); });
    });
    raiz.querySelectorAll('[data-ir-momento]').forEach(function (b) {
        b.addEventListener('click', function () {
            ponerMomento(b.getAttribute('data-ir-momento'));
            raiz.querySelector('.bt-momentos').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    ponerMomento(momentoActual);

    var veredictoInput = form.querySelector('[data-veredicto-input]');
    raiz.addEventListener('click', function (e) {
        var b = e.target.closest('[data-veredicto]');
        if (!b) return;
        var anterior = veredictoInput.value;
        veredictoInput.value = b.getAttribute('data-veredicto');
        raiz.querySelectorAll('.bt-seg-ver [data-veredicto]').forEach(function (x) { x.classList.toggle('on', x.getAttribute('data-veredicto') === veredictoInput.value); });
        if (anterior === veredictoInput.value) return;
        guardar(function () { location.reload(); });
    });

    var botonAhora = raiz.querySelector('[data-ahora]');
    function fechaLocal() {
        var d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    }
    if (botonAhora) botonAhora.addEventListener('click', function () {
        campo('impresa_en').value = fechaLocal();
        guardar();
    });
    var puesta = raiz.querySelector('[data-puesta-ahora]');
    if (puesta) puesta.addEventListener('click', function () {
        puesta.disabled = true;
        campo('impresa_en').value = fechaLocal();
        guardar(function () { location.reload(); });
    });

    var irInv = raiz.querySelector('[data-ir-inventario]');
    if (irInv) irInv.addEventListener('click', function () {
        ponerMomento('despues');
        document.getElementById('inventario').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    var selResina = raiz.querySelector('[data-resina-select]');
    var resinaNueva = raiz.querySelector('[data-resina-nueva]');
    if (selResina) selResina.addEventListener('change', function () {
        resinaNueva.classList.toggle('d-none', selResina.value !== 'nueva');
        if (selResina.value === 'nueva') resinaNueva.focus();
    });

    var copiar = raiz.querySelector('[data-copiar-nombre]');
    if (copiar) copiar.addEventListener('click', function () {
        var texto = campo('nombre').value;
        var hecho = function () { copiar.innerHTML = '<i class="bi bi-check2"></i> Copiado'; setTimeout(function () { copiar.innerHTML = '<i class="bi bi-clipboard"></i> Copiar'; }, 1400); };
        if (navigator.clipboard) navigator.clipboard.writeText(texto).then(hecho).catch(function () { campo('nombre').select(); });
        else { campo('nombre').select(); }
    });

    var grupoImp = raiz.querySelector('[data-impresora-placa]');
    var estadoImp = raiz.querySelector('[data-impresora-estado]');
    var desbloqueado = form.querySelector('[data-desbloqueado]');
    var candado = raiz.querySelector('[data-candado]');
    if (candado) candado.addEventListener('click', function () {
        if (!confirm('Esta placa ya se envió. ¿Desbloquear la máquina y el nombre?\n\nSi los cambias, avisa a quien la vaya a poner.')) return;
        desbloqueado.value = '1';
        grupoImp.classList.remove('bloq');
        grupoImp.querySelectorAll('[data-candado-icono]').forEach(function (i) { i.className = 'bi bi-unlock'; });
        grupoImp.querySelectorAll('[name="impresora"]').forEach(function (r) { r.disabled = false; });
        campo('nombre').readOnly = false;
        campo('nombre').removeAttribute('title');
        candado.innerHTML = '<i class="bi bi-unlock-fill"></i>';
        candado.title = 'Desbloqueada: ya puedes cambiar nombre y máquina';
        candado.disabled = true;
        var est = raiz.querySelector('.bt-env-est');
        if (est) { est.classList.remove('ok'); est.classList.add('abierta'); }
    });
    if (grupoImp) grupoImp.addEventListener('change', function (e) {
        var radio = e.target.closest('[name="impresora"]');
        if (!radio) return;
        e.stopPropagation();
        var datos = new FormData();
        datos.append('impresora', radio.value);
        datos.append('nombre', campo('nombre').value);
        if (desbloqueado) datos.append('desbloqueado', desbloqueado.value);
        datos.append(form.dataset.csrfName, form.dataset.csrfHash);
        if (estadoImp) estadoImp.textContent = '· guardando…';
        fetch(grupoImp.getAttribute('data-url'), { method: 'POST', body: datos, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j.ok && j.nombre) campo('nombre').value = j.nombre;
                if (estadoImp) estadoImp.textContent = j.ok ? '· guardada' : '· ' + (j.mensaje || 'no se pudo');
            })
            .catch(function () { if (estadoImp) estadoImp.textContent = '· no se pudo guardar'; });
    }, true);

    function anadirFila(lista, selector) {
        var filas = lista.querySelectorAll(selector);
        var nueva = filas[filas.length - 1].cloneNode(true);
        nueva.querySelectorAll('input, textarea').forEach(function (c) { c.value = ''; });
        nueva.querySelectorAll('[data-abrir-enlace]').forEach(function (a) { a.classList.add('apagado'); a.setAttribute('href', '#'); });
        lista.appendChild(nueva);
        var primero = nueva.querySelector('input, textarea');
        if (primero) primero.focus();
    }
    var listaPruebas = raiz.querySelector('[data-lista-pruebas]');
    raiz.querySelector('[data-anadir-prueba]').addEventListener('click', function () { anadirFila(listaPruebas, '[data-prueba]'); });
    listaPruebas.addEventListener('click', function (e) {
        var b = e.target.closest('[data-quitar-prueba]');
        if (!b) return;
        var fila = b.closest('[data-prueba]');
        var tieneTexto = Array.prototype.some.call(fila.querySelectorAll('input, textarea'), function (c) { return c.value.trim() !== ''; });
        if (tieneTexto && !confirm('¿Quitar esta pregunta y su respuesta?')) return;
        if (listaPruebas.querySelectorAll('[data-prueba]').length > 1) fila.remove();
        else fila.querySelectorAll('input, textarea').forEach(function (c) { c.value = ''; });
        guardar();
    });

    var panelEnlaces = raiz.querySelector('[data-enlaces-panel]');
    var listaEnlaces = raiz.querySelector('[data-lista-enlaces]');
    raiz.querySelector('[data-toggle-enlaces]').addEventListener('click', function () {
        panelEnlaces.classList.toggle('d-none');
        if (!panelEnlaces.classList.contains('d-none')) {
            var vacio = Array.prototype.find.call(listaEnlaces.querySelectorAll('[name="enlace_url[]"]'), function (c) { return c.value.trim() === ''; });
            if (vacio) vacio.focus();
        }
    });
    raiz.querySelector('[data-anadir-enlace]').addEventListener('click', function () { anadirFila(listaEnlaces, '[data-enlace]'); });
    function refrescarDrive() {
        var urls = Array.prototype.map.call(listaEnlaces.querySelectorAll('[name="enlace_url[]"]'), function (c) { return c.value.trim(); }).filter(Boolean);
        var drive = raiz.querySelector('[data-drive-abrir]');
        var primera = urls[0] || '';
        if (primera && !/^https?:\/\//i.test(primera)) primera = 'https://' + primera;
        drive.classList.toggle('d-none', !primera);
        drive.setAttribute('href', primera || '#');
        raiz.querySelector('[data-enlaces-cuenta]').textContent = urls.length || '';
    }
    listaEnlaces.addEventListener('input', function (e) {
        var c = e.target.closest('[name="enlace_url[]"]');
        if (!c) return;
        var abrir = c.closest('[data-enlace]').querySelector('[data-abrir-enlace]');
        var url = c.value.trim();
        if (url !== '' && !/^https?:\/\//i.test(url)) url = 'https://' + url;
        abrir.setAttribute('href', url || '#');
        abrir.classList.toggle('apagado', url === '');
        refrescarDrive();
    });

    function llamada(url, extra) {
        var body = new FormData();
        body.append(form.dataset.csrfName, form.dataset.csrfHash);
        Object.keys(extra || {}).forEach(function (k) { body.append(k, extra[k]); });
        return fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: body })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok && d.ok, datos: d }; }); });
    }
    function recargarTrasGuardar() { guardar(function () { location.reload(); }); }

    var cajaPieza = raiz.querySelector('[data-buscar-pieza]');
    var resultados = raiz.querySelector('[data-resultados-pieza]');
    if (cajaPieza) {
        var espera = null;
        cajaPieza.addEventListener('input', function () {
            clearTimeout(espera);
            var q = cajaPieza.value.trim();
            if (q.length < 2) { resultados.hidden = true; resultados.innerHTML = ''; return; }
            espera = setTimeout(function () {
                fetch('<?= site_url('piezas/pieza-buscar') ?>?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        var lista = d.resultados || [];
                        resultados.innerHTML = lista.length
                            ? lista.map(function (p) { return '<button type="button" data-elegir-pieza data-version-id="' + p.version_id + '">' + String(p.texto).replace(/&/g, '&amp;').replace(/</g, '&lt;') + '</button>'; }).join('')
                            : '<div class="t3">Sin resultados</div>';
                        resultados.hidden = false;
                    });
            }, 250);
        });
        cajaPieza.addEventListener('blur', function () { setTimeout(function () { resultados.hidden = true; }, 200); });
        resultados.addEventListener('mousedown', function (e) { e.preventDefault(); });
        resultados.addEventListener('click', function (e) {
            var b = e.target.closest('[data-elegir-pieza]');
            if (!b) return;
            cajaPieza.disabled = true;
            llamada(URL_PLACA + '/pieza/agregar', { version_id: b.getAttribute('data-version-id'), cantidad: 1 })
                .then(function (r) {
                    if (!r.ok) { cajaPieza.disabled = false; alert((r.datos && r.datos.mensaje) || 'No se pudo añadir.'); return; }
                    recargarTrasGuardar();
                });
        });
    }

    raiz.addEventListener('click', function (e) {
        var b = e.target.closest('[data-actualizar-pieza], [data-actualizar-versiones]');
        if (!b) return;
        var url = b.hasAttribute('data-actualizar-pieza') ? URL_PLACA + '/pieza/' + b.getAttribute('data-fila') + '/actualizar' : URL_PLACA + '/actualizar-versiones';
        b.disabled = true;
        guardar(function () {
            llamada(url, {}).then(function (r) {
                if (!r.ok) { b.disabled = false; alert((r.datos && r.datos.mensaje) || 'No se pudo actualizar.'); return; }
                location.reload();
            });
        });
    });

    raiz.addEventListener('click', function (e) {
        var b = e.target.closest('[data-quitar-pieza]');
        if (!b) return;
        if (!confirm('¿Quitar esta pieza de la placa?')) return;
        b.disabled = true;
        guardar(function () {
            llamada(URL_PLACA + '/pieza/' + b.getAttribute('data-fila') + '/quitar', {}).then(function (r) {
                if (!r.ok) { b.disabled = false; alert((r.datos && r.datos.mensaje) || 'No se pudo quitar.'); return; }
                location.reload();
            });
        });
    });

    raiz.addEventListener('submit', function (e) {
        var f = e.target.closest('[data-ajax-form]');
        if (!f) return;
        e.preventDefault();
        if (f.dataset.confirmar && !confirm(f.dataset.confirmar)) return;
        var tipo = f.dataset.ajaxForm;
        var botones = f.querySelectorAll('button');
        botones.forEach(function (b) { b.disabled = true; });
        fetch(f.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: new FormData(f) })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok && d.ok, datos: d }; }); })
            .then(function (r) {
                if (!r.ok) { alert((r.datos && r.datos.mensaje) || 'No se pudo completar.'); botones.forEach(function (b) { b.disabled = false; }); return; }
                if (tipo === 'hueco-defecto') {
                    var fila = f.closest('[data-fila-colocacion]');
                    var controles = fila && fila.querySelector('[data-colocacion-controles]');
                    var panel = f.closest('[data-panel-inventario]');
                    var base = panel ? panel.dataset.urlHuecosBase : '';
                    var codigo = String(r.datos.codigo || '').replace(/&/g, '&amp;').replace(/</g, '&lt;');
                    if (controles) controles.innerHTML = r.datos.huecoId
                        ? '<a href="' + base + '/' + r.datos.huecoId + '" class="badge rounded-pill text-decoration-none text-bg-primary"><i class="bi bi-geo-alt"></i> ' + codigo + '</a>'
                        : '<span class="text-muted small">Sin hueco fijado.</span>';
                    return;
                }
                var panelInv = f.closest('[data-panel-inventario]');
                if (panelInv && r.datos.html) panelInv.outerHTML = r.datos.html;
                if (tipo === 'inventario-sincronizar' || tipo === 'inventario-recuento' || tipo === 'inventario-quitar') {
                    setTimeout(function () { location.reload(); }, 400);
                }
            })
            .catch(function () { alert('No se pudo completar.'); botones.forEach(function (b) { b.disabled = false; }); });
    });

    var columnaFotos = document.getElementById('fotos');
    var formFotos = columnaFotos.querySelector('[data-fotos-form]');
    var inputFotos = formFotos.querySelector('[data-fotos-input]');
    var estadoFotos = formFotos.querySelector('[data-fotos-estado]');
    var textoFotos = estadoFotos.textContent;
    var subiendo = false;
    function subir(ficheros) {
        var imagenes = [].filter.call(ficheros || [], function (f) { return /^image\//.test(f.type); });
        if (!imagenes.length || subiendo) return;
        subiendo = true;
        var errores = [];
        function siguiente(i) {
            if (i >= imagenes.length) {
                fetch(window.location.href, { credentials: 'same-origin' })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        var nueva = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-fotos-lista]');
                        if (nueva) columnaFotos.querySelector('[data-fotos-lista]').innerHTML = nueva.innerHTML;
                    })
                    .finally(function () {
                        subiendo = false;
                        inputFotos.value = '';
                        estadoFotos.textContent = errores.length ? errores.join(' ') : textoFotos;
                        estadoFotos.classList.toggle('e-bad', errores.length > 0);
                    });
                return;
            }
            estadoFotos.classList.remove('e-bad');
            estadoFotos.textContent = 'Subiendo' + (imagenes.length > 1 ? ' ' + (i + 1) + '/' + imagenes.length : '') + '…';
            var datos = new FormData(formFotos);
            datos.set('imagen', imagenes[i], imagenes[i].name || 'captura.png');
            fetch(formFotos.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: datos })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (!d.ok) errores.push(d.mensaje || 'No se pudo subir.'); })
                .catch(function () { errores.push('No se pudo hablar con el servidor.'); })
                .finally(function () { siguiente(i + 1); });
        }
        siguiente(0);
    }
    inputFotos.addEventListener('change', function () { subir(inputFotos.files); });
    ['dragenter', 'dragover'].forEach(function (ev) {
        columnaFotos.addEventListener(ev, function (e) {
            if (!e.dataTransfer || [].indexOf.call(e.dataTransfer.types, 'Files') < 0) return;
            e.preventDefault();
            columnaFotos.classList.add('soltando');
        });
    });
    ['dragleave', 'drop'].forEach(function (ev) { columnaFotos.addEventListener(ev, function () { columnaFotos.classList.remove('soltando'); }); });
    columnaFotos.addEventListener('drop', function (e) {
        if (!e.dataTransfer || !e.dataTransfer.files.length) return;
        e.preventDefault();
        subir(e.dataTransfer.files);
    });
    document.addEventListener('paste', function (e) {
        if (e.target.closest && e.target.closest('input, textarea, select, [contenteditable]')) return;
        var ficheros = e.clipboardData ? e.clipboardData.files : [];
        if (ficheros.length) { e.preventDefault(); subir(ficheros); }
    });

    var visor = document.getElementById('btVisor');
    var visorImg = visor.querySelector('[data-visor-img]');
    var visorFotos = [], visorI = 0, visorFoco = null;
    function visorMostrar(i) {
        visorI = (i + visorFotos.length) % visorFotos.length;
        var a = visorFotos[visorI];
        visorImg.classList.add('cargando');
        visorImg.onload = function () { visorImg.classList.remove('cargando'); };
        visorImg.src = a.getAttribute('href');
        visor.querySelector('[data-visor-original]').href = a.getAttribute('data-original') || a.getAttribute('href');
        visor.querySelector('[data-visor-nota]').textContent = a.getAttribute('data-notas') || '';
        visor.querySelector('[data-visor-cuenta]').textContent = visorFotos.length > 1 ? '· ' + (visorI + 1) + ' de ' + visorFotos.length : '';
        visor.classList.toggle('una', visorFotos.length < 2);
    }
    function visorAbrir(a) {
        visorFotos = Array.prototype.slice.call(document.querySelectorAll('#fotos [data-visor]'));
        visorFoco = document.activeElement;
        visor.hidden = false;
        document.documentElement.classList.add('bt-visor-abierto');
        visorMostrar(Math.max(0, visorFotos.indexOf(a)));
        visor.querySelector('[data-visor-cerrar]').focus();
    }
    function visorCerrar() {
        visor.hidden = true;
        document.documentElement.classList.remove('bt-visor-abierto');
        visorImg.removeAttribute('src');
        if (visorFoco) visorFoco.focus();
    }
    document.getElementById('fotos').addEventListener('click', function (e) {
        var a = e.target.closest('[data-visor]');
        if (!a || e.ctrlKey || e.metaKey) return;
        e.preventDefault();
        visorAbrir(a);
    });
    visor.addEventListener('click', function (e) {
        if (e.target.closest('[data-visor-cerrar]') || e.target.matches('[data-visor-fondo]')) { visorCerrar(); return; }
        var p = e.target.closest('[data-visor-paso]');
        if (p) visorMostrar(visorI + parseInt(p.getAttribute('data-visor-paso'), 10));
    });
    document.addEventListener('keydown', function (e) {
        if (visor.hidden) return;
        if (e.key === 'Escape') visorCerrar();
        else if (e.key === 'ArrowLeft') visorMostrar(visorI - 1);
        else if (e.key === 'ArrowRight') visorMostrar(visorI + 1);
    });
    var toqueX = null;
    visor.addEventListener('touchstart', function (e) { toqueX = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
    visor.addEventListener('touchend', function (e) {
        if (toqueX === null || visorFotos.length < 2) return;
        var dx = e.changedTouches[0].clientX - toqueX;
        if (Math.abs(dx) > 50) visorMostrar(visorI + (dx < 0 ? 1 : -1));
        toqueX = null;
    });

    function progreso() {
        raiz.querySelectorAll('.bt-prog').forEach(function (p) {
            var ini = +p.getAttribute('data-inicio'), fin = +p.getAttribute('data-fin'), ahora = Date.now();
            p.querySelector('i').style.width = Math.max(0, Math.min(100, (ahora - ini) * 100 / Math.max(1, fin - ini))) + '%';
            var resto = Math.max(0, Math.round((fin - ahora) / 60000));
            var h = Math.floor(resto / 60), m = resto % 60;
            p.querySelector('[data-quedan]').textContent = resto > 0 ? 'quedan ' + (h ? h + ' h ' + (m < 10 ? '0' : '') + m : m + ' min') : 'terminando';
        });
    }
    progreso();
    setInterval(progreso, 30000);

    recalcularTodo();
})();
</script>
