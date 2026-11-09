<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<script>
if (window.top !== window.self) {
    document.documentElement.classList.add('bt-embebida');
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href]');
        if (!a || a.target || a.getAttribute('href').charAt(0) === '#') return;
        a.target = '_top';
    });
}
</script>
<div class="bt-solo-top"><?= $this->include('piezas/_nav') ?></div>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/css/ficha-variante.css') ?>?v=14">
<link rel="stylesheet" href="<?= base_url('assets/css/piezas-bitacora.css') ?>?v=9">
<?php
    $idPlaca  = (int) $placa['id'];
    $idForm   = 'bitacora-form-' . $idPlaca;
    $enlaces  = $enlaces ?? [];
    $imagenes = $imagenes ?? [];
    $reparto  = $reparto ?? [];
    $calc     = $calcTiempo ?? ['capasReferencia' => 0, 'minutosReferencia' => 0, 'minutosPreparacion' => 0];
    $veredictos = \App\Models\PiezaPlacaModel::VEREDICTOS;
    $tonoVer  = ['buena' => 'e-ok', 'regular' => 'e-warn', 'repetir' => 'e-bad'];

    $paraInput = static fn(?string $f) => $f ? date('Y-m-d\TH:i', strtotime($f)) : '';
    $peso = static fn($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $duracion = static function ($m): string {
        if ($m === null || $m === '') {
            return '';
        }
        $m = (int) $m;
        $h = intdiv($m, 60);
        $r = $m % 60;
        return $h ? ($r ? "{$h}h {$r}" : "{$h}h") : (string) $r;
    };
    $durTexto = static function ($m): string {
        $m = (int) $m;
        $h = intdiv($m, 60);
        $r = $m % 60;
        return $h ? ($r ? "{$h} h {$r}" : "{$h} h") : "{$r} min";
    };
    $fechaCorta = static fn($f) => date('d/m', strtotime($f)) === date('d/m') ? 'hoy ' . date('H:i', strtotime($f)) : date('d/m H:i', strtotime($f));

    $copias = array_sum(array_map(static fn($p) => (int) $p['fila']['cantidad'], $piezas));
    $fallidas = array_sum(array_map(static fn($p) => min((int) $p['fila']['cantidad'], (int) ($p['fila']['fallidas'] ?? 0)), $piezas));
    $servibles = max(0, $copias - $fallidas);
    $preguntas = array_values(array_filter($pruebas, static fn($p) => trim((string) $p['pregunta']) !== ''));
    $respondidas = count(array_filter($preguntas, static fn($p) => trim((string) $p['respuesta']) !== ''));
    $bins = $reparto['bins'] ?? [];
    $cabe = count($bins) <= 1;
    $porCapas = ($placa['numero_capas'] && (float) $calc['capasReferencia'] > 0)
        ? (int) round($placa['numero_capas'] * (float) $calc['minutosReferencia'] / (float) $calc['capasReferencia'] + (float) $calc['minutosPreparacion'])
        : null;

    $ahoraTs = time();
    $minRef = (int) ($placa['minutos_reales'] ?: ($placa['minutos_previstos'] ?: ($placa['minutos_estimados'] ?: 0)));
    if ($placa['impresa_en']) {
        $iniTs = strtotime($placa['impresa_en']);
        $finTs = $minRef ? $iniTs + $minRef * 60 : ($placa['acaba_en'] ? strtotime($placa['acaba_en']) : null);
    } else {
        $iniTs = $placa['poner_en'] ? strtotime($placa['poner_en']) : null;
        $finTs = $placa['acaba_en'] ? strtotime($placa['acaba_en']) : ($iniTs && $minRef ? $iniTs + $minRef * 60 : null);
    }

    if ($placa['veredicto']) {
        $estado = ($placa['inventario_sincronizado_en'] || $placa['inventario_en_recuento']) ? 'cerrada' : 'inventario';
    } elseif ($iniTs && $finTs && $iniTs <= $ahoraTs && $ahoraTs < $finTs) {
        $estado = 'imprimiendo';
    } elseif ($placa['impresa_en'] || ($finTs && $finTs <= $ahoraTs && $placa['enviada_en'])) {
        $estado = 'juzgar';
    } elseif ($placa['enviada_en']) {
        $estado = 'programada';
    } else {
        $estado = 'preparar';
    }
    $orden = ['preparar', 'programada', 'imprimiendo', 'juzgar', 'inventario', 'cerrada'];
    $pos = array_search($estado, $orden, true);
    $momento = $pos < 3 ? 'antes' : 'despues';
    $bloqueada = (bool) $placa['enviada_en'];
    $impActual = (string) ($placa['impresora'] ?? '');

    $estadoMeta = [
        'preparar'    => ['e-acc', 'Por preparar'],
        'programada'  => ['e-acc', 'Enviada' . ($placa['poner_en'] ? ' · se pone ' . $fechaCorta($placa['poner_en']) : '')],
        'imprimiendo' => ['e-info', 'Imprimiendo'],
        'juzgar'      => ['e-warn', 'Sin juzgar'],
        'inventario'  => [$tonoVer[$placa['veredicto']] ?? 'e-neu', $veredictos[$placa['veredicto']] ?? ''],
        'cerrada'     => [$tonoVer[$placa['veredicto']] ?? 'e-neu', $veredictos[$placa['veredicto']] ?? ''],
    ][$estado];

    $ruta = [
        ['Preparar', count($piezas) . ' piezas · ' . $copias . ' copias · ' . ($cabe ? 'cabe en 1 placa' : 'no cabe en 1 placa')],
        ['Enviar y poner', $placa['enviada_en'] ? 'Enviada ' . date('d/m H:i', strtotime($placa['enviada_en'])) . ($impActual ? ' · ' . $impActual : '') . ($placa['poner_en'] ? ' · ' . date('d/m H:i', strtotime($placa['poner_en'])) : '') : 'Sin enviar'],
        ['Imprimir', $placa['minutos_reales'] ? $durTexto($placa['minutos_reales']) . ' reales' . ($placa['numero_capas'] ? ' · ' . (int) $placa['numero_capas'] . ' capas' : '') : ($placa['numero_capas'] ? (int) $placa['numero_capas'] . ' capas' : '')],
        ['Sacar y juzgar', $placa['veredicto'] ? ($veredictos[$placa['veredicto']] ?? '') . ' · ' . $servibles . ' servibles' : $servibles . ' servibles · ' . $fallidas . ' fallidas'],
        ['Inventario', $placa['inventario_sincronizado_en'] ? 'Alta el ' . date('d/m H:i', strtotime($placa['inventario_sincronizado_en'])) : ($placa['inventario_en_recuento'] ? 'Contada en el recuento' : 'Sin dar de alta')],
        ['Cerrada', 'Todo anotado'],
    ];
?>

<div class="fv bt" id="bt" data-vista-momento="<?= $momento ?>" data-placa="<?= $idPlaca ?>">
    <form id="<?= $idForm ?>" method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora') ?>" class="d-none"
        data-calc-capas-ref="<?= esc($calc['capasReferencia'], 'attr') ?>"
        data-calc-minutos-ref="<?= esc($calc['minutosReferencia'], 'attr') ?>"
        data-calc-minutos-prep="<?= esc($calc['minutosPreparacion'], 'attr') ?>"
        data-csrf-name="<?= csrf_token() ?>" data-csrf-hash="<?= csrf_hash() ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="veredicto" value="<?= esc((string) $placa['veredicto'], 'attr') ?>" data-veredicto-input>
        <?php if ($bloqueada): ?><input type="hidden" name="desbloqueado" value="" data-desbloqueado><?php endif; ?>
    </form>

    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/placas') ?>">Placas</a><i class="bi bi-chevron-right"></i><span class="fv-mono">#<?= $idPlaca ?></span></nav>
        <div class="fv-acciones">
            <?php foreach (['anterior' => 'bi-chevron-left', 'siguiente' => 'bi-chevron-right'] as $lado => $icono): ?>
                <?php $v = $vecinas[$lado] ?? null; ?>
                <?php if ($v): ?>
                    <a href="<?= site_url('piezas/placa/' . (int) $v['id'] . '/bitacora/editar') ?>" class="fv-gbtn bt-vecina" title="<?= esc(ucfirst($lado) . ': ' . $v['nombre'], 'attr') ?>">
                        <?php if ($lado === 'anterior'): ?><i class="bi <?= $icono ?>"></i> Anterior <span class="fv-mono">#<?= (int) $v['id'] ?></span><?php else: ?>Siguiente <span class="fv-mono">#<?= (int) $v['id'] ?></span> <i class="bi <?= $icono ?>"></i><?php endif; ?>
                    </a>
                <?php else: ?>
                    <span class="fv-gbtn bt-vecina apagada" title="No hay placa <?= $lado ?> <?= $placa['impresa_en'] ? 'entre las impresas' : 'entre las por imprimir' ?>">
                        <?php if ($lado === 'anterior'): ?><i class="bi <?= $icono ?>"></i> Anterior<?php else: ?>Siguiente <i class="bi <?= $icono ?>"></i><?php endif; ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
            <span class="fv-vsep"></span>
            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" class="fv-gbtn"><i class="bi bi-send"></i> <span class="lbl-m">Enviar placa</span></a>
            <form method="post" action="<?= site_url('piezas/placa/' . $idPlaca . '/borrar') ?>"
                onsubmit="return confirm('¿Borrar «<?= esc($placa['nombre'], 'attr') ?>» del histórico? Los STL y versiones no se tocan, solo esta anotación.');">
                <?= csrf_field() ?>
                <button class="fv-gbtn peligro"><i class="bi bi-trash"></i> <span class="lbl-m">Borrar</span></button>
            </form>
            <a href="<?= site_url('piezas/placa/' . $idPlaca . '/bitacora/dual') ?>" class="fv-gbtn" title="Ver esta placa en paralelo con otra"><i class="bi bi-layout-split"></i> <span class="lbl-m">Vista dual</span></a>
            <span class="fv-vsep"></span>
            <a href="<?= current_url() ?>?nueva=0" class="fv-gbtn" title="Volver a la bitácora de siempre"><i class="bi bi-arrow-left-right"></i> <span class="lbl-m">Vista clásica</span></a>
        </div>
    </div>

    <div class="bt-hero">
        <span class="bt-id fv-mono">#<?= $idPlaca ?></span>
        <input type="text" name="nombre" form="<?= $idForm ?>" class="bt-ed bt-tit" maxlength="150" required
            value="<?= esc($placa['nombre'], 'attr') ?>" <?= $bloqueada ? 'readonly title="Enviada: nombre bloqueado. Ábrelo desde la tarjeta de envío."' : '' ?> aria-label="Nombre de la placa">
        <button type="button" class="fv-btn fv-btn-sm" data-copiar-nombre title="Copiar para pegarlo como nombre del fichero"><i class="bi bi-clipboard"></i> Copiar</button>
    </div>
    <div class="fv-meta bt-meta">
        <span class="fv-estado <?= $estadoMeta[0] ?>"><?= esc($estadoMeta[1]) ?></span>
        <?php if ($impActual): ?><span class="bt-maq <?= esc($impActual, 'attr') ?>"><?= esc($impActual) ?></span><?php endif; ?>
        <span class="fv-dot"></span>
        <span>creada <b><?= esc(date('d/m H:i', strtotime($placa['creado_en']))) ?></b></span>
        <span class="fv-dot"></span>
        <span class="bt-imp-en">impresa <input type="datetime-local" name="impresa_en" form="<?= $idForm ?>" class="bt-ed" value="<?= esc($paraInput($placa['impresa_en']), 'attr') ?>">
            <button type="button" class="fv-btn fv-btn-sm" data-ahora>Ahora</button></span>
        <span class="fv-dot"></span>
        <span class="fv-estado e-ok" id="btGuardado">Guardado</span>
    </div>

    <?php if (session('error')): ?>
        <div class="fv-flash fv-flash-err"><i class="bi bi-exclamation-triangle"></i> <?= esc(session('error')) ?></div>
    <?php endif; ?>
    <?php if (session('success')): ?>
        <div class="fv-flash fv-flash-ok"><i class="bi bi-check-circle"></i> <?= esc(session('success')) ?></div>
    <?php endif; ?>

    <section class="fv-panel bt-banda">
        <div class="bt-banda-f">
            <div class="bt-campo bt-c-maq">
                <span class="bt-lbl">Máquina <span class="t3" data-impresora-estado></span></span>
                <div class="bt-seg<?= $bloqueada ? ' bloq' : '' ?>" <?= $bloqueada ? 'title="Bloqueada porque la placa ya se envió: desbloquéala en la tarjeta de envío"' : '' ?> data-impresora-placa data-url="<?= site_url('piezas/placa/' . $idPlaca . '/impresora') ?>">
                    <?php foreach (['OLD' => 'OLD', 'NEW' => 'NEW', '' => '—'] as $imp => $texto): ?>
                        <input type="radio" name="impresora" value="<?= $imp ?>" id="imp-<?= $idPlaca ?>-<?= $imp ?: 'no' ?>" form="<?= $idForm ?>"
                            <?= $impActual === $imp ? 'checked' : '' ?> <?= $bloqueada ? 'disabled' : '' ?>>
                        <label for="imp-<?= $idPlaca ?>-<?= $imp ?: 'no' ?>" class="<?= $imp ?>" title="<?= $imp ?: 'Sin asignar' ?>"><?= $texto ?><?= $bloqueada && $impActual === $imp ? '<i class="bi bi-lock-fill" data-candado-icono></i>' : '' ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bt-campo bt-c-env">
                <span class="bt-lbl">Envío y Drive</span>
                <div class="bt-envio">
                    <?php if ($placa['enviada_en']): ?>
                        <span class="bt-env-est ok" title="Enviada el <?= esc(date('d/m/Y H:i', strtotime($placa['enviada_en'])), 'attr') ?>: nombre y máquina bloqueados"><i class="bi bi-lock-fill"></i> Enviada <?= esc(date('d/m H:i', strtotime($placa['enviada_en']))) ?></span>
                        <button type="button" class="bt-env-des" data-candado title="Desbloquear nombre y máquina" aria-label="Desbloquear nombre y máquina"><i class="bi bi-unlock"></i></button>
                    <?php else: ?>
                        <span class="bt-env-est">Sin enviar</span>
                        <a href="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" class="fv-btn fv-btn-sm fv-btn-pri"><i class="bi bi-send"></i> Enviar</a>
                    <?php endif; ?>
                    <span class="bt-env-links">
                        <button type="button" class="bt-env-clip" data-toggle-enlaces title="Enlaces de esta placa"><i class="bi bi-paperclip"></i><span data-enlaces-cuenta><?= count($enlaces) ?: '' ?></span></button>
                        <a href="<?= $enlaces ? esc($enlaces[0]['url'], 'attr') : '#' ?>" target="_blank" rel="noopener" class="bt-env-drive<?= $enlaces ? '' : ' d-none' ?>" data-drive-abrir title="Abrir en Drive">
                            <svg viewBox="0 0 24 22" width="16" height="15" aria-hidden="true"><path d="M8 1h8l8 13.5h-8z" fill="#ffba00"/><path d="M0 14.5 4 21.5l8-13.5-4-7z" fill="#0f9d58"/><path d="M4 21.5h16l4-7H8z" fill="#4285f4"/></svg>
                        </a>
                    </span>
                </div>
            </div>

            <label class="bt-campo"><span class="bt-lbl">Capas <span class="t3" data-estimado-capas></span></span>
                <input type="number" min="1" inputmode="numeric" name="numero_capas" form="<?= $idForm ?>" class="bt-ed" placeholder="741" value="<?= esc((string) ($placa['numero_capas'] ?? ''), 'attr') ?>"></label>
            <label class="bt-campo"><span class="bt-lbl">Programa</span>
                <input type="text" name="minutos_estimados" form="<?= $idForm ?>" class="bt-ed" data-tiempo placeholder="1h 50" value="<?= esc($duracion($placa['minutos_estimados']), 'attr') ?>"></label>
            <label class="bt-campo"><span class="bt-lbl">Máquina prevé</span>
                <input type="text" name="minutos_previstos" form="<?= $idForm ?>" class="bt-ed" data-tiempo placeholder="2h 05" value="<?= esc($duracion($placa['minutos_previstos']), 'attr') ?>"></label>
            <label class="bt-campo"><span class="bt-lbl">Resina g</span>
                <input type="text" inputmode="decimal" name="resina_estimada" form="<?= $idForm ?>" class="bt-ed" placeholder="48" value="<?= esc($peso($placa['resina_estimada']), 'attr') ?>"></label>
            <label class="bt-campo"><span class="bt-lbl">Temp. °C</span>
                <input type="text" inputmode="decimal" name="temperatura" form="<?= $idForm ?>" class="bt-ed" placeholder="24,5" value="<?= esc($peso($placa['temperatura']), 'attr') ?>"></label>
        </div>

        <div class="bt-banda-f dos">
            <label class="bt-campo"><span class="bt-lbl">Resina <a href="<?= site_url('piezas/resinas') ?>" class="t3">· gestionar</a></span>
                <?php $resinaActual = (int) $placa['resina_id']; ?>
                <select name="resina_id" form="<?= $idForm ?>" class="bt-ed" data-resina-select>
                    <option value="">— sin resina —</option>
                    <?php foreach ($resinas ?? [] as $r): ?>
                        <?php $en = $resinasEnMaquina[(int) $r['id']] ?? []; ?>
                        <option value="<?= (int) $r['id'] ?>" <?= (int) $r['id'] === $resinaActual ? 'selected' : '' ?>><?= esc($r['nombre']) ?><?= $en ? ' · en ' . esc(implode(' y ', $en)) : '' ?></option>
                    <?php endforeach; ?>
                    <option value="nueva">+ Nueva resina…</option>
                </select>
                <input type="text" name="resina_nueva" form="<?= $idForm ?>" class="bt-ed d-none" maxlength="120" placeholder="Nombre de la resina o mezcla" data-resina-nueva>
            </label>
            <label class="bt-campo"><span class="bt-lbl">Tiempos de exposición</span>
                <input type="text" name="exposicion" form="<?= $idForm ?>" class="bt-ed fv-mono" maxlength="255" placeholder="3.2s capa / 30s base, 0.05mm" value="<?= esc((string) $placa['exposicion'], 'attr') ?>"></label>
            <label class="bt-campo"><span class="bt-lbl">Tiempo real <span class="t3" data-calculado></span></span>
                <input type="text" name="minutos_reales" form="<?= $idForm ?>" class="bt-ed" data-tiempo placeholder="1h 44" value="<?= esc($duracion($placa['minutos_reales']), 'attr') ?>"></label>
        </div>

        <div class="bt-enlaces d-none" data-enlaces-panel>
            <div class="bt-lbl">Enlaces <span class="t3">· el primero es el que abre el icono de Drive</span></div>
            <div data-lista-enlaces>
                <?php $filasEnlace = $enlaces; $filasEnlace[] = ['url' => '', 'titulo' => '']; ?>
                <?php foreach ($filasEnlace as $enlace): ?>
                    <div class="bt-enlace" data-enlace>
                        <input type="text" name="enlace_titulo[]" form="<?= $idForm ?>" class="bt-ed" maxlength="150" placeholder="Nombre para identificarlo" value="<?= esc((string) $enlace['titulo'], 'attr') ?>">
                        <input type="url" inputmode="url" name="enlace_url[]" form="<?= $idForm ?>" class="bt-ed fv-mono" placeholder="https://drive.google.com/…" value="<?= esc((string) $enlace['url'], 'attr') ?>">
                        <a href="<?= $enlace['url'] ? esc($enlace['url'], 'attr') : '#' ?>" target="_blank" rel="noopener" class="fv-ibtn<?= $enlace['url'] ? '' : ' apagado' ?>" data-abrir-enlace title="Abrir"><i class="bi bi-box-arrow-up-right"></i></a>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="fv-link" data-anadir-enlace><i class="bi bi-plus-lg"></i> Otro enlace</button>
        </div>
    </section>

    <div class="bt-grid">
        <aside class="bt-lado">
            <section class="fv-panel bt-fotos" id="fotos">
                <div class="fv-ph"><h3>Captura del laminador</h3><span class="cuenta"><?= count($imagenes) ?: '' ?></span></div>
                <div data-fotos-lista class="bt-fotos-lista">
                    <?php foreach ($imagenes as $img): ?>
                        <div class="bt-foto">
                            <a href="<?= imagen_pieza($img, 'placa-imagen', 'v') ?>" target="_blank" data-visor
                                data-original="<?= site_url('piezas/placa-imagen/' . (int) $img['id'] . '/imagen') ?>"
                                data-notas="<?= esc((string) ($img['notas'] ?? ''), 'attr') ?>" title="Ver en grande">
                                <img src="<?= imagen_pieza($img, 'placa-imagen') ?>" alt="Plataforma del laminador" loading="lazy">
                            </a>
                            <form method="post" action="<?= site_url('piezas/placa-imagen/' . (int) $img['id'] . '/borrar') ?>" onsubmit="return confirm('¿Apartar esta foto a la papelera?');">
                                <?= csrf_field() ?>
                                <button class="bt-foto-x" title="Borrar"><i class="bi bi-x"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <form method="post" enctype="multipart/form-data" action="<?= site_url('piezas/placa/' . $idPlaca . '/imagen') ?>" data-fotos-form class="bt-soltar">
                    <?= csrf_field() ?>
                    <input type="hidden" name="notas" value="">
                    <label><input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="d-none" data-fotos-input>
                        <span data-fotos-estado>Pega (Ctrl+V), arrastra o elige una captura: se guarda sola</span></label>
                </form>
            </section>

            <nav class="fv-panel bt-ruta" aria-label="Hoja de ruta">
                <div class="fv-ph"><h3>Hoja de ruta</h3></div>
                <?php foreach ($ruta as $j => [$titulo, $resumen]): ?>
                    <?php $cls = $j < $pos ? 'hecho' : ($j === $pos ? 'actual' : 'futuro'); ?>
                    <button type="button" class="bt-rp <?= $cls ?>" data-ir-momento="<?= $j < 3 ? 'antes' : 'despues' ?>">
                        <i><?= $j < $pos ? '✓' : $j + 1 ?></i>
                        <span><b><?= esc($titulo) ?></b><?php if ($j < $pos && $resumen !== ''): ?><small><?= esc($resumen) ?></small><?php elseif ($j === $pos): ?><small class="e-acc">ahora</small><?php endif; ?></span>
                    </button>
                <?php endforeach; ?>
            </nav>

            <section class="fv-panel bt-resumen">
                <div class="fv-ph"><h3>Resumen</h3></div>
                <div class="fv-trio">
                    <div><span class="k">Copias</span><span class="v" data-total-copias><?= $copias ?></span></div>
                    <div><span class="k">Servibles</span><span class="v e-ok" data-total-servibles><?= $servibles ?></span></div>
                    <div><span class="k">Tiempo</span><span class="v"><?= $placa['minutos_reales'] ? $durTexto($placa['minutos_reales']) : ($porCapas ? $durTexto($porCapas) : '—') ?></span><span class="s"><?= $placa['minutos_reales'] ? 'real' : ($porCapas ? 'por capas' : '') ?></span></div>
                </div>
                <div class="bt-barra" data-barra-serv><i class="ok" style="width: <?= $copias ? round($servibles * 100 / $copias) : 0 ?>%"></i><i class="bad" style="width: <?= $copias ? round($fallidas * 100 / $copias) : 0 ?>%"></i></div>
            </section>
        </aside>

        <div class="bt-centro">
            <section class="fv-panel bt-ahora est-<?= $estado ?>">
                <?php if ($estado === 'preparar'): ?>
                    <span class="bt-eti e-acc">Ahora toca · preparar</span>
                    <h2>Revisa la placa y envíala a la máquina</h2>
                    <div class="bt-check">
                        <div class="<?= $piezas ? 'si' : '' ?>"><i><?= $piezas ? '✓' : '' ?></i><?= count($piezas) ?> piezas · <?= $copias ?> copias</div>
                        <div class="<?= $cabe ? 'si' : 'aviso' ?>"><i><?= $cabe ? '✓' : '!' ?></i><?= $cabe ? 'Cabe en 1 placa' : 'No cabe en 1 placa: hacen falta ' . count($bins) ?><?= ($sinMedir ?? 0) > 0 ? ' · ' . (int) $sinMedir . ' STL sin medir' : '' ?></div>
                        <div class="<?= $placa['numero_capas'] ? 'si' : '' ?>"><i><?= $placa['numero_capas'] ? '✓' : '' ?></i><?= $placa['numero_capas'] ? (int) $placa['numero_capas'] . ' capas' . ($porCapas ? ' → ≈ ' . $durTexto($porCapas) . ' con preparación' : '') : 'Apunta las capas (las dice el laminador)' ?></div>
                        <div class="<?= $impActual ? 'si' : '' ?>"><i><?= $impActual ? '✓' : '' ?></i><?= $impActual ? 'Máquina ' . esc($impActual) : 'Máquina sin elegir' ?></div>
                        <?php if ($preguntas): ?><div class="aviso"><i>?</i><?= count($preguntas) ?> pregunta(s) para esta prueba</div><?php endif; ?>
                    </div>
                    <div class="bt-acc"><a href="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" class="fv-btn fv-btn-pri"><i class="bi bi-send"></i> Enviar placa</a></div>
                <?php elseif ($estado === 'programada'): ?>
                    <span class="bt-eti e-acc">Ahora toca · ponerla</span>
                    <h2><?= $placa['poner_en'] ? 'Se pone ' . esc($fechaCorta($placa['poner_en'])) : 'Enviada, sin hora de poner' ?><?= $impActual ? ' en <span class="bt-maq ' . esc($impActual, 'attr') . '">' . esc($impActual) . '</span>' : '' ?></h2>
                    <p>Enviada el <?= esc(date('d/m H:i', strtotime($placa['enviada_en']))) ?><?= $finTs ? '. Acabará hacia las ' . date('H:i', $finTs) : '' ?>.</p>
                    <div class="bt-acc">
                        <button type="button" class="fv-btn fv-btn-pri" data-puesta-ahora><i class="bi bi-play-fill"></i> Ya está puesta</button>
                        <a href="<?= site_url('piezas/placa/' . $idPlaca . '/enviar') ?>" class="fv-btn">Cambiar hora</a>
                    </div>
                <?php elseif ($estado === 'imprimiendo'): ?>
                    <span class="bt-eti e-info">Imprimiendo</span>
                    <h2>En <?= $impActual ? '<span class="bt-maq ' . esc($impActual, 'attr') . '">' . esc($impActual) . '</span>' : 'la máquina' ?> desde las <?= date('H:i', $iniTs) ?></h2>
                    <div class="bt-prog" data-inicio="<?= $iniTs * 1000 ?>" data-fin="<?= $finTs * 1000 ?>">
                        <div class="bt-prog-b"><i style="width: <?= round(($ahoraTs - $iniTs) * 100 / max(1, $finTs - $iniTs)) ?>%"></i></div>
                        <div class="bt-prog-t"><span data-quedan></span><span>acaba a las <b><?= date('H:i', $finTs) ?></b></span></div>
                    </div>
                    <p>Mientras tanto puedes apuntar las preguntas de esta prueba o pegar la captura del laminador.</p>
                <?php elseif ($estado === 'juzgar'): ?>
                    <span class="bt-eti e-warn">Ahora toca · juzgar</span>
                    <h2><?= $finTs ? 'Terminó ' . esc($fechaCorta(date('Y-m-d H:i:s', $finTs))) . '. ' : '' ?>¿Cómo salió?</h2>
                    <p><?= $copias ?> copias · <span class="e-bad"><span data-eco-fallidas><?= $fallidas ?></span> fallidas</span> · <span data-eco-servibles><?= $servibles ?></span> servibles<?= $preguntas ? ' · ' . $respondidas . ' de ' . count($preguntas) . ' preguntas respondidas' : '' ?>. Anota las fallidas abajo antes de juzgar.</p>
                    <div class="bt-veredicto">
                        <?php foreach ($veredictos as $clave => $texto): ?>
                            <button type="button" class="fv-btn v-<?= $clave ?>" data-veredicto="<?= $clave ?>"><?= esc($texto) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($estado === 'inventario'): ?>
                    <span class="bt-eti e-acc">Ahora toca · guardar las piezas</span>
                    <h2>Da de alta las <?= $servibles ?> servibles en el inventario</h2>
                    <p>Juzgada como «<?= esc($veredictos[$placa['veredicto']] ?? '') ?>». Después te digo en qué hueco va cada pieza.</p>
                    <div class="bt-acc"><button type="button" class="fv-btn fv-btn-pri" data-ir-inventario><i class="bi bi-boxes"></i> Ir al inventario</button></div>
                <?php else: ?>
                    <span class="bt-eti e-ok">Cerrada</span>
                    <h2>Todo anotado. Nada pendiente.</h2>
                    <p><?= $placa['inventario_sincronizado_en'] ? $servibles . ' piezas en el inventario desde el ' . esc(date('d/m H:i', strtotime($placa['inventario_sincronizado_en']))) . '.' : 'Contada en el recuento a mano.' ?></p>
                <?php endif; ?>
                <ol class="bt-pasos">
                    <?php foreach ($ruta as $j => [$titulo]): ?>
                        <li class="<?= $j < $pos ? 'hecho' : ($j === $pos ? 'actual' : '') ?>"><?= esc($titulo) ?></li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <div class="bt-momentos" role="group" aria-label="Momento">
                <button type="button" data-momento="antes">Antes de imprimir<?= $pos < 3 ? ' <span class="n">ahora</span>' : '' ?></button>
                <button type="button" data-momento="despues">Resultado<?= $pos >= 3 ? ' <span class="n">ahora</span>' : '' ?></button>
            </div>

            <section class="fv-panel" id="piezas">
                <div class="fv-ph">
                    <h3>Qué lleva</h3>
                    <span class="cuenta"><?= count($piezas) ?> piezas · <span data-total-copias><?= $copias ?></span> copias</span>
                    <?php if (!empty($versionesNuevas)): ?>
                        <button type="button" class="fv-gbtn on der solo-antes" data-actualizar-versiones title="Pasa todas las piezas de esta placa a su última versión para imprimir"><i class="bi bi-arrow-up-circle"></i> Actualizar todas (<?= count($versionesNuevas) ?>)</button>
                    <?php endif; ?>
                </div>
                <?php if (empty($piezas)): ?>
                    <p class="fv-vacio">Ninguna de esas versiones existe ya.</p>
                <?php else: ?>
                    <div class="bt-pz">
                        <div class="bt-pz-h"><span></span><span>Pieza</span><span class="c">Copias</span><span class="c solo-despues">Fallidas</span><span class="c solo-despues">Serv.</span><span></span></div>
                        <?php foreach ($piezas as $p): ?>
                            <?php $filaId = (int) $p['fila']['id']; $imagenesFila = $p['imagenes'] ?? []; ?>
                            <div class="bt-pz-f" data-fila-pieza>
                                <?php if ($p['miniatura']): ?><img src="<?= $p['miniatura'] ?>" alt="" loading="lazy" class="bt-pz-th"><?php else: ?><span class="bt-pz-th vacia"><i class="bi bi-box"></i></span><?php endif; ?>
                                <span class="bt-pz-n">
                                    <?php if ($p['variante'] && $p['familia']): ?>
                                        <a class="bt-pz-t" href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) ?>" target="_blank" rel="noopener" title="<?= esc($p['familia']['nombre'] . ' · ' . $p['variante']['nombre'], 'attr') ?>"><?= esc($p['familia']['nombre']) ?> <span>· <?= esc($p['variante']['nombre']) ?></span></a>
                                        <span class="bt-pz-m">
                                            <span class="fv-mono">v<?= sprintf('%03d', (int) $p['version']['numero']) ?></span>
                                            <?php if (isset($versionesNuevas[$filaId])): ?>
                                                <button type="button" class="bt-chip nueva solo-antes" data-actualizar-pieza data-fila="<?= $filaId ?>" title="Hay una versión más nueva: cambiar esta pieza a ella"><i class="bi bi-arrow-up-circle"></i> v<?= sprintf('%03d', (int) $versionesNuevas[$filaId]['numero']) ?></button>
                                            <?php endif; ?>
                                            <a href="<?= site_url('piezas/variante/' . (int) $p['variante']['id']) . '#capturas' ?>" target="_blank" rel="noopener" class="bt-chip" title="Histórico de capturas de esta pieza"><i class="bi bi-camera"></i><?= $imagenesFila ? ' ' . count($imagenesFila) : '' ?></a>
                                        </span>
                                    <?php else: ?>
                                        <span class="bt-pz-t t3">(esa pieza ya no existe)</span>
                                        <span class="bt-pz-m"></span>
                                    <?php endif; ?>
                                    <input type="text" maxlength="500" name="nota_pieza[<?= $filaId ?>]" form="<?= $idForm ?>" class="bt-ed sm" placeholder="Soportes y pruebas de esta pieza…" value="<?= esc((string) $p['fila']['notas'], 'attr') ?>" aria-label="Soportes y pruebas de esta pieza">
                                </span>
                                <span class="bt-stp c" title="Copias"><button type="button" data-stp="-1" aria-label="Una copia menos">−</button><input type="number" min="1" max="99" inputmode="numeric" name="cantidad[<?= $filaId ?>]" form="<?= $idForm ?>" value="<?= (int) $p['fila']['cantidad'] ?>" data-fila-copias aria-label="Copias"><button type="button" data-stp="1" aria-label="Una copia más">+</button></span>
                                <span class="bt-stp x solo-despues" title="Fallidas"><button type="button" data-stp="-1" aria-label="Una fallida menos">−</button><input type="number" min="0" max="99" inputmode="numeric" name="fallidas[<?= $filaId ?>]" form="<?= $idForm ?>" value="<?= (int) ($p['fila']['fallidas'] ?? 0) ?>" data-fila-fallidas aria-label="Fallidas"><button type="button" data-stp="1" aria-label="Una fallida más">+</button></span>
                                <span class="bt-serv fv-mono solo-despues" data-fila-servibles><?= max(0, (int) $p['fila']['cantidad'] - (int) ($p['fila']['fallidas'] ?? 0)) ?></span>
                                <button type="button" class="bt-quitar" data-quitar-pieza data-fila="<?= $filaId ?>" title="Quitar esta pieza de la placa"><i class="bi bi-x-lg"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="solo-despues bt-serv-res">
                        <div class="bt-barra" data-barra-serv><i class="ok" style="width: <?= $copias ? round($servibles * 100 / $copias) : 0 ?>%"></i><i class="bad" style="width: <?= $copias ? round($fallidas * 100 / $copias) : 0 ?>%"></i></div>
                        <span class="t3"><b data-total-servibles><?= $servibles ?></b> servibles · <b data-total-fallidas><?= $fallidas ?></b> fallidas</span>
                    </div>
                <?php endif; ?>
                <div class="solo-antes bt-anadir">
                    <div class="position-relative" data-anadir-pieza>
                        <input type="text" class="bt-ed" placeholder="+ Añadir pieza: nombre o SKU…" data-buscar-pieza autocomplete="off" aria-label="Añadir pieza a esta placa">
                        <div class="bt-resultados" data-resultados-pieza hidden></div>
                    </div>
                    <details class="fv-mas bt-reparto">
                        <summary><i class="bi bi-chevron-right ch"></i> <?= $cabe ? 'Cabe en 1 placa' . (isset($bins[0]['porcentajeUsado']) ? ' (' . round($bins[0]['porcentajeUsado']) . ' % ocupada)' : '') : 'No cabe en 1 placa: hacen falta ' . count($bins) ?> · reparto</summary>
                        <div class="fv-cuerpo">
                            <?= view('piezas/_bitacora_reparto', ['placa' => $placa, 'piezas' => $piezas, 'reparto' => $reparto, 'sinMedir' => $sinMedir ?? 0, 'sugerenciaReparto' => $sugerenciaReparto ?? []]) ?>
                        </div>
                    </details>
                </div>
            </section>

            <section class="fv-panel solo-despues bt-inv" id="inventario">
                <div class="fv-ph"><h3>Inventario</h3></div>
                <?= view('piezas/_bitacora_inventario', ['placa' => $placa, 'colocacion' => $colocacion ?? [], 'colocacionHuecos' => $colocacionHuecos ?? []]) ?>
            </section>

            <div class="bt-dos-col">
                <section class="fv-panel" id="pruebas">
                    <div class="fv-ph"><h3>Qué se probaba</h3><button type="button" class="fv-link der" data-anadir-prueba><i class="bi bi-plus-lg"></i> Otra pregunta</button></div>
                    <div data-lista-pruebas class="bt-pruebas">
                        <?php $filasPrueba = $pruebas; $filasPrueba[] = ['pregunta' => '', 'respuesta' => '']; ?>
                        <?php foreach ($filasPrueba as $prueba): ?>
                            <div class="bt-prueba" data-prueba>
                                <label class="bt-campo"><span class="bt-lbl">Pregunta</span>
                                    <span class="bt-ed-g"><input type="text" name="pregunta[]" form="<?= $idForm ?>" class="bt-ed" maxlength="255" placeholder="¿Qué querías averiguar?" value="<?= esc((string) $prueba['pregunta'], 'attr') ?>">
                                        <button type="button" class="bt-quitar" data-quitar-prueba title="Quitar"><i class="bi bi-x-lg"></i></button></span></label>
                                <label class="bt-campo solo-despues"><span class="bt-lbl">Respuesta</span>
                                    <textarea name="respuesta[]" form="<?= $idForm ?>" class="bt-ed area" rows="2" placeholder="Qué pasó al imprimirla"><?= esc((string) $prueba['respuesta']) ?></textarea></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="t3 solo-antes bt-nota">La respuesta se escribe al sacar la placa, en «Resultado».</p>
                </section>

                <section class="fv-panel solo-despues" id="notas">
                    <div class="fv-ph"><h3>Notas y conclusiones</h3></div>
                    <label class="bt-campo"><span class="bt-lbl">Conclusiones</span>
                        <textarea name="conclusiones" form="<?= $idForm ?>" class="bt-ed area" rows="3" placeholder="Qué hacer distinto en la próxima placa"><?= esc((string) $placa['conclusiones']) ?></textarea></label>
                    <label class="bt-campo"><span class="bt-lbl">Notas</span>
                        <textarea name="notas" form="<?= $idForm ?>" class="bt-ed area" rows="3" placeholder="Lo que pasó, lo que cambiarías…"><?= esc((string) $placa['notas']) ?></textarea></label>
                    <div class="bt-campo"><span class="bt-lbl">Veredicto</span>
                        <div class="bt-seg-ver">
                            <button type="button" data-veredicto="" class="<?= !$placa['veredicto'] ? 'on' : '' ?>">Sin juzgar</button>
                            <?php foreach ($veredictos as $clave => $texto): ?>
                                <button type="button" data-veredicto="<?= $clave ?>" class="v-<?= $clave ?><?= $placa['veredicto'] === $clave ? ' on' : '' ?>"><?= esc($texto) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="bt-visor" id="btVisor" hidden role="dialog" aria-modal="true" aria-label="Captura del laminador">
    <div class="bt-visor-barra">
        <span class="bt-visor-tit">#<?= $idPlaca ?> · Captura del laminador <span data-visor-cuenta></span></span>
        <span class="bt-visor-nota" data-visor-nota></span>
        <a href="#" target="_blank" rel="noopener" class="bt-visor-btn" data-visor-original title="Abrir el original a tamaño completo"><i class="bi bi-box-arrow-up-right"></i> <span>Original</span></a>
        <button type="button" class="bt-visor-btn" data-visor-cerrar title="Cerrar (Esc)"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="bt-visor-cuerpo" data-visor-fondo>
        <button type="button" class="bt-visor-flecha izq" data-visor-paso="-1" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
        <img src="" alt="Captura del laminador" data-visor-img>
        <button type="button" class="bt-visor-flecha der" data-visor-paso="1" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
    </div>
</div>

<?= view('piezas/_bitacora_nueva_js', ['idPlaca' => $idPlaca, 'idForm' => $idForm]) ?>

<?= $this->endSection() ?>
