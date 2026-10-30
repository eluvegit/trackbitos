<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= $this->include('piezas/_nav') ?>

<?php
    $cmd = static function (string $comando, string $nota = ''): string {
        return '<div class="pa-cmd"><code>' . esc($comando) . '</code>'
            . '<button type="button" class="pa-copiar" data-copiar="' . esc($comando, 'attr') . '" title="Copiar"><i class="bi bi-clipboard"></i></button>'
            . ($nota !== '' ? '<span class="pa-cmd-nota">' . esc($nota) . '</span>' : '')
            . '</div>';
    };
    $pag = static fn (string $ruta, string $texto): string => '<a href="' . esc(site_url($ruta), 'attr') . '">' . esc($texto) . '</a>';
?>

<style>
    .pa-portada {
        border-radius: 1rem;
        padding: 1.4rem 1.6rem;
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), .16), rgba(var(--bs-success-rgb), .12));
        border: 1px solid var(--bs-border-color);
        display: flex;
        gap: 1.2rem;
        align-items: center;
        margin-bottom: 1rem;
    }
    .pa-portada-icono {
        flex: none;
        width: 3.6rem;
        height: 3.6rem;
        border-radius: 1rem;
        display: grid;
        place-items: center;
        font-size: 1.8rem;
        background: var(--bs-primary);
        color: #fff;
    }
    .pa-portada h1 { font-size: 1.35rem; margin: 0 0 .25rem; font-weight: 650; }
    .pa-portada p { margin: 0; color: var(--bs-secondary-color); font-size: .92rem; }

    .pa-buscador {
        position: sticky;
        top: 0;
        z-index: 5;
        background: var(--bs-body-bg);
        padding: .75rem 0 .5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .pa-buscador .form-control { font-size: 1.05rem; padding: .65rem .9rem .65rem 2.4rem; }
    .pa-buscador .bi-search { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--bs-secondary-color); }
    .pa-atajos { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .pa-atajos button {
        font-size: .75rem;
        border: 1px solid var(--bs-border-color);
        background: transparent;
        color: var(--bs-secondary-color);
        border-radius: 999px;
        padding: .1rem .6rem;
    }
    .pa-atajos button:hover { color: var(--bs-emphasis-color); border-color: var(--bs-emphasis-color); }
    .pa-indice { display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .8rem; margin-top: .5rem; }
    .pa-indice a { color: var(--bs-secondary-color); text-decoration: none; }
    .pa-indice a:hover { color: var(--bs-emphasis-color); }

    .pa-seccion { margin-bottom: 2.25rem; scroll-margin-top: 9rem; }
    .pa-seccion-cab { display: flex; align-items: center; gap: .7rem; margin-bottom: .8rem; }
    .pa-seccion-num {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        font-size: .8rem;
        color: var(--bs-primary);
        border: 1px solid var(--bs-primary);
        border-radius: .4rem;
        padding: .05rem .4rem;
    }
    .pa-seccion-cab h2 { font-size: .95rem; letter-spacing: .06em; text-transform: uppercase; font-weight: 700; margin: 0; }
    .pa-seccion-cab span { font-size: .75rem; color: var(--bs-secondary-color); text-transform: uppercase; letter-spacing: .05em; }
    .pa-seccion-cab::after { content: ''; flex: 1; height: 1px; background: var(--bs-border-color); }

    .pa-rejilla { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 22rem), 1fr)); gap: 1rem; }
    .pa-ficha {
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        padding: .9rem 1rem;
        background: var(--bs-body-bg);
        scroll-margin-top: 9rem;
    }
    .pa-ficha.ancha { grid-column: 1 / -1; }
    .pa-ficha:target { border-color: var(--bs-primary); box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb), .25); }
    .pa-ficha h3 { font-size: .98rem; font-weight: 650; margin: 0 0 .35rem; display: flex; gap: .45rem; align-items: baseline; }
    .pa-ficha h3 > i { color: var(--bs-primary); }
    .pa-ficha h3 a.pa-ancla { margin-left: auto; color: var(--bs-secondary-color); opacity: 0; text-decoration: none; font-size: .8rem; }
    .pa-ficha:hover h3 a.pa-ancla { opacity: 1; }
    .pa-corto { font-size: .86rem; color: var(--bs-secondary-color); margin-bottom: .55rem; }
    .pa-ficha ol, .pa-ficha ul { padding-left: 1.15rem; margin-bottom: .5rem; font-size: .88rem; }
    .pa-ficha li { margin-bottom: .2rem; }
    .pa-ficha p { font-size: .88rem; margin-bottom: .5rem; }
    .pa-ficha table { font-size: .82rem; margin-bottom: .5rem; }
    .pa-ficha details { font-size: .84rem; margin-top: .4rem; }
    .pa-ficha details summary { color: var(--bs-secondary-color); cursor: pointer; }

    .pa-cmd {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .25rem .5rem;
        background: var(--bs-tertiary-bg);
        border-radius: .45rem;
        padding: .3rem .4rem .3rem .65rem;
        margin: .3rem 0;
    }
    .pa-cmd code { color: var(--bs-emphasis-color); font-size: .84rem; word-break: break-all; }
    .pa-cmd-nota { flex-basis: 100%; font-size: .76rem; color: var(--bs-secondary-color); }
    .pa-copiar { margin-left: auto; border: 0; background: transparent; color: var(--bs-secondary-color); padding: 0 .3rem; }
    .pa-copiar:hover { color: var(--bs-emphasis-color); }

    .pa-ojo { font-size: .82rem; border-left: 3px solid var(--bs-warning); padding: .2rem 0 .2rem .6rem; margin: .45rem 0; }
    .pa-truco { font-size: .82rem; border-left: 3px solid var(--bs-success); padding: .2rem 0 .2rem .6rem; margin: .45rem 0; }

    .pa-flujo { display: flex; flex-wrap: wrap; align-items: stretch; gap: .5rem; margin: .4rem 0 .6rem; }
    .pa-paso { flex: 1 1 8.5rem; border: 1px solid var(--bs-border-color); border-radius: .65rem; padding: .6rem .7rem; font-size: .8rem; }
    .pa-paso b { display: block; font-size: .86rem; margin-bottom: .15rem; }
    .pa-paso .pa-paso-num { font-family: var(--bs-font-monospace); font-weight: 700; color: var(--bs-primary); font-size: .72rem; }
    .pa-paso .pa-donde { display: inline-block; margin-top: .3rem; font-size: .7rem; border-radius: 999px; padding: 0 .45rem; background: var(--bs-tertiary-bg); color: var(--bs-secondary-color); }
    .pa-flecha { align-self: center; color: var(--bs-secondary-color); }
    @media (max-width: 575.98px) { .pa-flecha { display: none; } .pa-portada { flex-direction: column; align-items: flex-start; } }

    .pa-estados { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin: .4rem 0 .6rem; font-size: .82rem; }
    .pa-niveles { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr)); gap: .6rem; margin: .4rem 0; }
    .pa-nivel { border-radius: .6rem; padding: .6rem .75rem; border: 1px solid var(--bs-border-color); font-size: .8rem; }
    .pa-nivel b { display: block; font-size: .9rem; }

    .pa-sin-resultados { display: none; text-align: center; color: var(--bs-secondary-color); padding: 2rem 0; }
    .pa-oculta { display: none !important; }
