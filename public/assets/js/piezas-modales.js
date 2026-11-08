window.pzInitCalc = function () {
    if (window.pzCalcLista) return;
    window.pzCalcLista = true;
    // ---- Calculadora de tiempo estimado por capas ---------------------
    // Todo en vivo y en el navegador: al teclear capas (o al retocar los
    // ajustes, aunque aún no se hayan guardado) se recalcula. El
    // minuto/capa sale de la referencia, nunca es una constante suelta.
    var calcModal = document.getElementById('modalCalcTiempo');
    if (calcModal) {
        var calcCaja      = calcModal.querySelector('.modal-content');
        var campoCapas    = document.getElementById('calcCapas');
        var campoCapasRef = document.getElementById('cfgCapasRef');
        var campoMinRef   = document.getElementById('cfgMinRef');
        var campoMinPrep  = document.getElementById('cfgMinPrep');

        var elVacio      = document.getElementById('calcVacio');
        var elTabla      = document.getElementById('calcResultado');
        var elImpBonito  = document.getElementById('calcImpresionBonito');
        var elImpMin     = document.getElementById('calcImpresionMin');
        var elPrepBonito = document.getElementById('calcPrepBonito');
        var elPrepMin    = document.getElementById('calcPrepMin');
        var elTotBonito  = document.getElementById('calcTotalBonito');
        var elTotMin     = document.getElementById('calcTotalMin');
        var elRefTexto   = document.getElementById('calcRefTexto');
        var elPorCapa    = document.getElementById('calcPorCapaTexto');
        var elAltura     = document.getElementById('calcAltura');
        var campoFin     = document.getElementById('calcFin');
        var elInicioFila = document.getElementById('calcInicioFila');
        var elInicio     = document.getElementById('calcInicio');
        var filasRegla   = calcModal.querySelectorAll('[data-regla]');
        var colsInicio   = calcModal.querySelectorAll('.calc-col-inicio');

        // Altura de capa fija del laminador, para pasar capas -> cm.
        var MM_POR_CAPA = 0.05;

        // Número con coma decimal a la española y sin ceros de relleno.
        function nEs(n, decs) {
            var s = n.toLocaleString('es-ES', { minimumFractionDigits: 0, maximumFractionDigits: decs });
            return s;
        }

        // Minutos -> "1 d 2 h 30 min", quitando los tramos a cero.
        function bonito(mins) {
            mins = Math.round(mins);
            var d = Math.floor(mins / 1440);
            var h = Math.floor((mins % 1440) / 60);
            var m = mins % 60;
            var partes = [];
            if (d) partes.push(d + ' d');
            if (h) partes.push(h + ' h');
            if (m || !partes.length) partes.push(m + ' min');
            return partes.join(' ');
        }

        // Hora de fin (HH:MM) menos los minutos; si ya pasó hoy, es mañana.
        function inicioPara(mins) {
            var p = campoFin.value.split(':');
            var fin = new Date();
            fin.setHours(+p[0], +p[1], 0, 0);
            if (fin <= new Date()) fin.setDate(fin.getDate() + 1);
            var ini = new Date(fin.getTime() - Math.round(mins) * 60000);
            var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
            var dias = Math.round((new Date(ini).setHours(0, 0, 0, 0) - hoy) / 86400000);
            var hora = ini.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
            var cuando = dias === 0 ? 'hoy' : dias === 1 ? 'mañana' : dias === -1 ? 'ayer' : ini.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
            return hora + ' (' + cuando + ')' + (ini < new Date() ? ' · ya no llegas' : '');
        }

        function leer(campo, porDefectoAttr) {
            var v = parseFloat(String(campo.value).replace(',', '.'));
            if (isFinite(v) && v >= 0) return v;
            return parseFloat(calcCaja.getAttribute(porDefectoAttr)) || 0;
        }

        function recalcular() {
            var capasRef = leer(campoCapasRef, 'data-capas-ref');
            var minRef   = leer(campoMinRef, 'data-minutos-ref');
            var minPrep  = leer(campoMinPrep, 'data-minutos-prep');
            var porCapa  = capasRef > 0 ? minRef / capasRef : 0;

            elRefTexto.textContent = nEs(capasRef, 0) + ' capas = ' + nEs(minRef, 2) + ' min';
            elPorCapa.textContent  = nEs(porCapa, 4);

            var capas = parseFloat(String(campoCapas.value).replace(',', '.'));
            var conFin = campoFin.value !== '';
            colsInicio.forEach(function (c) { c.classList.toggle('d-none', !conFin); });

            if (!isFinite(capas) || capas <= 0) {
                filasRegla.forEach(function (f) {
                    f.querySelector('[data-total]').textContent = '—';
                    f.querySelector('[data-inicio]').textContent = '—';
                });
                elInicioFila.classList.add('d-none');
                elAltura.textContent = '';
                elTabla.classList.add('d-none');
                elVacio.classList.remove('d-none');
                return;
            }

            elAltura.textContent = '≈ ' + nEs(capas * MM_POR_CAPA / 10, 1) + ' cm de alto (' + nEs(MM_POR_CAPA, 2) + ' mm/capa)';

            var impresion = capas * porCapa;
            var total     = impresion + minPrep;

            elImpBonito.textContent  = bonito(impresion);
            elImpMin.textContent     = nEs(impresion, 1) + ' min · ' + nEs(capas, 0) + ' capas × ' + nEs(porCapa, 4);
            elPrepBonito.textContent = bonito(minPrep);
            elPrepMin.textContent    = nEs(minPrep, 1) + ' min fijos';
            elTotBonito.textContent  = bonito(total);
            elTotMin.textContent     = nEs(total, 1) + ' min';

            elInicioFila.classList.toggle('d-none', !conFin);
            if (conFin) elInicio.textContent = inicioPara(impresion);

            filasRegla.forEach(function (f) {
                var pc = parseFloat(f.getAttribute('data-por-capa'));
                var tot = f.querySelector('[data-total]');
                var ini = f.querySelector('[data-inicio]');
                if (!isFinite(pc)) { tot.textContent = '—'; ini.textContent = '—'; return; }
                var t = pc * capas;
                tot.textContent = bonito(t);
                ini.textContent = conFin ? inicioPara(t) : '—';
            });

            elVacio.classList.add('d-none');
            elTabla.classList.remove('d-none');
        }

        [campoCapas, campoCapasRef, campoMinRef, campoMinPrep, campoFin].forEach(function (c) {
            c.addEventListener('input', recalcular);
        });
        calcModal.addEventListener('shown.bs.modal', function () {
            campoCapas.focus();
            recalcular();
        });

        // Chevron del plegable de ajustes: apunta a la derecha plegado, abajo abierto.
        var ajustes = document.getElementById('calcAjustes');
        var ajustesIcono = document.querySelector('#calcAjustesToggle i');
        if (ajustes && ajustesIcono) {
            ajustes.addEventListener('show.bs.collapse', function () {
                ajustesIcono.classList.replace('bi-chevron-right', 'bi-chevron-down');
            });
            ajustes.addEventListener('hide.bs.collapse', function () {
                ajustesIcono.classList.replace('bi-chevron-down', 'bi-chevron-right');
            });
        }
    }
};
