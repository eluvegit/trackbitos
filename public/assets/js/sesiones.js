/* Sesiones — ficha en estilo fv (sesiones/show_nueva.php). Todo sin recargar. */
(() => {
    const raiz = document.getElementById('ssFicha');
    if (!raiz) return;

    const BASE = raiz.dataset.base;
    const MEDIA = raiz.dataset.media || '';
    const ESTADOS = JSON.parse(raiz.dataset.estados);
    const ORDEN = Object.keys(ESTADOS);
    const cab = { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': raiz.dataset.csrf };

    const $ = (s, el = document) => el.querySelector(s);
    const $$ = (s, el = document) => [...el.querySelectorAll(s)];
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function post(ruta, datos) {
        const opts = { method: 'POST', headers: { ...cab } };
        if (datos instanceof FormData) opts.body = datos;
        else if (datos !== undefined) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(datos); }
        const r = await fetch(BASE + ruta, opts);
        if (!r.ok) throw new Error(r.status);
        const t = r.headers.get('content-type') || '';
        return t.includes('json') ? r.json() : r.text();
    }
    const fallo = (msg = 'No se ha podido guardar. ¿Hay conexión?') => alert(msg);

    // ---------- pestañas ----------
    const secs = $('#ssSecs');
    const claveTab = 'ss_tab_' + BASE;
    function abrirTab(tab) {
        $$('button', secs).forEach(b => b.setAttribute('aria-pressed', String(b.dataset.tab === tab)));
        $$('.ss-tab', raiz).forEach(p => { p.hidden = p.dataset.panel !== tab; });
        try { sessionStorage.setItem(claveTab, tab); } catch (e) {}
    }
    secs.addEventListener('click', e => { const b = e.target.closest('button'); if (b) abrirTab(b.dataset.tab); });
    let tabInicial = location.hash.slice(1);
    if (!$(`[data-panel="${tabInicial}"]`, raiz)) { try { tabInicial = sessionStorage.getItem(claveTab) || 'brief'; } catch (e) { tabInicial = 'brief'; } }
    abrirTab($(`[data-panel="${tabInicial}"]`, raiz) ? tabInicial : 'brief');
    raiz.addEventListener('click', e => { const a = e.target.closest('[data-ir-tab]'); if (a) { e.preventDefault(); abrirTab(a.dataset.irTab); } });
    const cuenta = (k, v) => { const el = $(`[data-cuenta="${k}"]`, secs); if (el) el.textContent = v; };

    // ---------- menú "más" ----------
    document.addEventListener('click', e => {
        $$('.ss-menu[open]').forEach(d => { if (!d.contains(e.target)) d.open = false; });
        if (!e.target.closest('.ss-mover-pop, .ss-mover')) $$('.ss-mover-pop').forEach(p => p.remove());
    });

    // ---------- etapas ----------
    $$('.ss-etapa', raiz).forEach(et => et.addEventListener('click', async e => {
        const b = e.target.closest('.ss-stepper button');
        if (!b || b.classList.contains('actual')) return; // misma etapa: no es un cambio
        const estado = b.dataset.estado;
        try { await post('/estado', { parte: et.dataset.parte, estado }); } catch (err) { return fallo('No se ha podido cambiar la etapa.'); }
        const idx = ORDEN.indexOf(estado);
        $$('.ss-stepper button', et).forEach((x, i) => { x.className = i < idx ? 'hecho' : (i === idx ? 'actual' : ''); });
        et.style.setProperty('--c', ESTADOS[estado].c);
    }));

    // ---------- pausa ----------
    const btnPausa = $('[data-accion="pausa"]', raiz);
    btnPausa?.addEventListener('click', async () => {
        let r;
        try { r = await post('/toggle-pausada'); } catch (e) { return fallo(); }
        const p = !!r.valor;
        $('#ssPausaChip').hidden = !p;
        $('.ss-etapas', raiz)?.classList.toggle('pausada', p);
        btnPausa.querySelector('i').className = 'bi ' + (p ? 'bi-play-fill' : 'bi-pause-fill');
        btnPausa.querySelector('span').textContent = p ? 'Reanudar sesión' : 'Pausar sesión';
        btnPausa.closest('details').open = false;
    });

    // ---------- entrega a modelos ----------
    $('#ssEntrega')?.addEventListener('click', async e => {
        const b = e.target.closest('button');
        if (!b) return;
        try { await post('/entrega-modelos', { valor: b.dataset.valor }); } catch (err) { return fallo(); }
        $$('#ssEntrega button').forEach(x => x.classList.toggle('on', x === b));
    });

    // ---------- notas y briefing ----------
    function marcaGuardado(campo, txt) {
        const el = $(`[data-guardado="${campo}"]`, raiz);
        if (!el) return;
        el.textContent = txt;
        el.classList.add('ver');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('ver'), 2200);
    }
    async function guardarCampo(campo, valor) {
        // Sesiones::update necesita título y fecha; solo toca el campo que llega.
        const fd = new FormData();
        fd.append('titulo', raiz.dataset.titulo);
        fd.append('fecha_sesion', raiz.dataset.fecha);
        fd.append(campo, valor);
        marcaGuardado(campo, 'Guardando…');
        try { await post('/actualizar', fd); marcaGuardado(campo, '✓ Guardado'); }
        catch (e) { marcaGuardado(campo, 'No se ha guardado'); }
    }
    const notas = $('#ssNotas');
    if (notas) {
        let previo = notas.value;
        notas.addEventListener('blur', () => { if (notas.value !== previo) { previo = notas.value; guardarCampo('notas', notas.value); } });
    }
    let quill = null;
    try {
        quill = new Quill('#ssBriefing', {
            theme: 'snow',
            placeholder: 'Desarrollo de la idea, looks, localización, horarios…',
            modules: { toolbar: [['bold', 'italic', 'underline'], [{ header: [2, 3, false] }], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'clean']] },
        });
    } catch (e) { console.error('No se pudo cargar el editor de briefing', e); }
    $('#ssGuardarBriefing')?.addEventListener('click', () => {
        const html = quill ? quill.root.innerHTML : $('#ssBriefing').innerHTML;
        guardarCampo('briefing', html === '<p><br></p>' ? '' : html);
    });

    // ---------- moodboard ----------
    const grupos = $('#ssGrupos');
    let grupoActivo = null;

    function htmlItem(it) {
        const archivo = it.origen === 'archivo';
        const src = archivo ? MEDIA + it.ruta_archivo : it.url_externa;
        let host = '';
        if (!archivo) { try { host = new URL(it.url_externa).hostname.replace(/^www\./, ''); } catch (e) { host = it.url_externa; } }
        return `<div class="ss-item${archivo ? '' : ' enlace'}" data-id="${it.id}" title="${esc(it.nota)}">
            <a href="${esc(src)}" target="_blank" rel="noopener">${archivo ? `<img src="${esc(src)}" alt="" loading="lazy">` : `<i class="bi bi-link-45deg"></i><small>${esc(host)}</small>`}</a>
            ${it.nota ? `<span class="nota">${esc(it.nota)}</span>` : ''}
            <div class="ss-item-acc">
                <button type="button" class="ss-mover" title="Mover a otra situación"><i class="bi bi-arrows-move"></i></button>
                <button type="button" class="ss-item-borrar" title="Borrar"><i class="bi bi-x-lg"></i></button>
            </div></div>`;
    }
    function htmlGrupo(sit) {
        return `<section class="fv-panel ss-grupo" data-situacion="${sit.id}" tabindex="0">
            <div class="fv-ph">
                <span class="ss-asa" title="Arrastrar para reordenar"><i class="bi bi-grip-vertical"></i></span>
                <h3 class="t1">${esc(sit.nombre)}</h3><span class="cuenta" data-n>0</span>
                <div class="der ss-grupo-acc">
                    <a class="fv-ibtn" href="${BASE}/situaciones/${sit.id}/exportar" target="_blank" title="Exportar esta situación"><i class="bi bi-printer"></i></a>
                    <button type="button" class="fv-ibtn ss-sit-borrar" title="Borrar situación"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="ss-mb">
                <label class="ss-mb-add" title="Subir imágenes"><input type="file" accept="image/*" multiple hidden><i class="bi bi-upload"></i><span>Subir</span></label>
                <button type="button" class="ss-mb-add ss-mb-enlace" title="Añadir enlace"><i class="bi bi-link-45deg"></i><span>Enlace</span></button>
            </div>
            <form class="ss-enlace-form" hidden>
                <input class="fv-input" type="url" name="url_externa" placeholder="https://…" required>
                <input class="fv-input" name="nota" placeholder="Nota (opcional)">
                <button type="submit" class="fv-btn fv-btn-sm fv-btn-pri">Añadir</button>
            </form></section>`;
    }
    function recontar() {
        let total = 0;
        $$('.ss-grupo', grupos).forEach(g => { const n = $$('.ss-item', g).length; total += n; $('[data-n]', g).textContent = n; });
        cuenta('moodboard', total);
    }
    function meterItems(grupo, items) {
        const ancla = $('.ss-mb-add', grupo);
        items.forEach(it => ancla.insertAdjacentHTML('beforebegin', htmlItem(it)));
        recontar();
    }
    async function subir(grupo, files) {
        const imgs = [...files].filter(f => f.type.startsWith('image/'));
        if (!imgs.length) return;
        const fd = new FormData();
        imgs.forEach(f => fd.append('archivo[]', f, f.name || 'pegada.png'));
        fd.append('situacion_id', grupo.dataset.situacion);
        grupo.classList.add('subiendo');
        try { const r = await post('/moodboard/subir', fd); meterItems(grupo, r.items || []); }
        catch (e) { fallo('No se han podido subir las imágenes.'); }
        finally { grupo.classList.remove('subiendo'); }
    }

    if (grupos) {
        grupos.addEventListener('focusin', e => { const g = e.target.closest('.ss-grupo'); if (g) marcarActivo(g); });
        grupos.addEventListener('pointerdown', e => { const g = e.target.closest('.ss-grupo'); if (g) marcarActivo(g); });
        function marcarActivo(g) { grupoActivo = g; $$('.ss-grupo', grupos).forEach(x => x.classList.toggle('activo', x === g)); }

        grupos.addEventListener('change', e => {
            if (e.target.matches('.ss-mb-add input[type=file]')) { subir(e.target.closest('.ss-grupo'), e.target.files); e.target.value = ''; }
        });

        // Arrastrar y soltar
        grupos.addEventListener('dragover', e => {
            if (![...e.dataTransfer.types].includes('Files')) return;
            const g = e.target.closest('.ss-grupo');
            if (!g) return;
            e.preventDefault();
            $$('.ss-grupo.soltar', grupos).forEach(x => x !== g && x.classList.remove('soltar'));
            g.classList.add('soltar');
        });
        grupos.addEventListener('dragleave', e => { const g = e.target.closest('.ss-grupo'); if (g && !g.contains(e.relatedTarget)) g.classList.remove('soltar'); });
        grupos.addEventListener('drop', e => {
            const g = e.target.closest('.ss-grupo');
            if (!g || !e.dataTransfer.files.length) return;
            e.preventDefault();
            g.classList.remove('soltar');
            subir(g, e.dataTransfer.files);
        });

        // Pegar (Ctrl+V) en la pestaña de moodboard
        document.addEventListener('paste', e => {
            if ($('[data-panel="moodboard"]', raiz).hidden) return;
            if (e.target.closest('input, textarea, [contenteditable]')) return;
            const files = [...(e.clipboardData?.files || [])];
            if (!files.length) return;
            e.preventDefault();
            subir(grupoActivo || $('.ss-grupo', grupos), files);
        });

        grupos.addEventListener('click', async e => {
            const g = e.target.closest('.ss-grupo');
            // Enlace: mostrar formulario
            if (e.target.closest('.ss-mb-enlace')) {
                const f = $('.ss-enlace-form', g); f.hidden = !f.hidden; if (!f.hidden) f.url_externa.focus();
                return;
            }
            // Borrar item
            const borrar = e.target.closest('.ss-item-borrar');
            if (borrar) {
                const it = borrar.closest('.ss-item');
                if (!confirm('¿Borrar esta referencia?')) return;
                try { await post(`/moodboard/${it.dataset.id}/borrar`); it.remove(); recontar(); } catch (err) { fallo(); }
                return;
            }
            // Mover item: menú con los grupos
            const mover = e.target.closest('.ss-mover');
            if (mover) {
                $$('.ss-mover-pop').forEach(p => p.remove());
                const it = mover.closest('.ss-item');
                const pop = document.createElement('div');
                pop.className = 'ss-mover-pop';
                pop.innerHTML = '<small>Mover a…</small>' + $$('.ss-grupo', grupos).filter(x => x !== g)
                    .map(x => `<button type="button" data-destino="${x.dataset.situacion}">${esc($('h3', x).textContent)}</button>`).join('');
                it.appendChild(pop);
                return;
            }
            const destino = e.target.closest('.ss-mover-pop button');
            if (destino) {
                const it = destino.closest('.ss-item');
                const sitId = destino.dataset.destino;
                try { await post(`/moodboard/${it.dataset.id}/vincular`, { situacion_id: sitId }); } catch (err) { return fallo(); }
                destino.parentElement.remove();
                const g2 = $(`.ss-grupo[data-situacion="${sitId}"]`, grupos);
                $('.ss-mb-add', g2).insertAdjacentElement('beforebegin', it);
                recontar();
                return;
            }
            // Borrar situación
            if (e.target.closest('.ss-sit-borrar')) {
                const n = $$('.ss-item', g).length;
                if (!confirm(`¿Borrar la situación «${$('h3', g).textContent}»?` + (n ? ` Sus ${n} referencias pasan a General.` : ''))) return;
                try { await post(`/situaciones/${g.dataset.situacion}/borrar`); } catch (err) { return fallo(); }
                // En BD el FK deja situacion_id a NULL: se mueven a General también aquí.
                const general = $('.ss-grupo[data-situacion=""]', grupos);
                $$('.ss-item', g).forEach(it => $('.ss-mb-add', general).insertAdjacentElement('beforebegin', it));
                g.remove();
                recontar();
            }
        });

        grupos.addEventListener('submit', async e => {
            const f = e.target.closest('.ss-enlace-form');
            if (!f) return;
            e.preventDefault();
            const g = f.closest('.ss-grupo');
            try {
                const r = await post('/moodboard/enlace', { situacion_id: g.dataset.situacion, url_externa: f.url_externa.value.trim(), nota: f.nota.value.trim() });
                meterItems(g, [r.item]);
                f.reset(); f.hidden = true;
            } catch (err) { fallo('Enlace no válido.'); }
        });

        // Nueva situación
        $('#ssNuevaSit')?.addEventListener('submit', async e => {
            e.preventDefault();
            const inp = e.target.nombre;
            const nombre = inp.value.trim();
            if (!nombre) return;
            let r;
            try { r = await post('/situaciones/crear', { nombre }); } catch (err) { return fallo(); }
            grupos.insertAdjacentHTML('beforeend', htmlGrupo(r.situacion));
            inp.value = '';
        });

        // Reordenar situaciones (General se queda arriba)
        if (window.Sortable) {
            Sortable.create(grupos, {
                handle: '.ss-asa', draggable: '.ss-grupo[data-situacion]:not([data-situacion=""])', animation: 150,
                onEnd: () => post('/situaciones/reordenar', { orden: $$('.ss-grupo', grupos).map(g => g.dataset.situacion).filter(Boolean) }).catch(() => fallo()),
            });
        }
    }

    // ---------- qué llevar: dos listas (equipo / props) ----------
    const equipo = $('#ssEquipo');
    const lista = tipo => $(`.ss-equipo[data-tipo="${tipo === 'props' ? 'props' : 'equipo'}"]`, equipo);
    function pintarEquipo() {
        let okT = 0, nT = 0;
        $$('.ss-equipo', equipo).forEach(sec => {
            const items = $$('.fv-check', sec);
            const ok = items.filter(i => $('input', i).checked).length;
            okT += ok; nT += items.length;
            $('[data-eq-cuenta]', sec).textContent = `${ok}/${items.length}`;
            const barra = $('[data-eq-barra]', sec);
            barra.style.width = (items.length ? ok / items.length * 100 : 0) + '%';
            barra.parentElement.classList.toggle('ok', items.length > 0 && ok === items.length);
            // El botón de mover dice siempre a qué lista va.
            $$('.ss-eq-mover', sec).forEach(b => { b.title = 'Pasar a ' + (sec.dataset.tipo === 'props' ? 'Equipo' : 'Props'); });
        });
        cuenta('equipo', `${okT}/${nT}`);
    }
    const htmlEq = it => `<label class="fv-check${+it.marcado ? ' hecha' : ''}" data-id="${it.id}"><input type="checkbox"${+it.marcado ? ' checked' : ''}><span>${esc(it.item)}</span><button type="button" class="deshacer ss-eq-editar" title="Editar"><i class="bi bi-pencil"></i></button><button type="button" class="deshacer ss-eq-mover" title=""><i class="bi bi-arrow-left-right"></i></button><button type="button" class="deshacer ss-eq-borrar" title="Quitar"><i class="bi bi-x-lg"></i></button></label>`;
    const meter = it => $('.ss-eq-lista', lista(it.tipo)).insertAdjacentHTML('beforeend', htmlEq(it));
    if (equipo) {
        equipo.addEventListener('change', async e => {
            const lab = e.target.closest('.fv-check');
            if (!lab) return;
            try { const r = await post(`/equipo/${lab.dataset.id}/toggle`); e.target.checked = !!r.marcado; }
            catch (err) { e.target.checked = !e.target.checked; fallo(); }
            lab.classList.toggle('hecha', e.target.checked);
            pintarEquipo();
        });
        // Editar el texto de un ítem en el sitio. El <label> envuelve el
        // checkbox, así que mientras se edita hay que evitar que un clic marque.
        function editarEq(lab) {
            if (lab.classList.contains('editando')) return;
            const span = $('span', lab);
            const original = span.textContent;
            const inp = document.createElement('input');
            inp.className = 'fv-input ss-eq-input';
            inp.maxLength = 150;
            inp.value = original;
            lab.classList.add('editando');
            span.replaceWith(inp);
            inp.focus();
            inp.select();
            let hecho = false;
            const cerrar = texto => { span.textContent = texto; inp.replaceWith(span); lab.classList.remove('editando'); };
            const guardar = async () => {
                if (hecho) return;
                hecho = true;
                const texto = inp.value.trim();
                if (!texto || texto === original) return cerrar(original);
                try { const r = await post(`/equipo/${lab.dataset.id}/editar`, { item: texto }); cerrar(r.item); }
                catch (err) { cerrar(original); fallo(); }
            };
            inp.addEventListener('keydown', ev => {
                if (ev.key === 'Enter') { ev.preventDefault(); guardar(); }
                if (ev.key === 'Escape') { ev.preventDefault(); hecho = true; cerrar(original); }
            });
            inp.addEventListener('blur', guardar);
            inp.addEventListener('click', ev => ev.preventDefault());
        }
        equipo.addEventListener('click', async e => {
            if (e.target.closest('.ss-eq-editar')) { e.preventDefault(); editarEq(e.target.closest('.fv-check')); return; }
            const borrar = e.target.closest('.ss-eq-borrar');
            const mover = e.target.closest('.ss-eq-mover');
            if (!borrar && !mover) return;
            e.preventDefault();
            const lab = e.target.closest('.fv-check');
            try {
                if (borrar) { await post(`/equipo/${lab.dataset.id}/borrar`); lab.remove(); }
                else { const r = await post(`/equipo/${lab.dataset.id}/tipo`); $('.ss-eq-lista', lista(r.tipo)).appendChild(lab); }
                pintarEquipo();
            } catch (err) { fallo(); }
        });
        // Varios ítems de golpe: separados por salto de línea o «;».
        async function agregarEquipo(texto, tipo) {
            const lineas = texto.split(/\r?\n|;/).map(s => s.replace(/^[-*•\s]+/, '').trim()).filter(Boolean);
            for (const item of lineas) {
                try { const r = await post('/equipo/agregar', { item, tipo }); meter(r.item); }
                catch (err) { fallo(); break; }
            }
            pintarEquipo();
        }
        $$('.ss-eq-nuevo', equipo).forEach(f => {
            const tipo = f.closest('.ss-equipo').dataset.tipo;
            f.addEventListener('submit', e => {
                e.preventDefault();
                const t = f.item.value;
                f.item.value = '';
                agregarEquipo(t, tipo);
            });
            // Pegar una lista de varias líneas: un <input> aplanaría los saltos, así que se intercepta.
            f.item.addEventListener('paste', e => {
                const t = e.clipboardData.getData('text');
                if (!/\n/.test(t.trim())) return;
                e.preventDefault();
                agregarEquipo(t, tipo);
            });
        });
        $('#ssEqCopiar')?.addEventListener('click', async () => {
            let r;
            try { r = await post('/equipo/copiar', { desde: $('#ssEqDesde').value }); } catch (err) { return fallo(); }
            r.items.forEach(meter);
            pintarEquipo();
            if (!r.items.length) alert('Ya tenías todos esos ítems.');
        });
        pintarEquipo();
    }

    // ---------- model releases ----------
    const releases = $('#ssReleases');
    const recontarModelos = () => {
        const nr = $$('li', releases).length, nm = $$('.ss-msg', raiz).length;
        $('#ssRelCuenta').textContent = nr; $('#ssMsgCuenta').textContent = nm; cuenta('modelos', $$('.ss-modelo', raiz).length + nr + nm);
    };
    $('#ssRelForm')?.addEventListener('change', e => {
        if (e.target.type === 'file') e.target.nextElementSibling.nextElementSibling.textContent = e.target.files[0]?.name || 'Archivo';
    });
    $('#ssRelForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const f = e.target;
        let r;
        try { r = await post('/releases/subir', new FormData(f)); } catch (err) { return fallo('No se ha podido subir el release.'); }
        const rel = r.release;
        releases.insertAdjacentHTML('beforeend', `<li data-id="${rel.id}"><i class="bi bi-file-earmark-check"></i><a href="${esc(MEDIA + rel.ruta_archivo)}" target="_blank" rel="noopener">${esc(rel.nombre_modelo)}</a><button type="button" class="fv-ibtn ss-rel-borrar" title="Borrar"><i class="bi bi-trash"></i></button></li>`);
        $('#ssModelosLista').insertAdjacentHTML('beforeend', `<option value="${esc(rel.nombre_modelo)}">`);
        f.reset();
        $('.ss-archivo span', f).textContent = 'Archivo';
        recontarModelos();
    });
    releases?.addEventListener('click', async e => {
        const b = e.target.closest('.ss-rel-borrar');
        if (!b || !confirm('¿Borrar este model release?')) return;
        const li = b.closest('li');
        try { await post(`/releases/${li.dataset.id}/borrar`); li.remove(); recontarModelos(); } catch (err) { fallo(); }
    });

    // ---------- mensajes ----------
    const mensajes = $('#ssMensajes');
    $('#ssMsgForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const f = e.target;
        let r;
        try { r = await post('/mensajes/crear', { nombre_modelo: f.nombre_modelo.value.trim(), mensaje: f.mensaje.value.trim() }); } catch (err) { return fallo(); }
        const m = r.item;
        const fecha = new Date((m.creado_at || '').replace(' ', 'T'));
        mensajes.insertAdjacentHTML('afterbegin', `<article class="ss-msg" data-id="${m.id}"><div class="ss-msg-cab"><b>${esc(m.nombre_modelo)}</b><span class="t3">${isNaN(fecha) ? '' : fecha.toLocaleDateString('es-ES')}</span><div class="der"><button type="button" class="fv-ibtn ss-msg-copiar" title="Copiar al portapapeles"><i class="bi bi-clipboard"></i></button><button type="button" class="fv-ibtn ss-msg-borrar" title="Borrar"><i class="bi bi-trash"></i></button></div></div><div class="ss-msg-txt">${esc(m.mensaje)}</div></article>`);
        f.reset();
        f.closest('details').open = false;
        recontarModelos();
    });
    mensajes?.addEventListener('click', async e => {
        const msg = e.target.closest('.ss-msg');
        if (e.target.closest('.ss-msg-copiar')) {
            const b = e.target.closest('.ss-msg-copiar');
            try { await navigator.clipboard.writeText($('.ss-msg-txt', msg).textContent); b.innerHTML = '<i class="bi bi-check-lg"></i>'; setTimeout(() => { b.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500); }
            catch (err) { fallo('No se ha podido copiar.'); }
        }
        if (e.target.closest('.ss-msg-borrar') && confirm('¿Borrar este mensaje?')) {
            try { await post(`/mensajes/${msg.dataset.id}/borrar`); msg.remove(); recontarModelos(); } catch (err) { fallo(); }
        }
    });

    // ---------- brief: hora y ubicaciones (del rodaje vinculado) ----------
    const hora = $('#ssHora');
    hora?.addEventListener('change', async () => {
        await guardarCampo('hora_sesion', hora.value);
        const chip = $('#ssHoraChip');
        if (chip) { chip.hidden = !hora.value; $('b', chip).textContent = hora.value; }
        const enTab = $('[data-cuenta="hora"]', secs);
        if (enTab) { enTab.hidden = !hora.value; enTab.textContent = hora.value; }
    });
    $('#ssUbis')?.addEventListener('change', async e => {
        const inp = e.target.closest('.ss-ubi-hora');
        if (!inp) return;
        try { await post(`/ubicaciones/${inp.closest('li').dataset.id}/hora`, { hora: inp.value }); marcaGuardado('hora_sesion', '✓ Guardado'); }
        catch (err) { fallo(); }
    });

    // ---------- modelos: perfiles con opciones de ropa ----------
    const modelos = $('#ssModelos');
    const perfiles = $('#ssPerfiles');
    const recontarTodo = () => {
        const nr = $$('li', releases).length, nm = $$('.ss-msg', raiz).length;
        cuenta('modelos', $$('.ss-modelo', modelos).length + nr + nm);
    };
    function ponerTarjeta(id, html) {
        const vieja = $(`.ss-modelo[data-id="${id}"]`, modelos);
        if (!html) { vieja?.remove(); return; }
        if (vieja) vieja.outerHTML = html; else modelos.insertAdjacentHTML('beforeend', html);
    }
    async function agregarModelo(datos) {
        let r;
        try { r = await post('/modelos/agregar', datos); } catch (err) { return fallo('No se ha podido añadir el modelo.'); }
        ponerTarjeta(r.id, r.html);
        $(`.ss-chip[data-id="${r.id}"]`, perfiles)?.remove();
        $$('#ssPerfilesLista option').forEach(o => { if (o.value === datos.nombre) o.remove(); });
        if (perfiles) perfiles.hidden = !$('.ss-chip', perfiles);
        recontarTodo();
    }
    const modNombre = $('#ssModNombre');
    $('#ssModCrear')?.addEventListener('click', () => {
        const n = modNombre.value.trim();
        if (!n) return modNombre.focus();
        modNombre.value = '';
        agregarModelo({ nombre: n });
    });
    modNombre?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); $('#ssModCrear').click(); } });
    perfiles?.addEventListener('click', e => { const c = e.target.closest('.ss-chip'); if (c) agregarModelo({ modelo_id: c.dataset.id }); });

    if (modelos) {
        // Datos del perfil: se guardan solos al salir del campo.
        modelos.addEventListener('focusin', e => { const c = e.target.closest('[data-campo]'); if (c) c._previo = c.value; });
        modelos.addEventListener('focusout', async e => {
            const c = e.target.closest('[data-campo]');
            if (!c || c.value === c._previo) return;
            if (c.dataset.campo === 'nombre' && !c.value.trim()) { c.value = c._previo; return; }
            const card = c.closest('.ss-modelo');
            let r;
            try { r = await post(`/modelos/${card.dataset.id}/guardar`, { [c.dataset.campo]: c.value }); c._previo = c.value; }
            catch (err) { return fallo(); }
            if ('instagram' in r) {
                const ig = $('[data-ig]', card);
                ig.hidden = !r.instagram;
                if (r.instagram) { ig.href = `https://www.instagram.com/${encodeURIComponent(r.instagram)}/`; $('span', ig).textContent = '@' + r.instagram; }
            }
        });
        modelos.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.matches('input[data-campo]')) { e.preventDefault(); e.target.blur(); } });

        modelos.addEventListener('click', async e => {
            const card = e.target.closest('.ss-modelo');
            if (!card) return;
            const mid = card.dataset.id;
            if (e.target.closest('.ss-mod-quitar')) {
                if (!confirm('¿Quitar este modelo de la sesión? El perfil y su ropa se conservan para otras sesiones.')) return;
                let r;
                try { r = await post(`/modelos/${mid}/quitar`); } catch (err) { return fallo(); }
                card.remove();
                if (r.perfil && perfiles) {
                    perfiles.insertAdjacentHTML('beforeend', `<button type="button" class="ss-chip" data-id="${r.perfil.id}"><i class="bi bi-plus"></i>${esc(r.perfil.nombre)}</button>`);
                    perfiles.hidden = false;
                    $('#ssPerfilesLista')?.insertAdjacentHTML('beforeend', `<option value="${esc(r.perfil.nombre)}">`);
                }
                return recontarTodo();
            }
            const foto = e.target.closest('.ss-mod-foto');
            if (foto && e.target.closest('.ss-foto-borrar')) {
                if (!confirm('¿Borrar esta foto del perfil?')) return;
                try { const r = await post(`/modelos/${mid}/fotos/${foto.dataset.id}/borrar`); ponerTarjeta(mid, r.html); } catch (err) { fallo(); }
                return;
            }
            if (foto && e.target.closest('.ss-foto-principal')) {
                try { const r = await post(`/modelos/${mid}/fotos/${foto.dataset.id}/principal`); ponerTarjeta(mid, r.html); } catch (err) { fallo(); }
                return;
            }
            const op = e.target.closest('.ss-ropa-op');
            if (op && e.target.closest('.ss-ropa-borrar')) {
                if (!confirm('¿Borrar esta opción de ropa del perfil? Desaparece también de las demás sesiones.')) return;
                try { const r = await post(`/modelos/${mid}/ropa/${op.dataset.id}/borrar`); ponerTarjeta(mid, r.html); } catch (err) { fallo(); }
                return;
            }
            if (op && e.target.closest('.ss-ropa-elegir')) {
                let r;
                try { r = await post(`/modelos/${mid}/ropa/${op.dataset.id}/elegir`); } catch (err) { return fallo(); }
                op.classList.toggle('on', !!r.elegida);
                const n = $$('.ss-ropa-op', card).length, ok = $$('.ss-ropa-op.on', card).length;
                $('.fv-ph .cuenta', card).textContent = `${ok}/${n}`;
            }
        });
        modelos.addEventListener('change', e => {
            if (e.target.matches('.ss-mod-foto-add input[type="file"]')) {
                subirFotosPerfil(e.target.closest('.ss-modelo'), e.target.files);
                return;
            }
            if (e.target.matches('.ss-ropa-nueva input[type="file"]')) {
                e.target.parentElement.querySelector('span').textContent = e.target.files[0]?.name || 'Foto';
            }
        });
        // Outfits arrastrando o pegando imágenes (Ctrl+V) sobre la tarjeta del modelo.
        let modeloActivo = null;
        const marcarModelo = c => { modeloActivo = c; $$('.ss-modelo', modelos).forEach(x => x.classList.toggle('activo', x === c)); };
        marcarModelo($('.ss-modelo', modelos)); // por defecto, el primero: así siempre se ve dónde irá lo pegado
        modelos.addEventListener('pointerdown', e => { const c = e.target.closest('.ss-modelo'); if (c) marcarModelo(c); });
        modelos.addEventListener('focusin', e => { const c = e.target.closest('.ss-modelo'); if (c) marcarModelo(c); });
        async function subirOutfits(card, files, texto = '') {
            files = [...files].filter(f => f.type.startsWith('image/'));
            if (!card || !files.length) return;
            const mid = card.dataset.id;
            card.classList.add('subiendo');
            let html = null;
            for (const f of files) {
                const fd = new FormData();
                fd.append('imagen', f, f.name || 'outfit.png');
                fd.append('texto', files.length === 1 ? texto : '');
                try { html = (await post(`/modelos/${mid}/ropa/crear`, fd)).html; } catch (err) { fallo('No se ha podido subir alguna imagen.'); break; }
            }
            if (html) { ponerTarjeta(mid, html); marcarModelo($(`.ss-modelo[data-id="${mid}"]`, modelos)); }
            else card.classList.remove('subiendo');
        }
        async function subirFotosPerfil(card, files) {
            files = [...files].filter(f => f.type.startsWith('image/'));
            if (!card || !files.length) return;
            const mid = card.dataset.id;
            const fd = new FormData();
            files.forEach(f => fd.append('fotos[]', f, f.name || 'foto.png'));
            card.classList.add('subiendo');
            try { const r = await post(`/modelos/${mid}/fotos/subir`, fd); ponerTarjeta(mid, r.html); marcarModelo($(`.ss-modelo[data-id="${mid}"]`, modelos)); }
            catch (err) { card.classList.remove('subiendo'); fallo('No se han podido subir las fotos.'); }
        }
        modelos.addEventListener('dragover', e => {
            if (![...e.dataTransfer.types].includes('Files')) return;
            const c = e.target.closest('.ss-modelo');
            if (!c) return;
            e.preventDefault();
            $$('.ss-modelo.soltar', modelos).forEach(x => x !== c && x.classList.remove('soltar'));
            c.classList.add('soltar');
            $$('[data-zona="fotos"]', modelos).forEach(z => z.classList.toggle('soltar', z.contains(e.target)));
        });
        modelos.addEventListener('dragleave', e => {
            const c = e.target.closest('.ss-modelo');
            if (c && !c.contains(e.relatedTarget)) { c.classList.remove('soltar'); $$('[data-zona="fotos"].soltar', c).forEach(z => z.classList.remove('soltar')); }
        });
        modelos.addEventListener('drop', e => {
            const c = e.target.closest('.ss-modelo');
            if (!c || !e.dataTransfer.files.length) return;
            e.preventDefault();
            c.classList.remove('soltar');
            $$('[data-zona="fotos"].soltar', modelos).forEach(z => z.classList.remove('soltar'));
            if (e.target.closest('[data-zona="fotos"]')) subirFotosPerfil(c, e.dataTransfer.files);
            else subirOutfits(c, e.dataTransfer.files);
        });
        document.addEventListener('paste', e => {
            if ($('[data-panel="modelos"]', raiz).hidden) return;
            const files = [...(e.clipboardData?.files || [])].filter(f => f.type.startsWith('image/'));
            if (!files.length) return; // texto normal: se pega como siempre
            const card = e.target.closest?.('.ss-modelo') || modeloActivo || $('.ss-modelo', modelos);
            if (!card) return;
            e.preventDefault();
            // Si se pega en «Nueva opción», lo escrito ahí se usa como nombre del outfit.
            const inp = e.target.closest?.('.ss-ropa-nueva input[name="texto"]');
            const texto = inp ? inp.value.trim() : '';
            if (inp) inp.value = '';
            subirOutfits(card, files, texto);
        });

        modelos.addEventListener('submit', async e => {
            const f = e.target.closest('.ss-ropa-nueva');
            if (!f) return;
            e.preventDefault();
            if (!f.texto.value.trim() && !f.imagen.files.length) return f.texto.focus();
            const mid = f.closest('.ss-modelo').dataset.id;
            try { const r = await post(`/modelos/${mid}/ropa/crear`, new FormData(f)); ponerTarjeta(mid, r.html); }
            catch (err) { fallo('No se ha podido añadir la opción de ropa.'); }
            $(`.ss-modelo[data-id="${mid}"] .ss-ropa-nueva input[name="texto"]`, modelos)?.focus();
        });
    }
})();
