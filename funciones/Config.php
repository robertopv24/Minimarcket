<?php
// config.php - Configuración global del sistema

class GlobalConfig
{
    private $db;
    private $settings = [];
    private $loaded = false;

    public function __construct()
    {
        $this->db = Database::getConnection();
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
                    $this->settings[$key] = $value; // Corregido: Asignar valores a $this->settings
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
        // Validación de parámetros
        if (empty($key)) {
            error_log("Error: La clave no puede estar vacía.");
            return false;
        }

        // Preparación de la consulta SQL (UPSERT)
        // Usamos VALUES(config_value) para evitar repetir el placeholder :value con emulated prepares = false
        $query = "INSERT INTO global_config (config_key, config_value) 
                  VALUES (:key, :value) 
                  ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";

        error_log("Consulta SQL (UPSERT): " . $query);
        error_log("Parámetros: key = " . print_r($key, true) . ", value = " . print_r($value, true));

        try {
            $stmt = $this->db->prepare($query);

            // Vinculación de parámetros
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->bindValue(':value', $value, PDO::PARAM_STR);

            // Ejecución de la consulta
            if (!$stmt->execute()) {
                error_log("Error al ejecutar la consulta: " . implode(", ", $stmt->errorInfo()));
                return false;
            }

            // Registro de evento exitoso
            error_log("Configuración actualizada: key = " . $key . ", value = " . $value);

            return true;

        } catch (PDOException $e) {
            error_log("Excepción PDO: " . $e->getMessage());
            error_log("Stack Trace: " . $e->getTraceAsString());
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

$config = new GlobalConfig();
$config->setGlobals();
?>