</style>

<section class="pa-portada">
    <span class="pa-portada-icono"><i class="bi bi-life-preserver"></i></span>
    <div>
        <h1>Ayuda de <strong>Piezas</strong></h1>
        <p>Todo el recorrido de una pieza: del <code>.blend</code> en Blender a la placa de impresión, el inventario, el hueco donde se guarda y los pedidos. Busca lo que quieres hacer o baja por las secciones.</p>
    </div>
</section>

<div class="pa-buscador">
    <div class="position-relative">
        <i class="bi bi-search"></i>
        <input type="search" id="pa-q" class="form-control" autocomplete="off"
               placeholder="¿Qué quieres hacer? Ej.: subir STL, cambiar de ordenador, dar de alta en inventario…  (atajo: /)">
    </div>
    <div class="pa-atajos">
        <?php foreach (['empezar pieza', 'promocionar', 'stl', 'validar', 'placa', 'bitácora', 'inventario', 'hueco', 'pedido', 'otra máquina', 'se niega', 'comandos'] as $atajo): ?>
            <button type="button" data-atajo="<?= esc($atajo, 'attr') ?>"><?= esc($atajo) ?></button>
        <?php endforeach; ?>
    </div>
    <nav class="pa-indice">
        <a href="#empieza">Empieza aquí</a>
        <a href="#disenar">Diseñar (CLI)</a>
        <a href="#ficha">Ficha de la pieza</a>
        <a href="#imprimir">Imprimir</a>
        <a href="#inventario">Inventario</a>
        <a href="#pedidos">Pedidos</a>
        <a href="#catalogo">Catálogo</a>
        <a href="#comandos">Comandos</a>
        <a href="#problemas">Problemas</a>
    </nav>
</div>

<p class="pa-sin-resultados" id="pa-vacio">
    <i class="bi bi-emoji-neutral fs-3 d-block mb-2"></i>
    Nada encaja con esa búsqueda. Prueba con otra palabra (p. ej. «stl», «hueco», «placa»).
</p>

<div id="pa-secciones">

<!-- ─────────── EMPIEZA AQUÍ ─────────── -->
<section class="pa-seccion" id="empieza">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">00</span><h2>Empieza aquí</h2><span>el sistema de un vistazo</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha ancha" id="recorrido" data-claves="resumen recorrido flujo ciclo vida general como funciona todo orden pasos">
            <h3><i class="bi bi-diagram-3"></i> La vida de una pieza, de principio a fin <a class="pa-ancla" href="#recorrido">#</a></h3>
            <p class="pa-corto">Cada etiqueta gris dice dónde se hace ese paso: en la terminal (CLI) o en esta web.</p>
            <div class="pa-flujo">
                <div class="pa-paso"><span class="pa-paso-num">1</span><b>Crear la pieza</b>Botón <i class="bi bi-plus-lg"></i> del índice. Nace con su variante <code>base</code>.<br><span class="pa-donde">web</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">2</span><b>Modelar</b><code>trackbitos t pieza</code>, editar en Blender, <code>subir</code>.<br><span class="pa-donde">CLI</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">3</span><b>Promocionar</b>Congela una versión <code>v001</code>, <code>v002</code>…<br><span class="pa-donde">CLI</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">4</span><b>STL</b>Exportar y adjuntar a la versión (o <code>stl</code>).<br><span class="pa-donde">web / CLI</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">5</span><b>Placa</b>Galería → añadir a la placa → crear placa → bitácora.<br><span class="pa-donde">web</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">6</span><b>Juzgar</b>Impresa → validar o descartar.<br><span class="pa-donde">web</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">7</span><b>Inventario</b>Bitácora → «Dar de alta en inventario».<br><span class="pa-donde">web</span></div>
                <span class="pa-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="pa-paso"><span class="pa-paso-num">8</span><b>Guardar</b>Entra en la caja; desde «Cajas» se guarda en su hueco. Ya sale en el TPV.<br><span class="pa-donde">web</span></div>
            </div>
            <p class="pa-truco">Regla para no liarse: si es el <code>.blend</code> que <b>editas</b>, se hace por terminal. Si es algo que <b>generas una vez y adjuntas</b> (foto, render, STL) o una <b>decisión</b> (validar, descartar), se hace en la web.</p>
        </article>

        <article class="pa-ficha" id="vocabulario" data-claves="vocabulario conceptos pieza familia variante version rama sesion jerarquia que es">
            <h3><i class="bi bi-diagram-2"></i> Vocabulario: pieza, variante, versión <a class="pa-ancla" href="#vocabulario">#</a></h3>
            <div class="pa-niveles">
                <div class="pa-nivel"><b>Pieza</b>La idea: «brazo», «casco romano». Va en una categoría.</div>
                <div class="pa-nivel"><b>Variante</b>Una línea de diseño de esa pieza. Casi siempre solo <code>base</code>.</div>
                <div class="pa-nivel"><b>Versión</b>Una foto congelada del <code>.blend</code>: v001, v002… Nunca cambia.</div>
                <div class="pa-nivel"><b>Sesión</b>Cada tanda de trabajo en Blender, entre <code>descargar</code> y <code>cerrar</code>.</div>
            </div>
            <p class="pa-corto mb-0">El stock, el hueco, el SKU y los pedidos van por <b>variante</b>: una copia física es la misma la imprima la versión que la imprima.</p>
        </article>

        <article class="pa-ficha" id="estados" data-claves="estados version borrador impresa validada superada descartada que significa">
            <h3><i class="bi bi-signpost"></i> Estados de una versión <a class="pa-ancla" href="#estados">#</a></h3>
            <div class="pa-estados">
                <span class="badge text-bg-secondary">borrador</span><i class="bi bi-arrow-right"></i>
                <span class="badge text-bg-primary">impresa</span><i class="bi bi-arrow-right"></i>
                <span class="badge text-bg-success">validada</span>
                <span class="text-body-secondary">o</span>
                <span class="badge text-bg-danger">descartada</span>
            </div>
            <ul>
                <li><b>Borrador</b>: promocionada, sin imprimir todavía.</li>
                <li><b>Impresa</b>: ya hay pieza física, falta decir si sirve.</li>
                <li><b>Validada</b>: la buena. Solo una por variante; la anterior pasa sola a <b>superada</b>.</li>
                <li><b>Descartada</b>: no sirve, con el motivo escrito. No se borra nunca.</li>
            </ul>
            <p class="pa-ojo">Una versión <b>impresa sin juzgar</b> bloquea seguir trabajando en esa pieza. Valídala o descártala primero.</p>
        </article>

        <article class="pa-ficha" id="indice-estado" data-claves="indice columna estado definitiva imprimir sin validar no sirve modificando sin empezar filtros chips">
            <h3><i class="bi bi-list-ul"></i> Qué dice la columna «Estado» del índice <a class="pa-ancla" href="#indice-estado">#</a></h3>
            <table class="table table-sm mb-1">
                <tr><td><span class="badge text-bg-success">Definitiva</span></td><td>Tiene versión validada.</td></tr>
                <tr><td><span class="badge text-bg-info">Imprimir</span></td><td>Promocionada y sin imprimir. El filtro distingue <i>con STL</i> y <i>falta STL</i>.</td></tr>
                <tr><td><span class="badge text-bg-primary">Sin validar</span></td><td>Impresa y sin juzgar.</td></tr>
                <tr><td><span class="badge text-bg-danger">No sirve</span></td><td>La última se descartó y no hay ninguna buena.</td></tr>
                <tr><td><span class="badge text-bg-secondary">Modificando</span></td><td>Hay trabajo subido sin promocionar.</td></tr>
                <tr><td><span class="badge text-bg-secondary">Sin empezar</span></td><td>Dada de alta, sin ningún <code>.blend</code>.</td></tr>
            </table>
            <p class="pa-corto mb-0">Los filtros de encima de la tabla se pulsan de uno en uno (otra vez para quitarlo) y se combinan con el buscador, que busca por nombre o SKU. Hay también <i>Sin imagen</i> y <i>Con tareas</i>.</p>
        </article>
    </div>
