<?php
require_once '../funciones/config.php';

class ExchangeRate {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    // Obtener la tasa de cambio más reciente
    public function getLatestRate() {
        try {
            $stmt = $this->pdo->query("SELECT rate FROM exchange_rate ORDER BY updated_at DESC LIMIT 1");
            $rate = $stmt->fetch(PDO::FETCH_ASSOC);
            return $rate ? floatval($rate['rate']) : 0;
        } catch (PDOException $e) {
            error_log("Error en getLatestRate: " . $e->getMessage());
            return 0;
        }
    }

    // Agregar una nueva tasa de cambio
    public function addRate($newRate) {
        if ($newRate <= 0) {
            return false; // Evita valores negativos o inválidos
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO exchange_rate (rate) VALUES (:rate)");
            $stmt->bindParam(':rate', $newRate);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en addRate: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar la tasa de cambio más reciente
    public function updateRate($newRate) {
        if ($newRate <= 0) {
            return false;
        }

        try {
            // Debido a restricciones de MySQL al usar ORDER BY y LIMIT en un UPDATE, usamos un subquery para obtener el ID del registro más reciente.
            $sql = "UPDATE exchange_rate
                    SET rate = :rate, updated_at = NOW()
                    WHERE id = (
                        SELECT id FROM (
                            SELECT id FROM exchange_rate ORDER BY updated_at DESC LIMIT 1
                        ) as subquery
                    )";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':rate', $newRate);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error en updateRate: " . $e->getMessage());
            return false;
        }
    }

    // Obtener historial de tasas de cambio
    public function getRateHistory($limit = 10) {
        try {
            // Debido a problemas al enlazar parámetros para LIMIT, se recomienda concatenar el valor (siempre sanitizado)
            $limit = intval($limit);
            $sql = "SELECT rate, updated_at FROM exchange_rate ORDER BY updated_at DESC LIMIT $limit";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getRateHistory: " . $e->getMessage());
            return [];
        }
    }
}
?>
