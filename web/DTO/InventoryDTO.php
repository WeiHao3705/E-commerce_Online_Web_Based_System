<?php

class InventoryDTO {
    public $id;
    public $product_id;
    public $variant_id;
    public $size;
    public $stock_quantity;
    public $updated_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->product_id = $data['product_id'] ?? null;
        $this->variant_id = $data['variant_id'] ?? null;
        $this->size = $data['size'] ?? null;
        $this->stock_quantity = $data['stock_quantity'] ?? 0;
        $this->updated_at = $data['updated_at'] ?? null;
    }
}
