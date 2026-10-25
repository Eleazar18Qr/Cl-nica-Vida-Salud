<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?></h2>
                <?= status_badge($medico['estado']) ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Especialidad</dt>
                    <dd class="col-sm-8"><?= e($medico['especialidad']) ?></dd>

                    <dt class="col-sm-4">Colegiatura</dt>
                    <dd class="col-sm-8"><?= e($medico['numero_colegiatura']) ?></dd>

                    <dt class="col-sm-4">Documento</dt>
                    <dd class="col-sm-8"><?= e($medico['documento_tipo'] . ' ' . $medico['documento_numero']) ?></dd>

                    <dt class="col-sm-4">Correo electrónico</dt>
                    <dd class="col-sm-8"><?= e($medico['email']) ?></dd>

                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8"><?= e($medico['telefono'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Tarifa de consulta</dt>
                    <dd class="col-sm-8">S/ <?= number_format((float) $medico['tarifa_consulta'], 2) ?></dd>

                    <dt class="col-sm-4">Biografía</dt>
                    <dd class="col-sm-8"><?= e($medico['biografia'] ?: 'Sin información registrada') ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h3 class="h6">Acciones</h3>
                <?php if (has_role(['Administrador'])): ?>
                    <a href="<?= url('medicos/edit/' . $medico['id']) ?>" class="btn btn-primary w-100 mb-2">Editar médico</a>
                <?php endif; ?>
                <a href="<?= url('medicos') ?>" class="btn btn-outline-secondary w-100">Volver al listado</a>
            </div>
        </div>
    </div>
</div>
