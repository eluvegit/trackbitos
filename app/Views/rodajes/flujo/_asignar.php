<?php
/**
 * Asignar tomas a una zona o a un setup, como en Temáticas: marcas las tomas
 * (cada una con su foto) y tocas el destino (cada uno con su imagen).
 *
 * @var int    $pid
 * @var string $titulo     «3 tomas sin zona»
 * @var array  $tomas      escenas
 * @var array  $portadas   [escena_id => ruta]
 * @var array  $destinos   [['id','nombre','imagen'|null,'sub']]
 * @var string $campo      ubicacion_id | setup_id
 * @var string $icono      icono de reserva del destino sin imagen
 * @var string $sinDestinos texto si aún no hay zonas/setups
 */
?>
<section class="rf-sec rf-asigna" data-campo="<?= esc($campo, 'attr') ?>" data-url="<?= site_url("rodajes/$pid/escenas") ?>">
    <div class="rf-sec-h">
        <h2><?= esc($titulo) ?></h2>
        <span class="der rf-sec-acc"><button type="button" class="fv-link" data-todas>Marcar todas</button></span>
    </div>
    <div class="rf-ideas">
        <?php foreach ($tomas as $t): ?>
            <?php $ruta = $portadas[(int) $t['id']] ?? null; $txt = rod_titulo_escena($t); ?>
            <label class="rf-idea">
                <input type="checkbox" value="<?= (int) $t['id'] ?>"><span class="rf-check" aria-hidden="true"></span>
                <span class="rf-media <?= $ruta ? 'archivo' : 'texto' ?>">
                    <?php if ($ruta): ?>
                        <img src="<?= base_url($ruta) ?>" alt="" loading="lazy"><span class="cap"><?= esc($txt) ?></span>
                    <?php else: ?>
                        <span class="kind"><i class="bi bi-film"></i>#<?= (int) $t['orden'] ?></span><span class="tx"><?= esc($txt) ?></span>
                    <?php endif; ?>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
    <div class="rf-destinos-h"><b data-n>0</b> marcadas → toca dónde van</div>
    <?php if (!$destinos): ?>
        <div class="rf-vacio"><?= $sinDestinos ?></div>
    <?php else: ?>
        <div class="rf-destinos">
            <?php foreach ($destinos as $d): ?>
                <button type="button" class="rf-destino" data-valor="<?= (int) $d['id'] ?>" disabled>
                    <span class="img"><?php if ($d['imagen']): ?><img src="<?= base_url($d['imagen']) ?>" alt="" loading="lazy"><?php else: ?><i class="bi <?= $icono ?>"></i><?php endif; ?></span>
                    <span class="t"><?= esc($d['nombre']) ?></span>
                    <?php if ($d['sub'] !== ''): ?><small><?= esc($d['sub']) ?></small><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<script>
(() => {
    const sec = document.currentScript.previousElementSibling;
    const cajas = [...sec.querySelectorAll('.rf-ideas input[type=checkbox]')];
    const n = sec.querySelector('[data-n]');
    const contar = () => {
        const k = cajas.filter(c => c.checked).length;
        n.textContent = k;
        sec.querySelectorAll('.rf-destino').forEach(b => b.disabled = !k);
    };
    sec.addEventListener('change', contar);
    sec.querySelector('[data-todas]').addEventListener('click', () => {
        const todas = cajas.every(c => c.checked);
        cajas.forEach(c => c.checked = !todas);
        contar();
    });
    sec.querySelectorAll('.rf-destino').forEach(b => b.addEventListener('click', () => {
        const ids = cajas.filter(c => c.checked).map(c => c.value);
        if (!ids.length) return;
        sec.querySelectorAll('.rf-destino').forEach(x => x.disabled = true);
        b.classList.add('on');
        Promise.all(ids.map(id => fetch(sec.dataset.url + '/' + id + '/asignar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            body: JSON.stringify({ campo: sec.dataset.campo, valor: b.dataset.valor }),
        }).then(r => { if (!r.ok) throw 0; })))
            .then(() => location.reload())
            .catch(() => { alert('No se ha podido guardar alguna.'); location.reload(); });
    }));
    contar();
})();
</script>
