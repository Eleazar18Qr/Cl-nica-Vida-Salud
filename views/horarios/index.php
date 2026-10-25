<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET" action="<?= url('horarios') ?>">
            <input type="hidden" name="route" value="horarios">
            <input type="search" class="form-control form-control-sm" name="search" placeholder="Buscar por médico o especialidad" value="<?= e($search) ?>">
            <select class="form-select form-select-sm" name="medico_id">
                <option value="">Todos los médicos</option>
                <?php foreach ($medicos as $medico): ?>
                    <option value="<?= (int) $medico['id'] ?>" <?= $medicoId === (int) $medico['id'] ? 'selected' : '' ?>>
                        <?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" name="dia_semana">
                <option value="">Todos los días</option>
                <?php for ($dia = 1; $dia <= 7; $dia++): ?>
                    <option value="<?= $dia ?>" <?= $diaSemana === $dia ? 'selected' : '' ?>><?= e(day_name($dia)) ?></option>
                <?php endfor; ?>
            </select>
            <select class="form-select form-select-sm" name="estado">
                <option value="Activo" <?= $estado === 'Activo' ? 'selected' : '' ?>>Activos</option>
                <option value="Inactivo" <?= $estado === 'Inactivo' ? 'selected' : '' ?>>Inactivos</option>
                <option value="Todos" <?= $estado === 'Todos' ? 'selected' : '' ?>>Todos</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
        </form>

        <?php if (has_role(['Administrador'])): ?>
            <a href="<?= url('horarios/create') ?>" class="btn btn-sm btn-primary">+ Nuevo horario</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Médico</th>
                    <th>Especialidad</th>
                    <th>Día</th>
                    <th>Hora inicio</th>
                    <th>Hora fin</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($horarios)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron horarios.</td></tr>
            <?php else: ?>
                <?php foreach ($horarios as $horario): ?>
                    <tr>
                        <td><strong><?= e($horario['apellidos'] . ', ' . $horario['nombres']) ?></strong></td>
                        <td><?= e($horario['especialidad']) ?></td>
                        <td><?= e(day_name((int) $horario['dia_semana'])) ?></td>
                        <td><?= e(format_time($horario['hora_inicio'])) ?></td>
                        <td><?= e(format_time($horario['hora_fin'])) ?></td>
                        <td><?= status_badge($horario['estado']) ?></td>
                        <td class="text-end">
                            <?php if (has_role(['Administrador'])): ?>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('horarios/edit/' . $horario['id']) ?>" class="btn btn-outline-primary">Editar</a>
                                    <?php if ($horario['estado'] === 'Activo'): ?>
                                        <form action="<?= url('horarios/delete/' . $horario['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este horario?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= url('horarios/restore/' . $horario['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Reactivar este horario?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-success">Reactivar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
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
</div>
