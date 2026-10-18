<?php
    // Login del TPV de inventario (solo en el subdominio, ver Config\Auth).
    // Página suelta con la estética del TPV; el formulario es el mismo de Myth.
    $errores = array_filter(array_merge(
        (array) session('errors'),
        [session('error')]
    ));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Inventario · Entrar</title>
    <script>
        try { if (localStorage.getItem('inv-tema') === 'dark') document.documentElement.dataset.theme = 'dark'; } catch (e) {}
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= base_url('assets/inventario/inventario.css') ?>?v=18" rel="stylesheet">
</head>
<body>
<main class="inv-login">
    <button type="button" class="inv-tema" data-tema aria-label="Cambiar entre modo claro y oscuro">
        <i class="bi bi-sun"></i>
        <i class="bi bi-moon-stars"></i>
    </button>

    <form class="inv-login-tarjeta" action="<?= url_to('login') ?>" method="post">
        <?= csrf_field() ?>

        <span class="inv-logo"><i class="bi bi-box-seam"></i></span>
        <h1>Inventario</h1>
        <p class="inv-subtitulo">Entra para consultar las piezas</p>

        <?php if (session()->has('message')): ?>
            <div class="inv-login-aviso ok"><?= esc(session('message')) ?></div>
        <?php endif; ?>

        <?php if ($errores): ?>
            <div class="inv-login-aviso">
                <?php foreach ($errores as $error): ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <label class="inv-buscador">
            <i class="bi bi-person"></i>
            <input type="<?= $config->validFields === ['email'] ? 'email' : 'text' ?>" name="login"
                   value="<?= esc(old('login') ?? '') ?>"
                   placeholder="<?= $config->validFields === ['email'] ? 'Email' : 'Usuario' ?>"
                   autocomplete="username" autocapitalize="none" required autofocus>
        </label>

        <label class="inv-buscador">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" placeholder="Contraseña"
                   autocomplete="current-password" required>
        </label>

        <?php if ($config->allowRemembering): ?>
            <label class="inv-login-recordar">
                <input type="checkbox" name="remember" <?= old('remember') ? 'checked' : '' ?>>
                <span>Recordarme en este dispositivo</span>
            </label>
        <?php endif; ?>

        <button type="submit" class="inv-login-entrar">
            Entrar <i class="bi bi-arrow-right"></i>
        </button>
    </form>
</main>

<script src="<?= base_url('assets/inventario/inventario.js') ?>?v=5"></script>
</body>
</html>