</section>

<!-- ─────────── DISEÑAR (CLI) ─────────── -->
<section class="pa-seccion" id="disenar">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">01</span><h2>Diseñar</h2><span>el .blend, desde la terminal</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="como-empezar" data-claves="empezar pieza nueva crear primera vez trabajar plantilla base carpeta abrir blender">
            <h3><i class="bi bi-play-circle"></i> Empezar o retomar una pieza <a class="pa-ancla" href="#como-empezar">#</a></h3>
            <ol>
                <li>Si es nueva, créala en el índice con <i class="bi bi-plus-lg"></i>.</li>
                <li>En la terminal, desde la carpeta donde guardas las piezas:</li>
            </ol>
            <?= $cmd('trackbitos t "nombre"', 'Crea la carpeta, descarga el .blend, abre la sesión, lanza Blender y te deja dentro de la carpeta.') ?>
            <p>El nombre vale de la pieza, de la variante, un trozo o el id. Si encaja con varias, te las lista y no crea nada.</p>
            <p class="pa-truco">Si existe una pieza llamada exactamente <b>«Plantilla base»</b>, las piezas nuevas arrancan con su <code>.blend</code> en vez de con la carpeta vacía.</p>
        </article>

        <article class="pa-ficha" id="como-dia" data-claves="dia a dia subir guardar cerrar sesion log trabajo blender">
            <h3><i class="bi bi-arrow-repeat"></i> El día a día: subir y cerrar <a class="pa-ancla" href="#como-dia">#</a></h3>
            <?= $cmd('trackbitos s --log "qué he hecho"', 'Sube el .blend. Puedes subir varias veces por sesión; todas quedan en el histórico.') ?>
            <?= $cmd('trackbitos c', 'Cierra la sesión al terminar la tanda.') ?>
            <?= $cmd('trackbitos e', '¿Cómo estoy? Te dice en castellano qué toca hacer.') ?>
            <p class="pa-corto mb-0">¿Abriste solo para mirar y no tocaste nada? <code>trackbitos c --sin-cambios</code>.</p>
        </article>

        <article class="pa-ficha" id="como-promocionar" data-claves="promocionar version nueva congelar cambio medidas pautas checklist v001">
            <h3><i class="bi bi-trophy"></i> Promocionar una versión <a class="pa-ancla" href="#como-promocionar">#</a></h3>
            <p>Cuando lo subido merece congelarse como versión:</p>
            <?= $cmd('trackbitos p --cambio "qué cambió" --medidas "alto 45mm"', '--cambio es obligatorio: es lo que leerás dentro de tres meses.') ?>
            <ul>
                <li>Crea la versión siguiente y abre la rama nueva para seguir.</li>
                <li>Descarta solos los <b>borradores</b> anteriores que no estén en ninguna placa («Superada en edición por la vNNN»).</li>
                <li>Las <b>Pautas</b> (menú <i class="bi bi-three-dots"></i>) son tu checklist para repasar antes de promocionar: una por línea.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-maquina" data-claves="otra maquina cambiar ordenador mac windows portatil sobremesa forzar cierre copia viva">
            <h3><i class="bi bi-laptop"></i> Cambiar de ordenador <a class="pa-ancla" href="#como-maquina">#</a></h3>
            <ol>
                <li>En el que dejas: <code>trackbitos s</code> y <code>trackbitos c</code>.</li>
                <li>En el otro: <code>trackbitos t "pieza"</code> o <code>trackbitos d</code>.</li>
            </ol>
            <p class="pa-ojo">¿Se te olvidó cerrar y no puedes volver a ese ordenador (formateado, roto)? En la ficha de la variante está <b>Forzar cierre</b>, con motivo. Es la válvula de escape, no el atajo de cada noche.</p>
            <p class="pa-corto mb-0">Los ordenadores registrados se ven en <?= $pag('piezas/maquinas', 'Máquinas') ?>, donde se pueden renombrar.</p>
        </article>

        <article class="pa-ficha" id="como-recoger" data-claves="recoger limpiar borrar carpeta local papelera disco espacio">
            <h3><i class="bi bi-trash3"></i> Recoger la mesa <a class="pa-ancla" href="#como-recoger">#</a></h3>
            <?= $cmd('trackbitos b', 'Aparta el .blend de esta carpeta a la papelera local, solo si todo está subido y cerrado.') ?>
            <p class="pa-corto mb-0">Si falta algo, no toca nada y te dice qué. La papelera local (<code>trackbitos pa</code>) caduca a los 30 días.</p>
        </article>

        <article class="pa-ficha" id="como-stl-cli" data-claves="stl.py generar exportar blender stl biblioteca revisar sin imagen automatico">
            <h3><i class="bi bi-box-arrow-up"></i> Generar STL con <code>stl</code> <a class="pa-ancla" href="#como-stl-cli">#</a></h3>
            <p>Saca los STL directamente del <code>.blend</code> con Blender, sin exportar a mano:</p>
            <?= $cmd('stl g "pieza"', 'Una sola pieza, para probar un .blend recién preparado.') ?>
            <?= $cmd('stl p 12', 'Todos los STL de la placa 12 en una carpeta plana, listos para ChituBox.') ?>
            <?= $cmd('stl s 31 47 52', 'Los STL de lo que llevas en el carrito, sin crear placa. El botón «Solo STL» de la galería copia este comando ya hecho.') ?>
            <details>
                <summary>Más comandos de stl</summary>
                <?= $cmd('stl b', 'Biblioteca: copia local de STL + .blend de todo el catálogo (incremental).') ?>
                <?= $cmd('stl si', 'STL de todas las piezas sin foto, para hacerles una.') ?>
                <?= $cmd('stl r', 'Compara lo que generaría el .blend con lo ya subido a mano.') ?>
                <p class="mb-0">Las piezas compuestas no se exportan: se usan los STL de sus componentes.</p>
            </details>
        </article>
    </div>
