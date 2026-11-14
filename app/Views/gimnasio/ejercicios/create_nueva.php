<?= $this->extend('layouts/default') ?>
<?= $this->section('content') ?>
<?= view('gimnasio/ejercicios/_form_nueva', ['ejercicio' => $ejercicio ?? null, 'grupo' => $grupo ?? '']) ?>
<?= $this->endSection() ?>
