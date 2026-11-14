/*
 * Gimnasio — editor de series (registro de entreno y plantillas).
 * Se configura con window.GM_EDITOR (ver la vista). La lista de ejercicios
 * se pinta en PHP con el mismo marcado que construye este fichero.
 */
(function () {
    'use strict';
    const C = window.GM_EDITOR;
    if (!C) return;

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
    const AJAX = { 'X-Requested-With': 'XMLHttpRequest' };
    const norm = window.gmNormaliza || (s => String(s || '').toLowerCase());
    const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const kg = p => { p = Math.round(parseFloat(p || 0) * 1000) / 1000; return String(p).replace('.', ','); };
    const miles = n => Math.round(n).toLocaleString('es-ES');
    const post = (url, datos) => {
        const fd = new FormData();
        Object.entries(datos || {}).forEach(([k, v]) => fd.append(k, v ?? ''));
        return fetch(url, { method: 'POST', headers: AJAX, body: fd }).then(r => r.json());
    };

    const lista = $('#gmEjs');
    const vacia = $('#gmEjsVacia');

    /* ------------------------------------------------------------------
     * Marcado de ejercicios y series
     * ---------------------------------------------------------------- */
    function htmlSerie(s, i, pr) {
        const peso = parseFloat(s.peso || 0);
        const extra = (s.rpe !== null && s.rpe !== '' && s.rpe !== undefined ? `<span class="rpe">RPE ${esc(s.rpe)}</span>` : '') + esc(s.nota || '');
        return `<button type="button" class="gm-serie" data-serie='${esc(JSON.stringify(s))}'>
            <span class="i">${i}</span>
            <span class="sr">${s.series}<small>×</small>${s.repeticiones}</span>
            ${peso > 0 ? `<span class="kg">${kg(peso)}<small>kg</small></span>` : ''}
            ${pr ? '<i class="bi bi-trophy-fill" title="Récord"></i>' : ''}
            <span class="ex">${extra}</span>
            <i class="bi bi-pencil"></i>
        </button>`;
    }

    function htmlEjercicio(ej) {
        return `<section class="gm-ej" data-ee="${ej.ee}" data-ej="${ej.id}" data-nombre="${esc(ej.nombre)}" data-grupo="${esc(ej.grupo)}">
            <div class="gm-ej-h">
                <div class="pp">
                    <div class="t"><a href="${C.urls.stats}${ej.id}">${esc(ej.nombre)}</a></div>
                    <div class="d">${esc(ej.grupo)} · <span data-resumen></span></div>
                </div>
                <button type="button" class="fv-ibtn" data-mover="up" title="Subir"><i class="bi bi-arrow-up"></i></button>
                <button type="button" class="fv-ibtn" data-mover="down" title="Bajar"><i class="bi bi-arrow-down"></i></button>
            </div>
            <div class="gm-series"></div>
            <button type="button" class="gm-ej-mas" data-mas><i class="bi bi-plus-lg"></i> Otra serie</button>
        </section>`;
    }

    function datosSerie(btn) { return JSON.parse(btn.dataset.serie); }

    function resumir(bloque) {
        let series = 0, vol = 0;
        $$('.gm-serie', bloque).forEach((b, i) => {
            const s = datosSerie(b);
            series += parseInt(s.series) || 0;
            vol += (parseInt(s.series) || 0) * (parseInt(s.repeticiones) || 0) * (parseFloat(s.peso) || 0);
            b.querySelector('.i').textContent = i + 1;
        });
        const r = $('[data-resumen]', bloque);
        if (r) r.textContent = series + (series === 1 ? ' serie' : ' series') + (vol > 0 ? ' · ' + miles(vol) + ' kg' : '');
    }

    function ordenBotones() {
        const bloques = $$('.gm-ej', lista);
        bloques.forEach((b, i) => {
            $('[data-mover=up]', b).disabled = i === 0;
            $('[data-mover=down]', b).disabled = i === bloques.length - 1;
        });
        if (vacia) vacia.hidden = bloques.length > 0;
        if (C.alCambiar) C.alCambiar(bloques.length);
    }

    function insertarSerie(serie, ej, pr) {
        let bloque = $(`.gm-ej[data-ee="${ej.ee}"]`, lista);
        if (!bloque) {
            lista.insertAdjacentHTML('beforeend', htmlEjercicio(ej));
            bloque = lista.lastElementChild;
            ordenBotones();
        }
        const cont = $('.gm-series', bloque);
        cont.insertAdjacentHTML('beforeend', htmlSerie(serie, cont.children.length + 1, pr));
        cont.lastElementChild.classList.add('nueva');
        resumir(bloque);
    }

    $$('.gm-ej', lista).forEach(resumir);
    ordenBotones();

    /* ------------------------------------------------------------------
     * Hoja inferior
     * ---------------------------------------------------------------- */
    const velo = $('#gmVelo'), hoja = $('#gmHoja');
    const paneElegir = $('#gmElegir'), paneSerie = $('#gmSerie');
    const titulo = $('#gmHojaT'), subtitulo = $('#gmHojaD');
    const btnGuardar = $('#gmGuardar'), btnBorrar = $('#gmBorrar'), btnCambiar = $('#gmCambiar');
    const feedback = $('#gmFeedback');
    const ultimaBox = $('#gmUltima');
    let actual = null;      // ejercicio elegido {id, nombre, grupo}
    let editando = null;    // botón .gm-serie que se edita

    function abrir() { velo.classList.add('abierto'); hoja.classList.add('abierta'); document.body.style.overflow = 'hidden'; }
    function cerrar() { velo.classList.remove('abierto'); hoja.classList.remove('abierta'); document.body.style.overflow = ''; editando = null; }
    velo.addEventListener('click', cerrar);
    $('#gmCerrar').addEventListener('click', cerrar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && hoja.classList.contains('abierta')) cerrar(); });

    function mostrarElegir() {
        editando = null;
        titulo.textContent = 'Añadir serie';
        subtitulo.textContent = 'Elige el ejercicio';
        paneElegir.hidden = false;
        paneSerie.hidden = true;
        btnCambiar.hidden = true;
        $('#gmBuscarEj').value = '';
        filtrarEjercicios('');
        pintarEnSesion();
    }

    function mostrarSerie(ej, valores, modoEditar) {
        actual = ej;
        titulo.textContent = modoEditar ? 'Editar serie' : ej.nombre;
        subtitulo.textContent = modoEditar ? ej.nombre : ej.grupo;
        paneElegir.hidden = true;
        paneSerie.hidden = false;
        btnBorrar.hidden = !modoEditar;
        btnCambiar.hidden = !!modoEditar;
        btnGuardar.innerHTML = modoEditar ? '<i class="bi bi-check-lg"></i> Guardar cambios' : '<i class="bi bi-check-lg"></i> Guardar serie';
        feedback.textContent = '';
        poner(valores);
        $('#gmNotaSerie').value = valores.nota || '';
        $('#gmMasSerie').open = !!(valores.nota || (valores.rpe !== null && valores.rpe !== undefined && valores.rpe !== ''));
        if (!modoEditar) cargarUltima(ej.id); else ultimaBox.hidden = true;
    }

    // Abrir: FAB → elegir; "Otra serie" → ese ejercicio; tocar serie → editar
    $('#gmFab').addEventListener('click', () => { mostrarElegir(); abrir(); setTimeout(() => window.matchMedia('(pointer: fine)').matches && $('#gmBuscarEj').focus(), 250); });
    btnCambiar.addEventListener('click', mostrarElegir);

    lista.addEventListener('click', e => {
        const mas = e.target.closest('[data-mas]');
        if (mas) {
            const b = mas.closest('.gm-ej');
            const ultimaSerie = $$('.gm-serie', b).pop();
            const ej = { id: b.dataset.ej, nombre: b.dataset.nombre, grupo: b.dataset.grupo };
            mostrarSerie(ej, ultimaSerie ? Object.assign({}, datosSerie(ultimaSerie), { nota: '' }) : { series: C.defecto.series, repeticiones: C.defecto.reps, peso: 0, rpe: null }, false);
            abrir();
            return;
        }
        const serie = e.target.closest('.gm-serie');
        if (serie) {
            const b = serie.closest('.gm-ej');
            editando = serie;
            mostrarSerie({ id: b.dataset.ej, nombre: b.dataset.nombre, grupo: b.dataset.grupo }, datosSerie(serie), true);
            abrir();
            return;
        }
        const mover = e.target.closest('[data-mover]');
        if (mover && !mover.disabled) moverEjercicio(mover.closest('.gm-ej'), mover.dataset.mover);
    });

    /* ---------- elegir ejercicio ---------- */
    const cajaResultados = $('#gmResultados');
    const cajaRecientes = $('#gmRecientes');
    const cajaEnSesion = $('#gmEnSesion');

    function chip(ej) {
        return `<button type="button" class="gm-chip" data-elegir='${esc(JSON.stringify(ej))}'>${esc(ej.nombre)}</button>`;
    }

    function pintarEnSesion() {
        if (!cajaEnSesion) return;
        const ejs = $$('.gm-ej', lista).map(b => ({ id: b.dataset.ej, nombre: b.dataset.nombre, grupo: b.dataset.grupo }));
        cajaEnSesion.innerHTML = ejs.map(chip).join('');
        cajaEnSesion.closest('[data-bloque]').hidden = ejs.length === 0;
    }

    function filtrarEjercicios(q) {
        q = norm(q.trim());
        const conBusqueda = q.length > 0;
        $$('[data-bloque=fijo]', paneElegir).forEach(el => { el.hidden = conBusqueda; });
        if (conBusqueda && cajaEnSesion) cajaEnSesion.closest('[data-bloque]').hidden = true;
        else pintarEnSesion();
        $('#gmBloqueResultados').hidden = !conBusqueda;
        if (!conBusqueda) return;
        const palabras = q.split(/\s+/);
        const hits = C.todos.filter(e => palabras.every(p => e._n.includes(p))).slice(0, 40);
        cajaResultados.innerHTML = hits.length ? hits.map(chip).join('') : '<span class="fv-vacio">Ninguno con ese nombre. Créalo en Ejercicios.</span>';
    }
    C.todos.forEach(e => { e._n = norm(e.nombre + ' ' + e.grupo); });
    $('#gmBuscarEj').addEventListener('input', e => filtrarEjercicios(e.target.value));

    $$('.gm-grupos button').forEach(b => b.addEventListener('click', () => {
        $$('.gm-grupos button').forEach(x => x.classList.toggle('on', x === b));
        const cont = $('#gmDeGrupo');
        cont.innerHTML = '<span class="fv-vacio">Cargando…</span>';
        fetch(C.urls.porGrupo + b.dataset.grupo).then(r => r.json()).then(datos => {
            cont.innerHTML = datos.length
                ? datos.map(d => chip({ id: d.id, nombre: d.nombre, grupo: C.grupos[d.grupo_muscular] || d.grupo_muscular })).join('')
                : '<span class="fv-vacio">No hay ejercicios en este grupo.</span>';
        });
    }));

    paneElegir.addEventListener('click', e => {
        const c = e.target.closest('[data-elegir]');
        if (!c) return;
        const ej = JSON.parse(c.dataset.elegir);
        // Si ya está en la sesión, se parte de su última serie
        const bloque = $(`.gm-ej[data-ej="${ej.id}"]`, lista);
        const ult = bloque ? $$('.gm-serie', bloque).pop() : null;
        mostrarSerie(ej, ult ? Object.assign({}, datosSerie(ult), { nota: '' }) : { series: C.defecto.series, repeticiones: C.defecto.reps, peso: 0, rpe: null }, false);
    });

    /* ---------- "la última vez" ---------- */
    function cargarUltima(ejId) {
        ultimaBox.hidden = true;
        if (!C.urls.ultima) return;
        const pedido = ejId;
        fetch(C.urls.ultima + ejId + (C.excluir ? '?excluir=' + C.excluir : ''), { headers: AJAX })
            .then(r => r.json())
            .then(d => {
                if (!actual || String(actual.id) !== String(pedido) || !d.sesion) return;
                const ss = d.sesion.series;
                $('.h span', ultimaBox).textContent = 'La última vez · ' + d.sesion.cuando;
                $('.ss', ultimaBox).innerHTML = ss.map(s =>
                    `<button type="button" data-copiar='${esc(JSON.stringify(s))}' title="Usar estos valores">${s.series}×${s.repeticiones}${s.peso > 0 ? ' · ' + kg(s.peso) : ''}</button>`
                ).join('');
                $('.rec', ultimaBox).innerHTML = d.record > 0 ? `Récord (1RM est.): <b>${kg(d.record)} kg</b>` : '';
                ultimaBox.hidden = false;
                // Sin valores propios todavía en la sesión: precargar la serie más pesada de la última vez
                const bloque = $(`.gm-ej[data-ej="${pedido}"]`, lista);
                if (!bloque && ss.length) {
                    const top = ss.reduce((a, b) => (b.peso > a.peso ? b : a), ss[0]);
                    poner({ series: top.series, repeticiones: top.repeticiones, peso: top.peso, rpe: null });
                }
            })
            .catch(() => {});
    }
    ultimaBox.addEventListener('click', e => {
        const b = e.target.closest('[data-copiar]');
        if (b) poner(Object.assign({ rpe: null }, JSON.parse(b.dataset.copiar)));
    });

    /* ---------- contadores ---------- */
    const campos = { series: $('#gmVSeries'), repeticiones: $('#gmVReps'), peso: $('#gmVPeso'), rpe: $('#gmVRpe') };
    const limites = { series: [1, 50], repeticiones: [0, 300], peso: [0, 999], rpe: [1, 10] };
    let pasoPeso = parseFloat(localStorage.getItem('gm_paso') || '2.5') || 2.5;
    const btnPaso = $('#gmPaso');
    const pintarPaso = () => { btnPaso.textContent = 'paso ' + kg(pasoPeso); };
    pintarPaso();
    btnPaso.addEventListener('click', () => {
        pasoPeso = pasoPeso === 2.5 ? 5 : pasoPeso === 5 ? 1.25 : pasoPeso === 1.25 ? 10 : 2.5;
        try { localStorage.setItem('gm_paso', pasoPeso); } catch (e) {}
        pintarPaso();
    });

    function poner(v) {
        campos.series.value = v.series ?? 1;
        campos.repeticiones.value = v.repeticiones ?? 10;
        campos.peso.value = parseFloat(v.peso || 0) ? Math.round(parseFloat(v.peso) * 1000) / 1000 : '';
        campos.rpe.value = v.rpe === null || v.rpe === undefined || v.rpe === '' ? '' : v.rpe;
    }
    function leer() {
        return {
            series: Math.max(1, parseInt(campos.series.value) || 1),
            repeticiones: Math.max(0, parseInt(campos.repeticiones.value) || 0),
            peso: Math.max(0, parseFloat(String(campos.peso.value).replace(',', '.')) || 0),
            rpe: campos.rpe.value === '' ? '' : Math.min(10, Math.max(1, parseInt(campos.rpe.value) || 0)),
            nota: $('#gmNotaSerie').value.trim(),
        };
    }
    $$('.gm-step').forEach(st => {
        const campo = st.dataset.campo;
        const input = campos[campo];
        st.querySelectorAll('.m').forEach(b => b.addEventListener('click', () => {
            const dir = parseInt(b.dataset.dir);
            const paso = campo === 'peso' ? pasoPeso : 1;
            let v = parseFloat(String(input.value).replace(',', '.'));
            if (isNaN(v)) v = campo === 'rpe' ? (dir > 0 ? 6 : 8) : 0;
            v = Math.round((v + dir * paso) * 1000) / 1000;
            const [min, max] = limites[campo];
            input.value = Math.min(max, Math.max(min, v));
            if (campo === 'peso' && input.value === '0') input.value = '';
        }));
    });

    /* ---------- guardar / editar / borrar ---------- */
    btnGuardar.addEventListener('click', () => {
        if (!actual) return;
        prepararAudio();
        const v = leer();
        btnGuardar.disabled = true;
        if (editando) {
            const id = datosSerie(editando).id;
            post(C.urls.actualizar + id, v).then(d => {
                if (!d.ok) return aviso('No se pudo guardar', true);
                const s = Object.assign({}, d.serie, { id: id });
                const pr = !!editando.querySelector('.bi-trophy-fill');
                editando.outerHTML = htmlSerie(s, 1, pr);
                lista.querySelectorAll('.gm-ej').forEach(resumir);
                cerrar();
            }).catch(() => aviso('Error de conexión', true)).finally(() => { btnGuardar.disabled = false; });
            return;
        }
        post(C.urls.guardar, Object.assign({ [C.campoId]: C.id, ejercicio_id: actual.id }, v)).then(d => {
            if (!d.ok) return aviso('No se pudo guardar', true);
            const ej = { ee: d.ejercicio[C.claveEe], id: d.ejercicio.id, nombre: d.ejercicio.nombre, grupo: d.ejercicio.grupo_nombre };
            insertarSerie(d.serie, ej, !!d.pr);
            aviso('Serie guardada · sigue o cambia de ejercicio');
            $('#gmNotaSerie').value = '';
            if (d.pr) celebrar(actual.nombre, d.pr);
            if (C.cronometro) Cronometro.arrancar();
        }).catch(() => aviso('Error de conexión', true)).finally(() => { btnGuardar.disabled = false; });
    });

    btnBorrar.addEventListener('click', () => {
        if (!editando || !confirm('¿Eliminar esta serie?')) return;
        const bloque = editando.closest('.gm-ej');
        const fila = editando;
        post(C.urls.eliminar + datosSerie(editando).id).then(d => {
            if (!d.ok) return;
            fila.remove();
            if (d.ejercicio_eliminado || !$('.gm-serie', bloque)) bloque.remove();
            else resumir(bloque);
            ordenBotones();
            cerrar();
        });
    });

    function moverEjercicio(bloque, dir) {
        const vecino = dir === 'up' ? bloque.previousElementSibling : bloque.nextElementSibling;
        if (!vecino) return;
        post(C.urls.reordenar, { [C.campoReordenar]: bloque.dataset.ee, direction: dir }).then(d => {
            if (!d.ok) return;
            if (dir === 'up') lista.insertBefore(bloque, vecino); else lista.insertBefore(vecino, bloque);
            ordenBotones();
        });
    }

    let avisoT;
    function aviso(txt, error) {
        feedback.textContent = txt;
        feedback.classList.toggle('err', !!error);
        clearTimeout(avisoT);
        avisoT = setTimeout(() => { feedback.textContent = ''; }, 2600);
    }

    /* ------------------------------------------------------------------
     * Récord
     * ---------------------------------------------------------------- */
    function celebrar(nombre, pr) {
        const t = $('#gmToast');
        if (!t) return;
        $('b', t).textContent = '¡Nuevo récord en ' + nombre + '!';
        $('small', t).textContent = '1RM estimado ' + kg(pr.e1rm) + ' kg (antes ' + kg(pr.anterior) + ')';
        t.classList.add('visible');
        if (navigator.vibrate) navigator.vibrate([60, 40, 60, 40, 120]);
        pitido(880, .12); setTimeout(() => pitido(1320, .18), 140);
        setTimeout(() => t.classList.remove('visible'), 4200);
    }

    /* ------------------------------------------------------------------
     * Sonido (se activa con el primer toque, como exigen los navegadores)
     * ---------------------------------------------------------------- */
    let audio = null;
    function prepararAudio() {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            if (audio.state === 'suspended') audio.resume();
        } catch (e) { audio = null; }
    }
    function pitido(freq, dur) {
        if (!audio) return;
        try {
            const o = audio.createOscillator(), g = audio.createGain();
            o.frequency.value = freq;
            o.type = 'sine';
            g.gain.setValueAtTime(.0001, audio.currentTime);
            g.gain.exponentialRampToValueAtTime(.25, audio.currentTime + .02);
            g.gain.exponentialRampToValueAtTime(.0001, audio.currentTime + dur);
            o.connect(g).connect(audio.destination);
            o.start();
            o.stop(audio.currentTime + dur + .05);
        } catch (e) {}
    }

    /* ------------------------------------------------------------------
     * Cronómetro de descanso
     * ---------------------------------------------------------------- */
    const Cronometro = (function () {
        const el = $('#gmTimer');
        if (!el) return { arrancar() {} };
        const reloj = $('.reloj', el), barra = $('.barra', el), lbl = $('.lbl b', el);
        const CLAVE = 'gm_timer_fin_' + C.id;
        let duracion = parseInt(localStorage.getItem('gm_descanso') || '90') || 90;
        let fin = 0, intervalo = null, avisado = false;
        let pausadoQueda = null;   // ms que quedaban al pausar (null = corriendo)
        const btnPausa = $('[data-pausa]', el);

        const fmt = s => { const a = Math.abs(s); return (s < 0 ? '+' : '') + Math.floor(a / 60) + ':' + String(a % 60).padStart(2, '0'); };
        function marcarPreset() { $$('.ps button', el).forEach(b => b.classList.toggle('on', parseInt(b.dataset.s) === duracion)); }
        function guardar() {
            try { sessionStorage.setItem(CLAVE, pausadoQueda !== null ? 'P|' + pausadoQueda + '|' + duracion : fin + '|' + duracion); } catch (e) {}
        }
        function pintarPausa() {
            const p = pausadoQueda !== null;
            el.classList.toggle('pausado', p);
            btnPausa.title = p ? 'Seguir' : 'Pausar';
            btnPausa.querySelector('.bi').className = 'bi ' + (p ? 'bi-play-fill' : 'bi-pause-fill');
            if (p && !avisado) lbl.textContent = 'En pausa';
            else if (!avisado) lbl.textContent = 'Descanso';
        }
        function pausar() {
            if (pausadoQueda !== null) return;
            pausadoQueda = fin - Date.now();
            clearInterval(intervalo);
            intervalo = null;
            guardar();
            pintarPausa();
        }
        function seguir() {
            if (pausadoQueda === null) return;
            fin = Date.now() + pausadoQueda;
            pausadoQueda = null;
            guardar();
            pintarPausa();
            clearInterval(intervalo);
            intervalo = setInterval(tic, 250);
            tic();
        }
        btnPausa.addEventListener('click', () => { prepararAudio(); pausadoQueda === null ? pausar() : seguir(); });

        function tic() {
            const queda = Math.round((fin - Date.now()) / 1000);
            reloj.textContent = fmt(queda);
            barra.style.transform = 'scaleX(' + Math.max(0, Math.min(1, queda / duracion)) + ')';
            if (queda <= 0 && !avisado) {
                avisado = true;
                el.classList.add('fin');
                lbl.textContent = '¡A por la siguiente!';
                if (navigator.vibrate) navigator.vibrate([250, 120, 250, 120, 400]);
                pitido(660, .2); setTimeout(() => pitido(990, .3), 250);
            }
            if (queda < -600) parar();
        }
        function mostrar() {
            el.classList.add('visible');
            document.body.classList.add('gm-con-timer');
            clearInterval(intervalo);
            intervalo = setInterval(tic, 250);
            tic();
        }
        function arrancar(seg) {
            if (seg) duracion = seg;
            fin = Date.now() + duracion * 1000;
            avisado = false;
            pausadoQueda = null;
            el.classList.remove('fin');
            guardar();
            pintarPausa();
            marcarPreset();
            mostrar();
        }
        function parar() {
            clearInterval(intervalo);
            pausadoQueda = null;
            el.classList.remove('visible', 'fin', 'pausado');
            document.body.classList.remove('gm-con-timer');
            try { sessionStorage.removeItem(CLAVE); } catch (e) {}
        }
        $$('.ps button', el).forEach(b => b.addEventListener('click', () => {
            duracion = parseInt(b.dataset.s);
            try { localStorage.setItem('gm_descanso', duracion); } catch (e) {}
            prepararAudio();
            arrancar(duracion);
        }));
        $('[data-parar]', el).addEventListener('click', parar);
        const btnManual = $('#gmDescanso');
        if (btnManual) btnManual.addEventListener('click', () => { prepararAudio(); arrancar(); });

        // Si se recarga la página a mitad de un descanso, sigue donde iba
        try {
            const g = (sessionStorage.getItem(CLAVE) || '').split('|');
            if (g[0] === 'P') {
                // Estaba en pausa: se queda en pausa con lo que le quedaba
                pausadoQueda = parseInt(g[1]) || 0;
                duracion = parseInt(g[2]) || duracion;
                fin = Date.now() + pausadoQueda;
                avisado = pausadoQueda <= 0;
                marcarPreset();
                mostrar();
                clearInterval(intervalo);
                intervalo = null;
                pintarPausa();
            } else if (g[0] && parseInt(g[0]) > Date.now() - 60000) {
                fin = parseInt(g[0]); duracion = parseInt(g[1]) || duracion; avisado = fin < Date.now(); marcarPreset(); mostrar();
            }
        } catch (e) {}
        marcarPreset();
        return { arrancar: () => arrancar() };
    })();
})();