</section>

<!-- ─────────── FICHA ─────────── -->
<section class="pa-seccion" id="ficha">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">02</span><h2>Ficha de la pieza</h2><span>todo lo que cuelga de una variante</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="como-stl" data-claves="stl adjuntar subir trozos medidas ancho fondo inmutable quitar descargar">
            <h3><i class="bi bi-file-earmark-arrow-up"></i> Adjuntar STL <a class="pa-ancla" href="#como-stl">#</a></h3>
            <ul>
                <li>En la tarjeta de la versión, <b>Adjuntar STL</b>. Puedes poner <b>varios trozos</b>, cada uno con su nombre.</li>
                <li>Un trozo puede darse de alta <b>solo con medidas</b> (ancho × fondo) y añadirle el <code>.stl</code> después. Las medidas sirven para repartir la placa.</li>
                <li>Todos los STL de una versión se bajan juntos: uno suelto, o un zip si son varios.</li>
            </ul>
            <p class="pa-ojo">Un STL subido no se sobrescribe: si el modelo cambia, quítalo y súbelo de nuevo, o mejor, promociona una versión nueva.</p>
        </article>

        <article class="pa-ficha" id="como-juzgar" data-claves="juzgar marcar impresa validar descartar deshacer motivo resultado parametros revisar">
            <h3><i class="bi bi-hand-thumbs-up"></i> Marcar impresa, validar, descartar <a class="pa-ancla" href="#como-juzgar">#</a></h3>
            <ol>
                <li><b>Impresa</b>: con los parámetros de impresión.</li>
                <li><b>Validar</b> si es la buena, o <b>Descartar</b> con el motivo.</li>
            </ol>
            <p><b>Deshacer</b> devuelve a borrador una impresa o descartada. Es para un botón mal pulsado, no para cambiar de opinión.</p>
            <p class="pa-truco mb-0">Tras una tanda de impresión, <?= $pag('piezas/revisar', 'Revisar impresiones') ?> (<i class="bi bi-clipboard-check"></i>) junta todas las versiones sin juzgar para hacerlo por lotes. Su botón <b>Descartar atrasados</b> limpia los borradores viejos.</p>
        </article>

        <article class="pa-ficha" id="como-imagenes" data-claves="imagenes fotos render referencia miniatura subir reutilizar enlace original">
            <h3><i class="bi bi-images"></i> Fotos: referencias y renders <a class="pa-ancla" href="#como-imagenes">#</a></h3>
            <ul>
                <li><b>Referencias</b>: fotos del original con medidas de calibre. Son de la pieza.</li>
                <li><b>Renders</b>: cómo es cada versión. Se recomprimen solos (máx. 1024 px).</li>
                <li>¿Una versión sin render? <b>Reutilizar imagen</b> copia el de otra versión.</li>
                <li><b>Enlace al original</b>: de dónde salió la idea o el modelo.</li>
            </ul>
            <p class="pa-corto mb-0">La miniatura del índice y del TPV sale del render más reciente, o de la referencia si no hay.</p>
        </article>

        <article class="pa-ficha" id="como-variantes" data-claves="derivar variante nueva independizar devolver a trabajo version antigua rama retomar">
            <h3><i class="bi bi-bezier2"></i> Variantes y volver atrás <a class="pa-ancla" href="#como-variantes">#</a></h3>
            <ul>
                <li><b>Derivar variante</b>: abre otra línea de diseño partiendo de una versión (silla alta / silla baja). Numera desde v001.</li>
                <li><b>Independizar</b>: saca una variante a una pieza propia (se mueve, no se copia).</li>
                <li><b>Devolver a trabajo</b>: retoma una versión antigua como punto de partida. Si había trabajo subido sin promocionar, te avisa y pide confirmarlo.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-extra" data-claves="compuesta de componentes tareas advertencia revision malla manifold normales sku blend descargar">
            <h3><i class="bi bi-puzzle"></i> Más cosas de la ficha <a class="pa-ancla" href="#como-extra">#</a></h3>
            <ul>
                <li><b>Compuesta de</b>: qué otras piezas forman esta (un muñeco = cuerpo + brazos…).</li>
                <li><b>Tareas y advertencia</b>: notas pendientes de la pieza; salen en el índice (filtro <i>Con tareas</i>).</li>
                <li><b>Revisión de malla</b>: apunta si la comprobaste (manifold, normales) y si dio fallos.</li>
                <li><b>SKU</b>: el código que usas en la tienda, para buscarla luego.</li>
                <li><b>Descargar .blend</b> de una versión cerrada: solo lectura, sin sesión.</li>
                <li>La caja <b>Desde tu máquina</b> tiene los comandos ya escritos para copiar.</li>
            </ul>
        </article>
    </div>
</section>

