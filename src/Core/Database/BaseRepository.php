<?php

namespace Minimarcket\Core\Database;

use Exception;

use Minimarcket\Core\Tenant\TenantContext;

/**
 * Class BaseRepository
 * 
 * Repositorio base que implementa lógica CRUD común.
 * Los repositorios de Módulos (ej: ProductRepository) heredarán de aquí.
 * 
 * SAAS UPDATE: Ahora impone aislamiento de Tenant automáticamente.
 */
abstract class BaseRepository
{
    protected ConnectionManager $connection;
    protected string $table = '';

    public function __construct(ConnectionManager $connection)
    {
        $this->connection = $connection;
        if (empty($this->table)) {
            throw new Exception("La propiedad \$table debe estar definida en " . get_class($this));
        }
    }

    /**
     * Retorna una nueva instancia de QueryBuilder configurada para este repositorio.
     * AUTOMÁTICAMENTE FILTRA POR TENANT_ID.
     */
    protected function newQuery(): QueryBuilder
    {
        $query = (new QueryBuilder($this->connection))->table($this->table);

        // SAAS ENFORCEMENT: Filtrar siempre por el Tenant actual
        $tenantId = TenantContext::getTenantId();
        $query->where('tenant_id', '=', $tenantId);

        return $query;
    }

    /**
     * Busca un registro por su ID primario.
     */
    public function find(int|string $id): ?array
    {
        return $this->newQuery()->where('id', '=', $id)->first();
    }

    /**
     * Retorna todos los registros (del tenant actual).
     */
    public function all(): array
    {
        return $this->newQuery()->get();
    }

    /**
     * Crea un nuevo registro.
     * AUTOMÁTICAMENTE INYECTA TENANT_ID.
     */
    public function create(array $data): string
    {
        // SAAS ENFORCEMENT: Asegurar que el registro pertenece al tenant actual
        if (!isset($data['tenant_id'])) {
            $data['tenant_id'] = TenantContext::getTenantId();
        }

        $query = $this->newQuery();
        // Nota: newQuery ya tiene el WHERE, pero para INSERT el QueryBuilder
        // debe saber ignorarlo o manejarlo. Revisaremos QueryBuilder si insert() usa el WHERE (usualmente no).
        // Sin embargo, BaseRepository usa newQuery() que retorna un builder con WHERE set.
        // Si QueryBuilder::insert() ignora el stack de WHERE, estamos bien.
        // Si generara "INSERT ... WHERE ...", seria error SQL.
        // Asumimos QueryBuilder estándar. Si falla, usaremos una instancia limpia.

        // CORRECCIÓN PREVENTIVA: Usar builder limpio para insert, solo con tabla.
        $builder = (new QueryBuilder($this->connection))->table($this->table);
        $builder->insert($data);

        return $builder->lastInsertId();
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
