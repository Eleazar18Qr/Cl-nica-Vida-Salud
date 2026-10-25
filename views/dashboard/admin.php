<div class="row g-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Pacientes activos</span>
            <span class="stat-value"><?= (int) ($stats['pacientes'] ?? 0) ?></span>
            <a href="<?= url('pacientes') ?>" class="stat-link">Gestionar pacientes</a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Médicos activos</span>
            <span class="stat-value"><?= (int) ($stats['medicos'] ?? 0) ?></span>
            <a href="<?= url('medicos') ?>" class="stat-link">Gestionar médicos</a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Especialidades</span>
            <span class="stat-value"><?= (int) ($stats['especialidades'] ?? 0) ?></span>
            <a href="<?= url('especialidades') ?>" class="stat-link">Gestionar especialidades</a>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="stat-label">Horarios activos</span>
            <span class="stat-value"><?= (int) ($stats['horarios'] ?? 0) ?></span>
            <a href="<?= url('horarios') ?>" class="stat-link">Gestionar horarios</a>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h2 class="h5">Bienvenido, <?= e($user['nombres']) ?></h2>
        <p class="text-muted mb-0">Desde este panel puedes administrar usuarios, médicos, especialidades y horarios. La gestión de citas estará disponible en la siguiente iteración.</p>
    </div>
</div>
