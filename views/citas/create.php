<?php
/**
 * Formulario de reserva de cita.
 *
 * Variables: $medicos, $pacientes, $medicoId, $fecha, $bloques, $estadosIniciales.
 */

$medicoSeleccionado = (string) old('medico_id', $medicoId ?: '');
$fechaSeleccionada = (string) old('fecha', $fecha ?: date('Y-m-d'));
$horarioSeleccionado = (string) old('horario_id', '');
$pacienteSeleccionado = (string) old('paciente_id', '');
$estadoSeleccionado = (string) old('estado', 'Pendiente');
$motivo = (string) old('motivo', '');
$notas = (string) old('notas', '');

$errorHorario = error('horario_id');
$endpointDisponibilidad = url('citas/disponibilidad');
?>
<div class="card">
    <div class="card-body">
        <form method="POST"
              action="<?= url('citas/guardar') ?>"
              class="needs-validation"
              id="form-cita"
              novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input type="date"
                           class="form-control <?= error('fecha') ? 'is-invalid' : '' ?>"
                           id="fecha"
                           name="fecha"
                           value="<?= e($fechaSeleccionada) ?>"
                           min="<?= e(date('Y-m-d')) ?>"
                           required>
                    <?php $err = error('fecha'); ?>
                    <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="medico_id">Médico</label>
                    <select class="form-select <?= error('medico_id') ? 'is-invalid' : '' ?>"
                            id="medico_id"
                            name="medico_id"
                            required>
                        <option value="">Seleccione un médico</option>
                        <?php foreach ($medicos as $medico): ?>
                            <option value="<?= (int) $medico['id'] ?>"
                                <?= $medicoSeleccionado === (string) $medico['id'] ? 'selected' : '' ?>>
                                <?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?>
                                — <?= e($medico['especialidad']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php $err = error('medico_id'); ?>
                    <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="paciente_id">Paciente</label>
                    <select class="form-select <?= error('paciente_id') ? 'is-invalid' : '' ?>"
                            id="paciente_id"
                            name="paciente_id"
                            required>
                        <option value="">Seleccione un paciente</option>
                        <?php foreach ($pacientes as $paciente): ?>
                            <option value="<?= (int) $paciente['id'] ?>"
                                <?= $pacienteSeleccionado === (string) $paciente['id'] ? 'selected' : '' ?>>
                                <?= e($paciente['apellidos'] . ', ' . $paciente['nombres']) ?>
                                — <?= e($paciente['documento_tipo'] . ' ' . $paciente['documento_numero']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php $err = error('paciente_id'); ?>
                    <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mt-4">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Bloque horario (30 minutos)</label>
                    <small class="text-muted" id="bloques-status"></small>
                </div>

                <div id="bloques-container" class="row g-2">
                    <?php if ($medicoSeleccionado === '' || $fechaSeleccionada === ''): ?>
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                Seleccione un médico y una fecha para consultar los bloques disponibles.
                            </div>
                        </div>
                    <?php elseif (empty($bloques)): ?>
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                No hay bloques de 30 minutos disponibles para la fecha seleccionada.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bloques as $bloque): ?>
                            <div class="col-6 col-md-3 col-lg-2">
                                <input type="radio"
                                       class="btn-check"
                                       name="horario_id"
                                       id="horario_<?= (int) $bloque['horario_id'] ?>"
                                       value="<?= (int) $bloque['horario_id'] ?>"
                                       autocomplete="off"
                                       required
                                    <?= $horarioSeleccionado === (string) $bloque['horario_id'] ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary w-100" for="horario_<?= (int) $bloque['horario_id'] ?>">
                                    <?= e(format_time($bloque['hora_inicio'])) ?> - <?= e(format_time($bloque['hora_fin'])) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="horario-error"
                     class="text-danger small mt-2 <?= $errorHorario === '' ? 'd-none' : '' ?>">
                    <?= e($errorHorario) ?>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label class="form-label" for="estado">Estado inicial</label>
                    <select class="form-select <?= error('estado') ? 'is-invalid' : '' ?>"
                            id="estado"
                            name="estado"
                            required>
                        <?php foreach ($estadosIniciales as $estado): ?>
                            <option value="<?= e($estado) ?>" <?= $estadoSeleccionado === $estado ? 'selected' : '' ?>>
                                <?= e(cita_estado_label($estado)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php $err = error('estado'); ?>
                    <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label class="form-label" for="motivo">Motivo de la consulta</label>
                    <input type="text"
                           class="form-control <?= error('motivo') ? 'is-invalid' : '' ?>"
                           id="motivo"
                           name="motivo"
                           maxlength="500"
                           value="<?= e($motivo) ?>"
                           placeholder="Ej. Control general, dolor de cabeza, seguimiento...">
                    <?php $err = error('motivo'); ?>
                    <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label" for="notas">Notas adicionales</label>
                <textarea class="form-control <?= error('notas') ? 'is-invalid' : '' ?>"
                          id="notas"
                          name="notas"
                          rows="3"
                          maxlength="1000"
                          placeholder="Información adicional para la cita (opcional)."><?= e($notas) ?></textarea>
                <?php $err = error('notas'); ?>
                <?php if ($err !== ''): ?><div class="invalid-feedback"><?= e($err) ?></div><?php endif; ?>
            </div>

            <div class="alert alert-info mt-4 mb-0">
                Los bloques disponibles se generan en intervalos de 30 minutos según el horario de atención
                del médico. Las citas canceladas liberan su bloque automáticamente.
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Guardar cita</button>
                <a href="<?= url('citas') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-cita');

    if (!form) {
        return;
    }

    const medicoSelect = document.getElementById('medico_id');
    const fechaInput = document.getElementById('fecha');
    const contenedor = document.getElementById('bloques-container');
    const status = document.getElementById('bloques-status');
    const errorBox = document.getElementById('horario-error');
    const endpoint = <?= json_encode($endpointDisponibilidad, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let solicitudActual = 0;

    const formatHora = (hora) => String(hora || '').slice(0, 5);

    const mostrarMensaje = (tipo, texto) => {
        contenedor.innerHTML = '';

        const col = document.createElement('div');
        col.className = 'col-12';

        const alert = document.createElement('div');
        alert.className = 'alert alert-' + tipo + ' mb-0';
        alert.textContent = texto;

        col.appendChild(alert);
        contenedor.appendChild(col);
    };

    const renderBloques = (bloques) => {
        contenedor.innerHTML = '';

        if (!Array.isArray(bloques) || bloques.length === 0) {
            mostrarMensaje('warning', 'No hay bloques de 30 minutos disponibles para la fecha seleccionada.');
            status.textContent = '';
            return;
        }

        bloques.forEach((bloque) => {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3 col-lg-2';

            const input = document.createElement('input');
            input.type = 'radio';
            input.className = 'btn-check';
            input.name = 'horario_id';
            input.id = 'horario_' + bloque.horario_id;
            input.value = String(bloque.horario_id);
            input.autocomplete = 'off';
            input.required = true;

            const label = document.createElement('label');
            label.className = 'btn btn-outline-primary w-100';
            label.htmlFor = input.id;
            label.textContent = formatHora(bloque.hora_inicio) + ' - ' + formatHora(bloque.hora_fin);

            col.appendChild(input);
            col.appendChild(label);
            contenedor.appendChild(col);
        });

        status.textContent = bloques.length + ' bloque(s) disponible(s)';
    };

    const cargarDisponibilidad = async () => {
        const medicoId = medicoSelect.value;
        const fecha = fechaInput.value;

        if (!medicoId || !fecha) {
            mostrarMensaje('info', 'Seleccione un médico y una fecha para consultar los bloques disponibles.');
            status.textContent = '';
            return;
        }

        const solicitud = ++solicitudActual;
        status.textContent = 'Consultando disponibilidad...';
        mostrarMensaje('light', 'Consultando disponibilidad...');

        try {
            const respuesta = await fetch(
                endpoint
                    + '&medico_id=' + encodeURIComponent(medicoId)
                    + '&fecha=' + encodeURIComponent(fecha)
                    + '&solo_libres=1',
                { headers: { 'Accept': 'application/json' } }
            );

            const data = await respuesta.json();

            if (solicitud !== solicitudActual) {
                return;
            }

            if (!respuesta.ok || !data.ok) {
                throw new Error(data.message || 'No se pudo consultar la disponibilidad.');
            }

            renderBloques(data.bloques || []);
        } catch (error) {
            if (solicitud !== solicitudActual) {
                return;
            }

            mostrarMensaje('danger', error.message || 'No se pudo consultar la disponibilidad.');
            status.textContent = '';
        }
    };

    medicoSelect.addEventListener('change', cargarDisponibilidad);
    fechaInput.addEventListener('change', cargarDisponibilidad);

    contenedor.addEventListener('change', (event) => {
        if (event.target.name === 'horario_id') {
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }
    });

    // Refleja la cantidad de bloques que el servidor ya renderizó.
    const bloquesIniciales = contenedor.querySelectorAll('input[name="horario_id"]').length;
    if (bloquesIniciales > 0) {
        status.textContent = bloquesIniciales + ' bloque(s) disponible(s)';
    }

    form.addEventListener('submit', (event) => {
        const radios = form.querySelectorAll('input[name="horario_id"]');
        const seleccionado = form.querySelector('input[name="horario_id"]:checked');

        if (radios.length === 0) {
            event.preventDefault();
            event.stopPropagation();
            errorBox.textContent = 'Seleccione un médico, una fecha y un bloque horario.';
            errorBox.classList.remove('d-none');
            contenedor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (!seleccionado) {
            event.preventDefault();
            event.stopPropagation();
            errorBox.textContent = 'Seleccione un bloque horario disponible.';
            errorBox.classList.remove('d-none');
            contenedor.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        form.classList.add('was-validated');
    }, false);
});
</script>
