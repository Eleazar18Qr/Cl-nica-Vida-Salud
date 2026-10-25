<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Horario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(
        string $search = '',
        ?string $estado = 'Activo',
        ?int $medicoId = null,
        ?int $diaSemana = null
    ): array {
        $sql = "SELECT h.id, h.medico_id, h.dia_semana, h.hora_inicio, h.hora_fin, h.estado,
                       u.nombres, u.apellidos, e.nombre AS especialidad
                FROM horarios h
                INNER JOIN medicos m ON m.id = h.medico_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE 1=1";
        $params = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " AND h.estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($medicoId !== null) {
            $sql .= " AND h.medico_id = :medico_id";
            $params[':medico_id'] = $medicoId;
        }

        if ($diaSemana !== null) {
            $sql .= " AND h.dia_semana = :dia_semana";
            $params[':dia_semana'] = $diaSemana;
        }

        if ($search !== '') {
            $sql .= " AND (u.nombres ILIKE :search OR u.apellidos ILIKE :search
                        OR e.nombre ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY h.dia_semana, h.hora_inicio, u.apellidos";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT h.id, h.medico_id, h.dia_semana, h.hora_inicio, h.hora_fin, h.estado,
                       u.nombres, u.apellidos, e.nombre AS especialidad
                FROM horarios h
                INNER JOIN medicos m ON m.id = h.medico_id
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE h.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $horario = $stmt->fetch();

        return $horario ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO horarios (medico_id, dia_semana, hora_inicio, hora_fin, estado)
             VALUES (:medico_id, :dia_semana, :hora_inicio, :hora_fin, :estado)
             RETURNING id"
        );
        $stmt->execute([
            ':medico_id' => (int) $data['medico_id'],
            ':dia_semana' => (int) $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':estado' => $data['estado'] ?? 'Activo',
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE horarios
             SET medico_id = :medico_id,
                 dia_semana = :dia_semana,
                 hora_inicio = :hora_inicio,
                 hora_fin = :hora_fin,
                 estado = :estado
             WHERE id = :id"
        );

        return $stmt->execute([
            ':medico_id' => (int) $data['medico_id'],
            ':dia_semana' => (int) $data['dia_semana'],
            ':hora_inicio' => $data['hora_inicio'],
            ':hora_fin' => $data['hora_fin'],
            ':estado' => $data['estado'] ?? 'Activo',
            ':id' => $id,
        ]);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE horarios SET estado = 'Inactivo' WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE horarios SET estado = 'Activo' WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    public function hasOverlap(
        int $medicoId,
        int $diaSemana,
        string $horaInicio,
        string $horaFin,
        ?int $excludeId = null
    ): bool {
        $sql = "SELECT COUNT(*)
                FROM horarios
                WHERE medico_id = :medico_id
                  AND dia_semana = :dia_semana
                  AND estado = 'Activo'
                  AND (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)";
        $params = [
            ':medico_id' => $medicoId,
            ':dia_semana' => $diaSemana,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin,
        ];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countFutureAppointments(int $horarioId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM citas
             WHERE horario_id = :id
               AND fecha >= CURRENT_DATE
               AND estado IN ('Pendiente', 'Confirmada')"
        );
        $stmt->execute([':id' => $horarioId]);

        return (int) $stmt->fetchColumn();
    }

    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM horarios WHERE estado = 'Activo'");

        return (int) $stmt->fetchColumn();
    }

    public function countActiveByDoctor(int $medicoId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM horarios WHERE medico_id = :medico_id AND estado = 'Activo'"
        );
        $stmt->execute([':medico_id' => $medicoId]);

        return (int) $stmt->fetchColumn();
    }
}
