<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use DateTimeImmutable;
use PDO;

/**
 * Modelo de consultas para los reportes administrativos.
 *
 * Reutiliza la conexión PDO/PostgreSQL configurada en App\Config\Database
 * (singleton) y expone únicamente operaciones de lectura; no modifica datos.
 *
 * Reportes disponibles:
 *  1. Totales de citas agrupadas por estado.
 *  2. Ranking de médicos con mayor cantidad de citas asignadas.
 *  3. Listado detallado de citas por rango de fechas y/o especialidad.
 */
class ReporteModel
{
    /**
     * Estados principales del reporte de totales. Siempre se devuelven, aunque
     * su total sea 0, para que la vista mantenga una estructura estable.
     */
    private const ESTADOS_REPORTE = [
        CitaModel::ESTADO_PENDIENTE,
        CitaModel::ESTADO_CONFIRMADA,
        CitaModel::ESTADO_ATENDIDA,
        CitaModel::ESTADO_CANCELADA,
    ];

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene el total de citas agrupadas por estado.
     *
     * Los cuatro estados principales (Pendiente, Confirmada, Atendida y
     * Cancelada) siempre aparecen en el resultado, incluso con total 0. Si la
     * base de datos contiene otros estados (por ejemplo 'No_Asistio'), se
     * agregan al final para no perder información.
     *
     * @param string|null $fechaDesde Fecha inicial opcional (Y-m-d).
     * @param string|null $fechaHasta Fecha final opcional (Y-m-d).
     * @return array<int, array{estado: string, total: int, porcentaje: float}>
     */
    public function totalesPorEstado(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        [$desde, $hasta] = $this->normalizarRango($fechaDesde, $fechaHasta);

        $sql = "SELECT c.estado, COUNT(*) AS total
                FROM citas c
                WHERE 1=1";
        $params = [];

        if ($desde !== null) {
            $sql .= " AND c.fecha >= CAST(:fecha_desde AS date)";
            $params[':fecha_desde'] = $desde;
        }

        if ($hasta !== null) {
            $sql .= " AND c.fecha <= CAST(:fecha_hasta AS date)";
            $params[':fecha_hasta'] = $hasta;
        }

        $sql .= " GROUP BY c.estado";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $conteo = [];
        foreach ($stmt->fetchAll() as $fila) {
            $conteo[(string) $fila['estado']] = (int) $fila['total'];
        }

        $estados = self::ESTADOS_REPORTE;
        foreach (array_keys($conteo) as $estado) {
            if (!in_array($estado, $estados, true)) {
                $estados[] = $estado;
            }
        }

        $totalGeneral = array_sum($conteo);
        $resultado = [];

        foreach ($estados as $estado) {
            $total = $conteo[$estado] ?? 0;

            $resultado[] = [
                'estado' => $estado,
                'total' => $total,
                'porcentaje' => $totalGeneral > 0
                    ? round(($total / $totalGeneral) * 100, 2)
                    : 0.0,
            ];
        }

        return $resultado;
    }

    /**
     * Ranking de médicos ordenado por cantidad de citas asignadas.
     *
     * Solo incluye médicos que tengan al menos una cita en el período
     * consultado. El límite se acota entre 1 y 100 resultados.
     *
     * @param int         $limite           Cantidad máxima de médicos a devolver.
     * @param string|null $fechaDesde       Fecha inicial opcional (Y-m-d).
     * @param string|null $fechaHasta       Fecha final opcional (Y-m-d).
     * @param bool        $excluirCanceladas Si es true, no cuenta las citas canceladas.
     * @return array<int, array{
     *     medico_id: int,
     *     nombres: string,
     *     apellidos: string,
     *     numero_colegiatura: string,
     *     especialidad_id: int,
     *     especialidad: string,
     *     total_citas: int,
     *     total_atendidas: int,
     *     total_canceladas: int,
     *     ultima_cita: string|null
     * }>
     */
    public function rankingMedicos(
        int $limite = 10,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        bool $excluirCanceladas = false
    ): array {
        [$desde, $hasta] = $this->normalizarRango($fechaDesde, $fechaHasta);
        $limite = max(1, min(100, $limite));

        $sql = "SELECT m.id AS medico_id,
                       u.nombres,
                       u.apellidos,
                       m.numero_colegiatura,
                       e.id AS especialidad_id,
                       e.nombre AS especialidad,
                       COUNT(c.id) AS total_citas,
                       COUNT(c.id) FILTER (WHERE c.estado = 'Atendida') AS total_atendidas,
                       COUNT(c.id) FILTER (WHERE c.estado = 'Cancelada') AS total_canceladas,
                       MAX(c.fecha) AS ultima_cita
                FROM citas c
                INNER JOIN medicos m ON m.id = c.medico_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE 1=1";
        $params = [];

        if ($desde !== null) {
            $sql .= " AND c.fecha >= CAST(:fecha_desde AS date)";
            $params[':fecha_desde'] = $desde;
        }

        if ($hasta !== null) {
            $sql .= " AND c.fecha <= CAST(:fecha_hasta AS date)";
            $params[':fecha_hasta'] = $hasta;
        }

        if ($excluirCanceladas) {
            $sql .= " AND c.estado <> 'Cancelada'";
        }

        $sql .= " GROUP BY m.id, u.nombres, u.apellidos, m.numero_colegiatura, e.id, e.nombre
                  ORDER BY total_citas DESC, u.apellidos ASC, u.nombres ASC
                  LIMIT :limite";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $clave => $valor) {
            $stmt->bindValue($clave, $valor);
        }

        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        $ranking = [];