<!-- ─────────── IMPRIMIR ─────────── -->
<section class="pa-seccion" id="imprimir">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">03</span><h2>Imprimir</h2><span>galería, placas y bitácora</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="como-placa" data-claves="placa montar crear carrito galeria añadir imprimir tanda nombre">
            <h3><i class="bi bi-printer"></i> Montar una placa <a class="pa-ancla" href="#como-placa">#</a></h3>
            <ol>
                <li>En la <?= $pag('piezas/galeria', 'Galería') ?> (<i class="bi bi-grid-3x3-gap"></i>), pulsa <b>Añadir a la placa</b> en cada pieza. El contador del botón dice cuántas llevas.</li>
                <li>Filtra por estado (validadas, para imprimir, sin validar), por <i>sin foto</i> o por <i>en placa / sin placa</i>.</li>
                <li><b>Crear placa</b>: le pones nombre y se guarda en el histórico con su bitácora.</li>
                <li>Saca los STL con <code>stl p &lt;id&gt;</code> y lamina.</li>
            </ol>
            <p class="pa-corto mb-0">En <?= $pag('piezas/placas', 'Placas') ?> (<i class="bi bi-printer"></i>) está todo lo impreso. <b>Cargar</b> una placa vieja la vuelve a meter en el carrito (sustituye lo que hubiera) para repetirla.</p>
        </article>

        <article class="pa-ficha" id="como-bitacora" data-claves="bitacora cuaderno copias fallidas pruebas tiempos resina peso exposicion veredicto conclusiones fotos">
            <h3><i class="bi bi-journal-text"></i> La bitácora de una placa <a class="pa-ancla" href="#como-bitacora">#</a></h3>
            <p>Pulsa la tarjeta de la placa. Es el cuaderno de esa impresión:</p>
            <ul>
                <li><b>Qué llevaba</b>: copias de cada pieza y cuántas <b>fallidas</b>. Servibles = copias − fallidas.</li>
                <li><b>Pruebas</b>: la pregunta antes de imprimir y la respuesta al sacarla.</li>
                <li><b>Tiempos</b>: programa, máquina y real. Acepta «2h 35», «2:35» o minutos.</li>
                <li><b>Resina</b>: peso del tanque antes y después frente a lo que estimó el laminador.</li>
                <li><b>Veredicto</b> (buena / regular / repetir), notas, conclusiones y fotos de la plataforma o de cada pieza.</li>
                <li>Añadir o quitar piezas sueltas sin rehacer la placa.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-enviar" data-claves="enviar correo email mail drive chitubox goo hora poner avisar">
            <h3><i class="bi bi-send"></i> Enviar una placa por correo <a class="pa-ancla" href="#como-enviar">#</a></h3>
            <p><b>Enviar placa</b> (<i class="bi bi-send"></i> en la tarjeta o arriba en la bitácora) manda el correo con el enlace de Drive del <code>.goo</code>, la captura de Chitubox (foto de la plataforma) y <b>a qué hora ponerla</b>.</p>
            <ul>
                <li>La duración sale de la placa (máquina, laminador o capas). Cambia la hora de ponerla o la de acabar y la otra se ajusta.</li>
                <li>Elige <b>OLD</b> o <b>NEW</b> (aquí o ya antes en la bitácora): el nombre de la placa pasa a <code>#44 OLD …</code>. Pulsa <b>Copiar</b> y pégalo como nombre del fichero en Chitubox: así placa, fichero y USB coinciden.</li>
                <li><b>Guardar</b> apunta todo sin enviar (por ejemplo, mientras subes el fichero a Drive); al volver está relleno.</li>
                <li>Si ese día ya hay una placa para esa impresora, o la placa ya se envió, te avisa y te propone la otra.</li>
                <li><b>Desfase</b>: si la otra impresora ya tiene placa ese día, esta se calcula para acabar los minutos del desfase después (45 por defecto, se cambia y guarda en la misma pantalla).</li>
                <li>Antes de enviar sale un aviso con la impresora en grande; la tarjeta queda en verde (<i class="bi bi-send-check"></i>) y con su etiqueta OLD/NEW.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-repartir" data-claves="repartir no cabe placa dividir deshacer reparto plataforma medidas">
            <h3><i class="bi bi-layout-split"></i> No cabe en la plataforma <a class="pa-ancla" href="#como-repartir">#</a></h3>
            <p><b>Repartir en otra placa</b> mueve unas cuantas piezas a una placa nueva enlazada a esta. <b>Deshacer reparto</b> las vuelve a juntar.</p>
            <p class="pa-corto mb-0">Para calcular lo que ocupa se usan las medidas (ancho × fondo) de cada trozo STL.</p>
        </article>

        <article class="pa-ficha" id="como-cuaderno" data-claves="cuaderno wiki resumen anotaciones conclusiones notas pruebas preguntas respuestas pendientes buscar mejoras historial">
            <h3><i class="bi bi-journal-bookmark"></i> Cuaderno: todas las anotaciones de golpe <a class="pa-ancla" href="#como-cuaderno">#</a></h3>
            <p>En <?= $pag('piezas/cuaderno', 'Cuaderno') ?> (<i class="bi bi-journal-bookmark"></i>) se leen de corrido las bitácoras de todas las placas, de la más reciente a la más antigua: pruebas con su respuesta, conclusiones, notas, notas por pieza y fotos. Las placas sin nada escrito no salen.</p>
            <ul>
                <li>Arriba, las <b>preguntas sin responder</b>: se contestan ahí mismo (Intro guarda).</li>
                <li>Busca un tema («soportes», «exposición», sin importar las tildes) o filtra por veredicto, pieza o impresora.</li>
                <li>Los botones <b>Preguntas · Conclusiones · Notas · Piezas · Fotos</b> muestran u ocultan cada parte (p. ej. solo conclusiones = resumen de lo aprendido); <b>Todo</b> lo vuelve a enseñar. Se recuerda en este navegador.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-tiradas" data-claves="tirada produccion plan placas dias maquinas resina plazo confirmacion diseño muñecos correo casa progreso">
            <h3><i class="bi bi-calendar-range"></i> Tiradas: plan de una producción grande <a class="pa-ancla" href="#como-tiradas">#</a></h3>
            <p>En <?= $pag('piezas/tiradas', 'Tiradas') ?> (<i class="bi bi-calendar-range"></i>) se reparte un encargo grande (p. ej. 250 muñecos) en placas por días y máquinas.</p>
            <ul>
                <li><b>Tipos</b>: resina, cuántas caben por placa, cuántas lleva cada muñeco, si hay versión H/M y si esperan a la confirmación del diseño.</li>
                <li><b>Placas</b> en orden de cola: cada máquina coge la primera de su resina que ya se pueda imprimir ese día.</li>
                <li>En el panel mueves el <b>día de confirmación</b>, permites o no el <b>cambio de resina</b> y marcas días <b>fuera de casa</b>: el plan se recoloca solo y avisa si se pasa del plazo.</li>
                <li>Pulsa una placa para ver por qué va ahí y <b>marcarla como impresa</b>; las barras muestran lo impreso frente a lo necesario.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-calcular" data-claves="calculadora tiempo capas minutos estimado resina precio litro densidad coste">
            <h3><i class="bi bi-stopwatch"></i> Calculadoras: tiempo y resina <a class="pa-ancla" href="#como-calcular">#</a></h3>
            <ul>
                <li><i class="bi bi-stopwatch"></i> <b>Tiempo</b>: con el número de capas calcula cuánto tarda. Sus ajustes (capas/minutos de referencia y preparación) se editan en el mismo modal.</li>
                <li><i class="bi bi-droplet"></i> <b>Resina</b> (menú <i class="bi bi-three-dots"></i>): precio por litro y densidad. Con eso y el volumen de cada trozo sale el coste por pieza.</li>
            </ul>
        </article>
    </div>
</section>

