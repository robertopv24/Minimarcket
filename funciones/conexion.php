<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                // AHORA SÍ: Obtenemos credenciales desde el .env seguro
                $host     = $_ENV['DB_HOST'] ?? 'localhost';
                $dbname   = $_ENV['DB_NAME'] ?? 'minimarket';
                $username = $_ENV['DB_USER'] ?? 'root';
                $password = $_ENV['DB_PASSWORD'] ?? '';
                $port     = $_ENV['DB_PORT'] ?? '3306';

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                self::$connection = new PDO(
                    "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    $options
                );

            } catch (PDOException $e) {
                // Mensaje genérico por seguridad (no mostrar detalles de conexión al público)
                error_log("Error de conexión: " . $e->getMessage()); // Loguear el error real
                die("<h3>Error de conexión.</h3><p>Verifica tu archivo .env y los logs del servidor.</p>");
            }
        }

        return self::$connection;
    }
}
?>
