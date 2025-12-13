<?php

namespace Minimarcket\Modules\User\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use Minimarcket\Core\Database\ConnectionManager;

/**
 * Class UserRepository
 * 
 * Repositorio para la gestión de datos de usuarios.
 */
class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    /**
     * Busca usuarios por query (nombre o email)
     */
    public function search(string $query): array
    {
        return $this->newQuery()
            ->where('name', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * Busca un usuario por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->newQuery()
            ->where('email', '=', $email)
            ->first();
    }

    /**
     * Obtiene todos los usuarios administradores
     */
    public function getAllAdmins(): array
    {
        return $this->newQuery()
            ->where('role', '=', 'admin')
            ->get();
    }

    /**
     * Cuenta el total de usuarios
     */
    public function count(): int
    {
        $result = $this->newQuery()
            ->select(['COUNT(*) as total'])
            ->first();
        return (int) ($result['total'] ?? 0);
    }
}
