<div class="text-center mb-4">
    <div class="login-logo">+</div>
    <h1 class="h3 mt-2">Clínica Vida Salud</h1>
    <p class="text-muted mb-0">Sistema de Gestión de Citas Médicas</p>
</div>

<?php if (isset($_GET['logged_out'])): ?>
    <div class="alert alert-success">Has cerrado sesión correctamente.</div>
<?php endif; ?>

<form method="POST" action="<?= url('auth/login') ?>" novalidate>
    <?= csrf_field() ?>

    <div class="mb-3">
        <label for="email" class="form-label">Correo electrónico</label>
        <input type="email" class="form-control <?= error('email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        <?php $emailError = error('email'); ?>
        <?php if ($emailError !== ''): ?>
            <div class="invalid-feedback"><?= e($emailError) ?></div>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Contraseña</label>
        <input type="password" class="form-control <?= error('password') ? 'is-invalid' : '' ?>" id="password" name="password" required>
        <?php $passwordError = error('password'); ?>
        <?php if ($passwordError !== ''): ?>
            <div class="invalid-feedback"><?= e($passwordError) ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
</form>

<div class="mt-4 small text-muted">
    <p class="mb-1"><strong>Credenciales de prueba:</strong></p>
    <p class="mb-0">admin@clinicavidasalud.com / Admin123!</p>
</div>