<!-- ─────────── INVENTARIO ─────────── -->
<section class="pa-seccion" id="inventario">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">04</span><h2>Inventario</h2><span>existencias, huecos y TPV</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="como-alta-inventario" data-claves="dar de alta inventario stock placa sincronizar quitar del inventario impreso caja cajas guardar pedido">
            <h3><i class="bi bi-box-arrow-in-down"></i> Meter lo impreso en el inventario <a class="pa-ancla" href="#como-alta-inventario">#</a></h3>
            <ol>
                <li>Apunta en la bitácora las copias y las fallidas.</li>
                <li>Pulsa <b>Dar de alta en inventario</b>. Suma las servibles de cada pieza.</li>
            </ol>
            <p>Se puede pulsar otra vez si corriges algo: <b>reajusta, no duplica</b>. <b>Quitar del inventario</b> deshace lo que aportó esa placa.</p>
            <p class="pa-corto mb-0">Lo que entra va a la caja que elijas junto al botón: <b>impresas</b> o <b>pedido</b> (lo que ha pedido Esther). En <?= $pag('piezas/ubicaciones/cajas', 'Ubicaciones → Cajas') ?> lo guardas en su hueco, pieza a pieza o «Guardar todo», o lo pasas de una caja a otra. Las cajas también salen arriba en la lista de Ubicaciones, con borde ámbar, y ahí mismo cada pieza tiene sus botones: <b>→ hueco</b> (si ya tiene), <b>Elegir hueco</b> (si no) , <b>A pedido / A impresas</b> y <b>Se las lleva</b> (salida de algunas o todas, con quién se las lleva). Con <b>Meter piezas</b>, en la cabecera de cada caja, pasas a la caja piezas que estaban en su hueco o sin ubicación. Para algo más largo («me llevo esto de aquí y esto de allá») o con la foto de Esther, usa <?= $pag('piezas/salidas', 'Albaranes') ?>: lo creas con la foto, añades líneas eligiendo de dónde sale cada pieza y lo confirmas. Si te equivocas, «Reabrir» devuelve las piezas a su sitio. Mientras está en la caja cuenta como stock, pero no sale en ningún hueco.</p>
        </article>

        <article class="pa-ficha" id="como-existencias" data-claves="existencias stock semaforo minimo alta baja manual ajuste motivo rojo amarillo verde">
            <h3><i class="bi bi-boxes"></i> Existencias <a class="pa-ancla" href="#como-existencias">#</a></h3>
            <p><?= $pag('piezas/existencias', 'Existencias') ?> (<i class="bi bi-boxes"></i>), en lista o galería, filtrable por stock y categoría.</p>
            <ul>
                <li>Semáforo: <span class="text-danger fw-semibold">rojo</span> a cero, <span class="text-warning-emphasis fw-semibold">amarillo</span> hasta el mínimo, <span class="text-success fw-semibold">verde</span> por encima.</li>
                <li>El <b>mínimo</b> se fija en la ficha de existencias de la pieza.</li>
                <li><b>Alta / baja manual</b> desde esa ficha, siempre con motivo. Las bajas restan de su hueco.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-hueco" data-claves="hueco estuche ubicacion guardar donde esta codigo e1h2 mover traer quitar etiquetas imprimir">
            <h3><i class="bi bi-geo-alt"></i> Estuches y huecos <a class="pa-ancla" href="#como-hueco">#</a></h3>
            <p>En <?= $pag('piezas/ubicaciones', 'Ubicaciones') ?> (<i class="bi bi-geo-alt"></i>): el <b>estuche</b> es la caja y el <b>hueco</b> el compartimento. El código une los dos: <code>E1H2</code> = hueco H2 del estuche E1.</p>
            <ul>
                <li><b>Una pieza vive en un solo hueco.</b> Si intentas meterla en otro, se niega y te lo explica.</li>
                <li>Desde la ficha del hueco: <b>Traer pieza aquí</b>, <b>Mover pieza</b> o <b>Quitar</b> (la deja sin hueco, sin tocar el stock).</li>
                <li>Una pieza con stock a 0 sigue en su hueco.</li>
                <li><b>Imprimir</b> saca una chuleta en papel por estuche y por pieza, con líneas para apuntar a mano.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-tpv" data-claves="tpv inventario publico consulta guias brazos asistente paso a paso opciones resultado">
            <h3><i class="bi bi-shop"></i> El TPV y sus guías <a class="pa-ancla" href="#como-tpv">#</a></h3>
            <p>El botón <i class="bi bi-shop" style="color:#ef5b5b"></i> abre el <b>TPV de inventario</b>: una vista pública, de solo lectura, para buscar piezas y ver dónde están y cuántas quedan.</p>
            <p>Las <?= $pag('piezas/guias', 'Guías') ?> (<i class="bi bi-signpost-split"></i>) son asistentes del TPV («Guía de brazos»: cogida → manga → talla):</p>
            <ol>
                <li>Crea la guía, sus <b>pasos</b> y las <b>opciones</b> de cada paso (con foto).</li>
                <li>Asigna qué pieza sale de cada combinación.</li>
                <li>Actívala. Solo sale en el TPV si tiene alguna pieza.</li>
            </ol>
            <p class="pa-corto mb-0">Reordenar pasos no rompe nada; añadir o quitar opciones borra las combinaciones que ya no encajan.</p>
        </article>
    </div>
</section>

<!-- ─────────── PEDIDOS ─────────── -->
<section class="pa-seccion" id="pedidos">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">05</span><h2>Pedidos y pendientes</h2><span>lo que hay que fabricar o crear</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="como-pedido" data-claves="pedido sterclicks manual lineas estado prioridad entrega cargar placa hecha completada copiar mover">
            <h3><i class="bi bi-cart-check"></i> Pedidos <a class="pa-ancla" href="#como-pedido">#</a></h3>
            <p><?= $pag('piezas/pedidos', 'Pedidos') ?> (<i class="bi bi-cart-check"></i>) entran solos desde <b>sterclicks</b> o se dan de alta a mano.</p>
            <ul>
                <li>Estados: <b>nuevo → diseñando → para impresión → hecho</b>.</li>
                <li>Arrastra para reordenar; la <b>prioridad</b> los sube arriba. La fecha de entrega se pone roja cuando vence.</li>
                <li>En cada línea, marca cuántas llevas hechas o la línea entera como hecha. Se pueden copiar o mover líneas a otro pedido.</li>
                <li><b>Cargar a la placa</b> mete todas sus piezas en el carrito, tengan STL o no.</li>
                <li>Una pieza <b>futura</b> tiene botón <b>Crear pieza</b>: la da de alta como pieza nueva o derivada de otra, y la línea pasa a apuntar a ella.</li>
            </ul>
            <p class="pa-corto mb-0">El <i class="bi bi-eye"></i> de al lado enseña el último pedido sin salir de donde estás.</p>
        </article>

        <article class="pa-ficha" id="como-pendientes" data-claves="pendientes crear ideas journal subtareas tarea enlazar referencias">
            <h3><i class="bi bi-list-check"></i> Pendientes de crear <a class="pa-ancla" href="#como-pendientes">#</a></h3>
            <p>Las ideas de piezas se apuntan como <b>subtareas de una tarea de Journal</b>. <?= $pag('piezas/pendientes', 'Pendientes') ?> las enseña filtradas.</p>
            <ol>
                <li><b>Enlaza</b> una vez la tarea de Journal que hace de lista.</li>
                <li>Desde cada subtarea, crea la pieza y márcala hecha.</li>
                <li><b>Copiar referencias</b> se trae las fotos de la subtarea a la pieza.</li>
            </ol>
        </article>

        <article class="pa-ficha" id="como-sterclicks" data-claves="sterclicks tienda ocultar visibilidad catalogo ojo mostrar">
            <h3><i class="bi bi-eye-slash"></i> Qué ve sterclicks <a class="pa-ancla" href="#como-sterclicks">#</a></h3>
            <p>sterclicks lee el catálogo de piezas desde aquí. El icono del <b>ojo</b> de una categoría, pieza o variante la <b>oculta</b> de sterclicks, y todo lo que cuelga de ella también.</p>
        </article>
    </div>
</section>

