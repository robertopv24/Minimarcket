<?php

class MockStatement
{
    public $queryString;
    public $params = [];
    private $resultSequence = [];

    public function __construct($query)
    {
        $this->queryString = $query;
    }

    public function execute($params = [])
    {
        $this->params = $params;
        return true;
    }

    public function setReturnResult($result)
    {
        $this->resultSequence[] = $result;
    }

    public function rowCount()
    {
        // Mock default: 1 affected row.
        return 1;
    }

    // For advanced testing with callbacks
    private $resultCallback = null;

    public function setReturnResultCallback($callback)
    {
        $this->resultCallback = $callback;
    }

    public function fetchAll()
    {
        if ($this->resultCallback) {
            return call_user_func($this->resultCallback, $this->params);
        }
        return array_shift($this->resultSequence) ?? [];
    }

    public function fetch()
    {
        if ($this->resultCallback) {
            $res = call_user_func($this->resultCallback, $this->params);
            return $res ? $res[0] : null;
        }
        $res = array_shift($this->resultSequence);
        return $res ? $res[0] : null;
    }

    public function fetchColumn()
    {
        return array_shift($this->resultSequence) ?? null;
    }
}

class MockDatabase
{
    public $queries = [];
    public $statements = [];
    public $inTransaction = false;
    public $lastInsertId = 1;
    private $predefinedResults = []; // map query string to results

    public function inTransaction()
    {
        return $this->inTransaction;
    }

    public function beginTransaction()
    {
        $this->queries[] = "BEGIN TRANSACTION";
        $this->inTransaction = true;
    }

    public function commit()
    {
        $this->queries[] = "COMMIT";
        $this->inTransaction = false;
    }

    public function rollBack()
    {
        $this->queries[] = "ROLLBACK";
        $this->inTransaction = false;
    }

    public function prepare($sql)
    {
        $this->queries[] = "PREPARE: " . $sql;
        $stmt = new MockStatement($sql);

        // Asignar resultados predefinidos si existen para esta query
        // Implementación básica: verificar si la query contiene palabras clave
        // En una implementación real seria un map más estricto

        if (strpos($sql, 'SELECT oi.id as order_item_id') !== false) {
            // Simular items para deductStockFromSale
            // Esto se configurará desde el test
        }

        $this->statements[] = $stmt;
        return $stmt;
    }

    public function lastInsertId()
    {
        return $this->lastInsertId++;
    }

    // Helper para test
    public function getLastStatement()
    {
        return end($this->statements);
    }
}
?>