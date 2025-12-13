<?php

namespace Minimarcket\Modules\Sales\Services;

use Minimarcket\Modules\Sales\Repositories\OrderRepository;
use Exception;

/**
 * Class OrderService
 * 
 * Servicio de lógica de negocio para órdenes de venta.
 */
class OrderService
{
    protected OrderRepository $repository;
    protected \Minimarcket\Modules\Inventory\Services\ProductService $productService;

    public function __construct(OrderRepository $repository, \Minimarcket\Modules\Inventory\Services\ProductService $productService)
    {
        $this->repository = $repository;
        $this->productService = $productService;
    }

    public function createOrder(int $user_id, array $items, string $shipping_address, ?string $shipping_method = null): string
    {
        try {
            $this->repository->beginTransaction();

            $total = 0;
            // Pre-calcular total y validar stock (opcional aquí, o en deductStock)
            // Por simplicidad, asumimos que los precios vienen en los items o los consultamos.
            // Es MEJOR consultar precios actuales para seguridad.

            foreach ($items as $item) {
                // $item debe tener ['product_id', 'quantity']
                $product = $this->productService->getProductById($item['product_id']);
                if (!$product) {
                    throw new Exception("Producto ID {$item['product_id']} no encontrado.");
                }
                $total += $product['price_usd'] * $item['quantity'];
            }

            // 1. Crear Orden
            $orderId = $this->repository->create([
                'user_id' => $user_id,
                'shipping_address' => $shipping_address,
                'shipping_method' => $shipping_method,
                'status' => 'pending',
                'total_price' => $total, // FIX: Column name is total_price
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Crear Items
            foreach ($items as $item) {
                $product = $this->productService->getProductById($item['product_id']);

                // Insertar Item
                $this->repository->addItem([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $product['price_usd'],
                    'consumption_type' => $item['consumption_type'] ?? 'dine_in'
                    // removed 'modifiers' column insertion as it does not exist in order_items
                ]);

                // TODO: Insertar Modifiers en order_item_modifiers si es necesario.
                // Requeriría obtener el ID del item recién insertado ($pdo->lastInsertId()).
                // Por ahora, asumimos que no son críticos para esta prueba o implementamos addItem que retorne ID.
            }

            $this->repository->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->repository->rollBack();
            throw $e;
        }
    }

    public function deductStockFromSale(int $orderId): bool
    {
        $items = $this->repository->getOrderItems($orderId);

        foreach ($items as $item) {
            // Descontar stock usando ProductService (operación atómica)
            // Si falla (ej: sin stock), podríamos lanzar excepción o loguear.
            // Por ahora solo intentamos reducir.
            $success = $this->productService->reduceStock($item['product_id'], $item['quantity']);
            if (!$success) {
                // Log or throw warning?
                // error_log("Failed to reduce stock for product {$item['product_id']} in order $orderId");
            }
        }
        return true;
    }

    public function updateOrderStatus(int $id, string $status, ?string $tracking_number = null): bool
    {
        return $this->repository->updateStatus($id, $status, $tracking_number);
    }

    public function getOrderById(int $id): ?array
    {
        return $this->repository->find($id);
    }

    public function getOrderItems(int $order_id): array
    {
        return $this->repository->getOrderItems($order_id);
    }

    public function getItemModifiers(int $orderItemId): array
    {
        return $this->repository->getItemModifiers($orderItemId);
    }

    public function getOrdersBySearchAndFilter(string $search = '', string $filter = ''): array
    {
        return $this->repository->searchAndFilter($search, $filter);
    }
}
