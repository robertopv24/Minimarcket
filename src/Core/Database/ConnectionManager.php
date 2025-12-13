<?php

namespace Minimarcket\Core\Database;

use PDO;
use PDOException;
use Exception;

/**
 * Class ConnectionManager
 * 
 * Gestiona la conexión a la base de datos PDO.
 * En el futuro soportará múltiples conexiones por tenant.
 * Por ahora conecta a la DB global definida en config.
 */
class ConnectionManager
{
    protected ?PDO $connection = null;

    /**
     * @var array Configuración de conexión
     */
    protected array $config = [];

    public function __construct(array $config = [])
    {
        if (!isset($config['host'], $config['database'], $config['username'], $config['password'])) {
            throw new Exception("ConnectionManager requires database configuration.");
        }
        $this->config = $config;
    }

    /**
     * Obtiene la conexión PDO activa.
     * Implementa Lazy Loading (conecta solo al pedirla).
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    protected function connect(): void
    {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);

        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Cierra la conexión PDO
     */
    public function close(): void
    {
        $this->connection = null;
    }
}
