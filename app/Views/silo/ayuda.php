<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<?= $this->include('silo/_estilos_control') ?>

<?php
    // Bloque de comando copiable. $nota es una aclaración corta opcional.
    $cmd = static function (string $comando, string $nota = ''): string {
        return '<div class="ayuda-cmd"><code>' . esc($comando) . '</code>'
            . '<button type="button" class="ayuda-copiar" data-copiar="' . esc($comando, 'attr') . '" title="Copiar"><i class="bi bi-clipboard"></i></button>'
            . ($nota !== '' ? '<span class="ayuda-cmd-nota">' . esc($nota) . '</span>' : '')
            . '</div>';
    };
    $pag = static fn (string $ruta, string $texto): string => '<a href="' . esc(site_url($ruta), 'attr') . '">' . esc($texto) . '</a>';
?>

<style>
    .ayuda-buscador {
        position: sticky;
        top: 0;
        z-index: 5;
        background: var(--bs-body-bg);
        padding: .75rem 0 .5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .ayuda-buscador .form-control {
        font-size: 1.05rem;
        padding: .65rem .9rem .65rem 2.4rem;
    }
    .ayuda-buscador .bi-search {
        position: absolute;
        left: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--bs-secondary-color);
    }
    .ayuda-atajos { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .ayuda-atajos button {
        font-size: .75rem;
        border: 1px solid var(--bs-border-color);
        background: transparent;
        color: var(--bs-secondary-color);
        border-radius: 999px;
        padding: .1rem .6rem;
    }
    .ayuda-atajos button:hover { color: var(--bs-emphasis-color); border-color: var(--bs-emphasis-color); }
    .ayuda-indice { display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .8rem; margin-top: .5rem; }
    .ayuda-indice a { color: var(--bs-secondary-color); text-decoration: none; }
    .ayuda-indice a:hover { color: var(--bs-emphasis-color); }

    .ayuda-seccion { margin-bottom: 2.25rem; scroll-margin-top: 8rem; }
    .ayuda-rejilla {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 22rem), 1fr));
        gap: 1rem;
    }
    .ayuda-ficha {
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        padding: .9rem 1rem;
        background: var(--bs-body-bg);
        scroll-margin-top: 8rem;
    }
    .ayuda-ficha:target { border-color: var(--silo-accent, #fbbf24); box-shadow: 0 0 0 .2rem rgba(251, 191, 36, .25); }
    .ayuda-ficha h3 { font-size: .98rem; font-weight: 650; margin: 0 0 .35rem; display: flex; gap: .45rem; align-items: baseline; }
    .ayuda-ficha h3 i { color: var(--silo-accent, #fbbf24); }
    .ayuda-ficha h3 a.ayuda-ancla { margin-left: auto; color: var(--bs-secondary-color); opacity: 0; text-decoration: none; font-size: .8rem; }
    .ayuda-ficha:hover h3 a.ayuda-ancla { opacity: 1; }
    .ayuda-corto { font-size: .86rem; color: var(--bs-secondary-color); margin-bottom: .55rem; }
    .ayuda-ficha ol, .ayuda-ficha ul { padding-left: 1.15rem; margin-bottom: .5rem; font-size: .88rem; }
    .ayuda-ficha li { margin-bottom: .2rem; }
    .ayuda-ficha p { font-size: .88rem; margin-bottom: .5rem; }
    .ayuda-ficha details { font-size: .84rem; margin-top: .4rem; }
    .ayuda-ficha details summary { color: var(--bs-secondary-color); cursor: pointer; }
    .ayuda-ficha details[open] summary { margin-bottom: .35rem; }
    .ayuda-ficha table { font-size: .82rem; margin-bottom: .5rem; }

    .ayuda-cmd {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .25rem .5rem;
        background: var(--bs-tertiary-bg);
        border-radius: .45rem;
        padding: .3rem .4rem .3rem .65rem;
        margin: .3rem 0;
    }
    .ayuda-cmd code { color: var(--bs-emphasis-color); font-size: .84rem; word-break: break-all; }
    .ayuda-cmd-nota { flex-basis: 100%; font-size: .76rem; color: var(--bs-secondary-color); }
    .ayuda-copiar {
        margin-left: auto;
        border: 0;
        background: transparent;
        color: var(--bs-secondary-color);
        padding: 0 .3rem;
    }
    .ayuda-copiar:hover { color: var(--bs-emphasis-color); }

    .ayuda-ejemplo {
        font-family: var(--bs-font-monospace);
        font-size: .8rem;
        background: var(--bs-tertiary-bg);
        border-radius: .45rem;
        padding: .45rem .65rem;
        margin: .3rem 0 .5rem;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .ayuda-ojo {
        font-size: .82rem;
        border-left: 3px solid var(--silo-accent, #fbbf24);
        padding: .2rem 0 .2rem .6rem;
        margin: .45rem 0;
    }

    .ayuda-flujo { display: flex; flex-wrap: wrap; align-items: stretch; gap: .5rem; margin: .25rem 0 1rem; }
    .ayuda-paso {
        flex: 1 1 9rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .65rem;
        padding: .6rem .7rem;
        font-size: .8rem;
        position: relative;
    }
    .ayuda-paso b { display: block; font-size: .86rem; margin-bottom: .15rem; }
    .ayuda-paso .ayuda-paso-num {
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        color: var(--silo-accent, #fbbf24);
        font-size: .72rem;
    }
    .ayuda-paso code { font-size: .75rem; }
    .ayuda-flecha { align-self: center; color: var(--bs-secondary-color); }
    @media (max-width: 575.98px) { .ayuda-flecha { display: none; } }

    .ayuda-niveles { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr)); gap: .6rem; }
    .ayuda-nivel { border-radius: .6rem; padding: .6rem .75rem; border: 1px solid var(--bs-border-color); font-size: .82rem; }
    .ayuda-nivel b { font-size: .9rem; }

    .ayuda-sin-resultados { display: none; text-align: center; color: var(--bs-secondary-color); padding: 2rem 0; }
    .ayuda-oculta { display: none !important; }
</style>

<div class="silo-control-breadcrumb">
    <span class="silo-control-dot"></span>
    <a href="<?= site_url('silo') ?>">Silo</a> / Ayuda
</div>
<h1 class="silo-control-titulo">Ayuda del <strong>Silo</strong></h1>

<div class="ayuda-buscador">
    <div class="position-relative">
        <i class="bi bi-search"></i>
        <input type="search" id="ayuda-q" class="form-control" autocomplete="off"
               placeholder="¿Qué quieres hacer? Ej.: copiar a un USB, espejo, un año no cabe…  (atajo: /)">
    </div>
    <div class="ayuda-atajos">
        <?php foreach (['copiar usb', 'escanear', 'nombre carpeta', 'espejo', 'reparto', 'pendiente', 'renombrar', 'restaurar', 'comandos'] as $atajo): ?>
            <button type="button" data-atajo="<?= esc($atajo, 'attr') ?>"><?= esc($atajo) ?></button>
        <?php endforeach; ?>
    </div>
    <nav class="ayuda-indice">
        <a href="#empieza">Empieza aquí</a>
        <a href="#como">Cómo se hace</a>
        <a href="#conceptos">Conceptos</a>
        <a href="#comandos">Comandos</a>
        <a href="#paginas">Páginas de la web</a>
        <a href="#problemas">Problemas frecuentes</a>
        <a href="#glosario">Glosario</a>
    </nav>
</div>

<p class="ayuda-sin-resultados" id="ayuda-vacio">
    <i class="bi bi-emoji-neutral fs-3 d-block mb-2"></i>
    Nada encaja con esa búsqueda. Prueba con otra palabra (p. ej. «usb», «año», «nombre»).
</p>

<div id="ayuda-secciones">
<!-- ───────────────────────── EMPIEZA AQUÍ ───────────────────────── -->
<section class="ayuda-seccion" id="empieza">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">00</span>
        <h6 class="silo-seccion-titulo">EMPIEZA AQUÍ</h6>
        <span class="silo-seccion-sub">EL SISTEMA EN 30 SEGUNDOS</span>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <article class="ayuda-ficha" id="resumen" style="grid-column: 1 / -1;" data-claves="resumen funciona sistema flujo general idea que es silo">
            <h3><i class="bi bi-diagram-3"></i> Cómo funciona todo, de un vistazo <a class="ayuda-ancla" href="#resumen">#</a></h3>
            <p class="ayuda-corto">Tus fotos y vídeos viven en carpetas en un disco <b>Maestro</b>. Silo las cataloga y las reparte en copias de seguridad en USB, por años y por temática.</p>
            <div class="ayuda-flujo">
                <div class="ayuda-paso"><span class="ayuda-paso-num">1</span><b>Ordenas el Maestro</b>Una carpeta por evento, con su nombre bien puesto (ID + fecha + categoría…).</div>
                <span class="ayuda-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="ayuda-paso"><span class="ayuda-paso-num">2</span><b>Escaneas</b><code>silo</code> lee el disco y la web lo cataloga, con miniaturas.</div>
                <span class="ayuda-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="ayuda-paso"><span class="ayuda-paso-num">3</span><b>La web reparte</b>Decide qué carpeta va a qué USB: por años (Nivel 2) y por temática (Nivel 3).</div>
                <span class="ayuda-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="ayuda-paso"><span class="ayuda-paso-num">4</span><b>Copias</b><code>silo --copiar</code> te pide cada USB y le copia lo suyo.</div>
                <span class="ayuda-flecha"><i class="bi bi-arrow-right"></i></span>
                <div class="ayuda-paso"><span class="ayuda-paso-num">5</span><b>Cada disco se protege</b>Todos llevan una réplica del catálogo por si se pierde la web.</div>
            </div>
            <div class="ayuda-ojo"><b>La regla de oro:</b> la web <b>decide</b> y el agente (<code>silo</code>, el programa de tu PC) <b>toca el disco</b>. La web nunca copia ni borra ficheros por su cuenta. Y el que manda sobre el contenido es siempre el <b>Maestro</b>: si quieres cambiar algo, lo cambias en el Maestro y vuelves a escanear.</div>
        </article>

        <article class="ayuda-ficha" id="niveles" style="grid-column: 1 / -1;" data-claves="niveles nivel 1 2 3 maestro año temática categoria copia 1 copia 2 copia 3 tres copias">
            <h3><i class="bi bi-layers"></i> Los tres niveles (las tres copias) <a class="ayuda-ancla" href="#niveles">#</a></h3>
            <p class="ayuda-corto">Cada carpeta acaba en tres sitios, cada uno ordenado a su manera.</p>
            <div class="ayuda-niveles">
                <div class="ayuda-nivel"><b>Nivel 1 · Maestro</b><br>El original, la fuente de la verdad. Carpetas sueltas en la raíz, ordenadas por ID. Es el <b>único que se escanea</b>. Puede tener <a href="#espejo">espejos</a>.</div>
                <div class="ayuda-nivel"><b>Nivel 2 · Año</b><br>Copia por años en USB. Cada USB guarda un <b>tramo de años seguidos</b> (p. ej. 2009-2012), en subcarpetas <code>2010/</code>, <code>2011/</code>… Ver <a href="#reparto-n2">reparto</a>.</div>
                <div class="ayuda-nivel"><b>Nivel 3 · Temática</b><br>Copia por categoría en USB. Cada USB es de <b>una categoría</b> (p. ej. «viajes»), en la subcarpeta <code>viajes/</code>. Ver <a href="#reparto-n3">reparto</a>.</div>
            </div>
        </article>
    </div>
</section>

<!-- ───────────────────────── CÓMO SE HACE ───────────────────────── -->
<section class="ayuda-seccion" id="como">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">01</span>
        <h6 class="silo-seccion-titulo">CÓMO SE HACE</h6>
        <span class="silo-seccion-sub">TAREAS PASO A PASO</span>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <article class="ayuda-ficha" id="como-preparar" data-claves="instalar configurar primera vez config.json token api agente python preparar empezar">
            <h3><i class="bi bi-gear"></i> Preparar el agente la primera vez <a class="ayuda-ancla" href="#como-preparar">#</a></h3>
            <p class="ayuda-corto">El agente es <code>silo-agente/agente.py</code>; el comando <code>silo</code> de PowerShell lo lanza desde cualquier carpeta.</p>
            <ol>
                <li>Copia <code>config.example.json</code> como <code>config.json</code> en <code>silo-agente/</code>.</li>
                <li>Rellena <code>api_base</code> (la URL de la web), <code>token</code> (el <code>silo.apiToken</code> del <code>.env</code> del servidor) y en <code>unidades</code> la <code>ruta</code> de tu Maestro (p. ej. <code>D:\Maestro</code>).</li>
                <li>Comprueba que la web reconoce el disco:</li>
            </ol>
            <?= $cmd('silo --solo-handshake', 'Solo saluda a la web y resuelve las unidades; no escanea nada.') ?>
            <details>
                <summary>Más detalle</summary>
                <ul>
                    <li><code>unidad_id</code> puede ir a <code>null</code>: si pones la misma ruta como «ruta de montaje» de la unidad en la web, la encuentra sola.</li>
                    <li><code>lista_negra</code>: nombres exactos de carpetas que nunca se ingestan (p. ej. «Descartes»).</li>
                    <li><code>verificar_tls: false</code> solo para la web local de desarrollo con certificado propio.</li>
                    <li>Solo usa Python 3 estándar. Para las miniaturas hace falta <code>ffmpeg</code> en el PATH; sin él escanea igual, pero sin miniaturas.</li>
                </ul>
            </details>
        </article>

        <article class="ayuda-ficha" id="como-alta-maestro" data-claves="alta unidad maestro disco nuevo nivel 1 registrar añadir disco">
            <h3><i class="bi bi-device-hdd"></i> Dar de alta un disco Maestro <a class="ayuda-ancla" href="#como-alta-maestro">#</a></h3>
            <p class="ayuda-corto">Antes de escanear, el disco tiene que existir en la web.</p>
            <ol>
                <li>Ve a <?= $pag('silo/unidades', 'Unidades') ?> → en <b>Nivel Maestro</b>, «Añadir unidad».</li>
                <li>Pon etiqueta, tipo, capacidad y la <b>ruta de montaje</b> (la letra/carpeta con la que aparece en tu PC).</li>
                <li>Deja «Espejo de» en <i>Ninguno</i> (es un Maestro de verdad).</li>
                <li>Añádelo a <code>config.json</code> y escanea (<a href="#como-escanear">ver cómo</a>).</li>
            </ol>
        </article>

        <article class="ayuda-ficha" id="como-escanear" data-claves="escanear escaneo ingesta leer disco actualizar catalogo silo pasada daemon solicitar">
            <h3><i class="bi bi-arrow-repeat"></i> Escanear el Maestro <a class="ayuda-ancla" href="#como-escanear">#</a></h3>
            <p class="ayuda-corto">Lleva a la web lo que hay en el disco: carpetas nuevas, cambios, renombres y borrados.</p>
            <?= $cmd('silo', 'Pasada normal: escanea los Maestros de config.json. Solo manda lo que ha cambiado.') ?>
            <?= $cmd('silo --dry-run', 'Ensayo: enseña lo que detectaría, sin tocar la web.') ?>
            <p class="mb-1"><b>O desde la web:</b> botón «Solicitar escaneo» en la tarjeta del Maestro, con el agente esperando:</p>
            <?= $cmd('silo --daemon', 'Se queda escuchando (cada 20 s; cámbialo con --intervalo N) y escanea solo cuando la web lo pide.') ?>
            <div class="ayuda-ojo">Al escanear, la web también reparte en Nivel 2 y 3 las carpetas nuevas (si hay un USB con sitio). Las carpetas que se ha saltado, con el motivo, aparecen en <?= $pag('silo/avisos', 'Avisos') ?>.</div>
        </article>

        <article class="ayuda-ficha" id="como-carpeta-nueva" data-claves="carpeta nueva añadir fotos videos crear pieza nueva meter contenido">
            <h3><i class="bi bi-folder-plus"></i> Añadir una carpeta nueva <a class="ayuda-ancla" href="#como-carpeta-nueva">#</a></h3>
            <p class="ayuda-corto">Se hace en el disco, no en la web.</p>
            <ol>
                <li>Crea la carpeta en la <b>raíz</b> del Maestro con un <a href="#nombre-carpeta">nombre válido</a> y el siguiente ID libre del año (p. ej. <code>260016</code>).</li>
                <li>Mete dentro las fotos y los vídeos, <b>sin subcarpetas</b>. A los vídeos ponles <code>+</code> delante (<code>+MVI_0042.mp4</code>).</li>
                <li>Escanea: <code>silo</code>.</li>
                <li>Para pasarla a los USB: <code>silo --copiar</code>.</li>
            </ol>
        </article>

        <article class="ayuda-ficha" id="como-reclasificar" data-claves="corregir cambiar categoria tema lugar personas renombrar carpeta reclasificar fecha mal">
            <h3><i class="bi bi-pencil-square"></i> Corregir el nombre, la fecha o la categoría de una carpeta <a class="ayuda-ancla" href="#como-reclasificar">#</a></h3>
            <p class="ayuda-corto">El nombre de la carpeta del Maestro manda. Renómbrala ahí y vuelve a escanear.</p>
            <ol>
                <li>Renombra la carpeta en el Maestro. <b>No cambies el ID</b> (los 6 primeros dígitos): es lo que la une a su ficha.</li>
                <li><code>silo</code> → la web actualiza la categoría, tema, lugar y personas.</li>
                <li>Si ya estaba copiada en los USB: <code>silo --renombrar</code> (renombra en los USB sin copiar nada) o <code>silo --copiar</code>.</li>
            </ol>
            <details>
                <summary>¿Y si cambia de año o de categoría?</summary>
                <p>Entonces su copia tiene que cambiar de cubo (p. ej. de <code>sin_fecha/</code> a <code>2006/</code>), quizá incluso de USB. Si aún no estaba copiada, la web lo corrige sola. Si ya lo estaba, se crea una tarea <i>mover_copia</i> en <?= $pag('silo/tareas', 'Tareas') ?>, que <code>silo --copiar</code> aplica y cierra.</p>
            </details>
        </article>

        <article class="ayuda-ficha" id="como-borrar" data-claves="borrar eliminar carpeta quitar pieza desaparecida">
            <h3><i class="bi bi-trash"></i> Borrar una carpeta <a class="ayuda-ancla" href="#como-borrar">#</a></h3>
            <ol>
                <li>Bórrala (o muévela fuera) del Maestro.</li>
                <li><code>silo</code> → desaparece del catálogo y queda un aviso <i>carpeta desaparecida</i>.</li>
                <li>En los USB queda como «sobrante»: <code>silo --copiar --purgar</code> la borra (te pide escribir BORRAR).</li>
            </ol>
            <div class="ayuda-ojo">Si solo quieres que deje de ingestarse sin borrarla, ponle <code>_</code> delante del nombre (ver <a href="#ignoradas">carpetas ignoradas</a>).</div>
        </article>

        <article class="ayuda-ficha" id="como-alta-usb" data-claves="alta usb nuevo nivel 2 nivel 3 añadir unidad copia capacidad pendrive registrar">
            <h3><i class="bi bi-usb-drive"></i> Dar de alta un USB nuevo (Nivel 2 o 3) <a class="ayuda-ancla" href="#como-alta-usb">#</a></h3>
            <ol>
                <li><?= $pag('silo/unidades', 'Unidades') ?> → «Añadir unidad» en <b>Nivel Año</b> o <b>Nivel Temática</b>.</li>
                <li>Pon la <b>capacidad real</b> (64 GB, 128 GB…): sin ella no entra en el reparto de años.</li>
                <li>Nivel 2: deja vacío «Año o categoría»; los años se los asigna el reparto. Nivel 3: déjalo vacío para que el reparto le asigne varias categorías, o escribe una (p. ej. <code>Viajes</code>) si quieres que guarde solo esa.</li>
                <li>Pulsa <a href="#como-recalcular">Recalcular reparto</a> y después <code>silo --copiar</code>.</li>
            </ol>
            <div class="ayuda-ojo">No hace falta añadir los USB a <code>config.json</code>: <code>silo --copiar</code> los reconoce solo por su <code>.silo_unit.json</code>. Uno recién estrenado lo eliges de la lista de discos la primera vez.</div>
        </article>

        <article class="ayuda-ficha" id="como-recalcular" data-claves="recalcular reparto boton redistribuir reorganizar usb años nivel 2 nivel 3 pendiente">
            <h3><i class="bi bi-arrow-repeat"></i> Recalcular el reparto <a class="ayuda-ancla" href="#como-recalcular">#</a></h3>
            <p class="ayuda-corto">Vuelve a decidir qué años van a cada USB y coloca lo que estaba pendiente.</p>
            <p><b>Cuándo:</b> al dar de alta o quitar un USB, o cuando ha crecido mucho el contenido y hay cosas <a href="#pendiente">pendientes de almacenar</a>.</p>
            <ol>
                <li><?= $pag('silo/unidades', 'Unidades') ?> → botón <b>Recalcular reparto</b> (arriba a la derecha).</li>
                <li>Revisa las tarjetas: cada USB de Nivel 2 enseña su tramo de años y lo que ocupa.</li>
                <li><code>silo --copiar</code> para llevarlo a los USB de verdad.</li>
            </ol>
            <?= $cmd('php spark silo:planificar-nivel2', 'Desde el servidor: enseña el reparto sin aplicarlo. Con --aplicar, lo aplica (igual que el botón).') ?>
            <div class="ayuda-ojo">No crea ni borra unidades. Lo ya copiado en un USB intenta quedarse en él para que no tengas que copiarlo otra vez.</div>
        </article>

        <article class="ayuda-ficha" id="como-copiar" data-claves="copiar usb copia fisica pasar llevar volcar propagar nivel 2 3 conectar pendrive">
            <h3><i class="bi bi-box-arrow-in-down"></i> Copiar a los USB <a class="ayuda-ancla" href="#como-copiar">#</a></h3>
            <p class="ayuda-corto">Lleva a cada USB (y a cada espejo) lo que la web dice que le toca.</p>
            <?= $cmd('silo --copiar', 'Te dice qué USB conectar, uno a uno («Conecta Nivel 2 #1 … y pulsa Enter»).') ?>
            <?= $cmd('silo --copiar 55 56', 'Solo esas unidades (por su #id). Sirve también para comprobar rápido que están al día.') ?>
            <ol>
                <li>Ten el <b>Maestro conectado</b> (de ahí salen los ficheros).</li>
                <li>Conecta el USB que te pide y pulsa Enter.</li>
                <li>Copia solo lo que falta o ha cambiado, y renombra en el sitio lo que se ha renombrado.</li>
                <li>Al acabar con cada USB, deja en él su manifiesto y una <a href="#replica">réplica del catálogo</a>.</li>
            </ol>
            <div class="ayuda-ojo">Escanea antes (<code>silo</code>): si un fichero del Maestro no coincide con lo que tiene la web, la copia da error.</div>
            <details>
                <summary>Más detalle</summary>
                <ul>
                    <li>Copia primero a una carpeta <code>*.silo_tmp</code> y la renombra al terminar: si se corta, la próxima vez continúa.</li>
                    <li>Compara por tamaño y fecha, con margen para FAT/exFAT y el cambio de horario.</li>
                    <li>Las carpetas que ya no le tocan a ese USB no se borran: se avisan como <i>sobrantes</i> (ver <a href="#como-purgar">purgar</a>).</li>
                </ul>
            </details>
        </article>

        <article class="ayuda-ficha" id="como-solo-renombrar" data-claves="renombrar solo nombres sin copiar rapido usb cambio de nombre">
            <h3><i class="bi bi-input-cursor-text"></i> Pasar solo los renombres a los USB <a class="ayuda-ancla" href="#como-solo-renombrar">#</a></h3>
            <p class="ayuda-corto">Has corregido nombres en el Maestro y quieres que los USB los tengan igual, sin copiar nada.</p>
            <?= $cmd('silo --renombrar', 'No necesita el Maestro conectado. También admite ids: silo --renombrar 55') ?>
        </article>

        <article class="ayuda-ficha" id="como-purgar" data-claves="purgar borrar sobrantes limpiar usb sobra liberar espacio">
            <h3><i class="bi bi-eraser"></i> Limpiar lo que sobra en un USB <a class="ayuda-ancla" href="#como-purgar">#</a></h3>
            <p class="ayuda-corto">Tras un reparto nuevo o tras borrar algo del Maestro, en un USB quedan carpetas que ya no le tocan.</p>
            <?= $cmd('silo --copiar --purgar', 'Copia lo pendiente y además borra lo sobrante. Pide escribir BORRAR.') ?>
            <?= $cmd('silo --copiar --purgar --dry-run', 'Primero, para ver qué borraría.') ?>
        </article>

        <article class="ayuda-ficha" id="como-espejo" data-claves="espejo crear copia exacta maestro duplicado clon segundo disco">
            <h3><i class="bi bi-files"></i> Crear un espejo del Maestro <a class="ayuda-ancla" href="#como-espejo">#</a></h3>
            <p class="ayuda-corto">Un segundo disco idéntico al Maestro. Qué es: <a href="#espejo">ver concepto</a>.</p>
            <ol>
                <li><?= $pag('silo/unidades', 'Unidades') ?> → «Añadir unidad» en <b>Nivel Maestro</b>.</li>
                <li>En <b>Espejo de</b>, elige el Maestro que copia.</li>
                <li><code>silo --copiar</code> → te lo pedirá como un USB más y lo dejará igual que el Maestro.</li>
            </ol>
            <div class="ayuda-ojo">Un espejo <b>no se escanea</b>: no lo pongas en <code>config.json</code> ni le pidas escaneo. Si cambias algo en él, se pisa con lo del Maestro.</div>
        </article>

        <article class="ayuda-ficha" id="como-verificar" data-claves="verificar corrupcion integridad hash bit rot comprobar ficheros dañados n3">
            <h3><i class="bi bi-shield-check"></i> Comprobar que nada se ha corrompido <a class="ayuda-ancla" href="#como-verificar">#</a></h3>
            <p class="ayuda-corto">Relee y calcula el hash de <b>todos</b> los ficheros. Es lento: hazlo de vez en cuando (cada mes o trimestre).</p>
            <?= $cmd('silo --verificar', 'Avisa de ficheros con el mismo tamaño y la misma fecha pero otro contenido.') ?>
        </article>

        <article class="ayuda-ficha" id="como-restaurar" data-claves="restaurar catalogo base de datos recuperar perdida backup servidor caido replica">
            <h3><i class="bi bi-life-preserver"></i> Restaurar el catálogo desde un disco <a class="ayuda-ancla" href="#como-restaurar">#</a></h3>
            <p class="ayuda-corto">Si se pierde o se estropea la base de datos del Silo, cualquier disco tocado lleva una copia.</p>
            <?= $cmd('silo --restaurar-catalogo 55', 'Desde tu PC, con el disco #55 conectado. Pide escribir RESTAURAR.') ?>
            <?= $cmd('php spark silo:restaurar <ruta>/.catalogo.sql.gz', 'Desde el servidor. Con --si no pregunta.') ?>
            <div class="ayuda-ojo">Si un disco trae una réplica <b>más nueva</b> que la web, lo verás en <?= $pag('silo/avisos', 'Avisos') ?>. Es la señal de que deberías restaurar.</div>
        </article>

        <article class="ayuda-ficha" id="como-etiquetar" data-claves="etiquetar contenido fotos videos montajes etiqueta tema">
            <h3><i class="bi bi-tag"></i> Añadir «(Fotos)», «(Vídeos)»… a los nombres <a class="ayuda-ancla" href="#como-etiquetar">#</a></h3>
            <p class="ayuda-corto">Herramienta puntual: propone añadir al tema de cada carpeta lo que contiene de verdad.</p>
            <?= $cmd('silo --etiquetar-contenido', 'Solo enseña la propuesta.') ?>
            <?= $cmd('silo --etiquetar-contenido --aplicar', 'Renombra en el disco. Después, escanea con silo.') ?>
            <div class="ayuda-ojo">«(Montajes)» es una suposición (solo vídeos, y 4 o menos): repasa esas propuestas.</div>
        </article>

        <article class="ayuda-ficha" id="como-revisar" data-claves="revisar datos faltan ocupa mas grande ranking vocabulario sin uso limpiar terminos">
            <h3><i class="bi bi-clipboard-check"></i> Repasar el catálogo <a class="ayuda-ancla" href="#como-revisar">#</a></h3>
            <ul>
                <li><b>Qué le falta a cada carpeta</b> (temática, lugar, personas, nombre mal formado): <?= $pag('silo/datos-faltan', 'Datos que faltan') ?>.</li>
                <li><b>Qué ocupa más</b>: <?= $pag('silo/ranking', 'Ranking') ?>, con filtro por foto, vídeo u otro.</li>
                <li><b>Términos sin uso</b> (categorías, personas…): <?= $pag('silo/vocabulario', 'Vocabulario') ?>, donde se borran a mano.</li>
            </ul>
        </article>
    </div>
</section>

<!-- ───────────────────────── CONCEPTOS ───────────────────────── -->
<section class="ayuda-seccion" id="conceptos">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">02</span>
        <h6 class="silo-seccion-titulo">CONCEPTOS</h6>
        <span class="silo-seccion-sub">CÓMO ESTÁ PENSADO</span>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <article class="ayuda-ficha" id="pieza" data-claves="pieza carpeta id negocio identificador numero aannnn correlativo">
            <h3><i class="bi bi-folder2"></i> Pieza e ID de negocio <a class="ayuda-ancla" href="#pieza">#</a></h3>
            <p class="ayuda-corto">Una <b>pieza</b> es una carpeta del Maestro. Su <b>ID</b> la identifica para siempre.</p>
            <ul>
                <li>Formato <code>AAnnnn</code>: los 2 dígitos del año del contenido y 4 correlativos que empiezan de nuevo cada año. <code>260015</code> = la 15.ª de 2026.</li>
                <li><b>Lo pones tú</b> al crear la carpeta; nadie lo genera ni lo cambia.</li>
                <li>Mientras no cambies el ID, puedes renombrar el resto sin que se duplique la pieza.</li>
                <li>Si dos carpetas comparten ID, sale un aviso.</li>
            </ul>
        </article>

        <article class="ayuda-ficha" id="nombre-carpeta" data-claves="nombre carpeta formato patron como nombrar convencion comas categoria tema lugar personas sinfecha fecha">
            <h3><i class="bi bi-type"></i> Nombre de una carpeta del Maestro: cómo se pone <a class="ayuda-ancla" href="#nombre-carpeta">#</a></h3>
            <div class="ayuda-ejemplo">ID  FECHA     CATEGORÍA, tema, lugar, persona, persona…
260099 20070101 Recuerdos, Cumpleaños, Sevilla, Ana, Luis</div>
            <ul>
                <li><b>Fecha</b> <code>AAAAMMDD</code>, o <code>sinfecha</code> si no la sabes.</li>
                <li>Tras la categoría, el <b>orden es fijo</b>: 1.º tema, 2.º lugar y después todas las personas que quieras.</li>
                <li>¿Falta un campo? Deja el hueco con <b>coma doble</b> para no descolocar los siguientes:</li>
            </ul>
            <div class="ayuda-ejemplo">260099 20070101 Recuerdos, , Sevilla, Ana   ← sin tema</div>
            <details>
                <summary>Dentro de la carpeta</summary>
                <ul>
                    <li>Ficheros sueltos, <b>sin subcarpetas</b>. Fotos y vídeos juntos, con los nombres de la cámara.</li>
                    <li>Los vídeos llevan <code>+</code> delante: <code>+MVI_0042.mp4</code>. Así salen primero en cualquier explorador o tele.</li>
                    <li>Si mezclas varias cámaras, añade la hora tras el <code>+</code>: <code>+20230715-141230 MVI_0042.mp4</code>.</li>
                </ul>
            </details>
        </article>

        <article class="ayuda-ficha" id="ignoradas" data-claves="ignorar saltar no se ingesta prefijo guion bajo lista negra no aparece carpeta trabajo borrador">
            <h3><i class="bi bi-eye-slash"></i> Qué carpetas se ignoran <a class="ayuda-ancla" href="#ignoradas">#</a></h3>
            <p class="ayuda-corto">En la raíz del Maestro puede haber otras cosas; estas no se catalogan:</p>
            <ul>
                <li>Nombre que empieza por <code>_</code>, <code>.</code> o <code>~</code> (borradores, RAW, carpetas de trabajo).</li>
                <li>Nombres de la <code>lista_negra</code> de <code>config.json</code>.</li>
                <li>Lo que no sigue el formato (sin ID o sin fecha) y los ficheros sueltos en la raíz.</li>
            </ul>
            <p>Nada se salta en silencio: todo sale con su motivo en <?= $pag('silo/avisos', 'Avisos') ?>.</p>
        </article>

        <article class="ayuda-ficha" id="nombre-copias" data-claves="nombre en copias usb corchetes fecha primero id al final cubo">
            <h3><i class="bi bi-sort-down"></i> Cómo se llaman las carpetas en los USB <a class="ayuda-ancla" href="#nombre-copias">#</a></h3>
            <p class="ayuda-corto">En los niveles 2 y 3 la fecha va primero y el ID al final, entre corchetes.</p>
            <div class="ayuda-ejemplo">Maestro:  260015 20030603 Recuerdos, University Day
USB año:  2003/20030603 Recuerdos, University Day [260015]
USB tema: recuerdos/20030603 Recuerdos, University Day [260015]</div>
            <p>Así cualquier explorador ordena el USB <b>por fecha</b> sin hacer nada. El <code>[ID]</code> sirve para reconocer la carpeta aunque cambie el resto del nombre. Sin fecha va a <code>sin_fecha/</code>; sin categoría, a <code>sin_clasificar/</code>.</p>
        </article>

        <article class="ayuda-ficha" id="espejo" data-claves="espejo que es copia exacta maestro mismos nombres hash origen al dia">
            <h3><i class="bi bi-files"></i> Espejo <a class="ayuda-ancla" href="#espejo">#</a></h3>
            <p class="ayuda-corto">Una copia <b>exacta</b> del Maestro en otro disco: mismos nombres, en la raíz, sin cubos.</p>
            <ul>
                <li>Es una unidad de Nivel 1 con «Espejo de» apuntando a su Maestro.</li>
                <li><b>No se escanea ni se ingesta</b>: se rellena con <code>silo --copiar</code>.</li>
                <li>Está «al día» cuando su contenido coincide con el último escaneo del Maestro. Si no, su tarjeta dice <i>por detrás del Maestro</i>.</li>
                <li>Para qué: tener un segundo Maestro completo por si el primero muere.</li>
            </ul>
            <p>Cómo crearlo: <a href="#como-espejo">paso a paso</a>.</p>
        </article>

        <article class="ayuda-ficha" id="reparto-n2" data-claves="reparto nivel 2 años usb como se reparte tramo consecutivos optimo capacidad orden fragmentar huecos">
            <h3><i class="bi bi-calendar-range"></i> Reparto por años (Nivel 2) <a class="ayuda-ancla" href="#reparto-n2">#</a></h3>
            <p class="ayuda-corto">Reparte los años entre los USB de Nivel 2 que tienes dados de alta.</p>
            <ul>
                <li>Cada USB guarda <b>años seguidos</b> (2009-2012). Nunca 2003 junto con 2019.</li>
                <li><b>Un año nunca se parte</b> entre dos USB.</li>
                <li>Solo usa tus USB, con su capacidad real. <b>Nunca inventa unidades.</b></li>
                <li>No los llena en el orden en que los diste de alta: prueba todas las combinaciones y elige la mejor, por este orden de prioridad:
                    <ol>
                        <li>que quede <b>lo mínimo pendiente</b>;</li>
                        <li>que haya que <b>recopiar lo mínimo</b> (lo ya copiado se queda en su USB);</li>
                        <li>que queden <b>los mínimos huecos</b>: los USB que sobran quedan enteros libres.</li>
                    </ol>
                </li>
            </ul>
            <details>
                <summary>Estados de un tramo</summary>
                <table class="table table-sm mb-0">
                    <tr><td><b>ok</b></td><td>Cabe en su USB.</td></tr>
                    <tr><td><b>excede</b></td><td>Ese año él solo es más grande que cualquiera de tus USB. Hace falta uno más grande.</td></tr>
                    <tr><td><b>sin unidad</b></td><td>Cabría, pero no queda ningún USB libre. Da de alta otro.</td></tr>
                </table>
            </details>
            <div class="ayuda-ojo">El año en curso sigue creciendo: si se llena su USB, el próximo «Recalcular reparto» puede moverlo a otro.</div>
        </article>

        <article class="ayuda-ficha" id="reparto-n3" data-claves="reparto nivel 3 tematica categoria usb disco como se reparte varias categorias automatico">
            <h3><i class="bi bi-bookmarks"></i> Reparto por temática (Nivel 3) <a class="ayuda-ancla" href="#reparto-n3">#</a></h3>
            <p class="ayuda-corto">Cada disco de Nivel 3 guarda una o varias categorías enteras. Una categoría nunca se parte entre dos discos.</p>
            <ul>
                <li><b>Sin categoría puesta</b> (deja vacío «Año o categoría» y pon la capacidad): el reparto le asigna <b>varias categorías</b> que quepan, eligiendo la mejor combinación igual que en <a href="#reparto-n2">Nivel 2</a> (lo mínimo pendiente, lo mínimo a recopiar, los mínimos huecos). Sin capacidad no entra en el reparto.</li>
                <li><b>Con categoría puesta</b> (p. ej. <code>Viajes</code>): ese disco solo recibe esa categoría, y esa categoría ya no va a los discos automáticos. Si una categoría tiene varios, los llena en orden de alta.</li>
                <li>Si cambias la categoría de un disco, lo copiado de antes queda como sobrante en el USB (<code>silo --copiar --purgar</code>).</li>
                <li>Si una categoría no cabe en ningún disco, sus carpetas quedan <a href="#pendiente">pendientes</a>.</li>
            </ul>
        </article>

        <article class="ayuda-ficha" id="pendiente" data-claves="pendiente de almacenar no cabe sin sitio tarjeta falta usb">
            <h3><i class="bi bi-hourglass-split"></i> «Pendiente de almacenar» <a class="ayuda-ancla" href="#pendiente">#</a></h3>
            <p class="ayuda-corto">Lo que ya está en el Maestro pero no cabe en ningún USB de ese nivel.</p>
            <p>Lo ves en una tarjeta discontinua al final de Nivel 2 y Nivel 3 en <?= $pag('silo/unidades', 'Unidades') ?>. <b>Solución:</b> da de alta un USB donde quepa y pulsa <a href="#como-recalcular">Recalcular reparto</a>.</p>
        </article>

        <article class="ayuda-ficha" id="ficheros-control" data-claves="ficheros control ocultos silo_unit json manifest catalogo sql gz silo_proxies raiz disco">
            <h3><i class="bi bi-file-earmark-code"></i> Ficheros ocultos en la raíz de cada disco <a class="ayuda-ancla" href="#ficheros-control">#</a></h3>
            <p class="ayuda-corto">Los escribe el agente. <b>No los borres.</b></p>
            <table class="table table-sm mb-0">
                <tr><td><code>.silo_unit.json</code></td><td>El DNI del disco: qué unidad es. Por eso se reconoce solo.</td></tr>
                <tr><td><code>.silo_manifest.json</code></td><td>La lista de lo que hay (tamaño, fecha, hash), para detectar cambios al momento.</td></tr>
                <tr><td><code>.catalogo.sql.gz</code><br><code>.catalogo.meta.json</code></td><td><a href="#replica">Réplica del catálogo</a> y su fecha y versión.</td></tr>
                <tr><td><code>.silo_proxies/</code></td><td>Solo en el Maestro: copia de las <a href="#proxies">miniaturas</a>.</td></tr>
            </table>
            <p class="mt-2 mb-0">El <code>.silo_unit.json</code> de una unidad también se descarga desde su tarjeta en <?= $pag('silo/unidades', 'Unidades') ?>.</p>
        </article>

        <article class="ayuda-ficha" id="deteccion" data-claves="deteccion cambios n0 n1 n2 n3 rapido hash manifiesto como sabe que cambio">
            <h3><i class="bi bi-speedometer2"></i> Cómo sabe qué ha cambiado (sin tardar) <a class="ayuda-ancla" href="#deteccion">#</a></h3>
            <p class="ayuda-corto">Cuatro niveles, del instantáneo al lento. Solo abre un fichero si hay sospecha.</p>
            <table class="table table-sm mb-0">
                <tr><td><b>N0</b></td><td>Instantáneo: ¿el disco coincide con lo que la web cree? Si coincide, ya está.</td></tr>
                <tr><td><b>N1</b></td><td>Segundos: mira tamaño y fecha de cada fichero, sin abrirlos.</td></tr>
                <tr><td><b>N2</b></td><td>Solo calcula el hash de los ficheros sospechosos (otra fecha, mismo tamaño).</td></tr>
                <tr><td><b>N3</b></td><td>Lento, a mano: el hash de todo. <code>silo --verificar</code></td></tr>
            </table>
        </article>

        <article class="ayuda-ficha" id="proxies" data-claves="miniaturas proxies previsualizacion ffmpeg fotogramas thumbnails imagenes">
            <h3><i class="bi bi-images"></i> Miniaturas (proxies) <a class="ayuda-ancla" href="#proxies">#</a></h3>
            <p class="ayuda-corto">Hasta 10 fotos y 10 fotogramas de vídeo por carpeta, para ver qué hay dentro sin abrir los originales.</p>
            <ul>
                <li>Las genera el agente con <code>ffmpeg</code> al escanear: en las carpetas nuevas y cuando cambian sus fotos o vídeos.</li>
                <li>Los fotogramas se toman del interior del vídeo, nunca del principio ni del final.</li>
                <li>Se guarda una copia en <code>.silo_proxies/</code> del Maestro: si la web las pierde, se vuelven a subir sin regenerarlas.</li>
            </ul>
        </article>

        <article class="ayuda-ficha" id="replica" data-claves="replica catalogo copia seguridad base de datos red de seguridad sql">
            <h3><i class="bi bi-database-check"></i> Réplica del catálogo <a class="ayuda-ancla" href="#replica">#</a></h3>
            <p class="ayuda-corto">Cada disco que toca el agente guarda una copia del catálogo del Silo (solo datos, sin fotos; pesa poco).</p>
            <p class="mb-0">Es una red de seguridad: con cualquier disco puedes reconstruir la web. Cómo: <a href="#como-restaurar">restaurar</a>.</p>
        </article>

        <article class="ayuda-ficha" id="correcciones" data-claves="tareas correccion renombrar_copia mover_copia reubicacion confirmar hecha cola">
            <h3><i class="bi bi-list-check"></i> Tareas y correcciones de copias <a class="ayuda-ancla" href="#correcciones">#</a></h3>
            <p class="ayuda-corto">Cuando renombras algo que ya estaba copiado en un USB, la web anota qué hay que cambiar en él.</p>
            <ul>
                <li><b>renombrar_copia</b>: misma carpeta del USB, otro nombre.</li>
                <li><b>mover_copia</b>: cambia de año o de categoría y quizá de USB.</li>
                <li>Las ves en <?= $pag('silo/tareas', 'Tareas') ?>. <code>silo --copiar</code> o <code>--renombrar</code> las aplican y las cierran. Si lo has hecho a mano, se confirma con el botón de la tarea (en Tareas o en la ficha de la pieza).</li>
                <li>Los escaneos pedidos con el botón también son tareas (<i>escaneo_maestro</i>).</li>
            </ul>
        </article>
    </div>
</section>

<!-- ───────────────────────── COMANDOS ───────────────────────── -->
<section class="ayuda-seccion" id="comandos">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">03</span>
        <h6 class="silo-seccion-titulo">COMANDOS</h6>
        <span class="silo-seccion-sub">CHULETA</span>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <article class="ayuda-ficha" id="cmd-agente" data-claves="comandos agente silo terminal powershell chuleta lista opciones flags">
            <h3><i class="bi bi-terminal"></i> Agente <code>silo</code> (en tu PC) <a class="ayuda-ancla" href="#cmd-agente">#</a></h3>
            <p class="ayuda-corto">Se ejecuta en PowerShell desde cualquier carpeta. Añade <code>--dry-run</code> para ensayar sin tocar nada.</p>
            <?= $cmd('silo', 'Escanea los Maestros de config.json.') ?>
            <?= $cmd('silo --dry-run', 'Ensayo: no toca nada.') ?>
            <?= $cmd('silo --solo-handshake', 'Comprueba la conexión y que se reconocen las unidades.') ?>
            <?= $cmd('silo --daemon', 'Espera órdenes de la web («Solicitar escaneo»). --intervalo N para el sondeo.') ?>
            <?= $cmd('silo --verificar', 'Hash de todo (lento): detecta corrupción.') ?>
            <?= $cmd('silo --copiar', 'Copia a los USB y espejos pendientes, pidiéndolos de uno en uno.') ?>
            <?= $cmd('silo --copiar 55 56', 'Solo esas unidades.') ?>
            <?= $cmd('silo --renombrar', 'Solo los cambios de nombre, sin copiar.') ?>
            <?= $cmd('silo --copiar --purgar', 'Además borra lo que ya no le toca (pide BORRAR).') ?>
            <?= $cmd('silo --restaurar-catalogo 55', 'Restaura la BD del Silo desde ese disco (pide RESTAURAR).') ?>
            <?= $cmd('silo --etiquetar-contenido', 'Propone (Fotos)/(Vídeos)/(Montajes). --aplicar para renombrar.') ?>
        </article>

        <article class="ayuda-ficha" id="cmd-spark" data-claves="comandos spark servidor php chuleta planificar propagar restaurar reclasificar simular">
            <h3><i class="bi bi-hdd-network"></i> Comandos del servidor (<code>php spark</code>) <a class="ayuda-ancla" href="#cmd-spark">#</a></h3>
            <p class="ayuda-corto">Se ejecutan en la carpeta de la web. Casi nunca hacen falta: la web y el agente ya lo hacen solos.</p>
            <?= $cmd('php spark silo:planificar-nivel2', 'Enseña el reparto por años. --aplicar = botón «Recalcular reparto».') ?>
            <?= $cmd('php spark silo:planificar-nivel3', 'Enseña el reparto por temática. --aplicar = botón «Recalcular reparto» (solo Nivel 3).') ?>
            <?= $cmd('php spark silo:propagar', 'Recalcula las copias 2 y 3 de todo el catálogo (tras una migración o un arreglo).') ?>
            <?= $cmd('php spark silo:restaurar <ruta>', 'Restaura desde un .catalogo.sql.gz. --si para no preguntar.') ?>
            <?= $cmd('php spark silo:reclasificar-ficheros', 'Vuelve a decidir foto/vídeo/otro por extensión. --dry-run para ensayar.') ?>
            <?= $cmd('php spark silo:simular-ingesta', 'Solo para pruebas: mete carpetas de ejemplo.') ?>
        </article>
    </div>
</section>

<!-- ───────────────────────── PÁGINAS ───────────────────────── -->
<section class="ayuda-seccion" id="paginas">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">04</span>
        <h6 class="silo-seccion-titulo">PÁGINAS DE LA WEB</h6>
        <span class="silo-seccion-sub">DÓNDE ESTÁ CADA COSA</span>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <article class="ayuda-ficha" id="paginas-lista" style="grid-column: 1 / -1;" data-claves="paginas web menu donde esta catalogo unidades mi pc avisos tareas ranking vocabulario datos faltan">
            <h3><i class="bi bi-signpost-split"></i> Mapa de la web <a class="ayuda-ancla" href="#paginas-lista">#</a></h3>
            <table class="table table-sm mb-0">
                <tr><td><i class="bi bi-grid"></i> <?= $pag('silo', 'Catálogo') ?></td><td>Todas las piezas, con filtros y miniaturas. Desde aquí entras en cada ficha.</td></tr>
                <tr><td><i class="bi bi-hdd-stack"></i> <?= $pag('silo/unidades', 'Unidades') ?></td><td>Tus discos y USB por nivel: alta, edición, lo que ocupan, <b>Recalcular reparto</b>, solicitar escaneo y lo pendiente.</td></tr>
                <tr><td><i class="bi bi-pc-display"></i> <?= $pag('silo/mi-pc', 'Mi PC') ?></td><td>Cada unidad como un disco del explorador, para reconocerla y entrar.</td></tr>
                <tr><td><i class="bi bi-bell"></i> <?= $pag('silo/avisos', 'Avisos') ?></td><td>Lo que dejó el último escaneo: carpetas saltadas, ID repetidos, errores, sobrantes, catálogo más nuevo en un disco…</td></tr>
                <tr><td><i class="bi bi-list-check"></i> <?= $pag('silo/tareas', 'Tareas') ?></td><td>Escaneos pedidos y correcciones de copias pendientes.</td></tr>
                <tr><td><i class="bi bi-clipboard-x"></i> <?= $pag('silo/datos-faltan', 'Datos que faltan') ?></td><td>Piezas sin temática, lugar o personas, o con el nombre mal formado.</td></tr>
                <tr><td><i class="bi bi-bar-chart-line"></i> <?= $pag('silo/ranking', 'Ranking') ?></td><td>Los ficheros que más ocupan.</td></tr>
                <tr><td><i class="bi bi-tags"></i> <?= $pag('silo/vocabulario', 'Vocabulario') ?></td><td>Categorías, temas, lugares y personas. Los que no tienen uso se borran a mano.</td></tr>
            </table>
        </article>
    </div>
</section>

<!-- ───────────────────────── PROBLEMAS ───────────────────────── -->
<section class="ayuda-seccion" id="problemas">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">05</span>
        <h6 class="silo-seccion-titulo">PROBLEMAS FRECUENTES</h6>
        <span class="silo-seccion-sub">SÍNTOMA → SOLUCIÓN</span>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <article class="ayuda-ficha" id="p-no-aparece" data-claves="carpeta no aparece no sale no se ingesta falta escaneo problema">
            <h3><i class="bi bi-question-circle"></i> Escaneo y la carpeta no aparece <a class="ayuda-ancla" href="#p-no-aparece">#</a></h3>
            <ul>
                <li>Mira <?= $pag('silo/avisos', 'Avisos') ?>: dirá por qué se ha saltado.</li>
                <li>Lo habitual: el nombre no sigue el <a href="#nombre-carpeta">formato</a> (falta el ID o la fecha), empieza por <code>_ . ~</code>, o está en la lista negra.</li>
                <li>Tiene que estar en la <b>raíz</b> del Maestro, no dentro de otra carpeta.</li>
            </ul>
        </article>

        <article class="ayuda-ficha" id="p-excede" data-claves="excede no cabe año grande usb pequeño exceso rojo problema">
            <h3><i class="bi bi-exclamation-triangle"></i> «Excede»: un año no cabe en ningún USB <a class="ayuda-ancla" href="#p-excede">#</a></h3>
            <p>Ese año, él solo, pesa más que tu USB más grande. Como los años no se parten, necesitas uno <b>más grande</b> para ese año: dalo de alta y <a href="#como-recalcular">recalcula</a>.</p>
        </article>

        <article class="ayuda-ficha" id="p-pendiente" data-claves="pendiente no baja sigue pendiente no se coloca problema">
            <h3><i class="bi bi-hourglass"></i> «Pendiente de almacenar» no baja <a class="ayuda-ancla" href="#p-pendiente">#</a></h3>
            <ul>
                <li>Pulsa <b>Recalcular reparto</b>: lo pendiente solo se recoloca al pulsarlo.</li>
                <li>Nivel 2: ¿el USB tiene <b>capacidad</b> puesta? Sin capacidad no entra en el reparto.</li>
                <li>Nivel 3: ¿hay un USB con <b>esa categoría</b> exacta y con sitio?</li>
            </ul>
        </article>

        <article class="ayuda-ficha" id="p-copia-error" data-claves="error copiar no casa base de datos fichero distinto maestro copia falla problema">
            <h3><i class="bi bi-x-octagon"></i> La copia da error: el fichero no coincide <a class="ayuda-ancla" href="#p-copia-error">#</a></h3>
            <p>Has cambiado algo en el Maestro después del último escaneo. Ejecuta <code>silo</code> y luego otra vez <code>silo --copiar</code>.</p>
        </article>

        <article class="ayuda-ficha" id="p-usb-desconocido" data-claves="usb no reconoce no lo encuentra disco desconocido identificar problema">
            <h3><i class="bi bi-usb-symbol"></i> <code>silo --copiar</code> no reconoce un USB <a class="ayuda-ancla" href="#p-usb-desconocido">#</a></h3>
            <ul>
                <li>Si es nuevo, es normal: elígelo de la lista de discos sin identificar y le escribirá su <code>.silo_unit.json</code>.</li>
                <li>Si lo has formateado o has borrado los ficheros ocultos, le pasa lo mismo: elígelo otra vez.</li>
            </ul>
        </article>

        <article class="ayuda-ficha" id="p-etiqueta" data-claves="etiqueta usb no coincide nombre años desactualizada problema">
            <h3><i class="bi bi-tag"></i> La etiqueta del USB no coincide con sus años <a class="ayuda-ancla" href="#p-etiqueta">#</a></h3>
            <p>El reparto nunca cambia las etiquetas que pones tú. Lo fiable son los años que enseña la tarjeta; cambia la etiqueta a mano si quieres.</p>
        </article>

        <article class="ayuda-ficha" id="p-id-duplicado" data-claves="id duplicado repetido mismo numero dos carpetas problema">
            <h3><i class="bi bi-files-alt"></i> Aviso de ID duplicado <a class="ayuda-ancla" href="#p-id-duplicado">#</a></h3>
            <p>Dos carpetas del Maestro empiezan por el mismo ID. Renombra una con el siguiente ID libre de su año y escanea.</p>
        </article>

        <article class="ayuda-ficha" id="p-catalogo-nuevo" data-claves="catalogo mas nuevo disco por delante aviso base de datos perdida revertida problema">
            <h3><i class="bi bi-database-exclamation"></i> «Catálogo más nuevo» en un disco <a class="ayuda-ancla" href="#p-catalogo-nuevo">#</a></h3>
            <p>Un disco tiene una réplica más reciente que la web: la base de datos se ha perdido o ha vuelto atrás. Revisa y, si toca, <a href="#como-restaurar">restaura</a> desde ese disco.</p>
        </article>

        <article class="ayuda-ficha" id="p-espejo-detras" data-claves="espejo por detras desactualizado no al dia problema">
            <h3><i class="bi bi-hourglass-split"></i> Espejo «por detrás del Maestro» <a class="ayuda-ancla" href="#p-espejo-detras">#</a></h3>
            <p>El Maestro ha cambiado desde la última copia. Conecta los dos y ejecuta <code>silo --copiar</code> (o <code>silo --copiar &lt;id del espejo&gt;</code>).</p>
        </article>
    </div>
</section>

<!-- ───────────────────────── GLOSARIO ───────────────────────── -->
<section class="ayuda-seccion" id="glosario">
    <div class="silo-seccion-header-row">
        <span class="silo-seccion-num">06</span>
        <h6 class="silo-seccion-titulo">GLOSARIO</h6>
        <div class="silo-seccion-linea"></div>
    </div>

    <div class="ayuda-rejilla">
        <?php
            $glosario = [
                ['Agente', 'El programa de tu PC (silo / agente.py). Es el único que toca discos.', 'agente py programa'],
                ['Maestro', 'Disco original (Nivel 1). Fuente de la verdad; el único que se escanea.', 'maestro nivel 1 original'],
                ['Espejo', 'Copia exacta de un Maestro, mismos nombres. Se rellena con silo --copiar.', 'espejo'],
                ['Pieza', 'Una carpeta del Maestro y su ficha en el catálogo.', 'pieza carpeta'],
                ['ID de negocio', 'AAnnnn (p. ej. 260015). Lo pones tú; une la carpeta con su ficha.', 'id negocio'],
                ['Cubo', 'Subcarpeta de un USB de copia: un año (2010/) o una categoría (viajes/).', 'cubo bucket agrupador'],
                ['Tramo', 'Años seguidos que guarda un USB de Nivel 2 (p. ej. 2009-2012).', 'tramo rango años'],
                ['Reparto', 'Decidir qué va a cada USB. Lo hace la web; «Recalcular reparto» lo rehace.', 'reparto propagacion'],
                ['Pendiente de almacenar', 'Lo que no cabe en ningún USB de ese nivel todavía.', 'pendiente'],
                ['Sobrante', 'Lo que hay en un USB y ya no le toca. Se borra con --purgar.', 'sobrante purgar'],
                ['Proxy', 'Miniatura de una foto o de un fotograma de vídeo.', 'proxy miniatura'],
                ['Manifiesto', 'Lista de ficheros de un disco (.silo_manifest.json) para detectar cambios rápido.', 'manifiesto manifest'],
                ['Réplica', 'Copia del catálogo (.catalogo.sql.gz) en la raíz de cada disco.', 'replica catalogo'],
                ['Handshake', 'El saludo inicial del agente a la web: qué discos hay y qué tareas tienen.', 'handshake saludo'],
            ];
        ?>
        <?php foreach ($glosario as [$termino, $definicion, $claves]): ?>
            <article class="ayuda-ficha" data-claves="glosario <?= esc($claves, 'attr') ?>">
                <h3><i class="bi bi-book"></i> <?= esc($termino) ?></h3>
                <p class="mb-0"><?= esc($definicion) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
</div>

<script>
    (function () {
        const input = document.getElementById('ayuda-q');
        const fichas = Array.from(document.querySelectorAll('.ayuda-ficha'));
        const secciones = Array.from(document.querySelectorAll('.ayuda-seccion'));
        const vacio = document.getElementById('ayuda-vacio');
        const contenedor = document.getElementById('ayuda-secciones');

        // Sin tildes ni mayúsculas: «vídeo» encuentra «video» y al revés.
        const normalizar = s => s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const textoDe = new Map(fichas.map(f => [f, normalizar(f.textContent + ' ' + (f.dataset.claves || ''))]));
        const tituloDe = new Map(fichas.map(f => [f, normalizar(f.querySelector('h3').textContent)]));
        const clavesDe = new Map(fichas.map(f => [f, normalizar(f.dataset.claves || '')]));
        const ordenOriginal = new Map(fichas.map((f, i) => [f, i]));

        // Relevancia: cuenta más que la palabra esté en el título que en las claves, y en las claves que en el texto.
        const puntos = (f, palabras) => palabras.reduce((s, p) =>
            s + (tituloDe.get(f).includes(p) ? 4 : 0) + (clavesDe.get(f).includes(p) ? 2 : 0) + 1, 0);

        function filtrar() {
            const palabras = normalizar(input.value.trim()).split(/\s+/).filter(Boolean);
            let visibles = 0;
            fichas.forEach(f => {
                const ok = palabras.every(p => textoDe.get(f).includes(p));
                f.classList.toggle('ayuda-oculta', !ok);
                if (ok) visibles++;
            });
            // Dentro de cada sección, lo más relevante arriba; sin búsqueda, el orden de siempre.
            document.querySelectorAll('.ayuda-rejilla').forEach(rejilla => {
                Array.from(rejilla.children)
                    .sort((a, b) => (palabras.length ? puntos(b, palabras) - puntos(a, palabras) : 0)
                        || ordenOriginal.get(a) - ordenOriginal.get(b))
                    .forEach(f => rejilla.appendChild(f));
            });
            const mejor = s => Math.max(0, ...Array.from(s.querySelectorAll('.ayuda-ficha:not(.ayuda-oculta)')).map(f => puntos(f, palabras)));
            secciones.slice()
                .sort((a, b) => (palabras.length ? mejor(b) - mejor(a) : 0) || secciones.indexOf(a) - secciones.indexOf(b))
                .forEach(s => contenedor.appendChild(s));
            secciones.forEach(s => s.classList.toggle('ayuda-oculta', !s.querySelector('.ayuda-ficha:not(.ayuda-oculta)')));
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

        // Pinchar un enlace interno con la búsqueda puesta la limpia, para que el destino se vea.
        document.querySelectorAll('a[href^="#"]').forEach(a => a.addEventListener('click', () => {
            if (input.value !== '') {
                input.value = '';
                filtrar();
            }
        }));

        // «/» enfoca el buscador; Esc lo vacía.
        document.addEventListener('keydown', e => {
            if (e.key === '/' && document.activeElement !== input) {
                e.preventDefault();
                input.focus();
            } else if (e.key === 'Escape' && document.activeElement === input) {
                input.value = '';
                filtrar();
            }
        });

        document.querySelectorAll('.ayuda-copiar').forEach(b => b.addEventListener('click', async () => {
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
