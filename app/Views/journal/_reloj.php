<div class="fv j-reloj" id="jReloj" data-url="<?= site_url('journal') ?>" hidden>
    <div class="jr-barra">
        <span class="jr-vivo"></span>
        <span class="jr-q"><b class="jr-t">00:00:00</b><a class="jr-nm" href="#"></a></span>
        <button type="button" class="jr-parar"><i class="bi bi-stop-fill"></i>Parar</button>
    </div>
    <div class="jr-hoja">
        <div class="jr-cab"><b>Apuntar tiempo</b><button type="button" class="jr-volver" title="Seguir contando"><i class="bi bi-x-lg"></i></button></div>
        <p class="jr-que"></p>
        <div class="jr-fila">
            <input type="number" class="jr-min" min="1" inputmode="numeric" aria-label="Minutos"><span>min</span>
            <input type="date" class="jr-fecha" aria-label="Fecha">
        </div>
        <div class="jr-chips">
            <button type="button" data-jr-mas="-15">−15</button><button type="button" data-jr-mas="-5">−5</button>
            <button type="button" data-jr-mas="5">+5</button><button type="button" data-jr-mas="15">+15</button>
        </div>
        <div class="jr-duo">
            <button type="button" class="jr-descartar">Descartar</button>
            <button type="button" class="jr-guardar"><i class="bi bi-check-lg"></i>Guardar</button>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/journal-reloj.js') ?>?v=1"></script>
