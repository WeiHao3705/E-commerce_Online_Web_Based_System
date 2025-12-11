<?php

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../service/ProductService.php';

class ProductController {
    private $productService;
    
    public function __construct() {
        $db = new Database();
        $conn = $db->getConnection();
        $this->productService = new ProductService($conn);
    }
    
    public function showProductDetails() {
        // Validate product_id
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $this->renderError("Invalid product.");
            return;
        }
        
        $product_id = (int)$_GET['id'];
        
        // Get product details from service
        $data = $this->productService->getProductDetails($product_id);
        
        if (!$data || !$data['product']) {
            $this->renderError("Product not found.");
            return;
        }
        
        // Render view
        $this->renderView($data);
    }
    
    private function renderView($data) {
        // Extract data for view
        extract($data);
        
        // Set page title
        $pageTitle = "Product Details";
        
        // Include layout and view
        require __DIR__ . '/../general/_header.php';
        require __DIR__ . '/../general/_navbar.php';
        require __DIR__ . '/../views/product/ProductDetailsView.php';
        require __DIR__ . '/../general/_footer.php';
    }
    
    private function renderError($message) {
        $pageTitle = "Error";
        require __DIR__ . '/../general/_header.php';
        require __DIR__ . '/../general/_navbar.php';
        echo "<h2>{$message}</h2>";
        require __DIR__ . '/../general/_footer.php';
    }
}
