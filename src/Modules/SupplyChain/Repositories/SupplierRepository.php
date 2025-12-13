<?php

namespace Minimarcket\Modules\SupplyChain\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class SupplierRepository extends BaseRepository
{
    protected string $table = 'suppliers';

    public function search(string $query = ''): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE name LIKE ? OR contact_person LIKE ? OR email LIKE ?";
            $term = "%$query%";
            $params = [$term, $term, $term];
        }

        $sql .= " ORDER BY name ASC";

        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
