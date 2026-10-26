<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $host = $this->env('DB_HOST', 'localhost');
        $port = $this->env('DB_PORT', '5432');
        $dbname = $this->env('DB_NAME', 'bd_clinica_vida_salud');
        $user = $this->env('DB_USER', 'postgres');
        $password = $this->env('DB_PASSWORD', 'postgres');

        // Se agrega sslmode=require para permitir la conexión segura exigida por Render
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

        try {
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->connection->exec("SET client_encoding TO 'UTF8'");
        } catch (PDOException $e) {
            $hostDebug = $this->env('DB_HOST', 'NO_DEFINIDO');
            throw new RuntimeException(
                "Error de conexión con Host [{$hostDebug}]: " . $e->getMessage(),
                0,               
                $e
            );
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function env(string $key, string $default): string
    {
        $value = getenv($key);

        return $value !== false && $value !== '' ? $value : $default;
    }

    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('No se puede deserializar un Singleton.');
    }
}