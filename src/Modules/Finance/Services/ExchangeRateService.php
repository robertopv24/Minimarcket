<?php

namespace Minimarcket\Modules\Finance\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;
use PDOException;

class ExchangeRateService
{
    private $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function getLatestRate()
    {
        try {
            $stmt = $this->pdo->query("SELECT rate FROM exchange_rate ORDER BY updated_at DESC LIMIT 1");
            $rate = $stmt->fetch(PDO::FETCH_ASSOC);
            return $rate ? floatval($rate['rate']) : 0;
        } catch (PDOException $e) {
            error_log("Error en getLatestRate: " . $e->getMessage());
            return 0;
        }
    }

    public function addRate($newRate)
    {
        if ($newRate <= 0) {
            return false;
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

    public function updateRate($newRate)
    {
        if ($newRate <= 0) {
            return false;
        }

        try {
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

    public function getRateHistory($limit = 10)
    {
        try {
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
