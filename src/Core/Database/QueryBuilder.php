<?php

namespace Minimarcket\Core\Database;

use PDO;
use Exception;

/**
 * Class QueryBuilder
 * 
 * Constructor de consultas SQL seguro y fluido.
 * Soporta cláusulas WHERE, ordenamientos, límites y operaciones CRUD básicas.
 */
class QueryBuilder
{
    protected ?PDO $pdo = null;

    protected string $table = '';
    protected array $select = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected array $orderBys = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    protected bool $debug = false;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->pdo = $connectionManager->getConnection();
    }

    /**
     * Define la tabla principal para la consulta.
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Define las columnas a seleccionar.
     */
    public function select(array $columns = ['*']): self
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Agrega una cláusula WHERE.
     * Uso: where('id', '=', 1) o where('status', 'active')
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'boolean' => 'AND'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Define el ordenamiento.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBys[] = compact('column', 'direction');
        return $this;
    }

    /**
     * Define el límite de registros.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Ejecuta la consulta SELECT y retorna todos los resultados.
     */
    public function get(): array
    {
        $sql = $this->compileSelect();
        return $this->runQuery($sql, $this->bindings)->fetchAll();
    }

    /**
     * Ejecuta la consulta SELECT y retorna el primer resultado.
     */
    public function first(): ?array
    {
        $this->limit(1);
        $sql = $this->compileSelect();
        $result = $this->runQuery($sql, $this->bindings)->fetch();
        return $result ?: null;
    }

    /**
     * Ejecuta una inserción de datos.
     */
    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $this->runQuery($sql, array_values($data));

        return true;
    }

    /**
     * Obtiene el ID del último registro insertado.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Ejecuta una actualización de datos con los WHERE actuales.
     */
    public function update(array $data): int
    {
        if (empty($this->wheres)) {
            // Protección simple para evitar updates masivos accidentales
            throw new Exception("No se permite UPDATE sin WHERE.");
        }

        $setPart = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            $setPart[] = "{$column} = ?";
            $bindings[] = $value;
        }

        // Agregar los bindings del WHERE al final
        $bindings = array_merge($bindings, $this->bindings);

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setPart) . $this->compileWheres();

        $stmt = $this->runQuery($sql, $bindings);
        return $stmt->rowCount();
    }

    /**
     * Ejecuta un borrado con los WHERE actuales.
     */
    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new Exception("No se permite DELETE sin WHERE.");
        }

        $sql = "DELETE FROM {$this->table}" . $this->compileWheres();
        $stmt = $this->runQuery($sql, $this->bindings);
        return $stmt->rowCount();
    }

    // --- Métodos Internos de Compilación ---

    protected function compileSelect(): string
    {
        if (empty($this->table)) {
            throw new Exception("Tabla no definida para SELECT.");
        }

        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";
        $sql .= $this->compileWheres();

        if (!empty($this->orderBys)) {
            $orders = array_map(function ($order) {
                return "{$order['column']} {$order['direction']}";
            }, $this->orderBys);
            $sql .= " ORDER BY " . implode(', ', $orders);
        }

        if ($this->limit) {
            $sql .= " LIMIT {$this->limit}";
        }

        return $sql;
    }

    protected function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $sql = " WHERE ";
        foreach ($this->wheres as $i => $where) {
            if ($i > 0) {
                $sql .= " {$where['boolean']} ";
            }
            $sql .= "{$where['column']} {$where['operator']} ?";
        }
        return $sql;
    }

    protected function runQuery(string $sql, array $bindings)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt;
        } catch (\PDOException $e) {
            throw new Exception("Error SQL: " . $e->getMessage() . " [SQL: {$sql}]");
        }
    }
}
