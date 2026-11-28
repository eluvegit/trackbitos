<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>
<?= $this->include('piezas/_fv_head') ?>
<?php
    /*
     * Guía del stock de Piezas: situación por situación, qué pantalla, qué
     * botón y qué cambia (el total o solo el sitio). Página de solo lectura;
     * las casillas de cada paso se recuerdan en este navegador para ir
     * tachando mientras se hace.
     */
    $a = static fn (string $ruta, string $texto): string => '<a href="' . esc(site_url($ruta), 'attr') . '">' . esc($texto) . '</a>';
    $b = static fn (string $texto, string $icono = ''): string => '<span class="gs-btn">' . ($icono !== '' ? '<i class="bi ' . $icono . '"></i> ' : '') . esc($texto) . '</span>';
    $total = '<span class="gs-tag t-total" title="Cambia cuántas piezas existen">cambia el total</span>';
    $sitio = '<span class="gs-tag t-sitio" title="Solo cambia dónde están; el total no se mueve">solo cambia el sitio</span>';

    $grupos = [
        'imprimir' => ['De la impresora a las cajas', 'bi-printer'],
        'guardar'  => ['De las cajas a los huecos', 'bi-geo-alt'],
        'salidas'  => ['Salidas y albaranes', 'bi-receipt'],
        'arreglos' => ['Corregir y cuadrar', 'bi-wrench-adjustable'],
    ];

    // [grupo, id, título, cuándo, pasos[], ojo|null]
    $situaciones = [
        ['imprimir', 'placa-normal', 'Sale una placa de la impresora', 'Lo normal: piezas para reponer stock.', [
            'Abre la ' . $a('piezas/placas', 'bitácora de la placa') . '. En cada línea pon las <b>Copias</b> que salieron y las <b>Fallidas</b> (rotas, mal curadas, sin soportes…).',
            'Pulsa ' . $b('Guardar bitácora', 'bi-save') . '.',
            'En el panel de inventario deja <b>a caja de impresas</b> y pulsa ' . $b('Dar de alta en inventario', 'bi-boxes') . '. Entran las copias − fallidas. ' . $total,
            'Físicamente: mete las buenas en la caja de impresas. Las fallidas, a la basura (no constan en ningún sitio).',
            'Cuando vayas a guardarlas, sigue con «Guardar lo impreso en su hueco».',
        ], 'Marca las fallidas <b>antes</b> de dar de alta. Si se te olvida no pasa nada (ver «Descubro fallidas después»), pero así nunca entran de más.'],

        ['imprimir', 'placa-pedido', 'Placa impresa para un pedido', 'Lo que sale va directo a lo que ha pedido Esther.', [
            'Igual que una placa normal: copias, fallidas y ' . $b('Guardar bitácora', 'bi-save') . '.',
            'En el panel de inventario elige <b>a caja de pedido</b> y pulsa ' . $b('Dar de alta en inventario', 'bi-boxes') . '. ' . $total,
            'Físicamente: a la caja de pedido.',
            'Si después vuelves a pulsar ' . $b('Actualizar inventario con esta placa') . ', ya sale elegida la caja en la que entró (no hace falta cambiarla).',
        ], 'Si la placa mezcla piezas para el pedido y para stock, dala de alta a una caja y luego pasa las otras con el botón de cambiar de caja (ver «De impresas a pedido»).'],

        ['imprimir', 'fallidas-despues', 'Descubro fallidas después de dar de alta', 'Al limpiar o al guardar ves que alguna está mal.', [
            'Vuelve a la bitácora de esa placa, sube las <b>Fallidas</b> de la línea y pulsa ' . $b('Guardar bitácora', 'bi-save') . '. El inventario se reajusta solo, no hay que darle a nada más. ' . $total,
            'Se descuentan de la caja en la que entró la placa. Si ya las habías guardado en su hueco, salen del hueco; la caja de pedido solo se toca si no quedan en ningún otro sitio.',
            'En ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . ', abajo, «Lo último que has movido» enseña el cuadre en gris para que veas de dónde salió.',
        ], null],

        ['imprimir', 'placa-antigua', 'Placa vieja, de antes del inventario', 'Sus piezas ya las contaste a mano.', [
            'En la bitácora pulsa ' . $b('Ya contada en el recuento', 'bi-clipboard-check') . ' en vez de darla de alta. No suma nada.',
            'Para varias a la vez: ' . $a('piezas/estadisticas', 'Estadísticas') . ' → marcar las placas sin alta como contadas.',
        ], 'Nunca des de alta una placa cuyas piezas ya entraron con un alta a mano: contarías dos veces.'],

        ['guardar', 'guardar-hueco', 'Guardar lo impreso en su hueco', 'La caja de impresas está llena y toca recoger.', [
            'Abre ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . '.',
            'Pieza que ya tiene hueco: pon cuántas y pulsa ' . $b('', 'bi-arrow-down-square') . ' (o ' . $b('Guardar todo', 'bi-arrow-down-square') . ' para toda la caja). ' . $sitio,
            'Pieza «Sin hueco»: elige el hueco en su fila. <b>Se fija en ese momento</b> (verás «fijado»); luego pulsa ' . $b('', 'bi-arrow-down-square') . ' para pasar las unidades.',
            'Físicamente: lleva las piezas a ese hueco.',
        ], '«Guardar todo» se salta las piezas sin hueco: quedan en la caja hasta que les elijas uno.'],

        ['guardar', 'impresas-pedido', 'De impresas a pedido (o al revés)', 'Algo que salió como stock se lo lleva Esther, o al revés.', [
            'En ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . ', en la fila de la pieza, pon cuántas y pulsa ' . $b('', 'bi-bag') . ' (a pedido) o ' . $b('', 'bi-box-seam') . ' (a impresas). ' . $sitio,
            'Físicamente: cámbialas de caja.',
        ], null],

        ['guardar', 'hueco-pedido', 'Preparar un pedido con piezas que ya están en el hueco', 'Esther pide algo que ya tienes guardado.', [
            'Abre ' . $a('piezas/ubicaciones', 'Estuches') . ' y en la caja de pedido pulsa ' . $b('Meter piezas', 'bi-box-arrow-in-down') . '.',
            'Busca la pieza, pon cuántas y confirma. ' . $sitio,
            'Físicamente: sácalas del hueco y a la caja de pedido.',
        ], null],

        ['guardar', 'sobran-pedido', 'Sobran piezas en la caja de pedido', 'Esther no se lleva todo lo apartado.', [
            'En ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . ', en la caja de pedido, pulsa ' . $b('', 'bi-arrow-down-square') . ' en lo que vuelve a su hueco. ' . $sitio,
        ], null],

        ['guardar', 'otro-hueco', 'Cambiar una pieza de hueco o juntar dos huecos', 'Reorganizas un estuche.', [
            'Una pieza: abre el hueco (desde ' . $a('piezas/ubicaciones', 'Estuches') . ') y usa mover pieza, o desde el hueco nuevo «Traer pieza aquí». ' . $sitio,
            'Todo un hueco: en el hueco, «Mover todo a otro hueco»; luego borra el que queda vacío.',
        ], 'Cada pieza vive en un solo hueco. Cambiarla de hueco nunca cambia cuántas hay.'],

        ['salidas', 'se-las-lleva', 'Esther se lleva piezas de una caja, sin papel', 'Salida rápida, sin foto.', [
            'En ' . $a('piezas/ubicaciones', 'Estuches') . ', en la fila de la pieza dentro de la caja, pulsa ' . $b('Se las lleva', 'bi-box-arrow-right') . ', pon cuántas y a quién. ' . $total,
            'Se crea solo un albarán ya confirmado. Lo verás en ' . $a('piezas/salidas', 'Albaranes') . '.',
        ], '<b>Una salida, un registro.</b> Si luego te llega la foto del albarán de esas mismas piezas, no crees otro: añade la foto al que se creó (o reábrelo y bórralo, y haz el de la foto).'],

        ['salidas', 'albaran-foto', 'Albarán con foto', 'Te pasan la foto de lo que se ha llevado.', [
            'En ' . $a('piezas/salidas', 'Albaranes') . ' pulsa ' . $b('Nuevo albarán', 'bi-plus-lg') . ' y ' . $b('Añadir la foto del albarán', 'bi-image') . '.',
            'Añade una línea por pieza y elige de dónde sale: <b>Hueco</b>, <b>Caja de impresas</b> o <b>Caja de pedido</b>. Para vaciar una caja entera, ' . $b('Toda la caja') . '.',
            'Si al añadir te avisa «solo constan N», revisa el origen antes de seguir.',
            'Pulsa ' . $b('Confirmar salida') . '. Ahora sí se restan. ' . $total,
        ], 'Mientras está en borrador no resta nada. Si al confirmar avisa de piezas en negativo, casi siempre es un origen mal elegido: ' . $b('Reabrir para corregir') . ', cambia la línea y confirma de nuevo.'],

        ['salidas', 'albaran-suelto', 'Albarán suelto, sin saber aún qué piezas son', 'Llega la foto pero no tienes tiempo de casarla.', [
            'Crea el albarán con la foto y déjalo en <b>borrador</b>. No resta nada.',
            'Cuando puedas, añade las líneas con su pieza y origen y ' . $b('Confirmar salida') . '.',
        ], 'No se puede confirmar con líneas sin pieza. Un borrador olvidado no descuadra, pero tampoco descuenta: repasa los borradores de vez en cuando.'],

        ['arreglos', 'deshacer', 'Me he liado moviendo y no sé qué he tocado', 'Has movido entre cajas y huecos y algo no cuadra.', [
            'En ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . ', abajo del todo, mira «Lo último que has movido»: cada traslado con hora, pieza, cuántas, de dónde y a dónde.',
            'Si uno sobra, ' . $b('Deshacer', 'bi-arrow-counterclockwise') . ': las piezas vuelven a donde estaban. ' . $sitio,
            'Alta de placa por error: en la bitácora, ' . $b('Quitar del inventario', 'bi-x-lg') . '. Albarán confirmado por error: ' . $b('Reabrir para corregir') . '.',
        ], 'Deshacer no quita el hueco que le dieras a una pieza al guardarla; eso se cambia desde el hueco.'],

        ['arreglos', 'rotura', 'Se rompe o se pierde una pieza', 'Ya estaba contada y ya no existe.', [
            'Desde la caja o desde la ficha de la pieza pulsa ' . $b('', 'bi-dash-lg') . ' (baja), elige de dónde sale (hueco, impresas o pedido) y escribe el motivo. ' . $total,
        ], 'De una caja no se puede dar de baja más de lo que hay en ella. Si la pieza estaba en el hueco, elige «hueco».'],

        ['arreglos', 'aparecen', 'Cuento y hay más (o menos) de lo que dice', 'Recuento a mano de un hueco.', [
            'Abre la ficha de la pieza (en ' . $a('piezas/existencias', 'Stock') . ').',
            'Mira el desglose: total en grande y, en pequeño, hueco · impresas · pedido. Comprueba primero si las que faltan están en una caja.',
            'Si de verdad sobran o faltan, ' . $b('', 'bi-plus-lg') . ' alta o ' . $b('', 'bi-dash-lg') . ' baja con motivo «recuento». ' . $total,
        ], 'Antes de ajustar, mira si hay placas sin dar de alta o albaranes en borrador de esa pieza: suelen ser la diferencia.'],

        ['arreglos', 'descuadradas', 'Sale el aviso «piezas descuadradas»', 'Una caja o un hueco en negativo mientras hay piezas en otro sitio.', [
            'Pulsa ' . $b('Cuadrar', 'bi-check2-square') . ' en ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . '. Pasa piezas de donde sobran a donde faltan. ' . $sitio,
            'Orden que sigue: si falta en impresas, del hueco y después de pedido; si falta en pedido, de impresas y después del hueco; si falta en el hueco, de impresas y después de pedido.',
            'Luego comprueba la caja de pedido a ojo: es la que más importa que coincida.',
        ], null],

        ['arreglos', 'repartir', 'Reparto o junto placas', 'Una placa partida en dos, o dos juntadas.', [
            'Repartir: la parte nueva hereda la caja de la original y las fallidas se reparten. El total no cambia.',
            'Juntar: solo deja si las dos están igual (las dos dadas de alta, o las dos sin alta, o las dos contadas en el recuento). Ponlas igual antes.',
        ], null],

        ['arreglos', 'partes', 'Piezas con partes (montajes)', 'Una figura hecha de varias piezas impresas por separado.', [
            'Las unidades a las que les falta una parte no cuentan en el stock de la pieza montada.',
            'Al dar de alta una placa con partes, lo que se pueda montar se monta solo y va a la misma caja que la placa.',
            'Los montajes no se borran a mano; si sobran, se corrige la bitácora de donde salieron.',
        ], null],
    ];
