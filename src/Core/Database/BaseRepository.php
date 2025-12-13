<?php

namespace Minimarcket\Core\Database;

use Exception;

/**
 * Class BaseRepository
 * 
 * Repositorio base que implementa lógica CRUD común.
 * Los repositorios de Módulos (ej: ProductRepository) heredarán de aquí.
 */
abstract class BaseRepository
{
    protected ConnectionManager $connection;
    protected string $table = '';

    public function __construct(ConnectionManager $connection)
    {
        $this->connection = $connection;
        if (empty($this->table)) {
            // Intenta deducir la tabla del nombre de la clase (Ej: UserRepository -> users)
            // Por simplicidad ahora, forzamos a definirla en la clase hija
            throw new Exception("La propiedad \$table debe estar definida en " . get_class($this));
        }
    }

    /**
     * Retorna una nueva instancia de QueryBuilder configurada para este repositorio.
     */
    protected function newQuery(): QueryBuilder
    {
        return (new QueryBuilder($this->connection))->table($this->table);
    }

    /**
     * Busca un registro por su ID primario.
     */
    public function find(int|string $id): ?array
    {
        return $this->newQuery()->where('id', '=', $id)->first();
    }

    /**
     * Retorna todos los registros.
     */
    public function all(): array
    {
        return $this->newQuery()->get();
    }

    /**
     * Crea un nuevo registro.
     */
    public function create(array $data): string
    {
        $query = $this->newQuery();
        $query->insert($data);
        return $query->lastInsertId();
    }

    /**
     * Actualiza un registro por ID.
     */
    public function update(int|string $id, array $data): bool
    {
        return $this->newQuery()->where('id', '=', $id)->update($data) > 0;
    }

    /**
     * Elimina un registro por ID.
     */
    public function delete(int|string $id): bool
    {
        return $this->newQuery()->where('id', '=', $id)->delete() > 0;
    }
}
