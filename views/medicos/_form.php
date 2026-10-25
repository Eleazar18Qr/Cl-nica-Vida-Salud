<?php
$medico = $medico ?? [];
$isEdit = isset($medico['id']);
$action = $isEdit ? url('medicos/update/' . $medico['id']) : url('medicos/store');
$value = static function (string $field, $default = '') use ($medico) {
    return old($field, $medico[$field] ?? $default);
};
?>
<form method="POST" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <h3 class="h6 text-uppercase text-muted mb-3">Datos de usuario</h3>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="nombres">Nombres</label>
            <input type="text" class="form-control <?= error('nombres') ? 'is-invalid' : '' ?>" id="nombres" name="nombres" value="<?= e($value('nombres')) ?>" maxlength="100" required>
            <?php $err = error('nombres'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="apellidos">Apellidos</label>
            <input type="text" class="form-control <?= error('apellidos') ? 'is-invalid' : '' ?>" id="apellidos" name="apellidos" value="<?= e($value('apellidos')) ?>" maxlength="100" required>
            <?php $err = error('apellidos'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="email">Correo electrónico</label>
            <input type="email" class="form-control <?= error('email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($value('email')) ?>" maxlength="150" required>
            <?php $err = error('email'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="password">Contraseña <?= $isEdit ? '(opcional)' : '' ?></label>
            <input type="password" class="form-control <?= error('password') ? 'is-invalid' : '' ?>" id="password" name="password" <?= $isEdit ? '' : 'required' ?>>
            <?php if ($isEdit): ?><div class="form-text">Dejar en blanco para mantener la contraseña actual.</div><?php endif; ?>
            <?php $err = error('password'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="documento_tipo">Tipo de documento</label>
            <select class="form-select <?= error('documento_tipo') ? 'is-invalid' : '' ?>" id="documento_tipo" name="documento_tipo" required>
                <?php foreach (['DNI', 'CE', 'PASAPORTE'] as $tipo): ?>
                    <option value="<?= $tipo ?>" <?= $value('documento_tipo') === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                <?php endforeach; ?>
            </select>
            <?php $err = error('documento_tipo'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="documento_numero">Número de documento</label>
            <input type="text" class="form-control <?= error('documento_numero') ? 'is-invalid' : '' ?>" id="documento_numero" name="documento_numero" value="<?= e($value('documento_numero')) ?>" maxlength="20" required>
            <?php $err = error('documento_numero'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="telefono">Teléfono</label>
            <input type="text" class="form-control <?= error('telefono') ? 'is-invalid' : '' ?>" id="telefono" name="telefono" value="<?= e($value('telefono')) ?>" maxlength="20">
            <?php $err = error('telefono'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>
    </div>

    <h3 class="h6 text-uppercase text-muted mt-4 mb-3">Datos profesionales</h3>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="especialidad_id">Especialidad</label>
            <select class="form-select <?= error('especialidad_id') ? 'is-invalid' : '' ?>" id="especialidad_id" name="especialidad_id" required>
                <option value="">Seleccione una especialidad</option>
                <?php foreach ($especialidades as $especialidad): ?>
                    <option value="<?= (int) $especialidad['id'] ?>" <?= (string) $value('especialidad_id') === (string) $especialidad['id'] ? 'selected' : '' ?>>
                        <?= e($especialidad['nombre']) ?><?= isset($especialidad['estado']) && $especialidad['estado'] === 'Inactivo' ? ' (inactiva)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php $err = error('especialidad_id'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="numero_colegiatura">Número de colegiatura</label>
            <input type="text" class="form-control <?= error('numero_colegiatura') ? 'is-invalid' : '' ?>" id="numero_colegiatura" name="numero_colegiatura" value="<?= e($value('numero_colegiatura')) ?>" maxlength="50" required>
            <?php $err = error('numero_colegiatura'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="tarifa_consulta">Tarifa de consulta (S/)</label>
            <input type="number" step="0.01" min="0" class="form-control <?= error('tarifa_consulta') ? 'is-invalid' : '' ?>" id="tarifa_consulta" name="tarifa_consulta" value="<?= e($value('tarifa_consulta', '0.00')) ?>" required>
            <?php $err = error('tarifa_consulta'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select <?= error('estado') ? 'is-invalid' : '' ?>" id="estado" name="estado">
                <option value="Activo" <?= $value('estado', 'Activo') === 'Activo' ? 'selected' : '' ?>>Activo</option>
                <option value="Inactivo" <?= $value('estado', 'Activo') === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
            <?php $err = error('estado'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-12">
            <label class="form-label" for="biografia">Biografía</label>
            <textarea class="form-control <?= error('biografia') ? 'is-invalid' : '' ?>" id="biografia" name="biografia" rows="3"><?= e($value('biografia')) ?></textarea>
            <?php $err = error('biografia'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar médico' : 'Registrar médico' ?></button>
        <a href="<?= url('medicos') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
