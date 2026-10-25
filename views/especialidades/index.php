<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <form class="d-flex flex-wrap gap-2" method="GET" action="<?= url('especialidades') ?>">
            <input type="hidden" name="route" value="especialidades">
            <input type="search" class="form-control form-control-sm" name="search" placeholder="Buscar especialidad" value="<?= e($search) ?>">
            <select class="form-select form-select-sm" name="estado">
                <option value="Activo" <?= $estado === 'Activo' ? 'selected' : '' ?>>Activas</option>
                <option value="Inactivo" <?= $estado === 'Inactivo' ? 'selected' : '' ?>>Inactivas</option>
                <option value="Todos" <?= $estado === 'Todos' ? 'selected' : '' ?>>Todas</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">Filtrar</button>
        </form>

        <a href="<?= url('especialidades/create') ?>" class="btn btn-sm btn-primary">+ Nueva especialidad</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($especialidades)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No se encontraron especialidades.</td></tr>
            <?php else: ?>
                <?php foreach ($especialidades as $especialidad): ?>
                    <tr>
                        <td><strong><?= e($especialidad['nombre']) ?></strong></td>
                        <td><?= e($especialidad['descripcion'] ?: '—') ?></td>
                        <td><?= status_badge($especialidad['estado']) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('especialidades/edit/' . $especialidad['id']) ?>" class="btn btn-outline-primary">Editar</a>
                                <?php if ($especialidad['estado'] === 'Activo'): ?>
                                    <form action="<?= url('especialidades/delete/' . $especialidad['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar esta especialidad?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger">Desactivar</button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?= url('especialidades/restore/' . $especialidad['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('¿Reactivar esta especialidad?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-success">Reactivar</button>
                                    </form>
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
