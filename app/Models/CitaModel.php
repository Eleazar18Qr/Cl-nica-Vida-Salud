<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Modelo de acceso a datos para la gestión transaccional de citas médicas.
 *
 * Reglas de negocio cubiertas:
 *  - RN08: la cita se agenda sobre un bloque de 30 minutos definido en `horarios`.
 *  - RN09: creación y reprogramación dentro de transacciones PDO con bloqueo de fila
 *          (`SELECT ... FOR UPDATE`) y verificación de disponibilidad en tiempo real.
 *  - RN10: el índice parcial único `uq_citas_medico_fecha_horario_activa`
 *          (medico_id, fecha, horario_id) WHERE estado <> 'Cancelada' es la última
 *          barrera ante condiciones de carrera; las violaciones 23505 se traducen
 *          a un error de negocio.
 *  - RN11: el estado inicial solo puede ser 'Pendiente' o 'Confirmada'.
 *  - RN12: al pasar a 'Cancelada' el bloque queda libre automáticamente porque el
 *          índice parcial excluye las citas canceladas.
 *  - RN14: `created_at` y `updated_at` se registran en base de datos; además las
 *          actualizaciones fijan `updated_at = CURRENT_TIMESTAMP` de forma explícita.
 */
class CitaModel
{
    // Estados válidos (coinciden con el CHECK de la tabla citas).
    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_CONFIRMADA = 'Confirmada';
    public const ESTADO_ATENDIDA = 'Atendida';
    public const ESTADO_CANCELADA = 'Cancelada';
    public const ESTADO_NO_ASISTIO = 'No_Asistio';

