<?php
/**
 * Lista de recordatorios (estilo fv) con el diálogo «Hecho». La usan
 * Recordatorios (todos), Coche (categoría vehículo) y Lentillas.
 *
 * @var array  $recordatorios filas de RecordatorioServicio::lista()
 * @var string $volver        '' | coche | lentillas — a dónde vuelve el formulario de edición
 * @var bool   $conCategoria  enseñar la categoría de cada uno (lista general)
 * @var int    $limite        máximo de filas (0 = todas)
 * @var string $vacio         texto si no hay ninguno
 */
use App\Libraries\RecordatorioServicio;

$volver ??= '';
$conCategoria ??= false;
$limite ??= 0;
$vacio ??= 'No hay avisos todavía.';
$q = $volver !== '' ? '?volver=' . $volver : '';
$filas = $limite ? array_slice($recordatorios, 0, $limite) : $recordatorios;
$clase = ['caducado' => 'bad', 'urgente' => 'warn', 'proximo' => 'info', 'lejano' => 'ok', 'sinfecha' => 'neu'];
?>
<?php if (!$filas): ?>
    <div class="fv-vacio rc-vacio"><?= esc($vacio) ?></div>
<?php else: ?>
    <div class="rc-lista">
        <?php foreach ($filas as $r): ?>
            <?php
            $id       = (int) $r['id'];
            $modulo   = RecordatorioServicio::MODULOS[$r['categoria']] ?? null;
            $renovable = $modulo || $r['periodo_meses'] || $r['periodo_dias'];
            $meta = [];
            if ($r['fecha_mostrar']) $meta[] = '<span title="Próxima fecha"><i class="bi bi-calendar3"></i>' . rec_fecha($r['fecha_mostrar']) . ($r['recalculada'] ? ' <i class="bi bi-arrow-repeat e-info" title="Estaba vencido hace más de 30 días y ha saltado al siguiente ciclo. La fecha guardada es ' . rec_fecha($r['fecha_evento']) . '."></i>' : '') . '</span>';
            if ($r['periodo_texto'] !== '') $meta[] = '<span title="Se repite"><i class="bi bi-arrow-clockwise"></i>cada ' . $r['periodo_texto'] . '</span>';
            if (!empty($r['intervalo_km'])) $meta[] = '<span title="Dato informativo: no calcula el aviso"><i class="bi bi-speedometer"></i>' . number_format((int) $r['intervalo_km'], 0, ',', '.') . ' km</span>';
            if (!empty($r['ultima_vez'])) $meta[] = '<span title="Última vez: ' . rec_fecha($r['ultima_vez']) . '"><i class="bi bi-check2"></i>' . rec_hace($r['ultima_vez']) . '</span>';
            if ($conCategoria) $meta[] = $modulo
                ? '<a href="' . site_url($modulo[0]) . '" title="Ir a ' . $modulo[1] . '"><i class="bi bi-box-arrow-up-right"></i>' . $modulo[1] . '</a>'
                : '<span>' . esc($r['categoria_label']) . '</span>';
            ?>
            <div class="rc-fila n-<?= $r['nivel'] ?>" data-categoria="<?= esc($r['categoria'], 'attr') ?>" data-buscar="<?= esc(mb_strtolower($r['titulo'] . ' ' . $r['categoria_label'] . ' ' . $r['notas']), 'attr') ?>">
                <div class="rc-ico"><?= rec_icono((string) $r['icono']) ?></div>
                <div class="rc-txt">
                    <div class="rc-t">
                        <span class="t"><?= esc($r['titulo']) ?></span>
                        <?php if (empty($r['avisar'])): ?><i class="bi bi-bell-slash rc-mudo" title="No avisa en el panel ni en Telegram"></i><?php endif; ?>
                    </div>
                    <?php if ($meta): ?><div class="rc-meta"><?= implode('', $meta) ?></div><?php endif; ?>
                    <?php if (trim((string) $r['notas']) !== ''): ?><div class="rc-notas"><?= esc($r['notas']) ?></div><?php endif; ?>
                </div>
                <span class="fv-estado e-<?= $clase[$r['nivel']] ?> rc-estado"><?= esc($r['texto']) ?></span>
                <div class="rc-btns">
                    <?php if ($renovable): ?>
                        <button type="button" class="fv-btn fv-btn-sm rc-hecho" title="Marcar como hecho"
                                data-id="<?= $id ?>" data-titulo="<?= esc($r['titulo'], 'attr') ?>"
                                data-meses="<?= (int) $r['periodo_meses'] ?>" data-dias="<?= (int) $r['periodo_dias'] ?>"
                                data-categoria="<?= esc($r['categoria'], 'attr') ?>" data-elemento="<?= esc((string) $r['elemento'], 'attr') ?>"><i class="bi bi-check-lg"></i><span>Hecho</span></button>
                    <?php endif; ?>
                    <a class="fv-ibtn" href="<?= site_url("recordatorios/editar/$id") . $q ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (empty($GLOBALS['rc_dialogo_hecho'])): $GLOBALS['rc_dialogo_hecho'] = true; ?>
