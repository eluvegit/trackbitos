<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Hogar — qué toca hacer (estilo fv): todas las pendientes, por prioridad.
 *
 * @var array $tareas con habitacion, nunca, tiene_frecuencia, diff_dias, tiempo_relativo
 */
$n = count($tareas);
$plural = static fn (int $x, string $s, string $p) => $x . ' ' . ($x === 1 ? $s : $p);
?>
<div class="fv rc estrecho">
    <?= view('hogar/_cabecera', [
        'activa' => 'pendientes',
        'titulo' => 'Qué toca',
        'sub'    => '<span id="hgCuenta">' . $plural($n, 'tarea pendiente', 'tareas pendientes') . '</span><span class="fv-dot"></span><span>De la más urgente a la que menos</span>',
    ]) ?>

    <div class="hg-tareas" id="hgTareas"<?= $n ? '' : ' hidden' ?>>
        <?php foreach ($tareas as $t): ?>
            <?php
            $f = (int) $t['frecuencia_dias'];
            $d = $t['diff_dias'];
            [$cls, $txt] = match (true) {
                $t['nunca'] && $t['tiene_frecuencia'] => ['e-bad', 'Nunca hecha'],
                $d !== null && $d > 0                 => ['e-bad', 'Atrasada ' . $plural($d, 'día', 'días')],
                $d === 0                              => ['e-warn', 'Toca hoy'],
                $d !== null                           => ['e-info', 'Faltan ' . $plural(abs($d), 'día', 'días')],
                default                               => ['e-neu', ''],
            };
            ?>
            <div class="hg-t<?= in_array($cls, ['e-bad', 'e-warn'], true) ? ' atrasada' : '' ?>" data-id="<?= (int) $t['id'] ?>" style="grid-template-columns:40px minmax(0,1fr) auto;padding-left:10px">
                <button type="button" class="hg-check" aria-label="Marcar como hecha"><i class="bi bi-circle"></i></button>
                <div class="rc-txt">
                    <span class="t"><?= esc($t['nombre']) ?></span>
                    <div class="rc-meta">
                        <?php if ($t['habitacion']): ?><a href="<?= site_url('hogar/' . $t['habitacion']['id']) ?>"><i class="bi bi-<?= esc($t['habitacion']['icono'] ?: 'house') ?>"></i><?= esc($t['habitacion']['nombre']) ?></a><?php endif; ?>
                        <span><i class="bi bi-clock"></i><?= esc($t['tiempo_relativo']) ?></span>
                        <?php if ($f): ?><span><i class="bi bi-arrow-clockwise"></i>cada <?= $plural($f, 'día', 'días') ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if ($txt !== ''): ?><span class="fv-estado <?= $cls ?>"><?= $txt ?></span><?php else: ?><span></span><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="fv-panel rc-vacio" id="hgVacio"<?= $n ? ' hidden' : '' ?>><i class="bi bi-emoji-sunglasses" style="font-size:28px"></i><div>¡Todo al día! No hay tareas pendientes.</div></div>
</div>

<script>
(() => {
    const lista = document.getElementById('hgTareas');
    lista.addEventListener('click', async ev => {
        const b = ev.target.closest('.hg-check');
        if (!b || b.disabled) return;
        const fila = b.closest('.hg-t');
        b.disabled = true;
        const r = await fetch('<?= site_url('hogar/tareas') ?>/' + fila.dataset.id + '/marcar', {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
        });
        if (!r.ok) { b.disabled = false; return; }
        if (navigator.vibrate) navigator.vibrate(15);
        b.querySelector('i').className = 'bi bi-check-circle-fill';
        fila.classList.add('hecha');
        setTimeout(() => {
            fila.remove();
            const n = lista.querySelectorAll('.hg-t').length;
            document.getElementById('hgCuenta').textContent = n + (n === 1 ? ' tarea pendiente' : ' tareas pendientes');
            lista.hidden = !n;
            document.getElementById('hgVacio').hidden = !!n;
        }, 450);
    });
})();
</script>
<?= $this->endSection() ?>
