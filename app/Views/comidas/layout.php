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
    <!-- Font Awesome desde CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

</head>
<style>
    .card,
    .card-header,
    .card-body,
    .list-group-item {
        background-color: #1e1e1e !important;
        /* Fondo oscuro */
        color: #e0e0e0 !important;
        /* Texto claro */
        border-color: #333 !important;
        /* Bordes menos brillantes */
    }

    .btn,
    .btn-outline-primary,
    .btn-primary {
        color: #e0e0e0 !important;
    }

    a,
    a.text-dark {
        color: #e0e0e0 !important;
    }
    .text-muted {
    color: #aaaaaa !important; /* gris más claro para fondo oscuro */
}
/* Tablas modo oscuro */
table {
    background-color: #1e1e1e; /* fondo de la tabla */
    color: #e0e0e0; /* texto */
    border-color: #333333; /* bordes */
}

table thead {
    background-color: #2c2c2c; /* fondo del encabezado */
    color: #ffffff; /* texto del encabezado */
}

table th, table td {
    border-color: #333333; /* bordes de celdas */
}

table tbody tr:nth-child(even) {
    background-color: #1a1a1a; /* filas alternas */
}

.table-hover tbody tr:hover {
    background-color: #333333; /* hover filas */
}

</style>
<body class="bg-dark">

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
                            <i class="bi bi-calendar2-check"></i> <!--Diario-->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'alimentos') ? 'active' : '' ?>" href="<?= site_url('comidas/alimentos') ?>">
                            <i class="bi bi-basket"></i> <!--Alimentos-->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'recetas') ? 'active' : '' ?>" href="<?= site_url('comidas/recetas') ?>">
                            <i class="bi bi-egg-fried"></i> <!--Recetas-->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'objetivos') ? 'active' : '' ?>" href="<?= site_url('comidas/objetivos') ?>">
                            <i class="bi bi-bullseye"></i> <!--Objetivos-->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (service('uri')->getSegment(2) === 'peso') ? 'active' : '' ?>" href="<?= site_url('comidas/peso') ?>">
                            <i class="bi bi-graph-down"></i> <!--Peso-->
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
    <?= $this->renderSection('scripts') ?>
</body>
</html>
