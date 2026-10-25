<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5">Bienvenido, Dr(a). <?= e($user['nombres'] . ' ' . $user['apellidos']) ?></h2>
                <?php if (isset($medico)): ?>
                    <p class="text-muted mb-1">Especialidad: <strong><?= e($medico['especialidad']) ?></strong></p>
                    <p class="text-muted mb-1">Colegiatura: <strong><?= e($medico['numero_colegiatura']) ?></strong></p>
                    <p class="text-muted mb-0">Bloques de horario activos: <strong><?= (int) ($stats['horarios'] ?? 0) ?></strong></p>
                <?php else: ?>
                    <p class="text-muted mb-0">Tu perfil de médico aún no está configurado. Contacta al administrador.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h3 class="h6">Accesos rápidos</h3>
                <a class="btn btn-outline-primary w-100 mb-2" href="<?= url('pacientes') ?>">Consultar pacientes</a>
            </div>
        </div>
    </div>
</div>
