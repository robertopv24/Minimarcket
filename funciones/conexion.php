<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);





class Database {
    private static $instance = null;
    private $connection;
    private $host = "fdb1028.awardspace.net";
    private $dbname = "4560900_web";
    private $username = "4560900_web";
    private $password = "l{GB}Lg*7pwNGbcX";

    // Constructor privado (patrón Singleton)
    private function __construct() {
        try {
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Modo de fetch predeterminado
                PDO::ATTR_EMULATE_PREPARES   => false, // Deshabilitar la emulación de prepared statements
            ];
            $this->connection = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                $options
            );
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("No se pudo conectar a la base de datos. Contacte al administrador.");
        }
    }

    // Método para obtener la instancia única de la conexión
    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}

// Ejemplo de uso
// $db = Database::getConnection();
?>
