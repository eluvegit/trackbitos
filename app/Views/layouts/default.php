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

<body class="bg-dark text-light">
    <?php $isPrint = (service('request')->getGet('print') === '1'); ?>
    <?php if (!$isPrint): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-2 py-0 mb-1">
            <a class="navbar-brand d-flex align-items-center gap-1" href="<?= site_url('dashboard') ?>">
                <img src="<?= base_url('assets/images/logo-trackbitos-icon.png') ?>" alt="Trackbitos" class="img-fluid d-inline-block" style="height:16px;">
                <span class="fs-6">Trackbitos</span>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <?php if (logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link py-0" href="<?= site_url('logout') ?>">Cerrar sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link py-0" href="<?= site_url('login') ?>">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>



    <?php endif; ?>

    <!-- Contenido principal -->
    <div class="container mt-3">
        <?= $this->renderSection('content') ?>
    </div>

    <!-- Footer opcional -->
    <?php if (!$isPrint): ?>
        <footer class="text-center mt-5 mb-3 text-muted">
            <small>&copy; <?= date('Y') ?> Trackbitos</small>
        </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>