    public const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_CONFIRMADA,
        self::ESTADO_ATENDIDA,
        self::ESTADO_CANCELADA,
        self::ESTADO_NO_ASISTIO,
    ];

    /** RN11: estados permitidos al crear una cita. */
    public const ESTADOS_INICIALES = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_CONFIRMADA,
    ];

    /** RN06: estados terminales, no se pueden reprogramar ni cambiar de flujo. */
    public const ESTADOS_TERMINALES = [
        self::ESTADO_ATENDIDA,
        self::ESTADO_CANCELADA,
    ];

    /** Transiciones permitidas entre estados. */
    private const TRANSICIONES = [
        self::ESTADO_PENDIENTE => [
            self::ESTADO_CONFIRMADA,
            self::ESTADO_ATENDIDA,
            self::ESTADO_CANCELADA,
            self::ESTADO_NO_ASISTIO,
        ],
        self::ESTADO_CONFIRMADA => [
            self::ESTADO_ATENDIDA,
            self::ESTADO_CANCELADA,
            self::ESTADO_NO_ASISTIO,
        ],
        self::ESTADO_NO_ASISTIO => [
            self::ESTADO_CONFIRMADA,
            self::ESTADO_ATENDIDA,
            self::ESTADO_CANCELADA,
        ],
        self::ESTADO_ATENDIDA => [],
        self::ESTADO_CANCELADA => [],
    ];

    // Códigos de error de negocio para que los controladores puedan distinguirlos.
    public const ERR_SLOT_OCUPADO = 1;
    public const ERR_HORARIO_INVALIDO = 2;
    public const ERR_MEDICO_INACTIVO = 3;
    public const ERR_PACIENTE_INVALIDO = 4;
    public const ERR_ESTADO_INVALIDO = 5;
    public const ERR_TRANSICION_INVALIDA = 6;
    public const ERR_FECHA_INVALIDA = 7;

    private const SELECT_BASE = "SELECT c.id, c.paciente_id, c.medico_id, c.horario_id,
                                        c.fecha, c.hora_inicio, c.hora_fin, c.estado,
                                        c.motivo, c.notas, c.created_by,
                                        c.created_at, c.updated_at,
                                        p.nombres AS paciente_nombres,
                                        p.apellidos AS paciente_apellidos,
                                        p.documento_tipo AS paciente_documento_tipo,
                                        p.documento_numero AS paciente_documento_numero,
                                        p.telefono AS paciente_telefono,
                                        mu.nombres AS medico_nombres,
                                        mu.apellidos AS medico_apellidos,
                                        e.nombre AS especialidad,
                                        h.dia_semana,
                                        cu.nombres AS creado_por_nombres,
                                        cu.apellidos AS creado_por_apellidos
                                 FROM citas c
                                 INNER JOIN pacientes p ON p.id = c.paciente_id
                                 INNER JOIN medicos m ON m.id = c.medico_id
                                 INNER JOIN usuarios mu ON mu.id = m.usuario_id
                                 INNER JOIN especialidades e ON e.id = m.especialidad_id
                                 INNER JOIN horarios h ON h.id = c.horario_id
                                 LEFT JOIN usuarios cu ON cu.id = c.created_by";

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Lista citas con filtros opcionales.
     *
     * Filtros soportados:
     *  - search:        nombre/documento de paciente o médico, motivo.
     *  - estado:        estado exacto; 'Todos' o null para no filtrar.
     *  - medico_id:     citas de un médico.
     *  - paciente_id:   citas de un paciente.
     *  - fecha:         fecha exacta (Y-m-d).
     *  - fecha_desde:   rango inicial (Y-m-d).
     *  - fecha_hasta:   rango final (Y-m-d).
     *  - solo_futuras:  true para fechas >= hoy.
     */
    public function all(array $filters = []): array
    {
        $sql = self::SELECT_BASE . " WHERE 1=1";
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $sql .= " AND (p.nombres ILIKE :search
                        OR p.apellidos ILIKE :search
                        OR p.documento_numero ILIKE :search
                        OR mu.nombres ILIKE :search
                        OR mu.apellidos ILIKE :search
                        OR c.motivo ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $estado = $filters['estado'] ?? null;
        if (is_string($estado) && $estado !== '' && $estado !== 'Todos') {
            $sql .= " AND c.estado = :estado";
            $params[':estado'] = $estado;
        }

        if (!empty($filters['medico_id'])) {
            $sql .= " AND c.medico_id = :medico_id";
            $params[':medico_id'] = (int) $filters['medico_id'];
        }

        if (!empty($filters['paciente_id'])) {
            $sql .= " AND c.paciente_id = :paciente_id";
            $params[':paciente_id'] = (int) $filters['paciente_id'];
        }

        if (!empty($filters['fecha'])) {
            $sql .= " AND c.fecha = CAST(:fecha AS date)";
            $params[':fecha'] = (string) $filters['fecha'];
        }

        if (!empty($filters['fecha_desde'])) {
            $sql .= " AND c.fecha >= CAST(:fecha_desde AS date)";
            $params[':fecha_desde'] = (string) $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= " AND c.fecha <= CAST(:fecha_hasta AS date)";
            $params[':fecha_hasta'] = (string) $filters['fecha_hasta'];
        }

        if (!empty($filters['solo_futuras'])) {
            $sql .= " AND c.fecha >= CAST(:hoy AS date)";
            $params[':hoy'] = date('Y-m-d');
        }

        $sql .= " ORDER BY c.fecha DESC, c.hora_inicio DESC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene una cita con todos sus datos relacionados.
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . " WHERE c.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $cita = $stmt->fetch();

        return $cita ?: null;
    }

    /**
     * Citas de un paciente (seguimiento / historial).
     */
    public function findByPaciente(int $pacienteId, ?string $estado = null, bool $soloFuturas = false): array
    {
        $sql = self::SELECT_BASE . " WHERE c.paciente_id = :paciente_id";
        $params = [':paciente_id' => $pacienteId];

        if ($estado !== null && $estado !== '' && $estado !== 'Todos') {
            $sql .= " AND c.estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($soloFuturas) {
            $sql .= " AND c.fecha >= CAST(:hoy AS date)";
            $params[':hoy'] = date('Y-m-d');
            $sql .= " ORDER BY c.fecha ASC, c.hora_inicio ASC, c.id ASC";
        } else {
            $sql .= " ORDER BY c.fecha DESC, c.hora_inicio DESC, c.id DESC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Agenda de un médico para una fecha determinada.
     */
    public function agendaMedico(int $medicoId, string $fecha, ?string $estado = null): array
    {
        $sql = self::SELECT_BASE . " WHERE c.medico_id = :medico_id
                                    AND c.fecha = CAST(:fecha AS date)";
        $params = [
            ':medico_id' => $medicoId,
            ':fecha' => $fecha,
        ];

        if ($estado !== null && $estado !== '' && $estado !== 'Todos') {
            $sql .= " AND c.estado = :estado";
            $params[':estado'] = $estado;
        }

        $sql .= " ORDER BY c.hora_inicio ASC, c.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Consulta la disponibilidad de un médico para una fecha concreta.
     *
     * Devuelve los bloques activos del día indicado con la marca `disponible`
     * (1 = libre, 0 = ocupado) y, si existe, la cita que ocupa el bloque.
     * Los bloques en estado 'Cancelada' no ocupan el horario (RN12).
     *
     * @param bool $soloLibres     Devuelve únicamente los bloques disponibles.
     * @param bool $incluirPasadas Incluye bloques cuya hora ya pasó (hoy o fechas previas).
     */
    public function disponibilidad(
        int $medicoId,
        string $fecha,
        bool $soloLibres = false,
        bool $incluirPasadas = false
    ): array {
        $this->validarFecha($fecha);

        $sql = "SELECT h.id AS horario_id, h.medico_id, h.dia_semana,
                       h.hora_inicio, h.hora_fin,
                       CASE WHEN c.id IS NULL THEN 1 ELSE 0 END AS disponible,
                       c.id AS cita_id, c.estado AS cita_estado, c.paciente_id
                FROM horarios h
                INNER JOIN medicos m ON m.id = h.medico_id AND m.estado = 'Activo'
                LEFT JOIN citas c
                       ON c.medico_id = h.medico_id
                      AND c.fecha = CAST(:fecha AS date)
                      AND c.horario_id = h.id
                      AND c.estado <> 'Cancelada'
                WHERE h.medico_id = :medico_id
                  AND h.estado = 'Activo'
                  AND h.dia_semana = EXTRACT(ISODOW FROM CAST(:fecha AS date))";
        $params = [
            ':medico_id' => $medicoId,
            ':fecha' => $fecha,
        ];

        if ($soloLibres) {
            $sql .= " AND c.id IS NULL";
        }

        if (!$incluirPasadas) {
            $sql .= " AND (CAST(:fecha AS date) > CAST(:hoy AS date)
                        OR (CAST(:fecha AS date) = CAST(:hoy AS date)
                            AND h.hora_inicio > CAST(:ahora AS time)))";
            $params[':hoy'] = date('Y-m-d');
            $params[':ahora'] = date('H:i:s');
        }

        $sql .= " ORDER BY h.hora_inicio ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Verifica si un bloque concreto sigue disponible para un médico y fecha.
     * `$excludeCitaId` permite ignorar la cita que se está reprogramando.
     */
    public function isSlotAvailable(
        int $medicoId,
        string $fecha,
        int $horarioId,
        ?int $excludeCitaId = null
    ): bool {
        $this->validarFecha($fecha);

        $conflicto = "SELECT 1
                      FROM citas c
                      WHERE c.medico_id = h.medico_id
                        AND c.fecha = CAST(:fecha AS date)
                        AND c.estado <> 'Cancelada'
                        AND (c.horario_id = h.id
                             OR (c.hora_inicio < h.hora_fin AND c.hora_fin > h.hora_inicio))";

        if ($excludeCitaId !== null) {
            $conflicto .= " AND c.id <> :exclude_id";
        }

        $sql = "SELECT COUNT(*)
                FROM horarios h
                INNER JOIN medicos m ON m.id = h.medico_id
                WHERE h.id = :horario_id
                  AND h.medico_id = :medico_id
                  AND h.estado = 'Activo'
                  AND m.estado = 'Activo'
                  AND h.dia_semana = EXTRACT(ISODOW FROM CAST(:fecha AS date))
                  AND NOT EXISTS ({$conflicto})";

        $params = [
            ':horario_id' => $horarioId,
            ':medico_id' => $medicoId,
            ':fecha' => $fecha,
        ];

        if ($excludeCitaId !== null) {
            $params[':exclude_id'] = $excludeCitaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Devuelve la cita activa que entra en conflicto con un bloque/horario dado.
     * Considera tanto el mismo bloque como rangos horarios superpuestos.
     */
    public function findConflicto(
        int $medicoId,
        string $fecha,
        int $horarioId,
        string $horaInicio,
        string $horaFin,
        ?int $excludeCitaId = null
    ): ?array {
        $sql = "SELECT c.id, c.paciente_id, c.horario_id, c.hora_inicio, c.hora_fin, c.estado,
                       p.nombres AS paciente_nombres, p.apellidos AS paciente_apellidos
                FROM citas c
                INNER JOIN pacientes p ON p.id = c.paciente_id
                WHERE c.medico_id = :medico_id
                  AND c.fecha = CAST(:fecha AS date)
                  AND c.estado <> 'Cancelada'
                  AND (c.horario_id = :horario_id
                       OR (c.hora_inicio < CAST(:hora_fin AS time)
                           AND c.hora_fin > CAST(:hora_inicio AS time)))";
        $params = [
            ':medico_id' => $medicoId,
            ':fecha' => $fecha,
            ':horario_id' => $horarioId,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin,
        ];

        if ($excludeCitaId !== null) {
            $sql .= " AND c.id <> :exclude_id";
            $params[':exclude_id'] = $excludeCitaId;
        }

        $sql .= " ORDER BY c.hora_inicio ASC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $cita = $stmt->fetch();

        return $cita ?: null;
    }

    /**
     * Agenda una nueva cita dentro de una transacción (RN09).
     *
     * Flujo:
     *  1. beginTransaction.
     *  2. Bloquea la fila del horario con FOR UPDATE para serializar reservas
     *     concurrentes del mismo bloque.
     *  3. Verifica médico activo, paciente activo, día de la semana, que la fecha
     *     no sea pasada y que no exista otra cita activa en el bloque.
     *  4. Inserta la cita con hora_inicio/hora_fin tomadas del bloque (RN08).
     *  5. commit; ante cualquier error hace rollBack.
     *
     * El índice parcial único es la salvaguarda final si dos transacciones
     * intentan reservar el mismo bloque; la violación se traduce a
     * ERR_SLOT_OCUPADO.
     *
     * @param array $data paciente_id, medico_id, horario_id, fecha, estado, motivo, notas
     * @return int ID de la cita creada.
     */
    public function agendar(array $data, ?int $createdBy = null): int
    {
        $pacienteId = (int) ($data['paciente_id'] ?? 0);
        $medicoId = (int) ($data['medico_id'] ?? 0);
        $horarioId = (int) ($data['horario_id'] ?? 0);
        $fecha = trim((string) ($data['fecha'] ?? ''));
        $estado = (string) ($data['estado'] ?? self::ESTADO_PENDIENTE);
        $motivo = isset($data['motivo']) ? trim((string) $data['motivo']) : '';
        $notas = isset($data['notas']) ? trim((string) $data['notas']) : '';

        if ($pacienteId <= 0 || $medicoId <= 0 || $horarioId <= 0) {
            throw new InvalidArgumentException('Los datos de la cita están incompletos.');
        }

        if (!in_array($estado, self::ESTADOS_INICIALES, true)) {
            throw new RuntimeException(
                'El estado inicial de una cita debe ser Pendiente o Confirmada.',
                self::ERR_ESTADO_INVALIDO
            );
        }

        $fechaObj = $this->validarFecha($fecha);

        $this->db->beginTransaction();

        try {
            $horario = $this->lockHorario($horarioId, $medicoId);

            if ((string) $horario['estado'] !== 'Activo') {
                throw new RuntimeException('El bloque horario seleccionado está inactivo.', self::ERR_HORARIO_INVALIDO);
            }

            if ((string) $horario['medico_estado'] !== 'Activo') {
                throw new RuntimeException(
                    'El médico está inactivo y no puede recibir nuevas citas.',
                    self::ERR_MEDICO_INACTIVO
                );
            }

            if ((int) $horario['dia_semana'] !== (int) $fechaObj->format('N')) {
                throw new RuntimeException(
                    'La fecha seleccionada no corresponde a un día de atención del médico.',
                    self::ERR_HORARIO_INVALIDO
                );
            }

            $this->validarNoPasada($fechaObj, (string) $horario['hora_inicio']);

            $this->assertPacienteActivo($pacienteId);

            $conflicto = $this->findConflicto(
                $medicoId,
                $fecha,
                $horarioId,
                (string) $horario['hora_inicio'],
                (string) $horario['hora_fin']
            );

            if ($conflicto !== null) {
                throw new RuntimeException(
                    'El bloque horario seleccionado ya está reservado para este médico.',
                    self::ERR_SLOT_OCUPADO
                );
            }

            $stmt = $this->db->prepare(
                "INSERT INTO citas
                    (paciente_id, medico_id, horario_id, fecha, hora_inicio, hora_fin,
                     estado, motivo, notas, created_by)
                 VALUES
                    (:paciente_id, :medico_id, :horario_id, CAST(:fecha AS date),
                     CAST(:hora_inicio AS time), CAST(:hora_fin AS time),
                     :estado, :motivo, :notas, :created_by)
                 RETURNING id"
            );
            $stmt->execute([
                ':paciente_id' => $pacienteId,
                ':medico_id' => $medicoId,
                ':horario_id' => $horarioId,
                ':fecha' => $fecha,
                ':hora_inicio' => (string) $horario['hora_inicio'],
                ':hora_fin' => (string) $horario['hora_fin'],
                ':estado' => $estado,
                ':motivo' => $motivo !== '' ? $motivo : null,
                ':notas' => $notas !== '' ? $notas : null,
                ':created_by' => $createdBy,
            ]);

            $citaId = (int) $stmt->fetchColumn();

            $this->db->commit();

            return $citaId;
        } catch (PDOException $e) {
            $this->rollBackIfNeeded();

            if ($this->esViolacionUnica($e)) {
                throw new RuntimeException(
                    'El bloque horario acaba de ser reservado por otra operación. Recargue la disponibilidad.',
                    self::ERR_SLOT_OCUPADO,
                    $e
                );
            }

            throw $e;
        } catch (Throwable $e) {
            $this->rollBackIfNeeded();
            throw $e;
        }
    }

    /**
     * Cambia el estado de una cita respetando las transiciones permitidas (RN06).
     *
     * RN12: al establecer 'Cancelada' no se elimina ni modifica nada más; el
     * índice parcial único deja de considerar la cita y el bloque vuelve a estar
     * disponible de inmediato.
     *
     * @return bool false si la cita no existe.
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new RuntimeException('El estado indicado no es válido.', self::ERR_ESTADO_INVALIDO);
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT id, estado FROM citas WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $cita = $stmt->fetch();

            if (!$cita) {
                $this->db->rollBack();
                return false;
            }

            $estadoActual = (string) $cita['estado'];

            if ($estadoActual === $estado) {
                $this->db->commit();
                return true;
            }

            $permitidos = self::TRANSICIONES[$estadoActual] ?? [];

            if (!in_array($estado, $permitidos, true)) {
                throw new RuntimeException(
                    "No se puede cambiar una cita de '{$estadoActual}' a '{$estado}'.",
                    self::ERR_TRANSICION_INVALIDA
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE citas
                 SET estado = :estado, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $stmt->execute([
                ':estado' => $estado,
                ':id' => $id,
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->rollBackIfNeeded();
            throw $e;
        }
    }

    /**
     * Reprograma una cita a otro bloque/fecha dentro de una transacción (RN09).
     *
     * No permite reprogramar citas 'Atendida' ni 'Cancelada' (RN06).
     *
     * @param array $data fecha, horario_id y opcionalmente medico_id.
     * @return bool false si la cita no existe.
     */
    public function reprogramar(int $id, array $data): bool
    {
        $horarioId = (int) ($data['horario_id'] ?? 0);
        $fecha = trim((string) ($data['fecha'] ?? ''));

        if ($horarioId <= 0) {
            throw new InvalidArgumentException('Debe indicar el nuevo bloque horario.');
        }

        $fechaObj = $this->validarFecha($fecha);

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("SELECT * FROM citas WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $id]);
            $cita = $stmt->fetch();

            if (!$cita) {
                $this->db->rollBack();
                return false;
            }

            $estadoActual = (string) $cita['estado'];

            if (in_array($estadoActual, self::ESTADOS_TERMINALES, true)) {
                throw new RuntimeException(
                    "Una cita en estado '{$estadoActual}' no puede reprogramarse.",
                    self::ERR_TRANSICION_INVALIDA
                );
            }

            $medicoId = isset($data['medico_id']) && (int) $data['medico_id'] > 0
                ? (int) $data['medico_id']
                : (int) $cita['medico_id'];

            $horario = $this->lockHorario($horarioId, $medicoId);

            if ((string) $horario['estado'] !== 'Activo') {
                throw new RuntimeException('El bloque horario seleccionado está inactivo.', self::ERR_HORARIO_INVALIDO);
            }

            if ((string) $horario['medico_estado'] !== 'Activo') {
                throw new RuntimeException(
                    'El médico está inactivo y no puede recibir nuevas citas.',
                    self::ERR_MEDICO_INACTIVO
                );
            }

            if ((int) $horario['dia_semana'] !== (int) $fechaObj->format('N')) {
                throw new RuntimeException(
                    'La fecha seleccionada no corresponde a un día de atención del médico.',
                    self::ERR_HORARIO_INVALIDO
                );
            }

            $this->validarNoPasada($fechaObj, (string) $horario['hora_inicio']);

            $conflicto = $this->findConflicto(
                $medicoId,
                $fecha,
                $horarioId,
                (string) $horario['hora_inicio'],
                (string) $horario['hora_fin'],
                $id
            );

            if ($conflicto !== null) {
                throw new RuntimeException(
                    'El nuevo bloque horario ya está reservado para este médico.',
                    self::ERR_SLOT_OCUPADO
                );
            }

            $stmt = $this->db->prepare(
                "UPDATE citas
                 SET medico_id = :medico_id,
                     horario_id = :horario_id,
                     fecha = CAST(:fecha AS date),
                     hora_inicio = CAST(:hora_inicio AS time),
                     hora_fin = CAST(:hora_fin AS time),
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $stmt->execute([
                ':medico_id' => $medicoId,
                ':horario_id' => $horarioId,
                ':fecha' => $fecha,
                ':hora_inicio' => (string) $horario['hora_inicio'],
                ':hora_fin' => (string) $horario['hora_fin'],
                ':id' => $id,
            ]);

            $this->db->commit();

            return true;
        } catch (PDOException $e) {
            $this->rollBackIfNeeded();

            if ($this->esViolacionUnica($e)) {
                throw new RuntimeException(
                    'El nuevo bloque horario acaba de ser reservado por otra operación. Recargue la disponibilidad.',
                    self::ERR_SLOT_OCUPADO,
                    $e
                );
            }

            throw $e;
        } catch (Throwable $e) {
            $this->rollBackIfNeeded();
            throw $e;
        }
    }

    /**
     * Atajo para cancelar una cita (libera el bloque, RN12).
     */
    public function cancelar(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_CANCELADA);
    }

    /**
     * Atajo para marcar una cita como atendida.
     */
    public function marcarAtendida(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_ATENDIDA);
    }

    /**
     * Atajo para registrar la inasistencia del paciente.
     */
    public function marcarNoAsistio(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_NO_ASISTIO);
    }

    /**
     * Bloquea y devuelve el bloque horario junto con el estado del médico.
     * El bloqueo FOR UPDATE serializa las reservas concurrentes del mismo bloque.
     */
    private function lockHorario(int $horarioId, int $medicoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.id, h.medico_id, h.dia_semana, h.hora_inicio, h.hora_fin, h.estado,
                    m.estado AS medico_estado
             FROM horarios h
             INNER JOIN medicos m ON m.id = h.medico_id
             WHERE h.id = :horario_id
               AND h.medico_id = :medico_id
             FOR UPDATE OF h"
        );
        $stmt->execute([
            ':horario_id' => $horarioId,
            ':medico_id' => $medicoId,
        ]);

        $horario = $stmt->fetch();

        if (!$horario) {
            throw new RuntimeException(
                'El bloque horario no existe o no pertenece al médico seleccionado.',
                self::ERR_HORARIO_INVALIDO
            );
        }

        return $horario;
    }

    private function assertPacienteActivo(int $pacienteId): void
    {
        $stmt = $this->db->prepare("SELECT estado FROM pacientes WHERE id = :id");
        $stmt->execute([':id' => $pacienteId]);
        $estado = $stmt->fetchColumn();

        if ($estado === false) {
            throw new RuntimeException('El paciente seleccionado no existe.', self::ERR_PACIENTE_INVALIDO);
        }

        if ((string) $estado !== 'Activo') {
            throw new RuntimeException(
                'El paciente está inactivo y no puede recibir nuevas citas.',
                self::ERR_PACIENTE_INVALIDO
            );
        }
    }

    private function validarFecha(string $fecha): DateTimeImmutable
    {
        $fechaObj = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);

        if ($fechaObj === false || $fechaObj->format('Y-m-d') !== $fecha) {
            throw new RuntimeException(
                'La fecha de la cita no es válida (formato esperado YYYY-MM-DD).',
                self::ERR_FECHA_INVALIDA
            );
        }

        return $fechaObj;
    }

    private function validarNoPasada(DateTimeImmutable $fecha, string $horaInicio): void
    {
        $hoy = new DateTimeImmutable('today');

        if ($fecha < $hoy) {
            throw new RuntimeException(
                'No se puede agendar una cita en una fecha pasada.',
                self::ERR_FECHA_INVALIDA
            );
        }

        if ($fecha->format('Y-m-d') === $hoy->format('Y-m-d') && substr($horaInicio, 0, 8) <= date('H:i:s')) {
            throw new RuntimeException(
                'No se puede agendar una cita en un bloque horario que ya pasó.',
                self::ERR_FECHA_INVALIDA
            );
        }
    }

    private function rollBackIfNeeded(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    private function esViolacionUnica(PDOException $e): bool
    {
        return (string) $e->getCode() === '23505'
            || (isset($e->errorInfo[0]) && (string) $e->errorInfo[0] === '23505');
    }
}
