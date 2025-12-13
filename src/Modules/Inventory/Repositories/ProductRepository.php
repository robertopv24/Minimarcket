<?php

namespace Minimarcket\Modules\Inventory\Repositories;

use Minimarcket\Core\Database\BaseRepository;

/**
 * Class ProductRepository
 * 
 * Repositorio para gestión de productos de venta.
 */
class ProductRepository extends BaseRepository
{
    protected string $table = 'products';

    /**
     * Busca productos por palabra clave
     */
    public function search(string $keyword): array
    {
        return $this->newQuery()
            ->where('name', 'LIKE', "%{$keyword}%")
            ->get();
    }

    /**
     * Obtiene productos disponibles (con stock > 0)
     */
    public function getAvailable(): array
    {
        return $this->newQuery()
            ->where('stock', '>', 0)
            ->get();
    }

    /**
     * Obtiene productos con stock bajo
     */
    public function getLowStock(int $threshold = 5): array
    {
        return $this->newQuery()
            ->where('stock', '<=', $threshold)
            ->orderBy('stock', 'ASC')
            ->get();
    }

    /**
     * Cuenta el total de productos
     */
    public function count(): int
    {
        $result = $this->newQuery()
            ->select(['COUNT(*) as total'])
            ->first();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Actualiza solo el stock de un producto
     */
    public function updateStock(int $id, float $stock): bool
    {
        return $this->update($id, ['stock' => $stock]);
    }

    /**
     * Actualiza solo el precio en bolívares
     */
    public function updatePriceVes(int $id, float $priceVes): bool
    {
        return $this->update($id, ['price_ves' => $priceVes]);
    }

    /**
     * Actualiza todos los precios basados en una nueva tasa
     */
    public function updateAllPricesByRate(float $newRate): int
    {
        // Esto requiere una query raw más compleja
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("UPDATE {$this->table} SET price_ves = price_usd * ?");
        $stmt->execute([$newRate]);
        return $stmt->rowCount();
    }
    /**
     * Reduce el stock de un producto atómicamente
     */
    /**
     * Reduce el stock de un producto atómicamente
     */
    public function reduceStock(int $id, float $qty): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("UPDATE {$this->table} SET stock = stock - ? WHERE id = ? AND stock >= ?");
        return $stmt->execute([$qty, $id, $qty]) && $stmt->rowCount() > 0;
    }

    /**
     * Actualiza stock y precios de un producto (usado en compras)
     */
    public function updateStockAndPrices(int $id, float $qtyToAdd, float $newPriceUsd, float $newPriceVes): bool
    {
        $pdo = $this->connection->getConnection();
        $stmt = $pdo->prepare("UPDATE {$this->table} SET stock = stock + ?, price_usd = ?, price_ves = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$qtyToAdd, $newPriceUsd, $newPriceVes, $id]);
    }
}
