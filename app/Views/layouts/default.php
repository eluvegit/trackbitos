<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Trackbitos') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Style css -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
</head>
<body class="bg-light">

   <!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 mb-4">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= site_url('dashboard') ?>">
        <img src="<?= base_url('assets/images/logo-trackbitos-icon.png') ?>" alt="Trackbitos" class="logo-navbar">
        <span>Trackbitos</span>
    </a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto">
            <?php if (logged_in()): ?>
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


    <!-- Contenido principal -->
    <div class="container">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Footer opcional -->
    <footer class="text-center mt-5 mb-3 text-muted">
        <small>&copy; <?= date('Y') ?> Trackbitos</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
