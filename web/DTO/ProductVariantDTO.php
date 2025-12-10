<?php

class ProductVariantDTO {
    public $variant_id;
    public $product_id;
    public $color;
    public $image_path;
    
    public function __construct($data = []) {
        $this->variant_id = $data['variant_id'] ?? null;
        $this->product_id = $data['product_id'] ?? null;
        $this->color = $data['color'] ?? null;
        $this->image_path = $data['image_path'] ?? null;
    }
}
