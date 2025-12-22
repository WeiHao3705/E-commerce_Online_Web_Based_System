<?php

require_once __DIR__ . '/../repository/ReviewRepository.php';

class ReviewService {
    private $reviewRepository;
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->reviewRepository = new ReviewRepository($conn);
    }
    
    /**
     * Get all reviews for a product with aggregated statistics
     * @param int $productId Product ID
     * @return array Reviews data with stats
     */
    public function getProductReviews($productId) {
        $reviews = $this->reviewRepository->getReviewsByProductId($productId);
        $stats = $this->reviewRepository->getAverageRating($productId);
        
        return [
            'reviews' => $reviews,
            'average_rating' => $stats['average'],
            'review_count' => $stats['count']
        ];
    }
    
    /**
     * Check if user can review a product (has delivered orders)
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return bool True if user has eligible orders
     */
    public function canUserReviewProduct($userId, $productId) {
        $eligibleItems = $this->reviewRepository->getUserEligibleOrderItems($userId, $productId);
        return !empty($eligibleItems);
    }
    
    /**
     * Get order items that are eligible for review
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return array Eligible order items
     */
    public function getUserEligibleOrderItems($userId, $productId) {
        return $this->reviewRepository->getUserEligibleOrderItems($userId, $productId);
    }
    
    /**
     * Submit a new review
     * @param array $reviewData Review data
     * @return array Result with success status and message
     * @throws Exception On validation or database errors
     */
    public function submitReview($reviewData) {
        // Validate required fields
        $required = ['product_id', 'user_id', 'order_id', 'order_item_id', 'rating'];
        foreach ($required as $field) {
            if (!isset($reviewData[$field]) || $reviewData[$field] === '') {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate rating is between 1 and 5
        $rating = (int)$reviewData['rating'];
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Rating must be between 1 and 5");
        }
        
        // Validate order belongs to user and is delivered
        $this->validateOrderEligibility($reviewData['user_id'], $reviewData['order_id'], $reviewData['order_item_id']);
        
        // Check if user already reviewed this order item
        if ($this->reviewRepository->hasUserReviewedOrderItem($reviewData['user_id'], $reviewData['order_item_id'])) {
            throw new Exception("You have already reviewed this order item");
        }
        
        // Validate order item belongs to the product
        $this->validateOrderItemProduct($reviewData['order_item_id'], $reviewData['product_id']);
        
        // Sanitize comment (optional)
        $comment = !empty($reviewData['comment']) ? trim($reviewData['comment']) : null;
        
        // Create review
        try {
            $reviewId = $this->reviewRepository->createReview([
                'product_id' => (int)$reviewData['product_id'],
                'user_id' => (int)$reviewData['user_id'],
                'order_id' => (int)$reviewData['order_id'],
                'order_item_id' => (int)$reviewData['order_item_id'],
                'rating' => $rating,
                'comment' => $comment
            ]);
            
            return [
                'success' => true,
                'message' => 'Review submitted successfully',
                'review_id' => $reviewId
            ];
        } catch (PDOException $e) {
            // Check for duplicate entry error
            if ($e->getCode() == 23000) {
                throw new Exception("You have already reviewed this order item");
            }
            throw new Exception("Failed to submit review: " . $e->getMessage());
        }
    }
    
    /**
     * Validate that order belongs to user and is delivered
     * @param int $userId User ID
     * @param int $orderId Order ID
     * @param int $orderItemId Order item ID
     * @throws Exception If validation fails
     */
    private function validateOrderEligibility($userId, $orderId, $orderItemId) {
        $sql = "
            SELECT o.order_status, o.user_id, oi.order_item_id
            FROM orders o
            INNER JOIN order_item oi ON o.order_id = oi.order_id
            WHERE o.order_id = :order_id 
            AND oi.order_item_id = :order_item_id
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':order_id' => $orderId,
            ':order_item_id' => $orderItemId
        ]);
        
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            throw new Exception("Order item not found");
        }
        
        if ((int)$order['user_id'] !== (int)$userId) {
            throw new Exception("You can only review your own orders");
        }
        
        if ($order['order_status'] !== 'delivered') {
            throw new Exception("You can only review products from delivered orders");
        }
    }
    
    /**
     * Validate that order item belongs to the specified product
     * @param int $orderItemId Order item ID
     * @param int $productId Product ID
     * @throws Exception If validation fails
     */
    private function validateOrderItemProduct($orderItemId, $productId) {
        $sql = "
            SELECT product_id
            FROM order_item
            WHERE order_item_id = :order_item_id
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':order_item_id' => $orderItemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new Exception("Order item not found");
        }
        
        if ((int)$item['product_id'] !== (int)$productId) {
            throw new Exception("Order item does not match the product");
        }
    }
    
    /**
     * Get all reviews for admin view
     * @param array $filters Optional filters
     * @return array Array of ReviewDTO objects
     */
    public function getAllReviewsForAdmin($filters = []) {
        return $this->reviewRepository->getAllReviewsForAdmin($filters);
    }
}