?>

<div class="fv gs">
    <div class="fv-top">
        <nav class="fv-crumbs"><a href="<?= site_url('piezas') ?>">Piezas</a><i class="bi bi-chevron-right"></i><a href="<?= site_url('piezas/existencias') ?>">Inventario</a><i class="bi bi-chevron-right"></i><span>Guía: cuadrar el stock</span></nav>
        <div class="fv-acciones">
            <a href="<?= site_url('piezas/ubicaciones/cajas') ?>" class="fv-gbtn"><i class="bi bi-box-seam"></i> Impresas y de pedido</a>
            <a href="<?= site_url('piezas/salidas') ?>" class="fv-gbtn"><i class="bi bi-receipt"></i> Albaranes</a>
            <a href="<?= site_url('piezas/existencias') ?>" class="fv-gbtn"><i class="bi bi-boxes"></i> Stock</a>
        </div>
    </div>

    <div class="fv-hero">
        <h1>Cuadrar el stock, paso a paso</h1>
        <div class="fv-meta"><span>De la impresora a las cajas, los huecos y los albaranes. Tacha los pasos mientras los haces: se recuerdan en este navegador.</span></div>
    </div>

    <section class="fv-panel gs-mapa">
        <div class="fv-ph"><i class="bi bi-diagram-3 t3"></i><h3>Dónde puede estar una pieza</h3></div>
        <div class="gs-flujo" aria-label="Recorrido de una pieza">
            <div class="gs-nodo n-placa"><i class="bi bi-printer"></i><b>Placa</b><small>copias − fallidas</small></div>
            <div class="gs-flecha"><span>dar de alta</span><i class="bi bi-arrow-right"></i></div>
            <div class="gs-cajas">
                <div class="gs-nodo n-caja"><i class="bi bi-box-seam"></i><b>Caja de impresas</b><small>recién salidas</small></div>
                <div class="gs-nodo n-caja"><i class="bi bi-bag"></i><b>Caja de pedido</b><small>apartadas para Esther</small></div>
            </div>
            <div class="gs-flecha"><span>guardar</span><i class="bi bi-arrow-right"></i></div>
            <div class="gs-nodo n-hueco"><i class="bi bi-geo-alt"></i><b>Su hueco</b><small>en un estuche</small></div>
            <div class="gs-flecha"><span>albarán</span><i class="bi bi-arrow-right"></i></div>
            <div class="gs-nodo n-fuera"><i class="bi bi-box-arrow-right"></i><b>Fuera</b><small>se la lleva alguien</small></div>
        </div>
        <div class="gs-reglas">
            <div><b>Total = hueco + caja de impresas + caja de pedido.</b> Es la cifra grande que sale en todas las pantallas; la pequeña es la parte de esa pantalla.</div>
            <div><span class="gs-tag t-total">cambia el total</span> solo cuatro cosas: dar de alta una placa (y sus fallidas), una baja, un alta a mano y un albarán confirmado.</div>
            <div><span class="gs-tag t-sitio">solo cambia el sitio</span> todo lo demás: guardar en el hueco, cambiar de caja, meter en caja, mover de hueco, fijar el hueco de una pieza, cuadrar. Si el total cambia al mover, algo va mal.</div>
            <div><b>Cada pieza tiene un solo hueco</b>, aunque esté a 0. Lo de las cajas todavía no está en él.</div>
        </div>
    </section>

    <div class="gs-busca">
        <label class="gs-buscar">
            <i class="bi bi-search"></i>
            <input type="search" id="gsBuscar" placeholder="¿Qué te ha pasado? fallidas, pedido, albarán, hueco, rotura…" autocomplete="off" aria-label="Buscar situación">
        </label>
        <div class="gs-indice">
            <?php foreach ($grupos as $g => [$titulo, $icono]): ?>
                <a href="#g-<?= $g ?>"><i class="bi <?= $icono ?>"></i> <?= esc($titulo) ?></a>
            <?php endforeach; ?>
            <button type="button" class="gs-limpiar" id="gsLimpiar" title="Quitar todas las marcas"><i class="bi bi-eraser"></i> Desmarcar todo</button>
        </div>
    </div>

    <?php foreach ($grupos as $g => [$titulo, $icono]): ?>
        <section class="gs-grupo" id="g-<?= $g ?>">
            <h2><i class="bi <?= $icono ?>"></i> <?= esc($titulo) ?></h2>
            <div class="gs-rej">
                <?php foreach ($situaciones as [$grupo, $id, $tit, $cuando, $pasos, $ojo]): ?>
                    <?php if ($grupo !== $g) continue; ?>
                    <article class="fv-panel gs-sit" id="<?= $id ?>" data-buscar="<?= esc(mb_strtolower(strip_tags($tit . ' ' . $cuando . ' ' . implode(' ', $pasos) . ' ' . $ojo)), 'attr') ?>">
                        <header>
                            <h3><?= esc($tit) ?></h3>
                            <p><?= esc($cuando) ?></p>
                        </header>
                        <ol class="gs-pasos">
                            <?php foreach ($pasos as $i => $paso): ?>
                                <li><label><input type="checkbox" data-paso="<?= $id . '-' . $i ?>"><span><?= $paso ?></span></label></li>
                            <?php endforeach; ?>
                        </ol>
                        <?php if ($ojo): ?>
                            <p class="gs-ojo"><i class="bi bi-exclamation-triangle"></i> <span><?= $ojo ?></span></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <p class="fv-vacio d-none" id="gsNada">Ninguna situación coincide. Prueba con otra palabra.</p>

    <section class="fv-panel gs-repaso">
        <div class="fv-ph"><i class="bi bi-list-check t3"></i><h3>Repaso rápido (una vez por semana)</h3></div>
        <ol class="gs-pasos">
            <?php foreach ([
                'Ninguna placa impresa sin dar de alta ni marcar como contada (' . $a('piezas/estadisticas', 'Estadísticas') . ' las cuenta).',
                'Ningún albarán en borrador olvidado en ' . $a('piezas/salidas', 'Albaranes') . '.',
                'En ' . $a('piezas/ubicaciones/cajas', 'Impresas y de pedido') . ' no sale el aviso de descuadradas; si sale, ' . $b('Cuadrar') . '.',
                'La caja de impresas está casi vacía (lo impreso ya está en su hueco) y ninguna pieza dice «Sin hueco».',
                'La caja de pedido coincide a ojo con lo que hay físicamente en ella.',
            ] as $i => $paso): ?>
                <li><label><input type="checkbox" data-paso="repaso-<?= $i ?>"><span><?= $paso ?></span></label></li>
            <?php endforeach; ?>
        </ol>
    </section>