<!-- ─────────── CATÁLOGO ─────────── -->
<section class="pa-seccion" id="catalogo">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">06</span><h2>Ordenar el catálogo</h2><span>categorías, nevera, papelera y copias</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="como-categorias" data-claves="categorias organizar mover pieza ordenar subir bajar renombrar">
            <h3><i class="bi bi-folder"></i> Categorías y Organizar <a class="pa-ancla" href="#como-categorias">#</a></h3>
            <ul>
                <li><b>Categorías</b> (menú <i class="bi bi-three-dots"></i>): crear, renombrar, ordenar y borrar.</li>
                <li><b>Organizar</b> (mismo menú) saca los selectores para cambiar piezas de categoría. Fuera de ese modo no aparecen, para que no estorben.</li>
            </ul>
        </article>

        <article class="pa-ficha" id="como-nevera" data-claves="nevera congelar aparcar incompleta no funciona descongelar">
            <h3><i class="bi bi-snow"></i> Nevera <a class="pa-ancla" href="#como-nevera">#</a></h3>
            <p>Para piezas hechas pero incompletas o que no acaban de funcionar: las <b>congelas</b> desde la ficha y desaparecen del índice. No caducan nunca.</p>
            <p class="pa-corto mb-0">Están en <?= $pag('piezas/nevera', 'Nevera') ?>; <b>descongelar</b> las devuelve al índice.</p>
        </article>

        <article class="pa-ficha" id="como-papelera" data-claves="papelera borrar pieza variante restaurar recuperar purgar dias">
            <h3><i class="bi bi-trash"></i> Papelera <a class="pa-ancla" href="#como-papelera">#</a></h3>
            <p>Aquí nada se borra de verdad: una pieza o variante borrada va a la <?= $pag('piezas/papelera', 'Papelera') ?> y se puede <b>restaurar</b>.</p>
            <p class="pa-corto mb-0">El servidor la vacía a los 90 días (<code>php spark piezas:purgar</code>). La papelera local del CLI caduca a los 30.</p>
        </article>

        <article class="pa-ficha" id="como-backup" data-claves="backup copia seguridad descargar estadisticas espacio ocupado">
            <h3><i class="bi bi-download"></i> Copia de seguridad y estadísticas <a class="pa-ancla" href="#como-backup">#</a></h3>
            <p>El botón <i class="bi bi-download"></i> baja el <code>.blend</code> de referencia de cada pieza más su historial en texto (sin STL ni fotos).</p>
            <p class="pa-corto mb-0"><?= $pag('piezas/estadisticas', 'Estadísticas') ?> enseña cuánto ocupa todo en el servidor.</p>
        </article>
    </div>
</section>

<!-- ─────────── COMANDOS ─────────── -->
<section class="pa-seccion" id="comandos">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">07</span><h2>Comandos</h2><span>chuleta de la terminal</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="cmd-trackbitos" data-claves="comandos trackbitos cli chuleta alias lista estado descargar subir cerrar promocionar ver variantes">
            <h3><i class="bi bi-terminal"></i> <code>trackbitos</code> <a class="pa-ancla" href="#cmd-trackbitos">#</a></h3>
            <table class="table table-sm">
                <tr><td><code>t</code> trabajar</td><td>Carpeta + descarga + Blender, todo junto.</td></tr>
                <tr><td><code>e</code> estado</td><td>Diagnóstico: qué toca hacer ahora.</td></tr>
                <tr><td><code>d</code> descargar</td><td>Descarga y abre sesión (en la carpeta actual).</td></tr>
                <tr><td><code>v</code> ver</td><td>Descarga solo para mirar, sin sesión.</td></tr>
                <tr><td><code>s</code> subir</td><td>Sube el <code>.blend</code>. <code>--log "…"</code></td></tr>
                <tr><td><code>c</code> cerrar</td><td>Cierra la sesión. <code>--sin-cambios</code></td></tr>
                <tr><td><code>p</code> promocionar</td><td>Versión nueva. <code>--cambio "…"</code></td></tr>
                <tr><td><code>b</code> borrar</td><td>Recoge la carpeta si todo está subido.</td></tr>
                <tr><td><code>l</code> lista</td><td>El catálogo entero por categoría.</td></tr>
                <tr><td><code>va</code> variantes</td><td>Las variantes de una pieza.</td></tr>
                <tr><td><code>pa</code> papelera</td><td>Lo apartado en local y cuándo caduca.</td></tr>
            </table>
            <p class="pa-corto mb-0">Todos aceptan <code>--dir</code>. <code>d</code>, <code>t</code> y <code>v</code> aceptan <code>--ignorar-pendiente</code>.</p>
        </article>

        <article class="pa-ficha" id="cmd-stl" data-claves="comandos stl chuleta biblioteca generar placa sin imagen revisar">
            <h3><i class="bi bi-terminal"></i> <code>stl</code> <a class="pa-ancla" href="#cmd-stl">#</a></h3>
            <table class="table table-sm">
                <tr><td><code>g</code> generar</td><td>STL de una pieza.</td></tr>
                <tr><td><code>p</code> placa</td><td>STL de una placa (id o nombre). <code>--como-anotado</code> para repetirla idéntica.</td></tr>
                <tr><td><code>b</code> biblioteca</td><td>Copia local de todo. <code>--sin-blend</code></td></tr>
                <tr><td><code>si</code> sin-imagen</td><td>STL de las piezas sin foto.</td></tr>
                <tr><td><code>s</code> sueltas</td><td>STL de unas versiones (ids del carrito), sin placa.</td></tr>
                <tr><td><code>r</code> revisar</td><td>Compara el .blend con lo subido.</td></tr>
            </table>
            <p class="pa-corto mb-0">Necesita Blender instalado. Su configuración (<code>stl.config.json</code>) es propia de cada ordenador.</p>
        </article>

        <article class="pa-ficha" id="cmd-config" data-claves="configurar instalar config.json token api primera vez ordenador nuevo uuid funcion shell zshrc powershell">
            <h3><i class="bi bi-gear"></i> Configurar un ordenador nuevo <a class="pa-ancla" href="#cmd-config">#</a></h3>
            <ol>
                <li>Crea <code>~/.trackbitos/config.json</code> con <code>url_base</code> y <code>token</code> (el <code>piezas.apiToken</code> del <code>.env</code> del servidor).</li>
                <li>Pon nombre al equipo antes: el primer comando lo registra con su hostname.</li>
                <li>Añade la función <code>trackbitos</code> (y <code>stl</code>) al <code>~/.zshrc</code> o al <code>$PROFILE</code> de PowerShell, para que <code>t</code> te deje dentro de la carpeta.</li>
            </ol>
            <p class="pa-corto mb-0">El detalle está en <code>piezas-cli/TUTORIAL.md</code>.</p>
        </article>
    </div>
</section>

