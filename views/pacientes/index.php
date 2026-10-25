<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET" action="<?= url('pacientes') ?>">
            <input type="hidden" name="route" value="pacientes">
            <input type="search" class="form-control form-control-sm" name="search" placeholder="Buscar por documento, nombre o email" value="<?= e($search) ?>">
            <select class="form-select form-select-sm" name="estado">
                <option value="Activo" <?= $estado === 'Activo' ? 'selected' : '' ?>>Activos</option>
                <option value="Inactivo" <?= $estado === 'Inactivo' ? 'selected' : '' ?>>Inactivos</option>
                <option value="Todos" <?= $estado === 'Todos' ? 'selected' : '' ?>>Todos</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
        </form>

        <?php if (has_role(['Administrador', 'Recepcionista'])): ?>
            <a href="<?= url('pacientes/create') ?>" class="btn btn-sm btn-primary">+ Nuevo paciente</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Paciente</th>
                    <th>Nacimiento</th>
                    <th>Género</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pacientes)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No se encontraron pacientes.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pacientes as $paciente): ?>
                    <tr>
                        <td><?= e($paciente['documento_tipo'] . ' ' . $paciente['documento_numero']) ?></td>
                        <td>
                            <strong><?= e($paciente['apellidos'] . ', ' . $paciente['nombres']) ?></strong>
                            <?php if ($paciente['email']): ?><br><small class="text-muted"><?= e($paciente['email']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e(format_date($paciente['fecha_nacimiento'])) ?></td>
                        <td><?= e($paciente['genero']) ?></td>
                        <td><?= e($paciente['telefono'] ?: '—') ?></td>
                        <td><?= status_badge($paciente['estado']) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('pacientes/show/' . $paciente['id']) ?>" class="btn btn-outline-secondary">Ver</a>
                                <?php if (has_role(['Administrador', 'Recepcionista'])): ?>
                                    <a href="<?= url('pacientes/edit/' . $paciente['id']) ?>" class="btn btn-outline-primary">Editar</a>
                                    <?php if ($paciente['estado'] === 'Activo'): ?>
                                        <form action="<?= url('pacientes/delete/' . $paciente['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este paciente?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= url('pacientes/restore/' . $paciente['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Reactivar este paciente?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-success">Reactivar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
