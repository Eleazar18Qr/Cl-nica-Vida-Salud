<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Clínica Vida Salud') ?> | Clínica Vida Salud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('public/css/app.css') ?>">
</head>
<body class="auth-body">
    <main class="auth-container">
        <?php $success = flash('success'); ?>
        <?php $error = flash('error'); ?>

        <?php if ($success !== null): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="auth-card">
            <?= $content ?>
        </div>

        <p class="auth-footer">Clínica Vida Salud &copy; <?= date('Y') ?></p>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
