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
    if (!$(`[data-panel="${tabInicial}"]`, raiz)) { try { tabInicial = sessionStorage.getItem(claveTab) || 'plan'; } catch (e) { tabInicial = 'plan'; } }
    abrirTab($(`[data-panel="${tabInicial}"]`, raiz) ? tabInicial : 'plan');
    const cuenta = (k, v) => { const el = $(`[data-cuenta="${k}"]`, secs); if (el) el.textContent = v; };

    // ---------- menú "más" ----------
    document.addEventListener('click', e => {
        $$('.ss-menu[open]').forEach(d => { if (!d.contains(e.target)) d.open = false; });
        if (!e.target.closest('.ss-mover-pop, .ss-mover')) $$('.ss-mover-pop').forEach(p => p.remove());
    });

    // ---------- etapas ----------
    $$('.ss-etapa', raiz).forEach(et => et.addEventListener('click', async e => {
        const b = e.target.closest('.ss-stepper button');
        if (!b) return;
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

    // ---------- equipo ----------
    const equipo = $('#ssEquipo');
    function pintarEquipo() {
        const items = $$('.fv-check', equipo);
        const ok = items.filter(i => $('input', i).checked).length;
        $('#ssEqCuenta').textContent = `${ok}/${items.length}`;
        $('#ssEqBarra').style.width = (items.length ? ok / items.length * 100 : 0) + '%';
        $('#ssEqBarra').parentElement.classList.toggle('ok', items.length > 0 && ok === items.length);
        cuenta('equipo', `${ok}/${items.length}`);
    }
    const htmlEq = it => `<label class="fv-check${+it.marcado ? ' hecha' : ''}" data-id="${it.id}"><input type="checkbox"${+it.marcado ? ' checked' : ''}><span>${esc(it.item)}</span><button type="button" class="deshacer ss-eq-borrar" title="Quitar"><i class="bi bi-x-lg"></i></button></label>`;
    if (equipo) {
        equipo.addEventListener('change', async e => {
            const lab = e.target.closest('.fv-check');
            if (!lab) return;
            try { const r = await post(`/equipo/${lab.dataset.id}/toggle`); e.target.checked = !!r.marcado; }
            catch (err) { e.target.checked = !e.target.checked; fallo(); }
            lab.classList.toggle('hecha', e.target.checked);
            pintarEquipo();
        });
        equipo.addEventListener('click', async e => {
            const b = e.target.closest('.ss-eq-borrar');
            if (!b) return;
            e.preventDefault();
            const lab = b.closest('.fv-check');
            try { await post(`/equipo/${lab.dataset.id}/borrar`); lab.remove(); pintarEquipo(); } catch (err) { fallo(); }
        });
        // Varios ítems de golpe: separados por salto de línea o «;».
        async function agregarEquipo(texto) {
            const lineas = texto.split(/\r?\n|;/).map(s => s.replace(/^[-*•\s]+/, '').trim()).filter(Boolean);
            for (const item of lineas) {
                try { const r = await post('/equipo/agregar', { item }); equipo.insertAdjacentHTML('beforeend', htmlEq(r.item)); }
                catch (err) { fallo(); break; }
            }
            pintarEquipo();
        }
        $('#ssEqNuevo').addEventListener('submit', e => {
            e.preventDefault();
            const inp = e.target.item;
            const t = inp.value;
            inp.value = '';
            agregarEquipo(t);
        });
        // Pegar una lista de varias líneas: un <input> aplanaría los saltos, así que se intercepta.
        $('#ssEqNuevo input').addEventListener('paste', e => {
            const t = e.clipboardData.getData('text');
            if (!/\n/.test(t.trim())) return;
            e.preventDefault();
            agregarEquipo(t);
        });
        $('#ssEqCopiar')?.addEventListener('click', async () => {
            let r;
            try { r = await post('/equipo/copiar', { desde: $('#ssEqDesde').value }); } catch (err) { return fallo(); }
            r.items.forEach(it => equipo.insertAdjacentHTML('beforeend', htmlEq(it)));
            pintarEquipo();
            if (!r.items.length) alert('Ya tenías todos esos ítems.');
        });
    }

    // ---------- model releases ----------
    const releases = $('#ssReleases');
    const recontarModelos = () => {
        const nr = $$('li', releases).length, nm = $$('.ss-msg', raiz).length;
        $('#ssRelCuenta').textContent = nr; $('#ssMsgCuenta').textContent = nm; cuenta('modelos', nr + nm);
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
})();
