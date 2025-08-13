<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>

<div class="container mt-5 d-flex justify-content-center align-items-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow">
            <h2 class="card-header"><?= lang('Auth.loginTitle') ?></h2>
            <div class="card-body">

                <?= view('App\Views\Auth\_message_block') ?>

                <form action="<?= url_to('login') ?>" method="post">
                    <?= csrf_field() ?>

                    <?php if ($config->validFields === ['email']): ?>
                        <div class="mb-3">
                            <label for="login" class="form-label"><?= lang('Auth.email') ?></label>
                            <input type="email" class="form-control <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>"
                                   name="login" placeholder="<?= lang('Auth.email') ?>">
                            <div class="invalid-feedback">
                                <?= session('errors.login') ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label for="login" class="form-label"><?= lang('Auth.emailOrUsername') ?></label>
                            <input type="text" class="form-control <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>"
                                   name="login" placeholder="<?= lang('Auth.emailOrUsername') ?>">
                            <div class="invalid-feedback">
                                <?= session('errors.login') ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="password" class="form-label"><?= lang('Auth.password') ?></label>
                        <input type="password" name="password" class="form-control <?php if (session('errors.password')) : ?>is-invalid<?php endif ?>"
                               placeholder="<?= lang('Auth.password') ?>">
                        <div class="invalid-feedback">
                            <?= session('errors.password') ?>
                        </div>
                    </div>

                    <?php if ($config->allowRemembering): ?>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember"
                                   <?php if (old('remember')) : ?> checked <?php endif ?>>
                            <label for="remember" class="form-check-label"><?= lang('Auth.rememberMe') ?></label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100"><?= lang('Auth.loginAction') ?></button>
                </form>

                <hr>

                <?php if ($config->allowRegistration) : ?>
                    <p class="mb-1"><a href="<?= url_to('register') ?>"><?= lang('Auth.needAnAccount') ?></a></p>
                <?php endif; ?>
                <?php if ($config->activeResetter): ?>
                    <p><a href="<?= url_to('forgot') ?>"><?= lang('Auth.forgotYourPassword') ?></a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
