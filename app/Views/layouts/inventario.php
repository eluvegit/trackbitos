<?php
    $seccion = $seccion ?? '';
    $menu = [
        'buscar'     => ['Buscar', 'bi-search', inventario_url('buscar')],
        'categorias' => ['Categorías', 'bi-grid', inventario_url('categorias')],
        'estuches'   => ['Estuches', 'bi-archive', inventario_url('estuches')],
    ];
    $usuario = user();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= esc($titulo ?? 'Inventario') ?></title>
    <script>
        try { if (localStorage.getItem('inv-tema') === 'dark') document.documentElement.dataset.theme = 'dark'; } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/inventario/inventario.css') ?>?v=19" rel="stylesheet">
</head>
<body>
<div class="inv-app">
    <aside class="inv-lateral">
        <a href="<?= inventario_url() ?>" class="inv-logo" title="Inicio">
            <i class="bi bi-box-seam"></i>
        </a>
        <nav class="inv-menu">
            <?php foreach ($menu as $clave => [$texto, $icono, $url]): ?>
                <a href="<?= $url ?>" class="<?= $seccion === $clave ? 'activo' : '' ?>">
                    <i class="bi <?= $icono ?>"></i>
                    <span><?= $texto ?></span>
                </a>
            <?php endforeach; ?>
            <?php foreach ($guias ?? [] as $g): ?>
                <a href="<?= inventario_url('guia/' . (int) $g['id']) ?>" class="<?= $seccion === 'guia-' . $g['id'] ? 'activo' : '' ?>">
                    <?= icono_guia($g['icono']) ?>
                    <span><?= esc($g['nombre']) ?></span>
                </a>
            <?php endforeach; ?>
            <a href="<?= inventario_url('ayuda') ?>" class="<?= $seccion === 'ayuda' ? 'activo' : '' ?>">
                <i class="bi bi-question-circle"></i>
                <span>Ayuda</span>
            </a>
        </nav>
        <?php if ($usuario): ?>
            <a href="<?= site_url('logout') ?>" class="inv-salir" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
                <span>Salir</span>
            </a>
        <?php endif; ?>
    </aside>

    <main class="inv-principal">
        <header class="inv-cabecera">
            <h1><?= $this->renderSection('titulo') ?></h1>
            <?= $this->renderSection('cabecera') ?>
            <div class="inv-cabecera-derecha">
                <button type="button" class="inv-tema" data-tema aria-label="Cambiar entre modo claro y oscuro">
                    <i class="bi bi-sun"></i>
                    <i class="bi bi-moon-stars"></i>
                </button>
                <?php if ($usuario): ?>
                    <div class="inv-usuario">
                        <span class="inv-avatar"><?= esc(mb_strtoupper(mb_substr($usuario->username, 0, 1))) ?></span>
                        <span class="inv-usuario-nombre"><?= esc($usuario->username) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <?= $this->renderSection('contenido') ?>
    </main>
</div>

<div class="inv-visor" id="inv-visor" hidden>
    <button type="button" class="inv-visor-cerrar" data-visor-cerrar aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
    <figure>
        <img src="" alt="" data-visor-img>
        <figcaption>
            <span class="inv-codigo grande" data-visor-codigo></span>
            <strong data-visor-nombre></strong>
            <span data-visor-stock></span>
        </figcaption>
    </figure>
</div>

<script src="<?= base_url('assets/inventario/inventario.js') ?>?v=5"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