<!-- ─────────── PROBLEMAS ─────────── -->
<section class="pa-seccion" id="problemas">
    <div class="pa-seccion-cab"><span class="pa-seccion-num">08</span><h2>Problemas frecuentes</h2><span>cuando algo se niega</span></div>
    <div class="pa-rejilla">
        <article class="pa-ficha" id="prob-sin-subir" data-claves="se niega descargar cambios sin subir local origen error">
            <h3><i class="bi bi-exclamation-triangle"></i> «Tienes cambios sin subir» <a class="pa-ancla" href="#prob-sin-subir">#</a></h3>
            <p>Descargar no pisa trabajo local. Sube primero (<code>trackbitos s</code>) y luego descarga. Si dudas, <code>trackbitos e</code> te dice qué hacer.</p>
        </article>

        <article class="pa-ficha" id="prob-impresa" data-claves="se niega bloqueada impresion sin juzgar impresa no deja trabajar devolver">
            <h3><i class="bi bi-exclamation-triangle"></i> «Hay una impresión sin juzgar» <a class="pa-ancla" href="#prob-impresa">#</a></h3>
            <p>Alguna versión de esa variante está en <b>impresa</b>. Valídala o descártala (desde su ficha o en <?= $pag('piezas/revisar', 'Revisar') ?>) y ya podrás seguir.</p>
        </article>

        <article class="pa-ficha" id="prob-otra-maquina" data-claves="se niega otra maquina copia viva descarga pendiente sesion abierta">
            <h3><i class="bi bi-exclamation-triangle"></i> «Hay una descarga sin cerrar en otra máquina» <a class="pa-ancla" href="#prob-otra-maquina">#</a></h3>
            <p>Lo mejor es ir a ese ordenador, subir y cerrar. Si no puedes, usa <b>Forzar cierre</b> en la ficha. Si solo quieres seguir sabiendo que allí no hay nada, añade <code>--ignorar-pendiente</code>.</p>
        </article>

        <article class="pa-ficha" id="prob-boton" data-claves="me equivoque boton mal pulsado validar descartar deshacer error texto">
            <h3><i class="bi bi-arrow-counterclockwise"></i> Pulsé el botón que no era <a class="pa-ancla" href="#prob-boton">#</a></h3>
            <p><b>Deshacer</b> devuelve a borrador una versión impresa o descartada. Una validada no se puede deshacer: si no era la buena, valida la correcta y la otra pasa a superada.</p>
        </article>

        <article class="pa-ficha" id="prob-hueco" data-claves="no deja meter hueco ya vive en otro hueco ubicacion mover">
            <h3><i class="bi bi-exclamation-triangle"></i> «Esa pieza ya vive en otro hueco» <a class="pa-ancla" href="#prob-hueco">#</a></h3>
            <p>Cada pieza tiene un solo hueco. Para cambiarla de sitio, usa <b>Mover pieza</b> desde su hueco actual o <b>Traer pieza aquí</b> desde el nuevo.</p>
        </article>

        <article class="pa-ficha" id="prob-stl" data-claves="cambiar stl sustituir reemplazar inmutable no deja subir">
            <h3><i class="bi bi-exclamation-triangle"></i> Quiero cambiar un STL ya subido <a class="pa-ancla" href="#prob-stl">#</a></h3>
            <p>Un STL subido no se edita. Si era un error, quítalo y súbelo de nuevo. Si el modelo ha cambiado de verdad, promociona una versión nueva y ponle ahí su STL.</p>
        </article>

        <article class="pa-ficha" id="prob-stock" data-claves="stock no cuadra existencias duplicado placa inventario dos veces">
            <h3><i class="bi bi-exclamation-triangle"></i> El stock no cuadra <a class="pa-ancla" href="#prob-stock">#</a></h3>
            <p>Revisa copias y fallidas en la bitácora y pulsa otra vez <b>Dar de alta en inventario</b>: reajusta sin duplicar. Para lo demás (roturas, regalos…), haz una baja o un ajuste manual con su motivo.</p>
        </article>
    </div>
</section>

</div>

<script>
    (function () {
        const input = document.getElementById('pa-q');
        const vacio = document.getElementById('pa-vacio');
        const contenedor = document.getElementById('pa-secciones');
        const secciones = Array.from(contenedor.querySelectorAll('.pa-seccion'));
        const fichas = Array.from(contenedor.querySelectorAll('.pa-ficha'));

        const normalizar = s => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
        const textoDe = new Map(), tituloDe = new Map(), clavesDe = new Map(), ordenOriginal = new Map();
        fichas.forEach((f, i) => {
            tituloDe.set(f, normalizar(f.querySelector('h3').textContent));
            clavesDe.set(f, normalizar(f.dataset.claves || ''));
            textoDe.set(f, normalizar(f.textContent + ' ' + (f.dataset.claves || '')));
            ordenOriginal.set(f, i);
        });

        const puntos = (f, palabras) => palabras.reduce((s, p) =>
            s + (tituloDe.get(f).includes(p) ? 4 : 0) + (clavesDe.get(f).includes(p) ? 2 : 0) + 1, 0);

        function filtrar() {
            const palabras = normalizar(input.value.trim()).split(/\s+/).filter(Boolean);
            let visibles = 0;
            fichas.forEach(f => {
                const ok = palabras.every(p => textoDe.get(f).includes(p));
                f.classList.toggle('pa-oculta', !ok);
                if (ok) visibles++;
            });
            document.querySelectorAll('.pa-rejilla').forEach(rejilla => {
                Array.from(rejilla.children)
                    .sort((a, b) => (palabras.length ? puntos(b, palabras) - puntos(a, palabras) : 0)
                        || ordenOriginal.get(a) - ordenOriginal.get(b))
                    .forEach(f => rejilla.appendChild(f));
            });
            const mejor = s => Math.max(0, ...Array.from(s.querySelectorAll('.pa-ficha:not(.pa-oculta)')).map(f => puntos(f, palabras)));
            secciones.slice()
                .sort((a, b) => (palabras.length ? mejor(b) - mejor(a) : 0) || secciones.indexOf(a) - secciones.indexOf(b))
                .forEach(s => contenedor.appendChild(s));
            secciones.forEach(s => s.classList.toggle('pa-oculta', !s.querySelector('.pa-ficha:not(.pa-oculta)')));
            vacio.style.display = visibles === 0 ? 'block' : 'none';

            const url = new URL(location.href);
            palabras.length ? url.searchParams.set('q', input.value.trim()) : url.searchParams.delete('q');
            history.replaceState(null, '', url);
        }

        input.addEventListener('input', filtrar);
        document.querySelectorAll('[data-atajo]').forEach(b => b.addEventListener('click', () => {
            input.value = b.dataset.atajo;
            filtrar();
            input.focus();
        }));

        document.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', () => {
            if (input.value !== '') {
                input.value = '';
                filtrar();
            }
        }));

        document.addEventListener('keydown', e => {
            if (e.key === '/' && document.activeElement !== input) {
                e.preventDefault();
                input.focus();
            } else if (e.key === 'Escape' && document.activeElement === input) {
                input.value = '';
                filtrar();
            }
        });

        document.querySelectorAll('.pa-copiar').forEach(b => b.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(b.dataset.copiar);
                b.innerHTML = '<i class="bi bi-check2"></i>';
                setTimeout(() => { b.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1200);
            } catch (e) {}
        }));

        const q = new URLSearchParams(location.search).get('q');
        if (q) {
            input.value = q;
            filtrar();
        }
    })();
</script>

<?= $this->endSection() ?>
