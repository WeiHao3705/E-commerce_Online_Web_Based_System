<?php
session_start();

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../service/ReviewService.php';

class ReviewController {
    private $reviewService;
    private $conn;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->reviewService = new ReviewService($this->conn);
    }
    
    /**
     * Handle review-related requests
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'submit':
                $this->submitReview();
                break;
            case 'getReviews':
                $this->getProductReviews();
                break;
            case 'viewAll':
                $this->viewAllReviews();
                break;
            case 'searchProducts':
                $this->searchProducts();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    }
    
    /**
     * Submit a new review (POST)
     */
    private function submitReview() {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        $userId = $this->getUserId();
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review']);
            return;
        }
        
        // Validate required fields
        $required = ['product_id', 'order_id', 'order_item_id', 'rating'];
        foreach ($required as $field) {
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                return;
            }
        }
        
        try {
            $reviewData = [
                'product_id' => (int)$_POST['product_id'],
                'user_id' => $userId,
                'order_id' => (int)$_POST['order_id'],
                'order_item_id' => (int)$_POST['order_item_id'],
                'rating' => (int)$_POST['rating'],
                'comment' => $_POST['comment'] ?? null
            ];
            
            $result = $this->reviewService->submitReview($reviewData);
            
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get reviews for a product (GET/AJAX)
     */
    private function getProductReviews() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
            return;
        }
        
        $productId = (int)$_GET['product_id'];
        
        try {
            $reviewsData = $this->reviewService->getProductReviews($productId);
            
            // Convert ReviewDTO objects to arrays for JSON
            $reviewsArray = [];
            foreach ($reviewsData['reviews'] as $review) {
                $reviewsArray[] = [
                    'review_id' => $review->review_id,
                    'user_id' => $review->user_id,
                    'username' => $review->username,
                    'full_name' => $review->full_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at
                ];
            }
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviewsArray,
                'average_rating' => $reviewsData['average_rating'],
                'review_count' => $reviewsData['review_count']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * View all reviews (Admin only)
     */
    private function viewAllReviews() {
        // Check if user is admin
        if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
            $_SESSION['error_message'] = 'You must be logged in as admin to view reviews.';
            header('Location: ../views/security/login.php');
            exit;
        }
        
        // Get filters
        $filters = [];
        if (!empty($_GET['product_name'])) {
            $filters['product_name'] = trim($_GET['product_name']);
        }
        if (!empty($_GET['rating'])) {
            $filters['rating'] = (int)$_GET['rating'];
        }
        
        try {
            $reviews = $this->reviewService->getAllReviewsForAdmin($filters);
            
            // Include the admin reviews view (no header/footer for iframe loading)
            $pageTitle = "All Reviews";
            require __DIR__ . '/../views/admin/AdminReviews.php';
        } catch (Exception $e) {
            $pageTitle = "Error";
            echo "<div style='max-width:1000px;margin:40px auto;padding:20px;'><h2 style='color:#dc2626;'>Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
        }
    }
    
    /**
     * Search products by name (AJAX)
     */
    private function searchProducts() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['search']) || empty(trim($_GET['search']))) {
            echo json_encode(['success' => true, 'products' => []]);
            return;
        }
        
        $searchTerm = trim($_GET['search']);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        try {
            $sql = "
                SELECT DISTINCT
                    p.product_id,
                    p.product_name
                FROM product p
                WHERE p.product_name LIKE :search
                ORDER BY p.product_name ASC
                LIMIT :limit
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get current user ID from session
     * @return int|null User ID or null if not logged in
     */
    private function getUserId() {
        if (isset($_SESSION['user']) && isset($_SESSION['user']->user_id)) {
            return $_SESSION['user']->user_id;
        } elseif (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        return null;
    }
}

// Handle request if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'ReviewController.php' || 
    (isset($_GET['controller']) && $_GET['controller'] === 'review')) {
    $controller = new ReviewController();
    $controller->handleRequest();
}

