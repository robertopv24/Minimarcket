<?php

namespace Minimarcket\Modules\Inventory\Models;

class Product
{
    public int $id;
    public string $name;
    public ?string $description;
    public float $price_usd;
    public float $price_ves;
    public int $stock;
    public string $product_type;
    public float $profit_margin;
    public string $image_url;

    public static function fromArray(array $data): self
    {
        $product = new self();
        $product->id = (int) $data['id'];
        $product->name = $data['name'];
        $product->description = $data['description'] ?? null;
        $product->price_usd = (float) $data['price_usd'];
        $product->price_ves = (float) $data['price_ves'];
        $product->stock = (int) $data['stock'];
        $product->product_type = $data['product_type'] ?? 'simple';
        $product->profit_margin = (float) ($data['profit_margin'] ?? 0);
        $product->image_url = $data['image_url'] ?? 'default.jpg';
        return $product;
    }
}
