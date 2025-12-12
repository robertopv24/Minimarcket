<?php

namespace Minimarcket\Core\Config;

use Minimarcket\Core\Database;
use PDO;
use Exception;
use PDOException;

class ConfigService
{
    private $db;
    private $settings = [];
    private $loaded = false;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function load()
    {
        if ($this->loaded) {
            return;
        }

        try {
            $query = "SELECT config_key, config_value FROM global_config";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($results) {
                foreach ($results as $row) {
                    $key = $row['config_key'];
                    $value = $row['config_value'];
                    $this->settings[$key] = $value;
                }
            }

            $this->loaded = true;
        } catch (Exception $e) {
            error_log("Excepción al cargar la configuración: " . $e->getMessage());
            $this->settings = [];
        }
    }

    public function get($key, $value = null)
    {
        $this->load();
        return $this->settings[$key] ?? $value;
    }

    public function update($key, $value)
    {
        if (empty($key)) {
            error_log("Error: La clave no puede estar vacía.");
            return false;
        }

        $query = "UPDATE global_config SET config_value = :value WHERE config_key = :key";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->bindValue(':value', $value, is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR);

            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            error_log("Configuración actualizada: key = " . $key . ", value = " . $value);
            return true;

        } catch (PDOException $e) {
            error_log("Excepción PDO: " . $e->getMessage());
            return false;
        }
    }

    public function getAll()
    {
        $this->load();
        return $this->settings;
    }

    public function setGlobals()
    {
        $this->load();
        foreach ($this->settings as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }
}
