<!DOCTYPE html>
<html lang="es" data-base="<?= base_url() ?>">

<head>
    <meta charset="UTF-8">
    <?php // "LOCAL" delante solo en desarrollo: es para distinguir de un vistazo la
          // pestaña de la copia local de la de eluve.es, que se abren a la vez. En
          // producción el título va limpio. ?>
    <title><?= esc($title ?? (ENVIRONMENT === 'production' ? 'Trackbitos' : 'LOCAL Trackbitos')) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Style css -->
    <?php
        /**
         * El ?v= es la fecha del propio fichero, no un número a mano: el
         * Hostinger sirve los assets con Cache-Control de una semana, así que
         * sin esto el navegador se queda con la versión vieja durante días
         * después de subir un cambio de estilos — y la pantalla parece rota
         * sin que el código tenga nada malo (pasó con el botón de fotos de
         * piezas/placas). Al cambiar el fichero cambia la URL y se baja solo.
         */
        $cssRuta = FCPATH . 'assets/css/style.css';
        $cssVer  = is_file($cssRuta) ? filemtime($cssRuta) : null;
    ?>
    <link href="<?= base_url('assets/css/style.css') . ($cssVer ? '?v=' . $cssVer : '') ?>" rel="stylesheet">
    <?php
        $tbModo = logged_in() && in_groups('admin') ? \App\Libraries\MenuPrincipal::modo() : 'clasica';
        $tbVer  = @filemtime(FCPATH . 'assets/css/tb-menu.css');
    ?>
    <?php if ($tbModo !== 'clasica' || uri_string() === 'dashboard'): ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap">
        <link href="<?= base_url('assets/css/tb-menu.css') . ($tbVer ? '?v=' . $tbVer : '') ?>" rel="stylesheet">
    <?php endif; ?>
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#1a1d21">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/app/apple-touch-icon.png') ?>">
    <?php // Embebido y no en style.css a propósito, mismo motivo que el resto
          // de ajustes puntuales del proyecto: esta hoja se sirve con una
          // semana de caché. El gris que trae Bootstrap por defecto para los
          // placeholders en tema oscuro es casi tan visible como el texto ya
          // escrito, y se confunde con él de un vistazo. ?>
    <style>
        .form-control::placeholder {
            color: rgba(255, 255, 255, .35);
            opacity: 1;
        }
    </style>
</head>

<body data-bs-theme="dark" class="tb-modo-<?= $tbModo ?><?= $tbModo === 'c' && ($_COOKIE['tb_riel'] ?? '') === '1' ? ' tb-plegado' : '' ?>">
    <?php if ($tbModo !== 'clasica'): ?>
        <?= view('layouts/_tb_menu', ['modo' => $tbModo]) ?>
    <?php else: ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand d-flex align-items-center gap-1" href="<?= site_url('dashboard') ?>">
            <img src="<?= base_url('assets/images/logo-trackbitos-icon.png') ?>" alt="Trackbitos" class="img-fluid d-inline-block" style="height:13px;">
            <span style="font-size: .85rem;">Trackbitos</span>
        </a>

        <!-- Botón toggle para móviles -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Contenido del navbar -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto">
                <?php if (logged_in()): ?>
                    <?php if (in_groups('admin')): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">Estilo</a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach (\App\Libraries\MenuPrincipal::MODOS as $k => $n): ?>
                                    <li><button type="button" class="dropdown-item" data-tb-menu="<?= $k ?>"><i class="bi <?= $k === 'clasica' ? 'bi-check2' : 'bi-dot' ?>"></i> <?= esc($n) ?></button></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('cuenta/usuarios') ?>">Usuarios</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('cuenta') ?>">Mi cuenta</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('logout') ?>">Cerrar sesión</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('login') ?>">Login</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    <script>
    document.addEventListener('click', function (e) {
        var m = e.target.closest('[data-tb-menu]');
        if (!m) return;
        document.cookie = 'tb_menu=' + m.getAttribute('data-tb-menu') + '; path=/; max-age=31536000; samesite=lax';
        location.reload();
    });
    </script>
    <?php endif; ?>

    <!-- Contenido principal -->
    <div class="container">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Footer opcional -->

    <footer class="text-center mt-5 mb-3 text-muted no-print">
        <small>&copy; <?= date('Y') ?> Trackbitos</small>
    </footer>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/app-pwa.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/app-pwa.js') ?>"></script>
</body>

</html>