        foreach ($stmt->fetchAll() as $fila) {
            $fila['medico_id'] = (int) $fila['medico_id'];
            $fila['especialidad_id'] = (int) $fila['especialidad_id'];
            $fila['total_citas'] = (int) $fila['total_citas'];
            $fila['total_atendidas'] = (int) $fila['total_atendidas'];
            $fila['total_canceladas'] = (int) $fila['total_canceladas'];
            $ranking[] = $fila;
        }

        return $ranking;
    }

    /**
     * Listado detallado de citas para los reportes administrativos.
     *
     * Filtros opcionales y combinables:
     *  - Rango de fechas (desde/hasta), ambos inclusive.
     *  - Especialidad médica.
     *  - Estado de la cita (adicional, útil para exportaciones).
     *  - Médico concreto (usado por el reporte y la exportación CSV).
     *
     * Las fechas inválidas se ignoran y, si el rango llega invertido, se
     * intercambian los extremos.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listadoDetallado(
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?int $especialidadId = null,
        ?string $estado = null,
        ?int $medicoId = null
    ): array {
        [$desde, $hasta] = $this->normalizarRango($fechaDesde, $fechaHasta);

        $sql = "SELECT c.id,
                       c.fecha,
                       c.hora_inicio,
                       c.hora_fin,
                       c.estado,
                       c.motivo,
                       c.notas,
                       c.created_at,
                       c.updated_at,
                       p.id AS paciente_id,
                       p.nombres AS paciente_nombres,
                       p.apellidos AS paciente_apellidos,
                       p.documento_tipo AS paciente_documento_tipo,
                       p.documento_numero AS paciente_documento_numero,
                       p.telefono AS paciente_telefono,
                       m.id AS medico_id,
                       m.numero_colegiatura,
                       u.nombres AS medico_nombres,
                       u.apellidos AS medico_apellidos,
                       e.id AS especialidad_id,
                       e.nombre AS especialidad,
                       h.dia_semana
                FROM citas c
                INNER JOIN pacientes p ON p.id = c.paciente_id
                INNER JOIN medicos m ON m.id = c.medico_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                INNER JOIN horarios h ON h.id = c.horario_id
                WHERE 1=1";
        $params = [];

        if ($desde !== null) {
            $sql .= " AND c.fecha >= CAST(:fecha_desde AS date)";
            $params[':fecha_desde'] = $desde;
        }

        if ($hasta !== null) {
            $sql .= " AND c.fecha <= CAST(:fecha_hasta AS date)";
            $params[':fecha_hasta'] = $hasta;
        }

        if ($especialidadId !== null && $especialidadId > 0) {
            $sql .= " AND m.especialidad_id = :especialidad_id";
            $params[':especialidad_id'] = $especialidadId;
        }

        if ($medicoId !== null && $medicoId > 0) {
            $sql .= " AND c.medico_id = :medico_id";
            $params[':medico_id'] = $medicoId;
        }

        if (
            $estado !== null
            && $estado !== ''
            && $estado !== 'Todos'
            && in_array($estado, CitaModel::ESTADOS, true)
        ) {
            $sql .= " AND c.estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY c.fecha DESC, c.hora_inicio DESC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Normaliza un rango de fechas: descarta valores inválidos y corrige
     * rangos invertidos.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function normalizarRango(?string $fechaDesde, ?string $fechaHasta): array
    {
        $desde = $this->normalizarFecha($fechaDesde);
        $hasta = $this->normalizarFecha($fechaHasta);

        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    /**
     * Devuelve la fecha en formato Y-m-d si es válida; null en caso contrario.
     */
    private function normalizarFecha(?string $fecha): ?string
    {
        $fecha = trim((string) $fecha);

        if ($fecha === '') {
            return null;
        }

        $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($fechaObj === false || $fechaObj->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $fecha;
    }
}
