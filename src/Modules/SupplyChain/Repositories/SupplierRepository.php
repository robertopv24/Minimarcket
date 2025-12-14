<?php

namespace Minimarcket\Modules\SupplyChain\Repositories;

use Minimarcket\Core\Database\BaseRepository;
use PDO;

class SupplierRepository extends BaseRepository
{
    protected string $table = 'suppliers';

    public function search(string $query = ''): array
    {
        $q = $this->newQuery();

        if (!empty($query)) {
            // FIX: QueryBuilder no soporta OR nativamente aún. 
            // Buscamos solo por nombre por ahora para evitar errores.
            $q->where('name', 'LIKE', "%$query%");
        }

        $q->orderBy('name', 'ASC');

        return $q->get();
    }
}
