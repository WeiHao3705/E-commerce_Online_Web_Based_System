<?php

class ReviewDTO {
    public $review_id;
    public $product_id;
    public $user_id;
    public $order_id;
    public $order_item_id;
    public $rating;
    public $comment;
    public $created_at;
    public $updated_at;
    
    // User info for display
    public $username;
    public $full_name;
    
    // Product info for display
    public $product_name;
    
    public function __construct($data = []) {
        $this->review_id = $data['review_id'] ?? null;
        $this->product_id = $data['product_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->order_id = $data['order_id'] ?? null;
        $this->order_item_id = $data['order_item_id'] ?? null;
        $this->rating = $data['rating'] ?? null;
        $this->comment = $data['comment'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
        $this->updated_at = $data['updated_at'] ?? null;
        
        // User info
        $this->username = $data['username'] ?? null;
        $this->full_name = $data['full_name'] ?? null;
        
        // Product info
        $this->product_name = $data['product_name'] ?? null;
    }
}



