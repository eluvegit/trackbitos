(function () {
    // ---- Modo claro / oscuro (claro por defecto, se recuerda en este navegador) ----
    var tema = document.querySelector('[data-tema]');
    if (tema) {
        tema.addEventListener('click', function () {
            var oscuro = document.documentElement.dataset.theme !== 'dark';
            if (oscuro) {
                document.documentElement.dataset.theme = 'dark';
            } else {
                delete document.documentElement.dataset.theme;
            }
            try { localStorage.setItem('inv-tema', oscuro ? 'dark' : 'light'); } catch (e) {}
        });
    }

    // ---- Visor de foto a pantalla completa ----
    var visor = document.getElementById('inv-visor');
    if (visor) {
        var img    = visor.querySelector('[data-visor-img]');
        var nombre = visor.querySelector('[data-visor-nombre]');
        var codigo = visor.querySelector('[data-visor-codigo]');
        var stock  = visor.querySelector('[data-visor-stock]');

        var cerrar = function () {
            visor.hidden = true;
            img.src = '';
        };

        document.addEventListener('click', function (e) {
            var boton = e.target.closest('[data-foto]');
            if (boton && !boton.disabled && boton.dataset.grande) {
                img.src = boton.dataset.grande;
                nombre.textContent = boton.dataset.nombre;
                codigo.textContent = boton.dataset.codigo;
                codigo.classList.toggle('sin', boton.dataset.sinHueco === '1');
                stock.textContent = boton.dataset.stock + ' uds.';
                visor.hidden = false;
                return;
            }
            if (!visor.hidden && (e.target === visor || e.target.closest('[data-visor-cerrar]'))) {
                cerrar();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !visor.hidden) cerrar();
        });
    }

    // ---- Buscador: filtra por nombre y categoría mientras se escribe ----
    var buscador = document.querySelector('[data-buscador]');
    if (!buscador) return;

    var input       = buscador.querySelector('[data-buscar]');
    var limpiar     = buscador.querySelector('[data-limpiar]');
    var chips       = document.querySelector('[data-chips]');
    var contador    = document.querySelector('[data-contador]');
    var vacio       = document.querySelector('[data-vacio]');
    var piezas      = Array.prototype.slice.call(document.querySelectorAll('[data-pieza]'));

    var normalizar = function (texto) {
        return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    };

    piezas.forEach(function (p) { p._nombre = normalizar(p.dataset.nombre); });

    var catActiva = (chips && chips.querySelector('.activo')) ? chips.querySelector('.activo').dataset.cat : '0';

    var aplicar = function () {
        var q = normalizar(input.value);
        var palabras = q ? q.split(/\s+/) : [];
        var visibles = [];

        piezas.forEach(function (p) {
            var okCat = catActiva === '0' || p.dataset.cat === catActiva;
            var okTexto = palabras.every(function (w) { return p._nombre.indexOf(w) !== -1; });
            var ver = okCat && okTexto;
            p.hidden = !ver;
            if (ver) visibles.push(p);
        });

        limpiar.hidden = input.value === '';
        vacio.hidden = visibles.length > 0;
        contador.textContent = visibles.length === 1 ? '1 pieza' : visibles.length + ' piezas';

        var url = new URL(window.location.href);
        input.value ? url.searchParams.set('q', input.value) : url.searchParams.delete('q');
        catActiva !== '0' ? url.searchParams.set('cat', catActiva) : url.searchParams.delete('cat');
        history.replaceState(null, '', url);
    };

    input.addEventListener('input', aplicar);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') input.blur();
    });

    limpiar.addEventListener('click', function () {
        input.value = '';
        aplicar();
        input.focus();
    });

    if (chips) {
        chips.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-cat]');
            if (!chip) return;
            chips.querySelectorAll('.activo').forEach(function (c) { c.classList.remove('activo'); });
            chip.classList.add('activo');
            catActiva = chip.dataset.cat;
            aplicar();
        });
    }

    aplicar();

    if (window.matchMedia('(pointer: fine)').matches && !input.value) input.focus();
})();
