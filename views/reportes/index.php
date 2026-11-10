<?php
/**
 * Dashboard de reportes administrativos.
 *
 * Variables: $kpis, $totalesPorEstado, $ranking, $citas, $medicos,
 *            $especialidades, $estados, $filtros.
 */

$kpis = $kpis ?? [];
$totalesPorEstado = $totalesPorEstado ?? [];
$ranking = $ranking ?? [];
$citas = $citas ?? [];
$medicos = $medicos ?? [];
$especialidades = $especialidades ?? [];
$estados = $estados ?? [];
$filtros = $filtros ?? [];

$filtro = static function (string $clave, $default = '') use ($filtros) {
    return $filtros[$clave] ?? $default;
};

$totalCitas = (int) ($kpis['total_citas'] ?? 0);
$citasHoy = (int) ($kpis['citas_hoy'] ?? 0);
$atendidas = (int) ($kpis['atendidas'] ?? 0);
$canceladas = (int) ($kpis['canceladas'] ?? 0);
$tasaAtencion = (float) ($kpis['tasa_atencion'] ?? 0);
$porcentajeCancelacion = $totalCitas > 0
    ? round(($canceladas / $totalCitas) * 100, 1)
    : 0.0;

$exportParams = array_filter([
    'fecha_desde' => (string) $filtro('fecha_desde'),
    'fecha_hasta' => (string) $filtro('fecha_hasta'),
    'medico_id' => $filtro('medico_id'),
    'especialidad_id' => $filtro('especialidad_id'),
    'estado' => $filtro('estado', 'Todos') !== 'Todos' ? $filtro('estado') : null,
], static fn ($valor): bool => $valor !== null && $valor !== '');

$exportUrl = url('reportes/exportar', $exportParams);

$rangoTexto = 'Todo el historial';
if ($filtro('fecha_desde') !== '' && $filtro('fecha_hasta') !== '') {
    $rangoTexto = 'Del ' . format_date((string) $filtro('fecha_desde'))
        . ' al ' . format_date((string) $filtro('fecha_hasta'));
} elseif ($filtro('fecha_desde') !== '') {
    $rangoTexto = 'Desde ' . format_date((string) $filtro('fecha_desde'));
} elseif ($filtro('fecha_hasta') !== '') {
    $rangoTexto = 'Hasta ' . format_date((string) $filtro('fecha_hasta'));
}

$porcentaje = static fn (float $valor): string => number_format($valor, 1, ',', '.');

$icono = static function (string $nombre): string {
    $paths = [
        'calendar' => '<path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>',
        'clock' => '<path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>',
        'check' => '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="m10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>',
        'x' => '<path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>',
        'download' => '<path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>',
        'people' => '<path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>',
    ];

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">'
        . ($paths[$nombre] ?? '') . '</svg>';
};
?>
<style>
    .report-kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: .75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .report-kpi-icon svg {
        width: 22px;
        height: 22px;
    }

    .report-inline-icon svg {
        width: 18px;
        height: 18px;
    }

    .report-export-btn svg {
        width: 16px;
        height: 16px;
    }

    .report-table-motivo {
        max-width: 240px;
    }
</style>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Indicadores y resultados de citas para la gestión administrativa.</p>
    <span class="badge text-bg-light border"><?= e($rangoTexto) ?></span>
</div>

