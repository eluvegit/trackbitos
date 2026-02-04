<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>

<style>
/* ===== WELCOME PAGE ===== */
.welcome-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    /* Quitamos centrado vertical */
    padding: 20px;
}

.welcome-logo {
    max-height: 200px;   /* Limitamos el tamaño del logo */
    width: auto;
    animation: fadeIn 1s ease-in-out;
}

/* Titulos */
.welcome-wrapper h1 {
    font-size: 2rem;
    color: #fff;
    margin-top: 15px;
}

.welcome-wrapper h2 {
    font-size: 1.3rem;
    color: #aaa;
}

/* Botón */
.enter-btn {
    padding: 12px 28px;
    font-size: 1rem;
    border-radius: 8px;
}

/* Animación */
@keyframes fadeIn {
    0% {
        opacity: 0;
        transform: scale(0.95);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsive */
@media (max-width: 576px) {
    .welcome-logo {
        max-height: 150px;
    }

    .welcome-wrapper h1 {
        font-size: 1.5rem;
    }

    .welcome-wrapper h2 {
        font-size: 1rem;
    }

    .enter-btn {
        padding: 10px 20px;
        font-size: 0.95rem;
    }
}
</style>

<div class="welcome-wrapper text-center">
    <img src="<?= base_url('assets/images/logo-trackbitos.png') ?>" alt="Trackbitos" class="welcome-logo mb-4">
    <h1 class="mb-2">Bienvenido</h1>
    <h2 class="mb-4">Registra tus hábitos</h2>
    <a href="<?= site_url('dashboard') ?>" class="btn btn-primary enter-btn">Entrar al panel</a>
</div>

<?= $this->endSection() ?>
