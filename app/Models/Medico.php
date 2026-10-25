<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use RuntimeException;
use Throwable;

class Medico
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(string $search = '', ?string $estado = 'Activo'): array
    {
        $sql = "SELECT m.id, m.usuario_id, m.especialidad_id, m.numero_colegiatura,
                       m.biografia, m.tarifa_consulta, m.estado,
                       u.nombres, u.apellidos, u.email, u.telefono,
                       u.documento_tipo, u.documento_numero,
                       e.nombre AS especialidad
                FROM medicos m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE 1=1";
        $params = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " AND m.estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($search !== '') {
            $sql .= " AND (u.nombres ILIKE :search OR u.apellidos ILIKE :search
                        OR u.email ILIKE :search OR u.documento_numero ILIKE :search
                        OR m.numero_colegiatura ILIKE :search OR e.nombre ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY u.apellidos, u.nombres";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT m.id, m.usuario_id, m.especialidad_id, m.numero_colegiatura,
                       m.biografia, m.tarifa_consulta, m.estado,
                       u.nombres, u.apellidos, u.email, u.telefono,
                       u.documento_tipo, u.documento_numero,
                       e.nombre AS especialidad
                FROM medicos m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE m.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $medico = $stmt->fetch();

        return $medico ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $sql = "SELECT m.id, m.usuario_id, m.especialidad_id, m.numero_colegiatura,
                       m.biografia, m.tarifa_consulta, m.estado,
                       u.nombres, u.apellidos, u.email, u.telefono,
                       e.nombre AS especialidad
                FROM medicos m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE m.usuario_id = :usuario_id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario_id' => $userId]);
        $medico = $stmt->fetch();

        return $medico ?: null;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $roleStmt = $this->db->prepare("SELECT id FROM roles WHERE nombre = 'Medico' LIMIT 1");
            $roleStmt->execute();
            $rolId = $roleStmt->fetchColumn();

            if ($rolId === false) {
                throw new RuntimeException('No se encontró el rol Medico.');
            }

            $userSql = "INSERT INTO usuarios
                            (rol_id, nombres, apellidos, email, password_hash, telefono,
                             documento_tipo, documento_numero, estado)
                        VALUES
                            (:rol_id, :nombres, :apellidos, :email, :password_hash, :telefono,
                             :documento_tipo, :documento_numero, :estado)
                        RETURNING id";

            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute([
                ':rol_id' => (int) $rolId,
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':email' => $data['email'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':telefono' => $data['telefono'] ?: null,
                ':documento_tipo' => $data['documento_tipo'],
                ':documento_numero' => $data['documento_numero'],
                ':estado' => $data['estado'] ?? 'Activo',
            ]);

            $usuarioId = (int) $userStmt->fetchColumn();

            $doctorSql = "INSERT INTO medicos
                              (usuario_id, especialidad_id, numero_colegiatura, biografia,
                               tarifa_consulta, estado)
                          VALUES
                              (:usuario_id, :especialidad_id, :numero_colegiatura, :biografia,
                               :tarifa_consulta, :estado)
                          RETURNING id";

            $doctorStmt = $this->db->prepare($doctorSql);
            $doctorStmt->execute([
                ':usuario_id' => $usuarioId,
                ':especialidad_id' => (int) $data['especialidad_id'],
                ':numero_colegiatura' => $data['numero_colegiatura'],
                ':biografia' => $data['biografia'] ?: null,
                ':tarifa_consulta' => $data['tarifa_consulta'],
                ':estado' => $data['estado'] ?? 'Activo',
            ]);

            $medicoId = (int) $doctorStmt->fetchColumn();

            $this->db->commit();

            return $medicoId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $medico = $this->find($id);

        if ($medico === null) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $userSql = "UPDATE usuarios SET
                            nombres = :nombres,
                            apellidos = :apellidos,
                            email = :email,
                            telefono = :telefono,
                            documento_tipo = :documento_tipo,
                            documento_numero = :documento_numero,
                            estado = :estado";

            $params = [
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':email' => $data['email'],
                ':telefono' => $data['telefono'] ?: null,
                ':documento_tipo' => $data['documento_tipo'],
                ':documento_numero' => $data['documento_numero'],
                ':estado' => $data['estado'] ?? 'Activo',
                ':id' => (int) $medico['usuario_id'],
            ];

            if (!empty($data['password'])) {
                $userSql .= ", password_hash = :password_hash";
                $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $userSql .= " WHERE id = :id";

            $userStmt = $this->db->prepare($userSql);
            $userStmt->execute($params);

            $doctorSql = "UPDATE medicos SET
                              especialidad_id = :especialidad_id,
                              numero_colegiatura = :numero_colegiatura,
                              biografia = :biografia,
                              tarifa_consulta = :tarifa_consulta,
                              estado = :estado
                          WHERE id = :id";

            $doctorStmt = $this->db->prepare($doctorSql);
            $doctorStmt->execute([
                ':especialidad_id' => (int) $data['especialidad_id'],
                ':numero_colegiatura' => $data['numero_colegiatura'],
                ':biografia' => $data['biografia'] ?: null,
                ':tarifa_consulta' => $data['tarifa_consulta'],
                ':estado' => $data['estado'] ?? 'Activo',
                ':id' => $id,
            ]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function softDelete(int $id): bool
    {
        $medico = $this->find($id);

        if ($medico === null) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE medicos SET estado = 'Inactivo' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->db->prepare("UPDATE usuarios SET estado = 'Inactivo' WHERE id = :id");
            $stmt->execute([':id' => (int) $medico['usuario_id']]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function restore(int $id): bool
    {
        $medico = $this->find($id);

        if ($medico === null) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE medicos SET estado = 'Activo' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->db->prepare("UPDATE usuarios SET estado = 'Activo' WHERE id = :id");
            $stmt->execute([':id' => (int) $medico['usuario_id']]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function existsEmail(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeUserId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeUserId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function existsDocumento(string $documento, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE documento_numero = :documento";
        $params = [':documento' => $documento];

        if ($excludeUserId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeUserId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function existsColegiatura(string $colegiatura, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM medicos WHERE numero_colegiatura = :colegiatura";
        $params = [':colegiatura' => $colegiatura];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function isActive(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM medicos WHERE id = :id AND estado = 'Activo'");
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM medicos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function activeForSelect(): array
    {
        $sql = "SELECT m.id, m.numero_colegiatura, u.nombres, u.apellidos, e.nombre AS especialidad
                FROM medicos m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                WHERE m.estado = 'Activo'
                ORDER BY u.apellidos, u.nombres";

        return $this->db->query($sql)->fetchAll();
    }

    public function allForSelect(): array
    {
        $sql = "SELECT m.id, m.numero_colegiatura, u.nombres, u.apellidos,
                       e.nombre AS especialidad, m.estado
                FROM medicos m
                INNER JOIN usuarios u ON u.id = m.usuario_id
                INNER JOIN especialidades e ON e.id = m.especialidad_id
                ORDER BY u.apellidos, u.nombres";

        return $this->db->query($sql)->fetchAll();
    }

    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM medicos WHERE estado = 'Activo'");

        return (int) $stmt->fetchColumn();
    }
}