</div>

<style>
    .gs-mapa { margin-bottom: 16px; }
    .gs-flujo { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 6px 2px 14px; }
    .gs-cajas { display: flex; flex-direction: column; gap: 8px; }
    .gs-nodo { display: grid; grid-template-columns: auto 1fr; column-gap: 8px; align-items: center; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--fv-border-2); background: rgb(255 255 255 / .02); min-width: 150px; }
    .gs-nodo > .bi { grid-row: span 2; font-size: 20px; }
    .gs-nodo b { font-weight: 600; font-size: 14px; }
    .gs-nodo small { color: var(--fv-text-3); font-size: 12px; }
    .n-placa .bi { color: #3d8bfd; } .n-caja .bi { color: #ffb02e; } .n-hueco .bi { color: #20c997; } .n-fuera .bi { color: #ef5b5b; }
    .gs-flecha { display: flex; flex-direction: column; align-items: center; color: var(--fv-text-3); font-size: 11.5px; }
    .gs-flecha .bi { font-size: 18px; }
    .gs-reglas { display: grid; gap: 8px; font-size: 14px; border-top: 1px solid var(--fv-border); padding-top: 12px; }
    .gs-tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 1px 8px; border-radius: 999px; white-space: nowrap; vertical-align: 1px; }
    .t-total { background: rgb(239 91 91 / .15); color: #ff8a80; border: 1px solid rgb(239 91 91 / .35); }
    .t-sitio { background: rgb(32 201 151 / .12); color: #3ddbb0; border: 1px solid rgb(32 201 151 / .35); }
    .gs-btn { display: inline-flex; align-items: center; gap: 4px; font-size: 12.5px; padding: 0 7px; border-radius: 6px; border: 1px solid var(--fv-border-2); background: rgb(255 255 255 / .04); white-space: nowrap; }

    .gs-busca { position: sticky; top: 0; z-index: 8; background: var(--fv-bg); padding: 8px 0 10px; margin-bottom: 6px; }
    .gs-buscar { display: flex; align-items: center; gap: 12px; height: 50px; padding: 0 16px; margin: 0; border-radius: 14px; background: var(--fv-panel); border: 1px solid var(--fv-border-2); cursor: text; }
    .gs-buscar:focus-within { border-color: rgb(255 106 61 / .55); box-shadow: 0 0 0 4px rgb(255 106 61 / .14); }
    .gs-buscar > .bi { font-size: 18px; color: var(--fv-text-3); }
    .gs-buscar input { flex: 1; min-width: 0; background: none !important; border: 0; outline: 0; color: var(--fv-text); font-size: 16px; }
    .gs-indice { display: flex; flex-wrap: wrap; gap: 6px 14px; margin-top: 8px; font-size: 13px; }
    .gs-indice a { color: var(--fv-text-3); text-decoration: none; }
    .gs-indice a:hover { color: var(--fv-text); }
    .gs-limpiar { margin-left: auto; border: 0; background: none; color: var(--fv-text-3); font-size: 12.5px; }
    .gs-limpiar:hover { color: var(--fv-text); }

    .gs-grupo { margin: 18px 0 8px; scroll-margin-top: 110px; }
    .gs-grupo h2 { font-size: 13px; letter-spacing: .06em; text-transform: uppercase; font-weight: 700; color: var(--fv-text-3); margin: 0 0 10px; }
    .gs-grupo.d-none { display: none; }
    .gs-rej { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    @media (max-width: 900px) { .gs-rej { grid-template-columns: 1fr; } }
    .gs-sit { scroll-margin-top: 110px; }
    .gs-sit.d-none { display: none; }
    .gs-sit:target { border-color: rgb(255 106 61 / .6); }
    .gs-sit header h3 { font-size: 16px; font-weight: 600; margin: 0 0 2px; }
    .gs-sit header p { margin: 0 0 10px; color: var(--fv-text-3); font-size: 13px; }
    .gs-pasos { margin: 0; padding: 0; list-style: none; counter-reset: paso; display: grid; gap: 6px; }
    .gs-pasos li { counter-increment: paso; }
    .gs-pasos label { display: grid; grid-template-columns: 20px 22px 1fr; gap: 6px; align-items: start; font-size: 14px; line-height: 1.45; cursor: pointer; margin: 0; }
    .gs-pasos label::before { content: counter(paso); grid-column: 2; grid-row: 1; font: 600 12px var(--fv-mono, monospace); color: var(--fv-accent); padding-top: 2px; }
    .gs-pasos input { grid-column: 1; grid-row: 1; margin-top: 4px; accent-color: #20c997; }
    .gs-pasos span { grid-column: 3; grid-row: 1; }
    .gs-pasos input:checked ~ span { color: var(--fv-text-3); text-decoration: line-through; text-decoration-color: rgb(255 255 255 / .25); }
    .gs-pasos a { color: var(--fv-accent); }
    .gs-ojo { display: flex; gap: 8px; margin: 12px 0 0; padding: 8px 10px; border-radius: 10px; background: rgb(255 176 46 / .08); border: 1px solid rgb(255 176 46 / .25); font-size: 13px; }
    .gs-ojo .bi { color: #ffb02e; margin-top: 2px; }
    .gs-repaso { margin-top: 20px; }
</style>
<script>
(function () {
    var CLAVE = 'piezas_guia_stock_pasos';
    var marcas = {};
    try { marcas = JSON.parse(localStorage.getItem(CLAVE) || '{}') || {}; } catch (e) { marcas = {}; }
    var casillas = document.querySelectorAll('[data-paso]');
    casillas.forEach(function (c) {
        c.checked = !!marcas[c.dataset.paso];
        c.addEventListener('change', function () {
            if (c.checked) marcas[c.dataset.paso] = 1; else delete marcas[c.dataset.paso];
            try { localStorage.setItem(CLAVE, JSON.stringify(marcas)); } catch (e) {}
        });
    });
    document.getElementById('gsLimpiar').addEventListener('click', function () {
        marcas = {};
        casillas.forEach(function (c) { c.checked = false; });
        try { localStorage.removeItem(CLAVE); } catch (e) {}
    });

    var input = document.getElementById('gsBuscar');
    var plano = function (t) { return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    var sits = document.querySelectorAll('.gs-sit');
    input.addEventListener('input', function () {
        var terminos = plano(input.value.trim()).split(/\s+/).filter(Boolean);
        var alguna = false;
        sits.forEach(function (s) {
            var texto = plano(s.dataset.buscar || '');
            var ok = terminos.every(function (t) { return texto.indexOf(t) !== -1; });
            s.classList.toggle('d-none', !ok);
            alguna = alguna || ok;
        });
        document.querySelectorAll('.gs-grupo').forEach(function (g) {
            g.classList.toggle('d-none', !g.querySelector('.gs-sit:not(.d-none)'));
        });
        document.getElementById('gsNada').classList.toggle('d-none', alguna);
    });
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { input.value = ''; input.dispatchEvent(new Event('input')); } });
})();
</script>

<?= $this->endSection() ?>
