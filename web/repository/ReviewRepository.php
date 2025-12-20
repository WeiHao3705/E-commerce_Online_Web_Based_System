<?php

require_once __DIR__ . '/../DTO/ReviewDTO.php';

class ReviewRepository {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all reviews for a specific product with user information
     * @param int $productId Product ID
     * @return array Array of ReviewDTO objects
     */
    public function getReviewsByProductId($productId) {
        $sql = "
            SELECT 
                r.review_id,
                r.product_id,
                r.user_id,
                r.order_id,
                r.order_item_id,
                r.rating,
                r.comment,
                r.created_at,
                r.updated_at,
                u.username,
                u.full_name,
                p.product_name
            FROM product_review r
            INNER JOIN users u ON r.user_id = u.user_id
            INNER JOIN product p ON r.product_id = p.product_id
            WHERE r.product_id = :product_id
            ORDER BY r.created_at DESC
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reviews = [];
        foreach ($rows as $row) {
            $reviews[] = new ReviewDTO($row);
        }
        
        return $reviews;
    }
    
    /**
     * Get a single review by ID
     * @param int $reviewId Review ID
     * @return ReviewDTO|null
     */
    public function getReviewById($reviewId) {
        $sql = "
            SELECT 
                r.review_id,
                r.product_id,
                r.user_id,
                r.order_id,
                r.order_item_id,
                r.rating,
                r.comment,
                r.created_at,
                r.updated_at,
                u.username,
                u.full_name,
                p.product_name
            FROM product_review r
            INNER JOIN users u ON r.user_id = u.user_id
            INNER JOIN product p ON r.product_id = p.product_id
            WHERE r.review_id = :review_id
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':review_id' => $reviewId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new ReviewDTO($data) : null;
    }
    
    /**
     * Create a new review
     * @param array $reviewData Review data
     * @return int Review ID on success
     * @throws PDOException On database error
     */
    public function createReview($reviewData) {
        $sql = "
            INSERT INTO product_review 
            (product_id, user_id, order_id, order_item_id, rating, comment)
            VALUES 
            (:product_id, :user_id, :order_id, :order_item_id, :rating, :comment)
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':product_id' => $reviewData['product_id'],
            ':user_id' => $reviewData['user_id'],
            ':order_id' => $reviewData['order_id'],
            ':order_item_id' => $reviewData['order_item_id'],
            ':rating' => $reviewData['rating'],
            ':comment' => $reviewData['comment'] ?? null
        ]);
        
        return (int)$this->conn->lastInsertId();
    }
    
    /**
     * Check if user has already reviewed a specific order item
     * @param int $userId User ID
     * @param int $orderItemId Order item ID
     * @return bool True if already reviewed
     */
    public function hasUserReviewedOrderItem($userId, $orderItemId) {
        $sql = "
            SELECT COUNT(*) as count
            FROM product_review
            WHERE user_id = :user_id AND order_item_id = :order_item_id
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':order_item_id' => $orderItemId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'] > 0;
    }
    
    /**
     * Get order items that are eligible for review (delivered orders)
     * @param int $userId User ID
     * @param int $productId Product ID
     * @return array Array of eligible order items
     */
    public function getUserEligibleOrderItems($userId, $productId) {
        $sql = "
            SELECT 
                oi.order_item_id,
                oi.order_id,
                oi.product_id,
                oi.product_name_snapshot,
                oi.quantity,
                o.order_status,
                o.create_at as order_date,
                CASE 
                    WHEN pr.review_id IS NOT NULL THEN 1 
                    ELSE 0 
                END as already_reviewed
            FROM order_item oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            LEFT JOIN product_review pr ON oi.order_item_id = pr.order_item_id AND pr.user_id = :user_id
            WHERE o.user_id = :user_id
            AND oi.product_id = :product_id
            AND o.order_status = 'delivered'
            ORDER BY o.create_at DESC
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Calculate average rating for a product
     * @param int $productId Product ID
     * @return float Average rating (0.0 to 5.0)
     */
    public function getAverageRating($productId) {
        $sql = "
            SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
            FROM product_review
            WHERE product_id = :product_id
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'average' => $result['avg_rating'] ? (float)$result['avg_rating'] : 0.0,
            'count' => (int)$result['review_count']
        ];
    }
    
    /**
     * Get total review count for a product
     * @param int $productId Product ID
     * @return int Review count
     */
    public function getReviewCount($productId) {
        $sql = "
            SELECT COUNT(*) as count
            FROM product_review
            WHERE product_id = :product_id
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['count'];
    }
    
    /**
     * Get all reviews for admin view
     * @param array $filters Optional filters (product_id, user_id, rating)
     * @return array Array of ReviewDTO objects
     */
    public function getAllReviewsForAdmin($filters = []) {
        $sql = "
            SELECT 
                r.review_id,
                r.product_id,
                r.user_id,
                r.order_id,
                r.order_item_id,
                r.rating,
                r.comment,
                r.created_at,
                r.updated_at,
                u.username,
                u.full_name,
                p.product_name
            FROM product_review r
            INNER JOIN users u ON r.user_id = u.user_id
            INNER JOIN product p ON r.product_id = p.product_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND r.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND r.user_id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        
        if (!empty($filters['rating'])) {
            $sql .= " AND r.rating = :rating";
            $params[':rating'] = $filters['rating'];
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reviews = [];
        foreach ($rows as $row) {
            $reviews[] = new ReviewDTO($row);
        }
        
        return $reviews;
    }
}

