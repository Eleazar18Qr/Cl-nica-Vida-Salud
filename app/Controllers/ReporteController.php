<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\CitaModel;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\ReporteModel;
use DateTimeImmutable;
use Throwable;

/**
 * Controlador de reportes administrativos.
 *
 * Rutas registradas en index.php:
 *   GET reportes          -> index
 *   GET reportes/exportar -> exportarCsv
 *
 * Acceso restringido a Administrador y Recepcionista (RN13). La validación se
 * repite en el servidor aunque el router ya aplique el middleware de roles.
 *
 * Vista esperada: views/reportes/index.php
 * Variables entregadas por index():
 *   - $kpis: total_citas, pendientes, confirmadas, atendidas, canceladas,
 *            no_asistio, tasa_atencion.
 *   - $totalesPorEstado: filas devueltas por ReporteModel::totalesPorEstado().
 *   - $ranking: top 10 de médicos por citas asignadas.
 *   - $citas: listado detallado filtrado.
 *   - $medicos, $especialidades: opciones para los filtros.
 *   - $filtros: fecha_desde, fecha_hasta, medico_id, especialidad_id, estado.
 */
class ReporteController extends Controller
{
    private const ROLES = ['Administrador', 'Recepcionista'];

    private ReporteModel $reporteModel;
    private Medico $medicoModel;
    private Especialidad $especialidadModel;

    public function __construct()
    {
        $this->reporteModel = new ReporteModel();
        $this->medicoModel = new Medico();
        $this->especialidadModel = new Especialidad();
    }

    /**
     * Dashboard de reportes: KPIs por estado, ranking de médicos y listado
     * detallado, todo filtrado por el rango de fechas y demás filtros activos.
     */
    public function index(): void
    {
        $this->authorize();

        $filtros = $this->filtrosDesdeRequest();

        $totales = $this->reporteModel->totalesPorEstado(
            $filtros['fecha_desde'],
            $filtros['fecha_hasta']
        );

        $totalesPorEstado = [];
        foreach ($totales as $fila) {
            $totalesPorEstado[(string) $fila['estado']] = (int) $fila['total'];
        }

        $totalCitas = array_sum($totalesPorEstado);
        $atendidas = $totalesPorEstado[CitaModel::ESTADO_ATENDIDA] ?? 0;

        $hoy = date('Y-m-d');
        $citasHoy = array_sum(array_column(
            $this->reporteModel->totalesPorEstado($hoy, $hoy),
            'total'
        ));

        $kpis = [
            'total_citas' => $totalCitas,
            'citas_hoy' => $citasHoy,
            'pendientes' => $totalesPorEstado[CitaModel::ESTADO_PENDIENTE] ?? 0,
            'confirmadas' => $totalesPorEstado[CitaModel::ESTADO_CONFIRMADA] ?? 0,
            'atendidas' => $atendidas,
            'canceladas' => $totalesPorEstado[CitaModel::ESTADO_CANCELADA] ?? 0,
            'no_asistio' => $totalesPorEstado[CitaModel::ESTADO_NO_ASISTIO] ?? 0,
            'tasa_atencion' => $totalCitas > 0
                ? round(($atendidas / $totalCitas) * 100, 2)
                : 0.0,
        ];

        $this->view('reportes/index', [
            'title' => 'Reportes',
            'kpis' => $kpis,
            'totalesPorEstado' => $totales,
            'ranking' => $this->reporteModel->rankingMedicos(
                10,
                $filtros['fecha_desde'],
                $filtros['fecha_hasta']
            ),
            'citas' => $this->reporteModel->listadoDetallado(
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
                $filtros['especialidad_id'],
                $filtros['estado'],
                $filtros['medico_id']
            ),
            'medicos' => $this->medicoModel->allForSelect(),
            'especialidades' => $this->especialidadModel->allForSelect(),
            'estados' => CitaModel::ESTADOS,
            'filtros' => $filtros,
        ]);
    }

    /**
     * Exporta el listado detallado a CSV compatible con Excel.
     *
     * Toma los filtros activos (fechas, médico y, si están presentes,
     * especialidad y estado) y entrega la descarga directamente. El archivo
     * incluye BOM UTF-8 y separador ';' para que Excel conserve tildes, ñ y
     * separe correctamente las columnas en configuraciones regionales en
     * español.
     */
    public function exportarCsv(): void
    {
        $this->authorize();

        $filtros = $this->filtrosDesdeRequest();

        try {
            $citas = $this->reporteModel->listadoDetallado(
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
                $filtros['especialidad_id'],
                $filtros['estado'],
                $filtros['medico_id']
            );
        } catch (Throwable $e) {
            error_log(
                '[Clínica Vida Salud][Reportes] ' . $e->getMessage()
                . ' en ' . $e->getFile() . ':' . $e->getLine()
            );

            Session::flash('error', 'No se pudo generar el archivo CSV. Intente nuevamente.');
            $this->redirect('reportes', $this->filtrosComoQuery($filtros));
        }

        // Evita que cualquier salida previa (warnings, espacios) corrompa el CSV.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $this->nombreArchivoCsv($filtros) . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $salida = fopen('php://output', 'wb');

        if ($salida === false) {
            $this->redirect('reportes', $this->filtrosComoQuery($filtros));
        }

        // BOM UTF-8: Excel reconoce el archivo como Unicode.
        fwrite($salida, "\xEF\xBB\xBF");

        fputcsv($salida, [
            'ID',
            'Fecha',
            'Día',
            'Hora inicio',
            'Hora fin',
            'Estado',
            'Paciente',
            'Documento',
            'Teléfono',
            'Médico',
            'Colegiatura',
            'Especialidad',
            'Motivo',
            'Notas',
            'Registrado',
        ], ';', '"', '');

        foreach ($citas as $cita) {
            fputcsv($salida, [
                (int) $cita['id'],
                \format_date((string) $cita['fecha']),
                \day_name((int) $cita['dia_semana']),
                \format_time((string) $cita['hora_inicio']),
                \format_time((string) $cita['hora_fin']),
                \cita_estado_label((string) $cita['estado']),
                $this->csvSeguro($cita['paciente_apellidos'] . ', ' . $cita['paciente_nombres']),
                $this->csvSeguro($cita['paciente_documento_tipo'] . ' ' . $cita['paciente_documento_numero']),
                $this->csvSeguro((string) ($cita['paciente_telefono'] ?? '')),
                $this->csvSeguro($cita['medico_apellidos'] . ', ' . $cita['medico_nombres']),
                $this->csvSeguro((string) $cita['numero_colegiatura']),
                $this->csvSeguro((string) $cita['especialidad']),
                $this->csvSeguro((string) ($cita['motivo'] ?? '')),
                $this->csvSeguro((string) ($cita['notas'] ?? '')),
                $this->formatearFechaHora((string) ($cita['created_at'] ?? '')),
            ], ';', '"', '');
        }

        fclose($salida);
        exit;
    }

