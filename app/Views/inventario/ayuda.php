<?= $this->extend('layouts/inventario') ?>

<?= $this->section('titulo') ?>Ayuda<?= $this->endSection() ?>

<?= $this->section('contenido') ?>
<section class="ay-portada">
    <span class="ay-portada-icono"><i class="bi bi-life-preserver"></i></span>
    <div>
        <h2>¿Cómo funciona el inventario?</h2>
        <p>Aquí puedes consultar <strong><?= (int) $total ?> piezas</strong>: cuántas quedan y en qué estuche y hueco están guardadas. Solo sirve para mirar: no se puede cambiar ni romper nada, así que toca lo que quieras.</p>
    </div>
</section>

<nav class="ay-indice">
    <a href="#inicio"><i class="bi bi-house"></i> Inicio</a>
    <a href="#buscar"><i class="bi bi-search"></i> Buscar</a>
    <a href="#categorias"><i class="bi bi-grid"></i> Categorías</a>
    <a href="#estuches"><i class="bi bi-archive"></i> Estuches</a>
    <a href="#ficha"><i class="bi bi-card-text"></i> Leer una pieza</a>
    <a href="#guias"><i class="bi bi-signpost-split"></i> Guías</a>
    <a href="#trucos"><i class="bi bi-stars"></i> Trucos</a>
    <a href="#preguntas"><i class="bi bi-chat-dots"></i> Preguntas</a>
</nav>

<section class="ay-bloque" id="inicio">
    <header><span class="ay-num">1</span><h2>La pantalla de inicio</h2></header>
    <p>Al pulsar el <strong>círculo rojo</strong> de arriba del menú vuelves siempre al inicio. Desde ahí tienes tres caminos:</p>
    <div class="ay-tarjetas">
        <a href="<?= inventario_url('buscar') ?>" class="ay-tarjeta">
            <i class="bi bi-search"></i>
            <strong>Buscar</strong>
            <span>Si sabes cómo se llama la pieza.</span>
        </a>
        <a href="<?= inventario_url('categorias') ?>" class="ay-tarjeta">
            <i class="bi bi-grid"></i>
            <strong>Categorías</strong>
            <span>Si sabes de qué tipo es, pero no su nombre.</span>
        </a>
        <a href="<?= inventario_url('estuches') ?>" class="ay-tarjeta">
            <i class="bi bi-archive"></i>
            <strong>Estuches</strong>
            <span>Si quieres ver qué hay guardado en un estuche.</span>
        </a>
    </div>
    <p class="ay-nota"><i class="bi bi-lightbulb"></i> Las pastillas de categorías del inicio te llevan directamente a la búsqueda con esa categoría ya elegida.</p>
</section>

<section class="ay-bloque" id="buscar">
    <header><span class="ay-num">2</span><h2>Buscar una pieza</h2></header>
    <ol class="ay-pasos">
        <li><strong>Escribe</strong> parte del nombre en la caja de arriba. No hace falta escribirlo entero ni pulsar Intro: la lista se filtra mientras escribes.</li>
        <li><strong>Filtra</strong> por categoría tocando una de las pastillas. <span class="inv-chip activo ay-chip">Todas</span> vuelve a mostrar todas.</li>
        <li>Debajo verás <strong>cuántas piezas</strong> coinciden. Para empezar de nuevo, borra el texto con la <i class="bi bi-x-circle-fill"></i>.</li>
    </ol>
    <p class="ay-nota"><i class="bi bi-lightbulb"></i> Puedes combinar las dos cosas: elige una categoría y luego escribe para afinar dentro de ella.</p>
</section>

<section class="ay-bloque" id="categorias">
    <header><span class="ay-num">3</span><h2>Navegar por categorías</h2></header>
    <p>Cada tarjeta es un tipo de pieza, con una foto de ejemplo y cuántas piezas tiene. Al tocarla se abre la búsqueda mostrando solo esa categoría.</p>
</section>

<section class="ay-bloque" id="estuches">
    <header><span class="ay-num">4</span><h2>Estuches y huecos</h2></header>
    <p>Las piezas se guardan en <strong>estuches</strong>, y cada estuche está dividido en <strong>huecos</strong>. Cada pieza está en un solo hueco.</p>
    <ol class="ay-pasos">
        <li>En <strong>Estuches</strong> ves todos, con su código en grande, la zona donde están y cuántos huecos y piezas tienen.</li>
        <li>Toca uno para ver sus huecos uno por uno, con las piezas que hay en cada uno. Los huecos vacíos salen más apagados.</li>
        <li>Toca la foto pequeña de cualquier pieza para verla en grande.</li>
    </ol>
</section>

