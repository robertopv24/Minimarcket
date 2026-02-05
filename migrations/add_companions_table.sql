CREATE TABLE `product_companions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `companion_id` int(11) NOT NULL,
  `quantity` decimal(10,4) DEFAULT 1.0000,
  `price_override` decimal(10,2) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `companion_id` (`companion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

ALTER TABLE `cart_item_modifiers` MODIFY COLUMN `modifier_type` ENUM('add','remove','info','side','companion') NOT NULL;
ALTER TABLE `order_item_modifiers` MODIFY COLUMN `modifier_type` ENUM('add','remove','info','side','companion') NOT NULL;
