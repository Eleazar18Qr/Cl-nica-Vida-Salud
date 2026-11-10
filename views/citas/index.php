<?php
/**
 * Listado de citas.
 *
 * Variables: $citas, $search, $estado, $medicoId, $fecha, $medicos, $estados,
 *            $puedeGestionar, $puedeMarcarAtendida, $medicoActual, $pacienteActual.
 */

$accionEstado = static function (
    array $cita,
    string $nuevoEstado,
    string $label,
    string $clase,
    string $confirmacion
): void {
    ?>
    <form action="<?= url('citas/cambiar-estado/' . $cita['id']) ?>"
          method="POST"
          class="d-inline"
          onsubmit="return confirm('<?= e($confirmacion) ?>');">
        <?= csrf_field() ?>
        <input type="hidden" name="estado" value="<?= e($nuevoEstado) ?>">
        <button type="submit" class="btn btn-sm <?= e($clase) ?>"><?= e($label) ?></button>
    </form>
    <?php
};

$accionesPorEstado = [
    'Pendiente' => [
        ['Confirmada', 'Confirmar', 'btn-outline-primary', '¿Confirmar esta cita?'],
        ['Cancelada', 'Cancelar', 'btn-outline-danger', '¿Cancelar esta cita?'],
    ],
    'Confirmada' => [
        ['Atendida', 'Atendida', 'btn-outline-success', '¿Marcar esta cita como atendida?'],
        ['No_Asistio', 'No asistió', 'btn-outline-secondary', '¿Registrar la inasistencia del paciente?'],
        ['Cancelada', 'Cancelar', 'btn-outline-danger', '¿Cancelar esta cita?'],
    ],
    'No_Asistio' => [
        ['Atendida', 'Atendida', 'btn-outline-success', '¿Marcar esta cita como atendida?'],
        ['Cancelada', 'Cancelar', 'btn-outline-danger', '¿Cancelar esta cita?'],
    ],
    'Atendida' => [],
    'Cancelada' => [],
];
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2 align-items-center" method="GET" action="<?= url('citas') ?>" id="filtros-citas">
            <input type="hidden" name="route" value="citas">

            <input type="search"
                   class="form-control form-control-sm"
                   name="search"
                   placeholder="Paciente, médico o motivo"
                   value="<?= e($search) ?>">

            <?php if (!empty($medicos)): ?>
                <select class="form-select form-select-sm" name="medico_id" data-auto-submit>
                    <option value="">Todos los médicos</option>
                    <?php foreach ($medicos as $medico): ?>
                        <option value="<?= (int) $medico['id'] ?>" <?= $medicoId === (int) $medico['id'] ? 'selected' : '' ?>>
                            <?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <input type="date"
                   class="form-control form-control-sm"
                   name="fecha"
                   value="<?= e($fecha) ?>"
                   title="Filtrar por fecha"
                   data-auto-submit>

            <select class="form-select form-select-sm" name="estado" data-auto-submit>
                <option value="Todos" <?= $estado === 'Todos' ? 'selected' : '' ?>>Todos los estados</option>
                <?php foreach ($estados as $estadoItem): ?>
                    <option value="<?= e($estadoItem) ?>" <?= $estado === $estadoItem ? 'selected' : '' ?>>
                        <?= e(cita_estado_label($estadoItem)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('citas', ['fecha' => '', 'estado' => 'Todos']) ?>">Limpiar</a>
        </form>

        <?php if ($puedeGestionar): ?>
            <a href="<?= url('citas/create') ?>" class="btn btn-sm btn-primary">+ Nueva Cita</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Médico</th>
                    <th>Paciente</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($citas)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No se encontraron citas con los filtros seleccionados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($citas as $cita): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e(format_date($cita['fecha'])) ?></div>
                            <small class="text-muted"><?= e(day_name((int) $cita['dia_semana'])) ?></small>
                        </td>
                        <td>
                            <span class="fw-semibold"><?= e(format_time($cita['hora_inicio'])) ?></span>
                            <span class="text-muted">–</span>
                            <span><?= e(format_time($cita['hora_fin'])) ?></span>
                        </td>
                        <td>
                            <strong><?= e($cita['medico_apellidos'] . ', ' . $cita['medico_nombres']) ?></strong>
                            <br><small class="text-muted"><?= e($cita['especialidad']) ?></small>
                        </td>
                        <td>
                            <strong><?= e($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombres']) ?></strong>
                            <br>
                            <small class="text-muted">
                                <?= e($cita['paciente_documento_tipo'] . ' ' . $cita['paciente_documento_numero']) ?>
                            </small>
                        </td>
                        <td><?= cita_estado_badge((string) $cita['estado']) ?></td>
                        <td class="text-end">
                            <?php if ($puedeGestionar): ?>
                                <?php $acciones = $accionesPorEstado[$cita['estado']] ?? []; ?>
                                <?php if ($acciones === []): ?>
                                    <span class="text-muted small">—</span>
                                <?php else: ?>
                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                        <?php foreach ($acciones as $accion): ?>
                                            <?php $accionEstado($cita, $accion[0], $accion[1], $accion[2], $accion[3]); ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($puedeMarcarAtendida): ?>
                                <?php if (in_array($cita['estado'], ['Pendiente', 'Confirmada', 'No_Asistio'], true)): ?>
                                    <?php $accionEstado($cita, 'Atendida', 'Marcar atendida', 'btn-outline-success', '¿Marcar esta cita como atendida?'); ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">Solo lectura</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white text-muted small">
        <?= count($citas) ?> cita(s) encontrada(s).
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filtros-citas');

    if (!form) {
        return;
    }

    form.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => form.submit());
    });
});
</script>
