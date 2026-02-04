<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>

<div class="container d-flex justify-content-center align-items-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <h2 class="card-header fs-5 py-2"><?= lang('Auth.loginTitle') ?></h2>
            <div class="card-body p-3">

                <?= view('App\Views\Auth\_message_block') ?>

                <form action="<?= url_to('login') ?>" method="post">
                    <?= csrf_field() ?>

                    <?php if ($config->validFields === ['email']): ?>
                        <div class="mb-2">
                            <label for="login" class="form-label fs-6"><?= lang('Auth.email') ?></label>
                            <input type="email" class="form-control form-control-sm <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>"
                                   name="login" placeholder="<?= lang('Auth.email') ?>">
                            <div class="invalid-feedback fs-7">
                                <?= session('errors.login') ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-2">
                           
                            <input type="text" class="form-control form-control-sm <?php if (session('errors.login')) : ?>is-invalid<?php endif ?>"
                                   name="login" placeholder="Usuario">
                            <div class="invalid-feedback fs-7">
                                <?= session('errors.login') ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-2">
                        
                        <input type="password" name="password" class="form-control form-control-sm <?php if (session('errors.password')) : ?>is-invalid<?php endif ?>"
                               placeholder="<?= lang('Auth.password') ?>">
                        <div class="invalid-feedback fs-7">
                            <?= session('errors.password') ?>
                        </div>
                    </div>

                    <?php if ($config->allowRemembering): ?>
                        <div class="form-check mb-2">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember"
                                   <?php if (old('remember')) : ?> checked <?php endif ?>>
                            <label for="remember" class="form-check-label fs-7"><?= lang('Auth.rememberMe') ?></label>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-sm mt-2 w-100"><?= lang('Auth.loginAction') ?></button>
                </form>

                <!--<hr class="my-2">

                <?php if ($config->allowRegistration) : ?>
                    <p class="mb-1 fs-7"><a href="<?= url_to('register') ?>"><?= lang('Auth.needAnAccount') ?></a></p>
                <?php endif; ?>
                <?php if ($config->activeResetter): ?>
                    <p class="fs-7"><a href="<?= url_to('forgot') ?>"><?= lang('Auth.forgotYourPassword') ?></a></p>
                <?php endif; ?>-->
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
