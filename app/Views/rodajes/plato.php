<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?php
/**
 * Modo plató (estilo fv): lista de tomas para usar en el móvil durante la
 * sesión. Cada escena se marca como hecha / repetir / descartada (tocar el
 * estado activo otra vez la devuelve a pendiente) y admite una nota rápida.
 * Las escenas «foto y vídeo» tienen dos filas de botones, una por parte, y
 * los contadores cuentan partes (tomas), no escenas.
 *
 * @var array      $proyecto
 * @var array      $escenas
 * @var array      $media      [escena_id => [['src','video'], ...]]
 * @var array|null $sesion     sesión vinculada (módulo Sesiones)
 * @var array      $moodboard  [situacion_id|'general' => items]
 */
$pid = (int) $proyecto['id'];

$cuenta = ['pendiente' => 0, 'hecha' => 0, 'repetir' => 0, 'descartada' => 0];
$hayFormato = false;
foreach ($escenas as $e) {
    foreach (rod_estados_partes($e) as $est) $cuenta[$est]++;
    if (!empty($e['formato'])) $hayFormato = true;
}
$total  = array_sum($cuenta);
$formatos = rod_formatos();
$pct    = static fn (int $n) => $total ? round($n / $total * 100, 2) : 0;

$sub = [];
if ($sesion) $sub[] = '<a class="rd-ses" href="' . site_url('sesiones/' . $sesion['id']) . '"><i class="bi bi-camera"></i>' . esc($sesion['titulo']) . '</a>';
?>
<div class="fv rd plato" id="plato" data-base="<?= site_url("rodajes/$pid/escenas") ?>">
    <?= view('rodajes/_cabecera', [
        'migas'   => [$proyecto['titulo'] => site_url("rodajes/$pid/escenas"), 'Plató' => null],
        'titulo'  => esc($proyecto['titulo']),
        'sub'     => implode('', $sub),
        'clasica' => false,
    ]) ?>

    <?php if (!$escenas): ?>
        <div class="fv-panel rd-vacio">
            <i class="bi bi-film"></i>
            <div>Este rodaje no tiene escenas todavía.</div>
            <a class="fv-btn fv-btn-pri" href="<?= site_url("rodajes/$pid/escenas/create") ?>"><i class="bi bi-plus-lg"></i>Crear la primera</a>
        </div>
    <?php else: ?>
        <div class="pl-fija">
            <div class="pl-cont">
                <span class="grande"><b id="plHechas"><?= $cuenta['hecha'] ?></b><small>/<span id="plUtiles"><?= $total - $cuenta['descartada'] ?></span></small></span>
                <span class="s">tomas hechas</span>
            </div>
            <div class="rd-barra" aria-hidden="true">
                <i class="hecha" style="width:<?= $pct($cuenta['hecha']) ?>%"></i><i class="repetir" style="width:<?= $pct($cuenta['repetir']) ?>%"></i><i class="descartada" style="width:<?= $pct($cuenta['descartada']) ?>%"></i>
            </div>
            <div class="pl-filtros">
                <button type="button" class="on" data-filtro="pendientes">Por hacer<span data-n="pendientes"><?= $cuenta['pendiente'] + $cuenta['repetir'] ?></span></button>
                <button type="button" data-filtro="repetir">Repetir<span data-n="repetir"><?= $cuenta['repetir'] ?></span></button>
                <button type="button" data-filtro="hecha">Hechas<span data-n="hecha"><?= $cuenta['hecha'] ?></span></button>
                <button type="button" data-filtro="todas">Todas<span data-n="todas"><?= $total ?></span></button>
            </div>
            <?php if ($hayFormato): ?>
                <div class="fv-seg pl-medio">
                    <button type="button" data-medio="" aria-pressed="true">Todo</button>
                    <button type="button" data-medio="foto" aria-pressed="false"><i class="bi bi-camera"></i> Foto</button>
                    <button type="button" data-medio="video" aria-pressed="false"><i class="bi bi-camera-video"></i> Vídeo</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="pl-lista">
            <?php $bloqueAnt = null; ?>
            <?php foreach ($escenas as $e): ?>
                <?php
                $eid    = (int) $e['id'];
                $estado = rod_estado_escena($e);
                $partes = rod_estados_partes($e);
                $bloque = trim((string) $e['escena_bloque']);
                $detalle = array_filter([
                    'Acción'              => $e['escena_accion'],
                    'Toma/s'              => $e['escena_tomas'],
                    'Objetos'             => $e['plano_objetos'],
                    'Iluminación'         => $e['plano_esquema_iluminacion'],
                    'Toma alternativa'    => $e['plano_toma_alternativa'],
                    'Efecto especial'     => $e['escena_efecto_especial'],
                    'Continuidad antes'   => $e['escena_cont_previa'],
                    'Continuidad después' => $e['escena_cont_posterior'],
                    'Notas'               => $e['plano_notas'],
                    'Diálogo'             => $e['sonido_dialogo_escrito'],
                ], static fn ($v) => trim((string) $v) !== '');
                $refs = $media[$eid] ?? [];
                $mood = $e['situacion_id'] ? ($moodboard[$e['situacion_id']] ?? []) : [];
                ?>
                <?php if ($bloque !== '' && $bloque !== $bloqueAnt): ?>
                    <div class="rd-bloque" data-bloque><?= esc($bloque) ?></div>
                <?php endif; ?>
                <?php $bloqueAnt = $bloque !== '' ? $bloque : $bloqueAnt; ?>

                <article class="pl-toma" data-id="<?= $eid ?>" data-estado="<?= $estado ?>">
                    <div class="pl-toma-cab" data-abrir>
                        <div class="pl-fila1">
                            <span class="rd-num"><?= (int) $e['orden'] ?></span>
                            <div class="pl-desc"><?= esc(rod_titulo_escena($e)) ?></div>
                            <i class="bi bi-chevron-down pl-flecha"></i>
                        </div>
                        <?= rod_ficha_tecnica($e) ?>
                        <div class="pl-meta">
                            <?= rod_formato_chip($e) ?>
                            <?php if (trim((string) $e['escena_ubicacion']) !== ''): ?><span><i class="bi bi-geo-alt"></i><?= esc($e['escena_ubicacion']) ?></span><?php endif; ?>
                            <?php if (trim((string) $e['plano_hora_dia']) !== ''): ?><span><i class="bi bi-tag"></i><?= esc($e['plano_hora_dia']) ?></span><?php endif; ?>
                            <?php if (($e['plano_actores'] ?? 'N') === 'S'): ?><span><i class="bi bi-person"></i>actores</span><?php endif; ?>
                            <?php if (trim((string) $e['escena_efecto_especial']) !== ''): ?><span class="e-bad"><i class="bi bi-stars"></i>FX</span><?php endif; ?>
                            <?php if ($refs || $mood): ?><span><i class="bi bi-images"></i><?= count($refs) + count($mood) ?></span><?php endif; ?>
                        </div>
                        <div class="pl-nota-vista"<?= trim((string) $e['toma_nota']) === '' ? ' hidden' : '' ?>><i class="bi bi-pencil"></i> <span><?= esc($e['toma_nota'] ?? '') ?></span></div>
                    </div>

                    <div class="pl-det" hidden>
                        <?php if ($refs || $mood): ?>
                            <div class="pl-refs">
                                <?php foreach ($refs as $r): ?>
                                    <a href="<?= esc($r['src'], 'attr') ?>" target="_blank" rel="noopener">
                                        <?php if ($r['video']): ?><video src="<?= esc($r['src'], 'attr') ?>#t=0.1" muted playsinline preload="metadata"></video><i class="bi bi-play-fill marca"></i>
                                        <?php else: ?><img src="<?= esc($r['src'], 'attr') ?>" loading="lazy" alt=""><?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                                <?php foreach ($mood as $m): ?>
                                    <?php if ($m['origen'] === 'archivo'): ?>
                                        <a href="<?= base_url($m['ruta_archivo']) ?>" target="_blank" rel="noopener" title="Moodboard de la sesión"><img src="<?= base_url($m['ruta_archivo']) ?>" loading="lazy" alt=""><i class="bi bi-camera marca"></i></a>
                                    <?php else: ?>
                                        <a href="<?= esc($m['url_externa'], 'attr') ?>" target="_blank" rel="noopener" title="<?= esc($m['url_externa'], 'attr') ?>"><i class="bi bi-link-45deg"></i></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?= rod_campos($detalle, ['Acción', 'Iluminación', 'Notas', 'Diálogo']) ?>

                        <input type="text" class="fv-input pl-nota" maxlength="255" placeholder="Nota rápida (ISO subido, repetir con luz…)" value="<?= esc($e['toma_nota'] ?? '', 'attr') ?>">
                        <a class="fv-link" href="<?= site_url("rodajes/$pid/escenas/edit/$eid") ?>"><i class="bi bi-pencil-square"></i>Editar escena completa</a>
                    </div>

                    <?php foreach ($partes as $parte => $est): ?>
                        <div class="pl-botones" data-parte="<?= $parte ?>" data-medio="<?= $parte ?>" data-actual="<?= $est ?>">
                            <?php if (count($partes) > 1): ?>
                                <div class="pl-parte"><i class="bi <?= $formatos[$parte][1] ?>"></i><?= $formatos[$parte][0] ?></div>
                            <?php endif; ?>
                            <button type="button" data-estado="hecha"><i class="bi bi-check-lg"></i>Hecha</button>
                            <button type="button" data-estado="repetir"><i class="bi bi-arrow-repeat"></i>Repetir</button>
                            <button type="button" data-estado="descartada"><i class="bi bi-x-lg"></i>Descartar</button>
                        </div>
                    <?php endforeach; ?>
                </article>
            <?php endforeach; ?>

            <div class="pl-fin" hidden>
                <i class="bi bi-check2-all"></i>
                <div>Nada por aquí. <span data-fin-txt></span></div>
            </div>
        </div>

        <form method="post" action="<?= site_url("rodajes/$pid/plato/reiniciar") ?>" class="pl-pie"
              onsubmit="return confirm('¿Volver a dejar todas las tomas como pendientes? Las notas se conservan.')">
            <?= csrf_field() ?>
            <button type="submit" class="fv-gbtn"><i class="bi bi-arrow-counterclockwise"></i>Reiniciar plató</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($escenas): ?>
