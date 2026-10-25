<?php
$horario = $horario ?? [];
$isEdit = isset($horario['id']);
$action = $isEdit ? url('horarios/update/' . $horario['id']) : url('horarios/store');
$value = static function (string $field, $default = '') use ($horario) {
    return old($field, $horario[$field] ?? $default);
};
?>
<form method="POST" action="<?= $action ?>" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="medico_id">Médico</label>
            <select class="form-select <?= error('medico_id') ? 'is-invalid' : '' ?>" id="medico_id" name="medico_id" required>
                <option value="">Seleccione un médico</option>
                <?php foreach ($medicos as $medico): ?>
                    <option value="<?= (int) $medico['id'] ?>" <?= (string) $value('medico_id') === (string) $medico['id'] ? 'selected' : '' ?>>
                        <?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?>
                        <?= isset($medico['estado']) && $medico['estado'] === 'Inactivo' ? ' (inactivo)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php $err = error('medico_id'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="dia_semana">Día de la semana</label>
            <select class="form-select <?= error('dia_semana') ? 'is-invalid' : '' ?>" id="dia_semana" name="dia_semana" required>
                <option value="">Seleccione un día</option>
                <?php for ($dia = 1; $dia <= 7; $dia++): ?>
                    <option value="<?= $dia ?>" <?= (string) $value('dia_semana') === (string) $dia ? 'selected' : '' ?>><?= e(day_name($dia)) ?></option>
                <?php endfor; ?>
            </select>
            <?php $err = error('dia_semana'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="hora_inicio">Hora de inicio</label>
            <input type="time" class="form-control <?= error('hora_inicio') ? 'is-invalid' : '' ?>" id="hora_inicio" name="hora_inicio" value="<?= e($value('hora_inicio')) ?>" required>
            <?php $err = error('hora_inicio'); ?>
            <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="hora_fin">Hora de fin</label>
            <input type="time" class="form-control <?= error('hora_fin') ? 'is-invalid' : '' ?>" id="hora_fin" name="hora_fin" value="<?= e($value('hora_fin')) ?>" required>
            <?php $err = error('hora_fin'); ?>
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
    </div>

    <div class="alert alert-info mt-4 mb-0">
        Los bloques de atención deben tener una duración coherente (por defecto 30 minutos) y no pueden superponerse con otro horario activo del mismo médico.
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Actualizar horario' : 'Registrar horario' ?></button>
        <a href="<?= url('horarios') ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>
