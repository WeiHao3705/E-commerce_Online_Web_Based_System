<?php

class ProductDTO {
    public $product_id;
    public $product_name;
    public $category;
    public $description;
    public $original_price;
    public $selling_price;
    
    public function __construct($data = []) {
        $this->product_id = $data['product_id'] ?? null;
        $this->product_name = $data['product_name'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->original_price = $data['original_price'] ?? null;
        $this->selling_price = $data['selling_price'] ?? null;
    }
}
