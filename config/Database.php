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
        $host = 'dpg-dbf9578ae00c73f7hing-a.virginia-postgres.render.com';
        $port = '5432';
        $dbname = 'bd_clinica_vida_salud';
        $user = 'bd_clinica_vida_salud_user';
        $password = 'EMvyKEuI2s1YCtULy1Czk6mwqgLMzVYz';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

        try {
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $this->connection->exec("SET client_encoding TO 'UTF8'");
        } catch (PDOException $e) {
            throw new RuntimeException(
                'No se pudo conectar a la base de datos: ' . $e->getMessage(),
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

    private function __clone(): void
    {
    }

    public function __wakeup(): void
    {
        throw new RuntimeException('No se puede deserializar un Singleton.');
    }
}