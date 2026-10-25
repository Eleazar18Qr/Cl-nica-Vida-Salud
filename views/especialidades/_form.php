<?php
$especialidad = $especialidad ?? [];
$isEdit = isset($especialidad['id']);
$action = $isEdit ? url('especialidades/update/' . $especialidad['id']) : url('especialidades/store');
$value = static function (string $field, $default = '') use ($especialidad) {
    return old($field, $especialidad[$field] ?? $default);
};
?>
<form method="POST" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label" for="nombre">Nombre</label>
            <input type="text" class="form-control <?= error('nombre') ? 'is-invalid' : '' ?>" id="nombre" name="nombre" value="<?= e($value('nombre')) ?>" maxlength="100" required>
            <?php $err = error('nombre'); ?>
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
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea class="form-control <?= error('descripcion') ? 'is-invalid' : '' ?>" id="descripcion" name="descripcion" rows="3"><?= e($value('descripcion')) ?></textarea>
            <?php $err = error('descripcion'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar especialidad' : 'Registrar especialidad' ?></button>
        <a href="<?= url('especialidades') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
