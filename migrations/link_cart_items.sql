-- Migración para soportar acompañantes como items independientes en el carrito
ALTER TABLE cart ADD COLUMN parent_cart_id INT(11) NULL DEFAULT NULL;
ALTER TABLE cart ADD COLUMN price_override DECIMAL(10,2) NULL DEFAULT NULL;

-- Asegurar que los pedidos también tengan la vinculación (opcional pero recomendado)
ALTER TABLE order_items ADD COLUMN parent_item_id INT(11) NULL DEFAULT NULL;
ALTER TABLE order_items ADD COLUMN price_override DECIMAL(10,2) NULL DEFAULT NULL;
