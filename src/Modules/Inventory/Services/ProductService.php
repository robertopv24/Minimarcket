<?php

namespace Minimarcket\Modules\Inventory\Services;

use Minimarcket\Modules\Inventory\Repositories\ProductRepository;
use Minimarcket\Modules\Inventory\Repositories\RawMaterialRepository;
use Exception;

/**
 * Class ProductService
 * 
 * Servicio de lógica de negocio para productos.
 */
class ProductService
{
    protected ProductRepository $productRepo;
    protected RawMaterialRepository $rawMaterialRepo;

    public function __construct(ProductRepository $productRepo, RawMaterialRepository $rawMaterialRepo)
    {
        $this->productRepo = $productRepo;
        $this->rawMaterialRepo = $rawMaterialRepo;
    }

    public function createProduct(string $name, string $description, float $price_usd, float $price_ves, float $stock, string $image_url = 'default.jpg', float $profit_margin = 20.00): string
    {
        return $this->productRepo->create([
            'name' => $name,
            'description' => $description,
            'price_usd' => $price_usd,
            'price_ves' => $price_ves,
            'stock' => $stock,
            'image_url' => $image_url,
            'profit_margin' => $profit_margin
        ]);
    }

    public function getProductById(int $id): ?array
    {
        return $this->productRepo->find($id);
    }

    public function getTotalProduct(): int
    {
        return $this->productRepo->count();
    }

    public function updateProductPriceVes(int $productId, float $newPriceVes): bool
    {
        return $this->productRepo->updatePriceVes($productId, $newPriceVes);
    }

    public function getAllProducts(): array
    {
        return $this->productRepo->all();
    }

    public function searchProducts(string $keyword): array
    {
        return $this->productRepo->search($keyword);
    }

    public function getAvailableProducts(): array
    {
        return $this->productRepo->getAvailable();
    }

    public function getLowStockProducts(int $threshold = 5): array
    {
        return $this->productRepo->getLowStock($threshold);
    }

    public function updateProduct(int $id, string $name, string $description, float $price_usd, float $price_ves, float $stock, ?string $image = null, float $profit_margin = 20.00): bool
    {
        $data = [
            'name' => $name,
            'description' => $description,
            'price_usd' => $price_usd,
            'price_ves' => $price_ves,
            'stock' => $stock,
            'profit_margin' => $profit_margin
        ];

        if ($image !== null) {
            $data['image_url'] = $image;
        }

        return $this->productRepo->update($id, $data);
    }

    public function updateProductStock(int $id, float $stock): bool
    {
        return $this->productRepo->updateStock($id, $stock);
    }

    public function deleteProduct(int $id): bool
    {
        return $this->productRepo->delete($id);
    }

    public function updateAllPricesBasedOnRate(float $newRate): int
    {
        return $this->productRepo->updateAllPricesByRate($newRate);
    }

    public function updateProductType(int $id, string $type): bool
    {
        return $this->productRepo->update($id, ['type' => $type]);
    }

    // Métodos de componentes (requieren acceso a product_components table)
    public function getProductComponents(int $productId, int $depth = 0): array
    {
        // TODO: Implementar cuando migremos ProductionManager
        return [];
    }

    public function calculateProductCost(int $productId, int $depth = 0): float
    {
        // TODO: Implementar cuando migremos ProductionManager
        return 0.0;
    }

    public function addComponent(int $productId, string $type, int $componentId, float $qty): bool
    {
        // TODO: Implementar cuando migremos ProductionManager
        return false;
    }

    public function removeComponent(int $id): bool
    {
        // TODO: Implementar cuando migremos ProductionManager
        return false;
    }

    public function getVirtualStock(int $productId, int $depth = 0): float
    {
        // TODO: Implementar cuando migremos ProductionManager
        return 0.0;
    }

    public function getValidExtras(int $productId): array
    {
        // TODO: Implementar cuando tengamos tabla de extras
        return [];
    }

    public function addValidExtra(int $productId, int $rawMaterialId, ?float $priceOverride = null): bool
    {
        // TODO: Implementar cuando tengamos tabla de extras
        return false;
    }

    public function removeValidExtra(int $extraId): bool
    {
        // TODO: Implementar cuando tengamos tabla de extras
        return false;
    }
    public function reduceStock(int $id, float $qty): bool
    {
        return $this->productRepo->reduceStock($id, $qty);
    }

    public function addStockByPurchase(int $id, float $qty, float $newPriceUsd, float $newPriceVes): bool
    {
        return $this->productRepo->updateStockAndPrices($id, $qty, $newPriceUsd, $newPriceVes);
    }
}
