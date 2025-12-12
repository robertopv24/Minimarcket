<?php

require_once __DIR__ . '/conexion.php';
// IMPORTANT: autoload.php handles the container loading now.
use Minimarcket\Core\Container;
use Minimarcket\Modules\Inventory\Services\ProductService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\Inventory\Services\ProductService instead.
 */
class ProductManager
{
    private $service;

    public function __construct($db = null)
    {
        // Ignore $db, get service from Container
        // If Container is not ready (weird edge case), fallback to direct instantiation?
        // Ideally we assume Container is ready via autoload.php
        $container = Container::getInstance();

        // Register if not registered (Lazy registration for this module)
        // Ideally this goes to a ServiceProvider, but for now we do it here or assume autoload handled it.
        // Let's use get() which auto-wires.
        try {
            $this->service = $container->get(ProductService::class);
        } catch (Exception $e) {
            // Fallback for non-container environments (unlikely if autoload is used)
            $this->service = new ProductService($db);
        }
    }

    public function createProduct($name, $description, $price_usd, $price_ves, $stock, $image_url = 'default.jpg', $profit_margin = 20.00)
    {
        return $this->service->createProduct($name, $description, $price_usd, $price_ves, $stock, $image_url, $profit_margin);
    }

    public function getProductById($id)
    {
        return $this->service->getProductById($id);
    }

    public function getTotalProduct()
    {
        return $this->service->getTotalProduct();
    }

    public function updateProductPriceVes($productId, $newPriceVes)
    {
        return $this->service->updateProductPriceVes($productId, $newPriceVes);
    }

    public function getAllProducts()
    {
        return $this->service->getAllProducts();
    }

    public function searchProducts($keyword)
    {
        return $this->service->searchProducts($keyword);
    }

    public function getAvailableProducts()
    {
        return $this->service->getAvailableProducts();
    }

    public function getLowStockProducts($threshold = 5)
    {
        return $this->service->getLowStockProducts($threshold);
    }

    public function updateProduct($id, $name, $description, $price_usd, $price_ves, $stock, $image = null, $profit_margin = 20.00)
    {
        return $this->service->updateProduct($id, $name, $description, $price_usd, $price_ves, $stock, $image, $profit_margin);
    }

    public function updateProductStock($id, $stock)
    {
        return $this->service->updateProductStock($id, $stock);
    }

    public function deleteProduct($id)
    {
        return $this->service->deleteProduct($id);
    }

    public function updateAllPricesBasedOnRate($newRate)
    {
        return $this->service->updateAllPricesBasedOnRate($newRate);
    }

    public function updateProductType($id, $type)
    {
        return $this->service->updateProductType($id, $type);
    }

    public function getProductComponents($productId, $depth = 0)
    {
        return $this->service->getProductComponents($productId, $depth);
    }

    public function calculateProductCost($productId, $depth = 0)
    {
        return $this->service->calculateProductCost($productId, $depth);
    }

    public function addComponent($productId, $type, $componentId, $qty)
    {
        return $this->service->addComponent($productId, $type, $componentId, $qty);
    }

    public function removeComponent($id)
    {
        return $this->service->removeComponent($id);
    }

    public function getVirtualStock($productId, $depth = 0)
    {
        return $this->service->getVirtualStock($productId, $depth);
    }

    public function getValidExtras($productId)
    {
        return $this->service->getValidExtras($productId);
    }

    public function addValidExtra($productId, $rawMaterialId, $priceOverride = null)
    {
        return $this->service->addValidExtra($productId, $rawMaterialId, $priceOverride);
    }

    public function removeValidExtra($extraId)
    {
        return $this->service->removeValidExtra($extraId);
    }
}