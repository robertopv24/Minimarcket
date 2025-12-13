<?php

namespace Minimarcket\Modules\HR\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class EmployeeRepository extends BaseRepository
{
    protected string $table = 'users';

    /**
     * Obtiene todos los usuarios que tienen salario configurado (empleados)
     */
    public function getEmployees(?string $filterRole = null): array
    {
        $sql = "SELECT id, name, email, role, phone, salary_amount, salary_frequency, job_role 
                FROM {$this->table} 
                WHERE salary_amount > 0";

        $params = [];
        if ($filterRole) {
            $sql .= " AND job_role = ?";
            $params[] = $filterRole;
        }

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEmployeeById(int $id): ?array
    {
        return $this->find($id);
    }
}