<dialog class="rc-dialogo" id="rcHecho">
    <form method="dialog" class="fv">
        <h3 id="rcHechoTitulo">Hecho</h3>
        <label class="rc-campo"><span>¿Qué día se hizo?</span><input type="date" class="fv-input" id="rcHechoFecha" required></label>
        <label class="rc-campo" id="rcHechoKmCampo" hidden><span>Kilómetros <small>(opcional)</small></span><input type="number" class="fv-input" id="rcHechoKm" min="0" inputmode="numeric"></label>
        <label class="rc-campo" id="rcHechoNotasCampo" hidden><span>Notas <small>(opcional)</small></span><input type="text" class="fv-input" id="rcHechoNotas" maxlength="255"></label>
        <p class="rc-ayuda" id="rcHechoAyuda"></p>
        <div class="rc-dialogo-pie">
            <button type="button" class="fv-btn" id="rcHechoCancelar">Cancelar</button>
            <button type="button" class="fv-btn fv-btn-pri" id="rcHechoOk"><i class="bi bi-check-lg"></i>Hecho</button>
        </div>
    </form>
</dialog>
<script>
(() => {
    const dlg = document.getElementById('rcHecho');
    const fecha = document.getElementById('rcHechoFecha');
    const km = document.getElementById('rcHechoKm');
    const notas = document.getElementById('rcHechoNotas');
    const ayuda = document.getElementById('rcHechoAyuda');
    const ok = document.getElementById('rcHechoOk');
    let btn = null;
    const hoy = () => { const d = new Date(); return new Date(d - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };

    function pintarAyuda() {
        const meses = +btn.dataset.meses, dias = +btn.dataset.dias, cat = btn.dataset.categoria;
        const partes = [];
        if (meses || dias) {
            const f = fecha.value ? new Date(fecha.value + 'T00:00:00') : new Date();
            if (meses) f.setMonth(f.getMonth() + meses);
            if (dias) f.setDate(f.getDate() + dias);
            partes.push('La próxima fecha quedará en ' + f.toLocaleDateString('es-ES') + '.');
        }
        if (cat === 'vehiculo') partes.push('Se apunta en el historial del coche.');
        if (cat === 'lentillas' && btn.dataset.elemento) partes.push('Se apunta el cambio de «' + btn.dataset.elemento + '» y se descuenta del stock.');
        ayuda.textContent = partes.join(' ');
    }

    document.addEventListener('click', ev => {
        const b = ev.target.closest('.rc-hecho');
        if (!b) return;
        btn = b;
        document.getElementById('rcHechoTitulo').textContent = b.dataset.titulo;
        fecha.value = hoy(); km.value = ''; notas.value = '';
        document.getElementById('rcHechoKmCampo').hidden = b.dataset.categoria !== 'vehiculo';
        document.getElementById('rcHechoNotasCampo').hidden = !['vehiculo', 'lentillas'].includes(b.dataset.categoria);
        pintarAyuda();
        ok.disabled = false;
        dlg.showModal();
    });
    fecha.addEventListener('change', () => btn && pintarAyuda());
    document.getElementById('rcHechoCancelar').addEventListener('click', () => dlg.close());
    dlg.addEventListener('click', ev => { if (ev.target === dlg) dlg.close(); });

    ok.addEventListener('click', async () => {
        if (!btn || !fecha.value) return;
        ok.disabled = true;
        try {
            const r = await fetch('<?= site_url('recordatorios') ?>/' + btn.dataset.id + '/renovar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
                body: JSON.stringify({ fecha_realizado: fecha.value, km: km.value, notas: notas.value }),
            });
            if (!r.ok) throw 0;
            // El orden y los contadores cambian: se recarga.
            location.reload();
        } catch (e) {
            ok.disabled = false;
            ayuda.textContent = 'No se ha podido guardar. Revisa la fecha y la conexión.';
        }
    });
})();
</script>
<?php endif; ?>
