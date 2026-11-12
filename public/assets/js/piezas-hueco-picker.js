/*
 * Selector de huecos de Piezas.
 *
 * Convierte cualquier <select data-hueco> en un botón que abre un panel con
 * los estuches y sus huecos en rejilla: qué hay dentro de cada uno, cuáles
 * están libres, buscador (por código, zona o pieza que hay dentro), recientes
 * y teclado. El <select> sigue ahí (oculto) y es el que se envía: el panel
 * solo cambia su valor, así que los formularios no cambian.
 *
 * Qué huecos se ofrecen lo decide el propio <select> (sus <option>); los
 * datos de cada hueco salen de piezas/ubicaciones/huecos/mapa, una vez por página.
 *
 * Atributos opcionales en el <select>:
 *   data-hueco-actual="ID"  hueco donde está ahora la pieza (se marca)
 *   data-hueco-para="Nombre" pieza para la que se elige (si no, se busca
 *                            un [data-nombre] / [data-ms-nombre] en su modal)
 *   data-hueco-enviar       al elegir, envía el formulario directamente
 */
(function () {
    'use strict';
    if (window.PzHuecos) return;

    var URL_MAPA = window.PZ_HUECOS_URL;
    var CLAVE_RECIENTES = 'pz_huecos_recientes';
    var mapa = null;
    var cargando = null;

    function cargar() {
        if (mapa) return Promise.resolve(mapa);
        if (!cargando) {
            cargando = fetch(URL_MAPA, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
                .then(function (d) { mapa = d; return d; })
                .catch(function () { cargando = null; return { estuches: [], huecos: {} }; });
        }
        return cargando;
    }

    function plano(t) {
        return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    function compacto(t) { return plano(t).replace(/[\s\-_.·]+/g, ''); }
    function esc(t) {
        return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function recientes() {
        try { return JSON.parse(localStorage.getItem(CLAVE_RECIENTES) || '[]'); } catch (e) { return []; }
    }
    function guardarReciente(id) {
        try {
            var r = recientes().filter(function (x) { return x !== id; });
            r.unshift(id);
            localStorage.setItem(CLAVE_RECIENTES, JSON.stringify(r.slice(0, 6)));
        } catch (e) { /* sin almacenamiento: no pasa nada */ }
    }

    /* ---------- estilos (una vez) ---------- */
    var css = ''
        + '.hp-oculto{position:absolute!important;opacity:0!important;width:1px!important;height:1px!important;pointer-events:none!important;overflow:hidden}'
        + '.hp-btn{display:flex;align-items:center;gap:8px;text-align:left;cursor:pointer;min-width:0;white-space:nowrap;overflow:hidden}'
        + '.hp-btn .hp-v{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis}'
        + '.hp-btn .hp-v.vacio{color:var(--bs-secondary-color)}'
        + '.hp-btn .hp-v b{font-family:"Geist Mono",ui-monospace,monospace;font-weight:600}'
        + '.hp-btn .bi-geo-alt{color:#ff6a3d}'
        + '.hp-btn.hp-mal{border-color:#f0553f!important;box-shadow:0 0 0 3px rgb(240 85 63 / .2)!important}'
        + '.hp-btn:disabled{opacity:.55;cursor:default}'
        + '.hp-capa{position:fixed;inset:0;z-index:2000;background:rgb(0 0 0 / .55);display:flex;align-items:center;justify-content:center;padding:16px}'
        + '.hp-capa[hidden]{display:none}'
        + '.hp-panel{width:min(980px,100%);height:min(720px,100%);display:flex;flex-direction:column;background:var(--bs-body-bg);color:var(--bs-body-color);border:1px solid var(--bs-border-color);border-radius:16px;box-shadow:0 24px 60px rgb(0 0 0 / .45);overflow:hidden;font-family:"Geist",system-ui,sans-serif}'
        + '.hp-cab{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid var(--bs-border-color)}'
        + '.hp-cab > .bi{font-size:18px;color:var(--bs-secondary-color)}'
        + '.hp-cab input{flex:1;min-width:0;border:0;outline:0;background:none;color:inherit;font-size:17px}'
        + '.hp-cerrar{border:0;background:none;color:var(--bs-secondary-color);font-size:20px;line-height:1;padding:4px 6px;border-radius:8px}'
        + '.hp-cerrar:hover{background:rgb(255 255 255 / .08);color:var(--bs-emphasis-color)}'
        + '.hp-barra{display:flex;align-items:center;gap:6px;flex-wrap:wrap;padding:10px 16px;border-bottom:1px solid var(--bs-border-color);font-size:13px}'
        + '.hp-chip{border:1px solid var(--bs-border-color);background:none;color:var(--bs-secondary-color);border-radius:999px;padding:3px 12px;font-size:12.5px}'
        + '.hp-chip[aria-pressed=true]{background:rgb(255 106 61 / .14);border-color:rgb(255 106 61 / .5);color:var(--bs-emphasis-color)}'
        + '.hp-para{margin-left:auto;color:var(--bs-secondary-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:55%}'
        + '.hp-para b{color:var(--bs-emphasis-color);font-weight:600}'
        + '.hp-cuerpo{flex:1;overflow:auto;padding:6px 16px 16px}'
        + '.hp-grupo{margin-top:14px}'
        + '.hp-gcab{display:flex;align-items:baseline;gap:8px;margin-bottom:8px;position:sticky;top:-6px;background:var(--bs-body-bg);padding:6px 0;z-index:1}'
        + '.hp-gcab b{font:600 15px "Geist Mono",ui-monospace,monospace;color:var(--bs-emphasis-color)}'
        + '.hp-gcab span{font-size:12px;color:var(--bs-secondary-color)}'
        + '.hp-rej{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px}'
        + '.hp-h{position:relative;display:flex;flex-direction:column;align-items:stretch;gap:3px;min-height:78px;padding:8px 10px;text-align:left;border:1px solid var(--bs-border-color);border-radius:12px;background:var(--bs-tertiary-bg);color:inherit;font-size:12px;line-height:1.3;cursor:pointer;transition:border-color .1s,background .1s,transform .1s}'
        + '.hp-h:hover{border-color:var(--bs-secondary-color)}'
        + '.hp-h .hp-c{display:flex;align-items:baseline;justify-content:space-between;gap:6px}'
        + '.hp-h .hp-c b{font:600 14px "Geist Mono",ui-monospace,monospace;color:var(--bs-emphasis-color)}'
        + '.hp-h .hp-u{font-size:11px;color:var(--bs-secondary-color);font-variant-numeric:tabular-nums}'
        + '.hp-h .hp-p{color:var(--bs-secondary-color);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}'
        + '.hp-h .hp-mas{color:var(--bs-tertiary-color);font-size:11px}'
        + '.hp-h.libre{border-style:dashed;border-color:rgb(32 201 151 / .55);background:rgb(32 201 151 / .06)}'
        + '.hp-h.libre .hp-l{color:#20c997;font-weight:600}'
        + '.hp-h.actual{border-color:#3d8bfd;background:rgb(61 139 253 / .08)}'
        + '.hp-h .hp-tag{position:absolute;top:-8px;right:8px;font-size:10px;font-weight:600;padding:1px 7px;border-radius:999px;background:#3d8bfd;color:#fff}'
        + '.hp-h.elegido{border-color:#ff6a3d;box-shadow:inset 0 0 0 1px #ff6a3d}'
        + '.hp-h.activo{outline:2px solid #ff6a3d;outline-offset:2px;transform:translateY(-1px)}'
        + '.hp-h mark{background:rgb(255 106 61 / .3);color:inherit;padding:0;border-radius:2px}'
        + '.hp-vacio{padding:40px 10px;text-align:center;color:var(--bs-secondary-color)}'
        + '.hp-pie{display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:10px 16px;border-top:1px solid var(--bs-border-color);font-size:12px;color:var(--bs-secondary-color)}'
        + '.hp-pie kbd{font-size:11px;padding:1px 5px;border-radius:4px;background:var(--bs-tertiary-bg);border:1px solid var(--bs-border-color);color:inherit}'
        + '.hp-pie .hp-quitar{margin-left:auto;border:0;background:none;color:var(--bs-secondary-color);text-decoration:underline;font-size:12px}'
        + '.hp-pie .hp-aviso{color:#ff6a3d}'
        + '@media (max-width:600px){.hp-capa{padding:0}.hp-panel{height:100%;border-radius:0;border:0}.hp-rej{grid-template-columns:repeat(2,minmax(0,1fr))}.hp-teclas{display:none}.hp-para{max-width:100%;margin-left:0;flex-basis:100%}}';
    var estilo = document.createElement('style');
    estilo.textContent = css;
    document.head.appendChild(estilo);

    /* ---------- panel (uno por página, se mueve donde haga falta) ---------- */
    var capa = document.createElement('div');
    capa.className = 'hp-capa';
    capa.hidden = true;
    capa.innerHTML = ''
        + '<div class="hp-panel" role="dialog" aria-modal="true" aria-label="Elegir hueco">'
        + '  <div class="hp-cab"><i class="bi bi-search"></i><input type="search" autocomplete="off" placeholder="E1H2, H2, zona o pieza que hay dentro…" aria-label="Buscar hueco"><button type="button" class="hp-cerrar" aria-label="Cerrar">&times;</button></div>'
        + '  <div class="hp-barra">'
        + '    <button type="button" class="hp-chip" data-f="todos" aria-pressed="true">Todos</button>'
        + '    <button type="button" class="hp-chip" data-f="libres" aria-pressed="false">Solo libres</button>'
        + '    <span class="hp-para"></span>'
        + '  </div>'
        + '  <div class="hp-cuerpo"></div>'
        + '  <div class="hp-pie"><span class="hp-teclas"><kbd>↑</kbd><kbd>↓</kbd><kbd>←</kbd><kbd>→</kbd> moverse · <kbd>Enter</kbd> elegir · <kbd>Esc</kbd> cerrar</span><span class="hp-aviso"></span><button type="button" class="hp-quitar" hidden>Sin hueco</button></div>'
        + '</div>';
    var entrada = capa.querySelector('input');
    var cuerpo = capa.querySelector('.hp-cuerpo');
    var para = capa.querySelector('.hp-para');
    var aviso = capa.querySelector('.hp-aviso');
    var quitar = capa.querySelector('.hp-quitar');
    var chips = capa.querySelectorAll('.hp-chip');

    var actualSelect = null;
    var filtro = 'todos';
    var activo = -1;
    var celdas = [];

    function opcionesDe(select) {
        var lista = [];
        Array.prototype.forEach.call(select.options, function (o) {
            if (o.value === '' || o.disabled) return;
            var grupo = o.parentNode && o.parentNode.tagName === 'OPTGROUP' ? o.parentNode.label : '';
            lista.push({ id: o.value, texto: o.textContent.trim(), grupo: grupo });
        });
        return lista;
    }

    function datosDe(op) {
        var h = mapa && mapa.huecos ? mapa.huecos[op.id] : null;
        if (h) return h;
        // Sin mapa (falló la carga): lo que diga la propia opción.
        var codigo = op.texto.replace(/\s*\(libre\)\s*$/i, '');
        var m = codigo.match(/^(.*?)(H\d.*)$/i);
        return {
            codigo: codigo, h: m ? m[2] : codigo, estuche: op.grupo || (m ? m[1] : ''), orden: 0,
            piezas: [], uds: 0, notas: '', _libre: /\(libre\)/i.test(op.texto), _sinMapa: true
        };
    }

    function estucheDe(d) {
        if (!mapa || d._sinMapa) return { codigo: String(d.estuche || 'Huecos'), zona: '' };
        for (var i = 0; i < mapa.estuches.length; i++) {
            if (mapa.estuches[i].id === d.estuche) return mapa.estuches[i];
        }
        return { codigo: '?', zona: '' };
    }

    function resaltar(texto, terminos) {
        var t = esc(texto);
        terminos.forEach(function (term) {
            if (term.length < 2) return;
            var i = plano(texto).indexOf(term);
            if (i >= 0) {
                var orig = texto.substr(i, term.length);
                t = t.replace(esc(orig), '<mark>' + esc(orig) + '</mark>');
            }
        });
        return t;
    }

    function tile(op, d, sel, act, terminos) {
        var libre = d._sinMapa ? d._libre : d.piezas.length === 0;
        var cls = 'hp-h' + (libre ? ' libre' : '') + (String(act) === op.id ? ' actual' : '') + (sel === op.id ? ' elegido' : '');
        var html = '<button type="button" class="' + cls + '" data-id="' + esc(op.id) + '" title="' + esc(d.codigo) + (d.notas ? ' — ' + esc(d.notas) : '') + '">';
        if (String(act) === op.id) html += '<span class="hp-tag">está aquí</span>';
        html += '<span class="hp-c"><b>' + esc(d.h || d.codigo) + '</b>';
        if (!libre && !d._sinMapa) html += '<span class="hp-u">' + d.uds + ' ud.</span>';
        html += '</span>';
        if (libre) {
            html += '<span class="hp-l">Libre</span>';
        } else if (d._sinMapa) {
            html += '<span class="hp-p">Ocupado</span>';
        } else {
            // Primero las piezas que encajan con la búsqueda.
            var piezas = d.piezas.slice();
            if (terminos.length) {
                piezas.sort(function (a, b) {
                    var ea = terminos.some(function (t) { return plano(a.n + ' ' + a.sku).indexOf(t) >= 0; });
                    var eb = terminos.some(function (t) { return plano(b.n + ' ' + b.sku).indexOf(t) >= 0; });
                    return (eb ? 1 : 0) - (ea ? 1 : 0);
                });
            }
            piezas.slice(0, 2).forEach(function (p) {
                html += '<span class="hp-p" title="' + esc(p.n) + ' · ' + p.uds + ' ud.">' + resaltar(p.n, terminos) + ' · ' + p.uds + '</span>';
            });
            if (piezas.length > 2) html += '<span class="hp-mas">+' + (piezas.length - 2) + ' más</span>';
        }
        return html + '</button>';
    }

    function pintar() {
        var select = actualSelect;
        if (!select) return;
        var q = entrada.value.trim();
        var terminos = plano(q).split(/\s+/).filter(Boolean);
        var qc = compacto(q);
        var sel = select.value;
        var act = select.dataset.huecoActual || '';
        var ops = opcionesDe(select);

        var exacto = null;
        var grupos = {};
        var ordenGrupos = [];
        ops.forEach(function (op) {
            var d = datosDe(op);
            var libre = d._sinMapa ? d._libre : d.piezas.length === 0;
            if (filtro === 'libres' && !libre) return;
            var est = estucheDe(d);
            if (terminos.length) {
                var pajar = plano([d.codigo, d.h, est.codigo, est.zona, d.notas, libre ? 'libre vacio' : ''].concat(
                    (d.piezas || []).map(function (p) { return p.n + ' ' + p.sku; })).join(' '));
                var porCodigo = qc && compacto(d.codigo).indexOf(qc) === 0;
                var ok = porCodigo || terminos.every(function (t) { return pajar.indexOf(t) >= 0; });
                if (!ok) return;
                if (qc && compacto(d.codigo) === qc) exacto = op.id;
            }
            var k = est.codigo + '|' + (d.orden || 0);
            if (!grupos[k]) { grupos[k] = { est: est, orden: d.orden || 0, items: [] }; ordenGrupos.push(k); }
            grupos[k].items.push({ op: op, d: d });
        });
        ordenGrupos.sort(function (a, b) { return grupos[a].orden - grupos[b].orden || a.localeCompare(b); });

        var html = '';
        // Recientes: solo sin búsqueda, y solo los que este selector ofrece.
        if (!terminos.length && filtro === 'todos') {
            var porId = {};
            ops.forEach(function (op) { porId[op.id] = op; });
            var rec = recientes().filter(function (id) { return porId[id]; }).slice(0, 6);
            if (rec.length) {
                html += '<div class="hp-grupo"><div class="hp-gcab"><b>Recientes</b></div><div class="hp-rej">';
                rec.forEach(function (id) {
                    var d = datosDe(porId[id]);
                    var copia = {}; for (var k in d) copia[k] = d[k];
                    copia.h = d.codigo; // aquí con el código entero: mezcla estuches
                    html += tile(porId[id], copia, sel, act, terminos);
                });
                html += '</div></div>';
            }
        }
        ordenGrupos.forEach(function (k) {
            var g = grupos[k];
            var libres = g.items.filter(function (x) { return x.d._sinMapa ? x.d._libre : x.d.piezas.length === 0; }).length;
            html += '<div class="hp-grupo"><div class="hp-gcab"><b>' + esc(g.est.codigo) + '</b>'
                + (g.est.zona ? '<span>' + esc(g.est.zona) + '</span>' : '')
                + '<span>· ' + g.items.length + ' huecos' + (libres ? ', ' + libres + ' libres' : '') + '</span></div><div class="hp-rej">';
            g.items.forEach(function (x) { html += tile(x.op, x.d, sel, act, terminos); });
            html += '</div></div>';
        });
        if (!html) {
            html = '<div class="hp-vacio">' + (ops.length ? 'Ningún hueco coincide' + (q ? ' con «' + esc(q) + '»' : '') + '.' : 'No hay huecos que elegir. Créalos en Ubicaciones.') + '</div>';
        }
        cuerpo.innerHTML = html;
        celdas = Array.prototype.slice.call(cuerpo.querySelectorAll('.hp-h'));

        // Qué queda marcado para Enter: el código exacto escrito, si no el
        // elegido ahora, si no el primero de la lista.
        var idx = -1;
        if (exacto !== null) idx = indicePorId(exacto, true);
        if (idx < 0 && !terminos.length && sel) idx = indicePorId(sel, false);
        if (idx < 0 && celdas.length && terminos.length) idx = 0;
        marcar(idx, false);
    }

    function indicePorId(id, saltarRecientes) {
        for (var i = celdas.length - 1; i >= 0; i--) {
            // Al buscar, el de su estuche (el último) antes que el de recientes.
            if (celdas[i].dataset.id === id) return i;
        }
        return -1;
    }

    function marcar(i, desplazar) {
        if (activo >= 0 && celdas[activo]) celdas[activo].classList.remove('activo');
        activo = i;
        if (i >= 0 && celdas[i]) {
            celdas[i].classList.add('activo');
            if (desplazar !== false) celdas[i].scrollIntoView({ block: 'nearest' });
        }
    }

    function mover(dir) {
        if (!celdas.length) return;
        if (activo < 0) { marcar(0); return; }
        if (dir === 'der') { marcar(Math.min(celdas.length - 1, activo + 1)); return; }
        if (dir === 'izq') { marcar(Math.max(0, activo - 1)); return; }
        var cur = celdas[activo].getBoundingClientRect();
        var mejor = -1, mejorDy = Infinity, mejorDx = Infinity;
        celdas.forEach(function (c, i) {
            var r = c.getBoundingClientRect();
            var dy = dir === 'abajo' ? r.top - cur.top : cur.top - r.top;
            if (dy <= 4) return;
            var dx = Math.abs(r.left - cur.left);
            if (dy < mejorDy - 4 || (Math.abs(dy - mejorDy) <= 4 && dx < mejorDx)) { mejor = i; mejorDy = dy; mejorDx = dx; }
        });
        if (mejor >= 0) marcar(mejor);
    }

    function abrir(select) {
        if (select.disabled) return;
        actualSelect = select;
        // Dentro de un modal de Bootstrap el panel va en el propio modal: si
        // no, su trampa de foco no deja escribir en el buscador.
        var modal = select.closest('.modal');
        var contenedor = modal || document.body;
        if (capa.parentNode !== contenedor) contenedor.appendChild(capa);

        var nombre = select.dataset.huecoPara || '';
        if (!nombre && modal) {
            var n = modal.querySelector('[data-nombre], [data-ms-nombre]');
            nombre = n ? n.textContent.trim() : '';
        }
        para.innerHTML = nombre ? 'Para <b>' + esc(nombre) + '</b>' : '';
        aviso.textContent = select.hasAttribute('data-hueco-enviar') ? 'Al elegir, se guarda.' : '';
        var vacia = Array.prototype.some.call(select.options, function (o) { return o.value === ''; });
        quitar.hidden = !(vacia && !select.required && select.value !== '');

        entrada.value = '';
        filtro = 'todos';
        chips.forEach(function (c) { c.setAttribute('aria-pressed', c.dataset.f === filtro ? 'true' : 'false'); });
        capa.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        cuerpo.innerHTML = '<div class="hp-vacio">Cargando huecos…</div>';
        setTimeout(function () { entrada.focus(); }, 0);
        cargar().then(pintar);
    }

    function cerrar() {
        capa.hidden = true;
        document.documentElement.style.overflow = '';
        if (actualSelect && actualSelect._hpBoton) actualSelect._hpBoton.focus();
        actualSelect = null;
    }

    function elegir(id) {
        var select = actualSelect;
        if (!select) return;
        select.value = id;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        if (id) guardarReciente(id);
        sync(select);
        cerrar();
        if (id && select.hasAttribute('data-hueco-enviar') && select.form) {
            if (select.form.requestSubmit) select.form.requestSubmit(); else select.form.submit();
        }
    }

    cuerpo.addEventListener('click', function (e) {
        var b = e.target.closest('.hp-h');
        if (b) elegir(b.dataset.id);
    });
    capa.addEventListener('mousedown', function (e) { if (e.target === capa) cerrar(); });
    capa.querySelector('.hp-cerrar').addEventListener('click', cerrar);
    quitar.addEventListener('click', function () { elegir(''); });
    chips.forEach(function (c) {
        c.addEventListener('click', function () {
            filtro = c.dataset.f;
            chips.forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
            pintar();
            entrada.focus();
        });
    });
    entrada.addEventListener('input', pintar);
    capa.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); cerrar(); return; }
        if (e.key === 'Enter') { e.preventDefault(); if (activo >= 0 && celdas[activo]) elegir(celdas[activo].dataset.id); return; }
        var dirs = { ArrowDown: 'abajo', ArrowUp: 'arriba', ArrowRight: 'der', ArrowLeft: 'izq' };
        if (dirs[e.key]) {
            // Izquierda/derecha dentro del texto del buscador siguen moviendo el cursor.
            if ((e.key === 'ArrowLeft' || e.key === 'ArrowRight') && e.target === entrada && entrada.value) return;
            e.preventDefault();
            mover(dirs[e.key]);
        }
    }, true);

    /* ---------- el botón que sustituye a cada <select> ---------- */
    function etiqueta(select) {
        var o = select.options[select.selectedIndex];
        if (o && o.value !== '') {
            var d = mapa && mapa.huecos ? mapa.huecos[o.value] : null;
            var codigo = d ? d.codigo : o.textContent.replace(/\s*\(libre\)\s*$/i, '').trim();
            var extra = d ? (d.piezas.length ? ' · ' + d.piezas.length + ' pieza' + (d.piezas.length === 1 ? '' : 's') : ' · libre') : '';
            return '<span class="hp-v"><b>' + esc(codigo) + '</b>' + esc(extra) + '</span>';
        }
        var vacia = Array.prototype.find.call(select.options, function (x) { return x.value === ''; });
        return '<span class="hp-v vacio">' + esc(vacia ? vacia.textContent.trim() : 'Elegir hueco…') + '</span>';
    }

    function sync(select) {
        var b = select._hpBoton;
        if (!b) return;
        b.innerHTML = '<i class="bi bi-geo-alt"></i>' + etiqueta(select);
        b.disabled = select.disabled;
        b.classList.remove('hp-mal');
    }

    function mejorar(select) {
        if (select._hpBoton) return;
        var b = document.createElement('button');
        b.type = 'button';
        b.className = (select.className + ' hp-btn').trim();
        if (select.getAttribute('style')) b.setAttribute('style', select.getAttribute('style'));
        b.setAttribute('aria-haspopup', 'dialog');
        if (select.title) b.title = select.title;
        select._hpBoton = b;
        select.classList.add('hp-oculto');
        select.setAttribute('tabindex', '-1');
        select.parentNode.insertBefore(b, select.nextSibling);
        b.addEventListener('click', function () { abrir(select); });
        select.addEventListener('change', function () { sync(select); });
        // Si el formulario se intenta enviar sin elegir (required): marca el
        // botón y abre el panel en vez del globo del navegador.
        select.addEventListener('invalid', function (e) {
            e.preventDefault();
            b.classList.add('hp-mal');
            abrir(select);
        });
        sync(select);
        // El nº de piezas de la etiqueta llega con el mapa (solo se pide si
        // la página tiene algún selector de hueco).
        cargar().then(function () { sync(select); });
    }

    function mejorarTodo(raiz) {
        (raiz || document).querySelectorAll('select[data-hueco]').forEach(mejorar);
    }

    // Los modales cambian el valor por código al abrirse (sin 'change'):
    // se repinta la etiqueta de sus selectores cada vez que se abren.
    document.addEventListener('shown.bs.modal', function (e) {
        e.target.querySelectorAll('select[data-hueco]').forEach(function (s) { mejorar(s); sync(s); });
    });
    // Paneles repintados por AJAX (p. ej. la bitácora).
    new MutationObserver(function (cambios) {
        cambios.forEach(function (c) {
            c.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                if (n.matches && n.matches('select[data-hueco]')) mejorar(n); else if (n.querySelectorAll) mejorarTodo(n);
            });
        });
    }).observe(document.documentElement, { childList: true, subtree: true });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { mejorarTodo(); });
    else mejorarTodo();

    window.PzHuecos = { abrir: abrir, sync: sync, mejorar: mejorarTodo };
})();
