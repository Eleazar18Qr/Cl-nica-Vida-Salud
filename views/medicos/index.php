<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET" action="<?= url('medicos') ?>">
            <input type="hidden" name="route" value="medicos">
            <input type="search" class="form-control form-control-sm" name="search" placeholder="Buscar por nombre, email o colegiatura" value="<?= e($search) ?>">
            <select class="form-select form-select-sm" name="estado">
                <option value="Activo" <?= $estado === 'Activo' ? 'selected' : '' ?>>Activos</option>
                <option value="Inactivo" <?= $estado === 'Inactivo' ? 'selected' : '' ?>>Inactivos</option>
                <option value="Todos" <?= $estado === 'Todos' ? 'selected' : '' ?>>Todos</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
        </form>

        <?php if (has_role(['Administrador'])): ?>
            <a href="<?= url('medicos/create') ?>" class="btn btn-sm btn-primary">+ Nuevo médico</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Colegiatura</th>
                    <th>Médico</th>
                    <th>Especialidad</th>
                    <th>Contacto</th>
                    <th>Tarifa</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($medicos)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron médicos.</td></tr>
            <?php else: ?>
                <?php foreach ($medicos as $medico): ?>
                    <tr>
                        <td><?= e($medico['numero_colegiatura']) ?></td>
                        <td>
                            <strong><?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?></strong>
                            <?php if ($medico['email']): ?><br><small class="text-muted"><?= e($medico['email']) ?></small><?php endif; ?>
                        </td>
                        <td><?= e($medico['especialidad']) ?></td>
                        <td><?= e($medico['telefono'] ?: '—') ?></td>
                        <td>S/ <?= number_format((float) $medico['tarifa_consulta'], 2) ?></td>
                        <td><?= status_badge($medico['estado']) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('medicos/show/' . $medico['id']) ?>" class="btn btn-outline-secondary">Ver</a>
                                <?php if (has_role(['Administrador'])): ?>
                                    <a href="<?= url('medicos/edit/' . $medico['id']) ?>" class="btn btn-outline-primary">Editar</a>
                                    <?php if ($medico['estado'] === 'Activo'): ?>
                                        <form action="<?= url('medicos/delete/' . $medico['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este médico?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= url('medicos/restore/' . $medico['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Reactivar este médico?');">
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
