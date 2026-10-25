<?php
$paciente = $paciente ?? [];
$isEdit = isset($paciente['id']);
$action = $isEdit ? url('pacientes/update/' . $paciente['id']) : url('pacientes/store');
$value = static function (string $field, $default = '') use ($paciente) {
    return old($field, $paciente[$field] ?? $default);
};
?>
<form method="POST" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
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
            <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
            <input type="date" class="form-control <?= error('fecha_nacimiento') ? 'is-invalid' : '' ?>" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= e($value('fecha_nacimiento')) ?>" required>
            <?php $err = error('fecha_nacimiento'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

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

        <div class="col-md-3">
            <label class="form-label" for="genero">Género</label>
            <select class="form-select <?= error('genero') ? 'is-invalid' : '' ?>" id="genero" name="genero" required>
                <option value="M" <?= $value('genero') === 'M' ? 'selected' : '' ?>>Masculino</option>
                <option value="F" <?= $value('genero') === 'F' ? 'selected' : '' ?>>Femenino</option>
                <option value="O" <?= $value('genero') === 'O' ? 'selected' : '' ?>>Otro</option>
            </select>
            <?php $err = error('genero'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="email">Correo electrónico</label>
            <input type="email" class="form-control <?= error('email') ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($value('email')) ?>" maxlength="150">
            <?php $err = error('email'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-5">
            <label class="form-label" for="telefono">Teléfono</label>
            <input type="text" class="form-control <?= error('telefono') ? 'is-invalid' : '' ?>" id="telefono" name="telefono" value="<?= e($value('telefono')) ?>" maxlength="20">
            <?php $err = error('telefono'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-12">
            <label class="form-label" for="direccion">Dirección</label>
            <input type="text" class="form-control <?= error('direccion') ? 'is-invalid' : '' ?>" id="direccion" name="direccion" value="<?= e($value('direccion')) ?>" maxlength="255">
            <?php $err = error('direccion'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-3">
            <label class="form-label" for="grupo_sanguineo">Grupo sanguíneo</label>
            <input type="text" class="form-control <?= error('grupo_sanguineo') ? 'is-invalid' : '' ?>" id="grupo_sanguineo" name="grupo_sanguineo" value="<?= e($value('grupo_sanguineo')) ?>" maxlength="5">
            <?php $err = error('grupo_sanguineo'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-5">
            <label class="form-label" for="contacto_emergencia_nombre">Contacto de emergencia</label>
            <input type="text" class="form-control <?= error('contacto_emergencia_nombre') ? 'is-invalid' : '' ?>" id="contacto_emergencia_nombre" name="contacto_emergencia_nombre" value="<?= e($value('contacto_emergencia_nombre')) ?>" maxlength="100">
            <?php $err = error('contacto_emergencia_nombre'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="contacto_emergencia_telefono">Teléfono de emergencia</label>
            <input type="text" class="form-control <?= error('contacto_emergencia_telefono') ? 'is-invalid' : '' ?>" id="contacto_emergencia_telefono" name="contacto_emergencia_telefono" value="<?= e($value('contacto_emergencia_telefono')) ?>" maxlength="20">
            <?php $err = error('contacto_emergencia_telefono'); ?>
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
            <label class="form-label" for="alergias">Alergias</label>
            <textarea class="form-control <?= error('alergias') ? 'is-invalid' : '' ?>" id="alergias" name="alergias" rows="2"><?= e($value('alergias')) ?></textarea>
            <?php $err = error('alergias'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar paciente' : 'Registrar paciente' ?></button>
        <a href="<?= url('pacientes') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
