/* Lectura — comportamiento de las vistas nuevas */
(function () {
    'use strict';

    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(body || {}),
    }).then(r => r.json());

    const esc = s => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };
    const normaliza = s => String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

    // Marca el punto de hoy como tocado en la tarjeta/ficha
    function marcarHoy(raiz) {
        raiz.querySelectorAll('.lc-puntos i.hoy').forEach(i => i.classList.add('on'));
    }

    // ---------- "Hoy he leído" rápido (biblioteca) ----------
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-lc-rapido]');
        if (!b) return;
        const caja = b.closest('[data-lc-hoy]');
        caja.querySelectorAll('button').forEach(x => x.disabled = true);
        post(b.dataset.lcRapido, {}).then(d => {
            if (!d.success) throw new Error();
            caja.innerHTML = '<span class="lc-hecho nueva"><i class="bi bi-check-circle-fill"></i>Leído hoy</span>';
            const tarjeta = caja.closest('[data-lc-libro]');
            marcarHoy(tarjeta);
            const ult = tarjeta.querySelector('[data-lc-ultima]');
            if (ult) ult.innerHTML = '<i class="bi bi-clock-history"></i>Última vez leído <b>hoy</b>';
            // Siguiente paso natural (opcional): apuntar por qué página vas
            const pag = tarjeta.querySelector('[data-lc-pagina]');
            if (pag) { pag.focus(); pag.select(); }
        }).catch(() => caja.querySelectorAll('button').forEach(x => x.disabled = false));
    });

    // ---------- "Hoy no toca" ----------
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-lc-notoca]');
        if (!b) return;
        b.disabled = true;
        post(b.dataset.lcNotoca, {}).then(d => {
            if (!d.success) throw new Error();
            if (b.hasAttribute('data-recargar')) { location.reload(); return; }
            b.closest('[data-lc-hoy]').innerHTML = '<span class="lc-hecho neu nueva"><i class="bi bi-moon"></i>Hoy no toca</span>';
        }).catch(() => { b.disabled = false; });
    });

    // ---------- Página actual: guarda al salir del campo o con Enter ----------
    document.querySelectorAll('[data-lc-pagina]').forEach(inp => {
        let ultimo = inp.value;
        const guardar = () => {
            const page = Math.max(0, parseInt(inp.value, 10) || 0);
            if (String(page) === String(ultimo)) return;
            post(inp.dataset.lcPagina, { page }).then(d => {
                if (!d.success) return;
                inp.value = ultimo = String(d.current_page);
                const raiz = inp.closest('[data-lc-libro]') || document;
                if (d.progreso !== null && d.progreso !== undefined) {
                    raiz.querySelectorAll('[data-lc-fill]').forEach(f => f.style.width = d.progreso + '%');
                    raiz.querySelectorAll('[data-lc-pct]').forEach(p => p.textContent = d.progreso + '%');
                }
                raiz.querySelectorAll('[data-lc-quedan]').forEach(q => {
                    const quedan = (+q.dataset.total) - d.current_page;
                    q.textContent = quedan > 0 ? 'quedan ' + quedan + ' págs.' : 'completo';
                });
                const ok = inp.parentElement.querySelector('.lc-ok');
                if (ok) { ok.classList.add('ver'); setTimeout(() => ok.classList.remove('ver'), 1400); }
            });
        };
        inp.addEventListener('blur', guardar);
        inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
    });

    // ---------- Selector de estado (ficha): guarda al toque ----------
    document.querySelectorAll('[data-lc-estados]').forEach(caja => {
        caja.addEventListener('click', e => {
            const b = e.target.closest('button[data-status]');
            if (!b || b.classList.contains('on')) return;
            caja.querySelectorAll('button').forEach(x => x.disabled = true);
            post(caja.dataset.lcEstados, { status: b.dataset.status })
                .then(d => { if (d.success) location.reload(); else throw new Error(); })
                .catch(() => caja.querySelectorAll('button').forEach(x => x.disabled = false));
        });
    });

    // ---------- Sesión del día (ficha) ----------
    const dia = document.querySelector('[data-lc-dia]');
    if (dia) {
        const form = dia.querySelector('[data-lc-sesion]');
        dia.querySelector('[data-lc-abrir-sesion]').addEventListener('click', () => {
            dia.querySelector('[data-lc-dia-botones]').hidden = true;
            form.hidden = false;
            form.querySelector('[name=page_reached]').focus();
        });
        const hiloN = form.querySelector('[data-lc-hilo-n]');
        const hiloIn = form.querySelector('[name=lost_thread_count]');
        form.querySelector('[data-lc-hilo]').addEventListener('click', () => {
            hiloIn.value = (+hiloIn.value) + 1;
            hiloN.textContent = hiloIn.value + '×';
        });
        form.addEventListener('submit', e => {
            e.preventDefault();
            const btn = form.querySelector('[type=submit]');
            btn.disabled = true;
            post(form.dataset.lcSesion, Object.fromEntries(new FormData(form)))
                .then(d => { if (d.success) location.reload(); else throw new Error(); })
                .catch(() => { btn.disabled = false; });
        });
    }

    // ---------- Valoración con estrellas ----------
    document.querySelectorAll('[data-lc-valorar]').forEach(caja => {
        const inp = caja.querySelector('input');
        const pinta = v => caja.querySelectorAll('button').forEach(b => {
            const on = +b.dataset.v <= v;
            b.classList.toggle('on', on);
            b.querySelector('.bi').className = 'bi ' + (on ? 'bi-star-fill' : 'bi-star');
        });
        caja.addEventListener('click', e => {
            const b = e.target.closest('button[data-v]');
            if (!b) return;
            const v = +b.dataset.v === +inp.value ? 0 : +b.dataset.v; // tocar la misma la quita
            inp.value = v || '';
            pinta(v);
        });
    });

    // ---------- Meta anual ----------
    document.addEventListener('click', e => {
        if (!e.target.closest('[data-lc-meta-toggle]')) return;
        const f = document.querySelector('[data-lc-meta]');
        f.hidden = !f.hidden;
        if (!f.hidden) f.querySelector('input[type=number]').focus();
    });

    // ---------- Filtro del estante ----------
    const filtro = document.querySelector('[data-lc-filtro]');
    if (filtro) {
        filtro.addEventListener('input', () => {
            const q = normaliza(filtro.value.trim());
            document.querySelectorAll('[data-lc-busca]').forEach(el => {
                el.hidden = q !== '' && !normaliza(el.dataset.lcBusca).includes(q);
            });
        });
    }

    // ---------- Buscador Open Library + portada ----------
    document.querySelectorAll('[data-lc-buscador]').forEach(caja => {
        const form = caja.closest('form');
        const q = caja.querySelector('[data-lc-q]');
        const btn = caja.querySelector('[data-lc-buscar]');
        const res = caja.querySelector('[data-lc-resultados]');
        const elegido = caja.querySelector('[data-lc-elegido]');
        const preview = caja.querySelector('[data-lc-preview]');
        const archivo = caja.querySelector('[data-lc-archivo]');
        const subirTxt = caja.querySelector('[data-lc-subir-txt]');
        const campo = n => form.querySelector('[name="' + n + '"]');

        const verPortada = url => {
            preview.src = url || '';
            elegido.hidden = !url;
        };

        const buscar = () => {
            const texto = q.value.trim();
            if (texto.length < 3) return;
            btn.disabled = true;
            res.hidden = false;
            res.innerHTML = '<div class="lc-res-vacio">Buscando…</div>';
            fetch(caja.dataset.lcBuscador + '?q=' + encodeURIComponent(texto), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => pinta(d.resultados || []))
                .catch(() => pinta([]))
                .finally(() => { btn.disabled = false; });
        };

        const pinta = items => {
            if (!items.length) {
                res.innerHTML = '<div class="lc-res-vacio">Sin resultados. Puedes rellenarlo a mano y subir la portada.</div>';
                return;
            }
            res.innerHTML = items.map((it, i) => `
                <button type="button" class="lc-res" data-i="${i}">
                    <span class="lc-portada${it.cover_url ? '' : ' sin'}">${it.cover_url ? `<img src="${esc(it.cover_url)}" alt="" loading="lazy">` : ''}</span>
                    <span><span class="t">${esc(it.title)}</span>${it.author ? `<br><span class="d">${esc(it.author)}${it.total_pages ? ' · ' + it.total_pages + ' págs.' : ''}</span>` : ''}</span>
                </button>`).join('');
            res.querySelectorAll('.lc-res').forEach(b => b.addEventListener('click', () => elegir(items[+b.dataset.i])));
        };

        const elegir = l => {
            if (l.title) campo('title').value = l.title;
            if (l.author) campo('author').value = l.author;
            if (l.total_pages && campo('total_pages')) campo('total_pages').value = l.total_pages;
            if (l.isbn) campo('isbn').value = l.isbn;
            if (l.cover_url) { campo('cover_url').value = l.cover_url; verPortada(l.cover_url); }
            res.hidden = true;
            res.innerHTML = '';
        };

        btn.addEventListener('click', buscar);
        q.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });

        caja.querySelector('[data-lc-quitar]').addEventListener('click', () => {
            campo('cover_url').value = '';
            archivo.value = '';
            subirTxt.textContent = '¿No aparece? Sube tú la portada';
            verPortada('');
        });

        archivo.addEventListener('change', () => {
            const f = archivo.files[0];
            if (!f) return;
            // La imagen subida gana siempre a la URL al guardar
            subirTxt.textContent = f.name;
            verPortada(URL.createObjectURL(f));
        });
    });
})();