<div class="row g-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="stat-label">Total de citas</span>
                    <span class="stat-value d-block"><?= $totalCitas ?></span>
                </div>
                <span class="report-kpi-icon bg-primary-subtle text-primary">
                    <?= $icono('calendar') ?>
                </span>
            </div>
            <span class="text-muted small"><?= e($rangoTexto) ?></span>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="stat-label">Citas de hoy</span>
                    <span class="stat-value d-block"><?= $citasHoy ?></span>
                </div>
                <span class="report-kpi-icon bg-info-subtle text-info-emphasis">
                    <?= $icono('clock') ?>
                </span>
            </div>
            <span class="text-muted small">Hoy, <?= e(format_date(date('Y-m-d'))) ?></span>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="stat-label">Atenciones completadas</span>
                    <span class="stat-value d-block"><?= $atendidas ?></span>
                </div>
                <span class="report-kpi-icon bg-success-subtle text-success">
                    <?= $icono('check') ?>
                </span>
            </div>
            <span class="text-muted small"><?= $porcentaje($tasaAtencion) ?>% del total</span>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <span class="stat-label">Cancelaciones</span>
                    <span class="stat-value d-block"><?= $canceladas ?></span>
                </div>
                <span class="report-kpi-icon bg-danger-subtle text-danger">
                    <?= $icono('x') ?>
                </span>
            </div>
            <span class="text-muted small"><?= $porcentaje($porcentajeCancelacion) ?>% del total</span>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h2 class="h6 mb-0">Filtros del reporte</h2>
            <small class="text-muted">Combina rango de fechas, médico, especialidad y estado.</small>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= url('reportes') ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="reportes">

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="fecha_desde">Desde</label>
                <input type="date"
                       class="form-control"
                       id="fecha_desde"
                       name="fecha_desde"
                       value="<?= e((string) $filtro('fecha_desde')) ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="fecha_hasta">Hasta</label>
                <input type="date"
                       class="form-control"
                       id="fecha_hasta"
                       name="fecha_hasta"
                       value="<?= e((string) $filtro('fecha_hasta')) ?>">
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="medico_id">Médico</label>
                <select class="form-select" id="medico_id" name="medico_id">
                    <option value="">Todos los médicos</option>
                    <?php foreach ($medicos as $medico): ?>
                        <option value="<?= (int) $medico['id'] ?>"
                            <?= (int) $filtro('medico_id', 0) === (int) $medico['id'] ? 'selected' : '' ?>>
                            <?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label" for="especialidad_id">Especialidad</label>
                <select class="form-select" id="especialidad_id" name="especialidad_id">
                    <option value="">Todas las especialidades</option>
                    <?php foreach ($especialidades as $especialidad): ?>
                        <option value="<?= (int) $especialidad['id'] ?>"
                            <?= (int) $filtro('especialidad_id', 0) === (int) $especialidad['id'] ? 'selected' : '' ?>>
                            <?= e($especialidad['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label" for="estado">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="Todos" <?= $filtro('estado', 'Todos') === 'Todos' ? 'selected' : '' ?>>
                        Todos
                    </option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= e($estado) ?>"
                            <?= $filtro('estado', 'Todos') === $estado ? 'selected' : '' ?>>
                            <?= e(cita_estado_label($estado)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-8 col-xl-10 d-flex flex-wrap gap-2 justify-content-md-end">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="<?= url('reportes') ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2 class="h6 mb-0">Resumen por estado</h2>
    </div>
    <div class="card-body d-flex flex-wrap gap-4">
        <?php foreach ($totalesPorEstado as $fila): ?>
            <div class="d-flex align-items-center gap-2">
                <?= cita_estado_badge((string) $fila['estado']) ?>
                <span class="fs-5 fw-bold"><?= (int) $fila['total'] ?></span>
                <span class="text-muted small"><?= $porcentaje((float) $fila['porcentaje']) ?>%</span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="report-inline-icon text-primary"><?= $icono('people') ?></span>
            <h2 class="h6 mb-0">Ranking de médicos por citas asignadas</h2>
        </div>
        <span class="text-muted small"><?= count($ranking) ?> médico(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="text-muted">#</th>
                    <th>Médico</th>
                    <th>Especialidad</th>
                    <th class="text-end">Total citas</th>
                    <th class="text-end">Atendidas</th>
                    <th class="text-end">Canceladas</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ranking)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No hay médicos con citas en el período seleccionado.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ranking as $indice => $medico): ?>
                    <tr>
                        <td class="text-muted"><?= $indice + 1 ?></td>
                        <td>
                            <strong><?= e($medico['apellidos'] . ', ' . $medico['nombres']) ?></strong>
                            <br>
                            <small class="text-muted"><?= e($medico['numero_colegiatura']) ?></small>
                        </td>
                        <td><?= e($medico['especialidad']) ?></td>
                        <td class="text-end fw-semibold"><?= (int) $medico['total_citas'] ?></td>
                        <td class="text-end"><?= (int) $medico['total_atendidas'] ?></td>
                        <td class="text-end"><?= (int) $medico['total_canceladas'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h2 class="h6 mb-0">Resultados del reporte</h2>
            <small class="text-muted">
                <?= count($citas) ?> cita(s) encontrada(s) · <?= e($rangoTexto) ?>
            </small>
        </div>
        <a href="<?= e($exportUrl) ?>"
           class="btn btn-success report-export-btn d-inline-flex align-items-center gap-2">
            <?= $icono('download') ?>
            Exportar a CSV
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th>Especialidad</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($citas)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        No se encontraron citas con los filtros seleccionados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($citas as $cita): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e(format_date((string) $cita['fecha'])) ?></div>
                            <small class="text-muted"><?= e(day_name((int) $cita['dia_semana'])) ?></small>
                        </td>
                        <td>
                            <span class="fw-semibold"><?= e(format_time((string) $cita['hora_inicio'])) ?></span>
                            <span class="text-muted">–</span>
                            <span><?= e(format_time((string) $cita['hora_fin'])) ?></span>
                        </td>
                        <td>
                            <strong><?= e($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombres']) ?></strong>
                            <br>
                            <small class="text-muted">
                                <?= e($cita['paciente_documento_tipo'] . ' ' . $cita['paciente_documento_numero']) ?>
                            </small>
                        </td>
                        <td><?= e($cita['medico_apellidos'] . ', ' . $cita['medico_nombres']) ?></td>
                        <td><?= e($cita['especialidad']) ?></td>
                        <td><?= cita_estado_badge((string) $cita['estado']) ?></td>
                        <td>
                            <?php if (($cita['motivo'] ?? '') !== ''): ?>
                                <span class="d-inline-block text-truncate report-table-motivo"
                                      title="<?= e((string) $cita['motivo']) ?>">
                                    <?= e((string) $cita['motivo']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white text-muted small d-flex flex-wrap gap-2 justify-content-between">
        <span><?= count($citas) ?> cita(s) en el resultado.</span>
        <span>La exportación respeta los filtros activos.</span>
    </div>
</div>
