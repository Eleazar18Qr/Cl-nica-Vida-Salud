<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.id, u.rol_id, u.nombres, u.apellidos, u.email, u.password_hash,
                       u.estado, r.nombre AS rol, r.estado AS rol_estado
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.email = :email
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateLastAccess(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }
}
