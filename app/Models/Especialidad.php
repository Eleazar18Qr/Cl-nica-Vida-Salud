<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class Especialidad
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function all(string $search = '', ?string $estado = 'Activo'): array
    {
        $sql = "SELECT * FROM especialidades WHERE 1=1";
        $params = [];

        if ($estado !== null && $estado !== '') {
            $sql .= " AND estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($search !== '') {
            $sql .= " AND (nombre ILIKE :search OR descripcion ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM especialidades WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $especialidad = $stmt->fetch();

        return $especialidad ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO especialidades (nombre, descripcion, estado)
             VALUES (:nombre, :descripcion, :estado)
             RETURNING id"
        );
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?: null,
            ':estado' => $data['estado'] ?? 'Activo',
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE especialidades
             SET nombre = :nombre, descripcion = :descripcion, estado = :estado
             WHERE id = :id"
        );

        return $stmt->execute([
            ':nombre' => $data['nombre'],
            ':descripcion' => $data['descripcion'] ?: null,
            ':estado' => $data['estado'] ?? 'Activo',
            ':id' => $id,
        ]);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE especialidades SET estado = 'Inactivo' WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE especialidades SET estado = 'Activo' WHERE id = :id");

        return $stmt->execute([':id' => $id]);
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM especialidades WHERE id = :id");
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function existsNombre(string $nombre, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM especialidades WHERE nombre = :nombre";
        $params = [':nombre' => $nombre];

        if ($excludeId !== null) {
            $sql .= " AND id <> :id";
            $params[':id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function countActiveDoctors(int $id): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM medicos WHERE especialidad_id = :id AND estado = 'Activo'"
        );
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function activeForSelect(): array
    {
        return $this->db->query(
            "SELECT id, nombre FROM especialidades WHERE estado = 'Activo' ORDER BY nombre"
        )->fetchAll();
    }

    public function allForSelect(): array
    {
        return $this->db->query(
            "SELECT id, nombre, estado FROM especialidades ORDER BY nombre"
        )->fetchAll();
    }

    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM especialidades WHERE estado = 'Activo'");

        return (int) $stmt->fetchColumn();
    }
}