<script>
(() => {
    const raiz = document.getElementById('plato');
    const base = raiz.dataset.base;
    const csrf = '<?= csrf_hash() ?>';
    let filtro = 'pendientes';
    let medio = '';   // '' | foto | video

    // Mismo criterio que rod_estado_escena() en PHP.
    const combinar = ests => {
        if (new Set(ests).size === 1) return ests[0];
        if (ests.includes('repetir')) return 'repetir';
        if (ests.every(e => e === 'hecha' || e === 'descartada')) return 'hecha';
        return 'pendiente';
    };
    const casa = g => !medio || g.dataset.medio === medio;
    const visible = (ests) => ests.length > 0 && ({
        pendientes: ests.some(e => e === 'pendiente' || e === 'repetir'),
        repetir: ests.includes('repetir'),
        hecha: ests.every(e => e === 'hecha'),
        todas: true,
    })[filtro];

    function pintar() {
        const tomas = [...raiz.querySelectorAll('.pl-toma')];
        const n = { pendiente: 0, hecha: 0, repetir: 0, descartada: 0 };
        tomas.forEach(t => {
            const grupos = [...t.querySelectorAll('.pl-botones')];
            grupos.forEach(g => g.hidden = !casa(g));
            const ests = grupos.filter(casa).map(g => g.dataset.actual);
            ests.forEach(e => n[e]++);
            t.hidden = !visible(ests);
        });
        // Cabeceras de bloque: solo si queda alguna toma visible bajo ellas.
        raiz.querySelectorAll('[data-bloque]').forEach(b => {
            let sig = b.nextElementSibling, alguna = false;
            while (sig && sig.classList.contains('pl-toma')) { if (!sig.hidden) { alguna = true; break; } sig = sig.nextElementSibling; }
            b.hidden = !alguna;
        });
        const total = n.pendiente + n.hecha + n.repetir + n.descartada;
        raiz.querySelector('[data-n="todas"]').textContent = total;
        document.getElementById('plHechas').textContent = n.hecha;
        document.getElementById('plUtiles').textContent = total - n.descartada;
        raiz.querySelector('[data-n="pendientes"]').textContent = n.pendiente + n.repetir;
        raiz.querySelector('[data-n="repetir"]').textContent = n.repetir;
        raiz.querySelector('[data-n="hecha"]').textContent = n.hecha;
        ['hecha', 'repetir', 'descartada'].forEach(k => {
            raiz.querySelector('.rd-barra .' + k).style.width = (total ? n[k] / total * 100 : 0) + '%';
        });
        const fin = raiz.querySelector('.pl-fin');
        fin.hidden = tomas.some(t => !t.hidden);
        fin.querySelector('[data-fin-txt]').textContent = filtro === 'pendientes' ? '¡Todas las tomas están hechas!' : '';
    }

    function enviar(toma, datos) {
        return fetch(base + '/' + toma.dataset.id + '/toma', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(datos),
        }).then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });
    }

    raiz.querySelectorAll('.pl-filtros button').forEach(b => b.addEventListener('click', () => {
        raiz.querySelectorAll('.pl-filtros button').forEach(x => x.classList.toggle('on', x === b));
        filtro = b.dataset.filtro;
        pintar();
    }));
    raiz.querySelectorAll('.pl-medio button').forEach(b => b.addEventListener('click', () => {
        raiz.querySelectorAll('.pl-medio button').forEach(x => x.setAttribute('aria-pressed', x === b));
        medio = b.dataset.medio;
        pintar();
    }));

    raiz.addEventListener('click', (ev) => {
        const cab = ev.target.closest('[data-abrir]');
        if (cab) {
            const toma = cab.closest('.pl-toma');
            const det = toma.querySelector('.pl-det');
            det.hidden = !det.hidden;
            toma.classList.toggle('abierta', !det.hidden);
            return;
        }
        const btn = ev.target.closest('.pl-botones button');
        if (!btn) return;
        const toma = btn.closest('.pl-toma');
        const grupo = btn.closest('.pl-botones');
        const anterior = grupo.dataset.actual;
        const nuevo = anterior === btn.dataset.estado ? 'pendiente' : btn.dataset.estado;
        const recalcular = () => { toma.dataset.estado = combinar([...toma.querySelectorAll('.pl-botones')].map(g => g.dataset.actual)); };

        // Optimista: se pinta ya; la toma se queda visible un instante para
        // que se vea el cambio antes de desaparecer del filtro.
        grupo.dataset.actual = nuevo;
        recalcular();
        toma.classList.add('guardando');
        if (navigator.vibrate) navigator.vibrate(15);
        enviar(toma, { estado: nuevo === 'pendiente' ? '' : nuevo, parte: grupo.dataset.parte })
            .then(() => setTimeout(pintar, 450))
            .catch(() => { grupo.dataset.actual = anterior; recalcular(); alert('No se ha podido guardar. ¿Hay conexión?'); pintar(); })
            .finally(() => toma.classList.remove('guardando'));
    });

    raiz.querySelectorAll('.pl-nota').forEach(inp => {
        let guardado = inp.value;
        const guardar = () => {
            if (inp.value === guardado) return;
            const toma = inp.closest('.pl-toma');
            const valor = inp.value;
            enviar(toma, { nota: valor }).then(() => {
                guardado = valor;
                const vista = toma.querySelector('.pl-nota-vista');
                vista.querySelector('span').textContent = valor.trim();
                vista.hidden = valor.trim() === '';
            }).catch(() => inp.classList.add('is-invalid'));
        };
        inp.addEventListener('blur', guardar);
        inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
    });

    pintar();
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
