<!DOCTYPE html>
<html lang="es">
<style>
    .small-icon {
        font-size: 0.7rem;
        /* más pequeño que fs-6 */
    }

    li a.nav-link {
        padding-bottom: 4px !important;
        padding-top: 4px !important;
    }
</style>

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
<<<<<<< HEAD
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
=======
>>>>>>> 8f2740303b8e451f4efb82ffff38852d26f8c6e1

<body data-bs-theme="dark">
    <!-- Navbar principal (idéntica a la tuya) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3">
        <a class="navbar-brand d-flex align-items-center gap-1" href="<?= site_url('dashboard') ?>">
            <img src="<?= base_url('assets/images/logo-trackbitos-icon.png') ?>" alt="Trackbitos" class="img-fluid d-inline-block" style="height:16px;">
            <span class="fs-6">Trackbitos</span>
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

    <!-- Subnavegación flotante -->
    <div class="bottom-nav bg-body border-top">
        <ul class="nav nav-pills w-100 d-flex m-0">
            <li class="nav-item flex-fill text-center">
                <a class="nav-link <?= (service('uri')->getSegment(2) === 'diario') ? 'active' : '' ?>" href="<?= site_url('comidas/diario/hoy') ?>">
                    <i class="bi bi-calendar2-check d-block"></i>
                    Diario
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link <?= (service('uri')->getSegment(2) === 'alimentos') ? 'active' : '' ?>" href="<?= site_url('comidas/alimentos') ?>">
                    <i class="bi bi-basket d-block"></i>
                    Alimentos
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link <?= (service('uri')->getSegment(2) === 'recetas') ? 'active' : '' ?>" href="<?= site_url('comidas/recetas') ?>">
                    <i class="bi bi-egg-fried d-block"></i>
                    Recetas
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link <?= (service('uri')->getSegment(2) === 'alimentos-control') ? 'active' : '' ?>" href="<?= site_url('comidas/alimentos-control') ?>">
                    <i class="bi bi-bullseye d-block"></i>
                    Limites
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link <?= (service('uri')->getSegment(2) === 'objetivos') ? 'active' : '' ?>" href="<?= site_url('comidas/objetivos') ?>">
                    <i class="bi bi-bullseye d-block"></i>
                    Objetivos
                </a>
            </li>
            <li class="nav-item flex-fill text-center">
                <a class="nav-link <?= (service('uri')->getSegment(2) === 'peso') ? 'active' : '' ?>" href="<?= site_url('comidas/peso') ?>">
                    <i class="bi bi-graph-down d-block"></i>
                    Peso
                </a>
            </li>
        </ul>
    </div>

    <style>
        /* Bottom nav flotante */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 1050;
            padding: .25rem 0;
        }

        .bottom-nav .nav-link {
            padding: .25rem 0;
            font-size: .75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .bottom-nav .nav-link i {
            font-size: 1.2rem;
        }

        body {
            padding-bottom: 56px;
            /* espacio para el nav flotante */
        }
    </style>



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