    /**
     * RN13: valida sesión y rol. Redirige si no está autorizado.
     */
    private function authorize(): void
    {
        $user = Session::user();

        if ($user === null) {
            Session::flash('error', 'Debes iniciar sesión para acceder a los reportes.');
            $this->redirect('auth/login');
        }

        if (!in_array((string) ($user['rol'] ?? ''), self::ROLES, true)) {
            Session::flash('error', 'No tienes permisos para acceder a los reportes.');
            $this->redirect('dashboard');
        }
    }

    /**
     * Normaliza los filtros recibidos por GET.
     *
     * @return array{
     *     fecha_desde: string,
     *     fecha_hasta: string,
     *     medico_id: int|null,
     *     especialidad_id: int|null,
     *     estado: string
     * }
     */
    private function filtrosDesdeRequest(): array
    {
        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));

        if (!$this->esFechaValida($fechaDesde)) {
            $fechaDesde = '';
        }

        if (!$this->esFechaValida($fechaHasta)) {
            $fechaHasta = '';
        }

        $estado = (string) ($_GET['estado'] ?? 'Todos');

        if ($estado !== 'Todos' && !in_array($estado, CitaModel::ESTADOS, true)) {
            $estado = 'Todos';
        }

        $medicoId = isset($_GET['medico_id']) ? (int) $_GET['medico_id'] : 0;
        $especialidadId = isset($_GET['especialidad_id']) ? (int) $_GET['especialidad_id'] : 0;

        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'medico_id' => $medicoId > 0 ? $medicoId : null,
            'especialidad_id' => $especialidadId > 0 ? $especialidadId : null,
            'estado' => $estado,
        ];
    }

    private function esFechaValida(string $fecha): bool
    {
        if ($fecha === '') {
            return false;
        }

        $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        return $fechaObj !== false && $fechaObj->format('Y-m-d') === $fecha;
    }

    /**
     * Convierte los filtros normalizados a parámetros de URL para poder
     * regresar al dashboard conservando la selección.
     */
    private function filtrosComoQuery(array $filtros): array
    {
        return array_filter([
            'fecha_desde' => $filtros['fecha_desde'],
            'fecha_hasta' => $filtros['fecha_hasta'],
            'medico_id' => $filtros['medico_id'],
            'especialidad_id' => $filtros['especialidad_id'],
            'estado' => $filtros['estado'] !== 'Todos' ? $filtros['estado'] : null,
        ], static fn ($valor): bool => $valor !== null && $valor !== '');
    }

    private function nombreArchivoCsv(array $filtros): string
    {
        $partes = ['reporte_citas'];

        if ($filtros['fecha_desde'] !== '' && $filtros['fecha_hasta'] !== '') {
            $partes[] = $filtros['fecha_desde'] . '_a_' . $filtros['fecha_hasta'];
        } elseif ($filtros['fecha_desde'] !== '') {
            $partes[] = 'desde_' . $filtros['fecha_desde'];
        } elseif ($filtros['fecha_hasta'] !== '') {
            $partes[] = 'hasta_' . $filtros['fecha_hasta'];
        } else {
            $partes[] = date('Ymd_His');
        }

        if ($filtros['medico_id'] !== null) {
            $partes[] = 'medico_' . $filtros['medico_id'];
        }

        return implode('_', $partes) . '.csv';
    }

    /**
     * Neutraliza fórmulas al abrir el CSV en Excel (CSV injection): si un
     * campo empieza con =, +, -, @, tabulador o retorno de carro, se antepone
     * una comilla simple para que Excel lo trate como texto.
     */
    private function csvSeguro(mixed $valor): string
    {
        $texto = (string) $valor;

        if ($texto === '') {
            return '';
        }

        if (in_array($texto[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $texto;
        }

        return $texto;
    }

    private function formatearFechaHora(string $valor): string
    {
        if ($valor === '') {
            return '';
        }

        $timestamp = strtotime($valor);

        return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
    }
}
