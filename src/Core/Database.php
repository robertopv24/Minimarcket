<?php

namespace Minimarcket\Core;

use PDO;
use PDOException;

class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $dbname = $_ENV['DB_NAME'] ?? 'minimarket';
                $username = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';
                $port = $_ENV['DB_PORT'] ?? '3306';

                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                self::$connection = new PDO(
                    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    $options
                );

            } catch (PDOException $e) {
                error_log("DB Connection Error: " . $e->getMessage());
                die("<h3>Error de conexión (Core).</h3><p>Verifica tu archivo .env y los logs del servidor.</p>");
            }
        }
        return self::$connection;
    }
}
