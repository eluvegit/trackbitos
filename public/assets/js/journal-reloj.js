(() => {
    const raiz = document.getElementById('jReloj');
    if (!raiz) return;
    const URL = raiz.dataset.url;
    const CLAVE = 'journalReloj';
    const $ = s => raiz.querySelector(s);
    const lee = () => { try { return JSON.parse(localStorage.getItem(CLAVE)) || null; } catch (e) { return null; } };
    const guarda = v => { try { v ? localStorage.setItem(CLAVE, JSON.stringify(v)) : localStorage.removeItem(CLAVE); } catch (e) {} };
    const hoy = () => { const d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
    const seg = r => Math.max(0, Math.floor((Date.now() - r.inicio) / 1000));
    const fmt = s => [Math.floor(s / 3600), Math.floor(s / 60) % 60, s % 60].map(n => String(n).padStart(2, '0')).join(':');
    const post = (url, body) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(body),
    }).then(r => r.json());

    let siguiente = null;
    let minutos = 0;

    function pintar() {
        const r = lee();
        raiz.hidden = !r;
        document.body.classList.toggle('j-reloj-on', !!r);
        if (!r) return;
        $('.jr-t').textContent = fmt(seg(r));
        $('.jr-nm').textContent = r.titulo;
        $('.jr-nm').href = URL + '/edit/' + r.id;
    }

    function abrirHoja() {
        const r = lee();
        if (!r) return;
        minutos = Math.max(1, Math.round(seg(r) / 60));
        $('.jr-min').value = minutos;
        $('.jr-que').textContent = r.titulo;
        $('.jr-fecha').value = hoy();
        raiz.classList.add('hoja');
        $('.jr-min').focus();
    }
    const cerrarHoja = () => raiz.classList.remove('hoja');

    async function guardar() {
        const r = lee();
        if (!r) return;
        const m = parseInt($('.jr-min').value, 10) || 0;
        const fecha = $('.jr-fecha').value || hoy();
        const b = $('.jr-guardar');
        b.disabled = true;
        try {
            let d = {};
            if (m > 0) {
                d = await post(URL + '/add-time/' + r.id, { minutes: m });
                if (!d.success) throw new Error('No se pudo sumar el tiempo.');
            }
            const l = await post(URL + '/add-log/' + r.id, { date: fecha, minutes: m });
            if (!l.success) throw new Error(l.error || 'No se pudo apuntar la fecha.');
            guarda(null);
            cerrarHoja();
            pintar();
            document.dispatchEvent(new CustomEvent('jreloj:guardado', { detail: { id: r.id, minutos: m, total: d.minutes, resumen: d.category_summary } }));
            seguir();
        } catch (err) {
            alert(err.message);
        } finally {
            b.disabled = false;
        }
    }

    function descartar() {
        if (!confirm('¿Descartar el tiempo del cronómetro sin apuntarlo?')) return;
        guarda(null);
        cerrarHoja();
        pintar();
        seguir();
    }

    function seguir() {
        if (!siguiente) return;
        const s = siguiente;
        siguiente = null;
        empezar(s.id, s.titulo);
    }

    function empezar(id, titulo) {
        const r = lee();
        if (r && String(r.id) === String(id)) return;
        if (r) {
            if (!confirm('El cronómetro está con «' + r.titulo + '». ¿Pararlo y apuntar ese tiempo antes de empezar esta?')) return;
            siguiente = { id, titulo };
            abrirHoja();
            return;
        }
        guarda({ id: String(id), titulo, inicio: Date.now() });
        pintar();
        document.dispatchEvent(new CustomEvent('jreloj:empezado', { detail: { id: String(id) } }));
    }

    $('.jr-parar').addEventListener('click', abrirHoja);
    $('.jr-guardar').addEventListener('click', guardar);
    $('.jr-descartar').addEventListener('click', descartar);
    $('.jr-volver').addEventListener('click', () => { siguiente = null; cerrarHoja(); });
    $('.jr-min').addEventListener('keydown', e => { if (e.key === 'Enter') guardar(); });
    raiz.querySelectorAll('[data-jr-mas]').forEach(b => b.addEventListener('click', () => {
        const v = Math.max(1, (parseInt($('.jr-min').value, 10) || 0) + parseInt(b.dataset.jrMas, 10));
        $('.jr-min').value = v;
    }));
    window.addEventListener('storage', e => { if (e.key === CLAVE) pintar(); });
    setInterval(() => { const r = lee(); if (r && !raiz.classList.contains('hoja')) $('.jr-t').textContent = fmt(seg(r)); }, 1000);

    window.JReloj = { empezar, actual: () => lee() };
    pintar();
})();