<section class="ay-bloque" id="ficha">
    <header><span class="ay-num">5</span><h2>Cómo leer una pieza</h2></header>
    <div class="ay-ficha">
        <div class="ay-ficha-ejemplo">
            <span class="ay-ficha-foto"><i class="bi bi-image"></i><span class="inv-lupa"><i class="bi bi-arrows-fullscreen"></i></span></span>
            <span class="inv-codigo">A1H3</span>
            <h3>Nombre de la pieza</h3>
            <span class="inv-stock"><strong>12</strong> uds.</span>
        </div>
        <dl class="ay-leyenda">
            <div>
                <dt><span class="inv-codigo">A1H3</span></dt>
                <dd><strong>Dónde está.</strong> Primero el estuche y luego el hueco. Búscalo en la etiqueta del estuche.</dd>
            </div>
            <div>
                <dt><span class="inv-codigo sin">Sin ubicación</span></dt>
                <dd>Todavía no tiene hueco asignado.</dd>
            </div>
            <div>
                <dt><span class="inv-stock"><strong>12</strong> uds.</span></dt>
                <dd><strong>Cuántas quedan.</strong> En verde si hay; en gris si está a cero.</dd>
            </div>
            <div>
                <dt><span class="ay-icono-lupa"><i class="bi bi-arrows-fullscreen"></i></span></dt>
                <dd><strong>Ver en grande.</strong> Toca la foto para ampliarla. Se cierra con la <i class="bi bi-x-lg"></i>, tocando fuera o con la tecla Esc.</dd>
            </div>
        </dl>
    </div>
</section>

<section class="ay-bloque" id="guias">
    <header><span class="ay-num">6</span><h2>Guías paso a paso</h2></header>
    <p>¿No sabes qué pieza necesitas? Las guías te hacen unas preguntas sencillas y, según lo que contestes, te dicen cuál es y dónde está.</p>
    <ol class="ay-pasos">
        <li>Abre una guía desde el menú lateral.</li>
        <li>En cada paso, toca la opción que corresponda. Arriba verás <em>"Paso 1 de 3"</em> para saber cuánto falta.</li>
        <li>Lo que vas eligiendo aparece arriba como un camino. Toca cualquier paso para <strong>cambiar esa respuesta</strong>, o <span class="inv-chip ay-chip"><i class="bi bi-arrow-counterclockwise"></i> Empezar</span> para volver al principio.</li>
        <li>Al terminar verás <strong>"Tu pieza es"</strong> con su foto, su ubicación y cuántas quedan.</li>
    </ol>
    <p class="ay-nota"><i class="bi bi-lightbulb"></i> Si un paso sale con borde de rayas es porque solo había una opción posible, así que se ha elegido sola.</p>
    <?php if ($guias !== []): ?>
        <div class="ay-guias">
            <?php foreach ($guias as $g): ?>
                <a href="<?= inventario_url('guia/' . (int) $g['id']) ?>" class="inv-chip"><?= icono_guia($g['icono']) ?> <?= esc($g['nombre']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="ay-bloque" id="trucos">
    <header><span class="ay-num">7</span><h2>Trucos</h2></header>
    <div class="ay-tarjetas">
        <div class="ay-tarjeta">
            <i class="bi bi-moon-stars"></i>
            <strong>Modo oscuro</strong>
            <span>El interruptor de arriba a la derecha cambia entre claro y oscuro. Se queda guardado en este aparato.</span>
        </div>
        <div class="ay-tarjeta">
            <i class="bi bi-phone"></i>
            <strong>En el móvil</strong>
            <span>El menú pasa a la parte de abajo. Si hay muchas guías, desliza la barra hacia los lados.</span>
        </div>
        <div class="ay-tarjeta">
            <i class="bi bi-bookmark-star"></i>
            <strong>Guárdalo</strong>
            <span>Añade esta web a la pantalla de inicio del móvil o la tablet para abrirla como una app.</span>
        </div>
    </div>
</section>

<section class="ay-bloque" id="preguntas">
    <header><span class="ay-num">8</span><h2>Preguntas frecuentes</h2></header>
    <div class="ay-faq">
        <details>
            <summary>¿Necesito usuario o contraseña?</summary>
            <p>No. El inventario se puede ver sin iniciar sesión.</p>
        </details>
        <details>
            <summary>¿Puedo cambiar el stock o mover piezas desde aquí?</summary>
            <p>No, esta sección es solo de consulta. Si ves algún dato que no cuadra con lo que hay en el estuche, avisa al responsable para que lo corrija.</p>
        </details>
        <details>
            <summary>Busco una pieza y no aparece</summary>
            <p>Prueba a escribir menos letras o una sola palabra, y mira que no tengas una categoría elegida (pulsa <em>Todas</em>). También puedes buscarla con una guía o en su categoría.</p>
        </details>
        <details>
            <summary>Una pieza pone "0 uds."</summary>
            <p>Está registrada, pero ahora mismo no queda ninguna.</p>
        </details>
        <details>
            <summary>Una pieza pone "Sin ubicación"</summary>
            <p>Existe en el inventario, pero todavía no se le ha asignado un hueco en ningún estuche.</p>
        </details>
        <details>
            <summary>Una foto no se puede ampliar</summary>
            <p>Esa pieza todavía no tiene foto: en su lugar se ve un icono gris.</p>
        </details>
    </div>
</section>
<?= $this->endSection() ?>
