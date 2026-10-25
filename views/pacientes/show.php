<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0"><?= e($paciente['apellidos'] . ', ' . $paciente['nombres']) ?></h2>
                <?= status_badge($paciente['estado']) ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Documento</dt>
                    <dd class="col-sm-8"><?= e($paciente['documento_tipo'] . ' ' . $paciente['documento_numero']) ?></dd>

                    <dt class="col-sm-4">Fecha de nacimiento</dt>
                    <dd class="col-sm-8"><?= e(format_date($paciente['fecha_nacimiento'])) ?></dd>

                    <dt class="col-sm-4">Género</dt>
                    <dd class="col-sm-8"><?= e($paciente['genero']) ?></dd>

                    <dt class="col-sm-4">Correo electrónico</dt>
                    <dd class="col-sm-8"><?= e($paciente['email'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8"><?= e($paciente['telefono'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Dirección</dt>
                    <dd class="col-sm-8"><?= e($paciente['direccion'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Grupo sanguíneo</dt>
                    <dd class="col-sm-8"><?= e($paciente['grupo_sanguineo'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Alergias</dt>
                    <dd class="col-sm-8"><?= e($paciente['alergias'] ?: 'Ninguna registrada') ?></dd>

                    <dt class="col-sm-4">Contacto de emergencia</dt>
                    <dd class="col-sm-8">
                        <?= e($paciente['contacto_emergencia_nombre'] ?: '—') ?>
                        <?php if ($paciente['contacto_emergencia_telefono']): ?>
                            — <?= e($paciente['contacto_emergencia_telefono']) ?>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h3 class="h6">Acciones</h3>
                <?php if (has_role(['Administrador', 'Recepcionista'])): ?>
                    <a href="<?= url('pacientes/edit/' . $paciente['id']) ?>" class="btn btn-primary w-100 mb-2">Editar paciente</a>
                <?php endif; ?>
                <a href="<?= url('pacientes') ?>" class="btn btn-outline-secondary w-100">Volver al listado</a>
            </div>
        </div>
    </div>
</div>
