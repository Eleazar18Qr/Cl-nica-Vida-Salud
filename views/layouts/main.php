<?php

use App\Core\Session;

$user = Session::user();
$role = $user['rol'] ?? '';
$success = flash('success');
$error = flash('error');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Clínica Vida Salud') ?> | Clínica Vida Salud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('public/css/app.css') ?>">
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">+</span>
            <div>
                <strong>Vida Salud</strong>
                <small>Gestión de citas</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a class="nav-link <?= is_active('dashboard') ? 'active' : '' ?>" href="<?= url('dashboard') ?>">Panel</a>

            <?php if (has_role(['Administrador', 'Recepcionista', 'Medico'])): ?>
                <a class="nav-link <?= is_active('pacientes') ? 'active' : '' ?>" href="<?= url('pacientes') ?>">Pacientes</a>
            <?php endif; ?>

            <?php if (has_role(['Administrador', 'Recepcionista'])): ?>
                <a class="nav-link <?= is_active('medicos') ? 'active' : '' ?>" href="<?= url('medicos') ?>">Médicos</a>
            <?php endif; ?>

            <?php if (has_role(['Administrador'])): ?>
                <a class="nav-link <?= is_active('especialidades') ? 'active' : '' ?>" href="<?= url('especialidades') ?>">Especialidades</a>
            <?php endif; ?>

            <?php if (has_role(['Administrador', 'Recepcionista'])): ?>
                <a class="nav-link <?= is_active('horarios') ? 'active' : '' ?>" href="<?= url('horarios') ?>">Horarios</a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-mini">
                <div class="avatar"><?= e(strtoupper(substr($user['nombres'] ?? 'U', 0, 1))) ?></div>
                <div>
                    <strong><?= e(($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? '')) ?></strong>
                    <small><?= e($role) ?></small>
                </div>
            </div>
            <form action="<?= url('auth/logout') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-light btn-sm w-100">Cerrar sesión</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 class="h4 mb-0"><?= e($title ?? 'Panel') ?></h1>
                <small class="text-muted">Sistema de Gestión de Citas Médicas</small>
            </div>
            <span class="badge text-bg-primary"><?= e($role) ?></span>
        </header>

        <div class="content-area">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('public/js/app.js') ?>"></script>
</body>
</html>
