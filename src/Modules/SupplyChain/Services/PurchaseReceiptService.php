<?php

namespace Minimarcket\Modules\SupplyChain\Services;

use Minimarcket\Modules\SupplyChain\Repositories\PurchaseReceiptRepository;
use Minimarcket\Modules\SupplyChain\Repositories\PurchaseOrderRepository;
use Minimarcket\Modules\Inventory\Services\ProductService;
use Minimarcket\Modules\Inventory\Services\RawMaterialService;
use Minimarcket\Core\Config\ConfigService;
use Exception;

class PurchaseReceiptService
{
    private PurchaseReceiptRepository $repository;
    private PurchaseOrderRepository $orderRepository;
    private ?ProductService $productService;
    private ?RawMaterialService $rawMaterialService;
    private ?ConfigService $configService;

    public function __construct(
        PurchaseReceiptRepository $repository,
        PurchaseOrderRepository $orderRepository,
        ?ProductService $productService = null,
        ?ConfigService $configService = null,
        ?RawMaterialService $rawMaterialService = null
    ) {
        $this->repository = $repository;
        $this->orderRepository = $orderRepository;
        $this->productService = $productService;
        $this->configService = $configService;
        $this->rawMaterialService = $rawMaterialService;
    }

    public function createPurchaseReceipt($purchaseOrderId, $receiptDate)
    {
        try {
            $this->repository->beginTransaction();

            $currentStatus = $this->repository->getOrderForUpdate($purchaseOrderId);

            if (!$currentStatus) {
                throw new Exception("Orden de compra #$purchaseOrderId no encontrada.");
            }
            if ($currentStatus === 'received') {
                throw new Exception("Esta orden ya fue recibida anteriormente.");
            }
            if ($currentStatus === 'canceled' || $currentStatus === 'cancelled') {
                throw new Exception("No se puede recibir una orden cancelada.");
            }

            // Crear Receipt
            $receiptId = $this->repository->create([
                'purchase_order_id' => $purchaseOrderId,
                'receipt_date' => $receiptDate
            ]);

            // Procesar Items (Logic de Legacy: Actualizar Stock y Precios)
            $items = $this->orderRepository->getOrderItems($purchaseOrderId);

            $currentRate = $this->configService ? $this->configService->get('exchange_rate') : 1;
            if (!$currentRate)
                $currentRate = 1;

            foreach ($items as $item) {
                // Item puede ser Product o RawMaterial
                $itemType = $item['item_type'] ?? 'product';
                $itemId = $item['item_id'] ?? $item['product_id'];

                if ($itemType === 'product') {
                    if (!$this->productService)
                        continue;

                    $product = $this->productService->getProductById($itemId);
                    if ($product) {
                        // Calcular nuevo precio
                        $margin = $product['profit_margin'];
                        $cost = $item['unit_price'];

                        $newPriceUsd = $cost * (1 + ($margin / 100));
                        $newPriceVes = $newPriceUsd * $currentRate;

                        // Añadir stock (Ahora es el único lugar donde se añade)
                        $this->productService->addStockByPurchase($itemId, $item['quantity'], $newPriceUsd, $newPriceVes);

                    } else {
                        throw new Exception("Producto no encontrado ID: " . $itemId);
                    }
                } elseif ($itemType === 'raw_material') {
                    if (!$this->rawMaterialService)
                        continue;

                    // Usamos RawMaterialService para añadir stock (promediando costo)
                    // Nota: RawMaterialService->addStock actualiza precio promedio
                    $this->rawMaterialService->addStock($itemId, $item['quantity'], $item['unit_price']);
                }
            }

            $this->repository->updateOrderStatus($purchaseOrderId, 'received');

            $this->repository->commit();
            return $receiptId;

        } catch (Exception $e) {
            if ($this->repository->inTransaction())
                $this->repository->rollBack();
            error_log("Error en recepción: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPurchaseReceiptById($id)
    {
        return $this->repository->find($id);
    }

    public function getAllPurchaseReceipts()
    {
        return $this->repository->getReceiptsWithDetails();
    }
}
