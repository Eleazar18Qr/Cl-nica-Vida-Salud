<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;
use Throwable;

class Paciente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(string $search = '', ?string $estado = 'Activo'): array
    {
        $sql = "SELECT * FROM pacientes WHERE 1=1";
        $params = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($search !== '') {
            $sql .= " AND (documento_numero ILIKE :search OR nombres ILIKE :search
                        OR apellidos ILIKE :search OR email ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY apellidos, nombres";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pacientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $paciente = $stmt->fetch();

        return $paciente ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pacientes WHERE usuario_id = :usuario_id LIMIT 1");
        $stmt->execute([':usuario_id' => $userId]);
        $paciente = $stmt->fetch();

        return $paciente ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO pacientes (
                    documento_tipo, documento_numero, nombres, apellidos,
                    fecha_nacimiento, genero, email, telefono, direccion,
                    grupo_sanguineo, alergias, contacto_emergencia_nombre,
                    contacto_emergencia_telefono, estado
                ) VALUES (
                    :documento_tipo, :documento_numero, :nombres, :apellidos,
                    :fecha_nacimiento, :genero, :email, :telefono, :direccion,
                    :grupo_sanguineo, :alergias, :contacto_emergencia_nombre,
                    :contacto_emergencia_telefono, :estado
                ) RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':documento_tipo' => $data['documento_tipo'],
            ':documento_numero' => $data['documento_numero'],
            ':nombres' => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':genero' => $data['genero'],
            ':email' => $data['email'] ?: null,
            ':telefono' => $data['telefono'] ?: null,
            ':direccion' => $data['direccion'] ?: null,
            ':grupo_sanguineo' => $data['grupo_sanguineo'] ?: null,
            ':alergias' => $data['alergias'] ?: null,
            ':contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?: null,
            ':contacto_emergencia_telefono' => $data['contacto_emergencia_telefono'] ?: null,
            ':estado' => $data['estado'] ?? 'Activo',
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE pacientes SET
                    documento_tipo = :documento_tipo,
                    documento_numero = :documento_numero,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    fecha_nacimiento = :fecha_nacimiento,
                    genero = :genero,
                    email = :email,
                    telefono = :telefono,
                    direccion = :direccion,
                    grupo_sanguineo = :grupo_sanguineo,
                    alergias = :alergias,
                    contacto_emergencia_nombre = :contacto_emergencia_nombre,
                    contacto_emergencia_telefono = :contacto_emergencia_telefono,
                    estado = :estado
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':documento_tipo' => $data['documento_tipo'],
            ':documento_numero' => $data['documento_numero'],
            ':nombres' => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':fecha_nacimiento' => $data['fecha_nacimiento'],
            ':genero' => $data['genero'],
            ':email' => $data['email'] ?: null,
            ':telefono' => $data['telefono'] ?: null,
            ':direccion' => $data['direccion'] ?: null,
            ':grupo_sanguineo' => $data['grupo_sanguineo'] ?: null,
            ':alergias' => $data['alergias'] ?: null,
            ':contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?: null,
            ':contacto_emergencia_telefono' => $data['contacto_emergencia_telefono'] ?: null,
            ':estado' => $data['estado'] ?? 'Activo',
            ':id' => $id,
        ]);
    }

    public function softDelete(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE pacientes SET estado = 'Inactivo' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->db->prepare(
                "UPDATE usuarios SET estado = 'Inactivo'
                 WHERE id = (SELECT usuario_id FROM pacientes WHERE id = :id AND usuario_id IS NOT NULL)"
            );
            $stmt->execute([':id' => $id]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function restore(int $id): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("UPDATE pacientes SET estado = 'Activo' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $stmt = $this->db->prepare(
                "UPDATE usuarios SET estado = 'Activo'
                 WHERE id = (SELECT usuario_id FROM pacientes WHERE id = :id AND usuario_id IS NOT NULL)"
            );
            $stmt->execute([':id' => $id]);

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function existsDocumento(string $documento, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM pacientes WHERE documento_numero = :documento";
        $params = [':documento' => $documento];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM pacientes WHERE estado = 'Activo'");

        return (int) $stmt->fetchColumn();
    }
}
