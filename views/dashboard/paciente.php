<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h2 class="h5">Bienvenido(a), <?= e($user['nombres'] . ' ' . $user['apellidos']) ?></h2>
                <?php if (isset($paciente)): ?>
                    <p class="text-muted mb-1">Documento: <strong><?= e($paciente['documento_tipo'] . ' ' . $paciente['documento_numero']) ?></strong></p>
                    <p class="text-muted mb-1">Teléfono: <strong><?= e($paciente['telefono'] ?: 'No registrado') ?></strong></p>
                    <p class="text-muted mb-0">Grupo sanguíneo: <strong><?= e($paciente['grupo_sanguineo'] ?: 'No registrado') ?></strong></p>
                <?php else: ?>
                    <p class="text-muted mb-0">Tu perfil de paciente aún no está configurado. Contacta a recepción.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h3 class="h6">Próximamente</h3>
                <p class="text-muted mb-0">Aquí podrás ver y gestionar tus citas médicas.</p>
            </div>
        </div>
    </div>
</div>
