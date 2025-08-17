<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'Trackbitos · Comidas') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN (alineado con tu layout principal) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Style css (opcional si usas el mismo) -->
    <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?= base_url('favicon.ico') ?>">
</head>
<body class="bg-light">

    <!-- Navbar principal (idéntica a la tuya) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 mb-3">
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

    <!-- Subnavegación del módulo Comidas -->
    <div class="container mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-body py-2">
                <ul class="nav nav-pills gap-2">
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'diario') ? 'active' : '' ?>" href="<?= site_url('comidas/diario/hoy') ?>">
                            <i class="bi bi-calendar2-check"></i> Diario
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'alimentos') ? 'active' : '' ?>" href="<?= site_url('comidas/alimentos') ?>">
                            <i class="bi bi-basket"></i> Alimentos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'recetas') ? 'active' : '' ?>" href="<?= site_url('comidas/recetas') ?>">
                            <i class="bi bi-egg-fried"></i> Recetas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'objetivos') ? 'active' : '' ?>" href="<?= site_url('comidas/objetivos') ?>">
                            <i class="bi bi-bullseye"></i> Objetivos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="container">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Footer -->
    <footer class="text-center mt-5 mb-3 text-muted">
        <small>&copy; <?= date('Y') ?> Trackbitos</small>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
