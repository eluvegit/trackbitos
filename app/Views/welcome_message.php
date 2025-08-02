<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bienvenido a Trackbitos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .logo-container {
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            max-width: 100%;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: scale(0.95); }
            100% { opacity: 1; transform: scale(1); }
        }

        .logo-container img {
            max-height: 150px;
            width: auto;
        }

        .logo-container h1 {
            font-size: 2rem;
            margin-top: 20px;
        }

        .logo-container h2 {
            font-size: 1.3rem;
            color: #666;
            margin-bottom: 30px;
        }

        .enter-btn {
            padding: 12px 24px;
            font-size: 1rem;
            border-radius: 8px;
        }

        @media (max-width: 576px) {
            .logo-container img {
                max-height: 100px;
            }

            .logo-container h1 {
                font-size: 1.5rem;
            }

            .logo-container h2 {
                font-size: 1rem;
            }

            .enter-btn {
                font-size: 0.95rem;
                padding: 10px 20px;
            }
        }
    </style>
</head>
<body>

    <div class="logo-container">
        <img src="<?= base_url('assets/images/logo-trackbitos.png') ?>" alt="Trackbitos" class="img-fluid">
        <h1 class="mt-4">Bienvenido</h1>
        <h2 class="mb-4">Registra tus hábitos</h2>
        <a href="<?= site_url('dashboard') ?>" class="btn btn-primary enter-btn">Entrar al panel</a>
    </div>

</body>
</html>
