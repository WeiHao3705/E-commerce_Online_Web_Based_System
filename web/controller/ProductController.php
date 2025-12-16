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
    
    /**
     * Route requests to appropriate action handlers
     */
    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : 'showDetails';
        
        switch ($action) {
            case 'showDetails':
                $this->showProductDetails();
                break;
            case 'showAll':
                $this->showAllProducts();
                break;
            default:
                $this->showProductDetails();
        }
    }
    
    /**
     * Display single product details
     */
    public function showProductDetails() {
        try {
            // Validate product_id
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                throw new Exception('Invalid product.');
            }
            
            $product_id = (int)$_GET['id'];
            
            // Get product details from service
            $data = $this->productService->getProductDetails($product_id);
            
            if (!$data || !$data['product']) {
                throw new Exception('Product not found.');
            }
            
            // Render view with data
            $this->renderView('ProductDetails', $data);
        } catch (Exception $e) {
            $this->renderError($e->getMessage());
        }
    }
    
    /**
     * Display all products (catalog)
     */
    public function showAllProducts() {
        try {
            // Get all products from service
            $data = $this->productService->getAllProducts();
            
            // Render view with data
            $this->renderView('ProductPage', $data);
        } catch (Exception $e) {
            $this->renderError($e->getMessage());
        }
    }
    
    /**
     * Render a view with data
     * @param string $viewName Name of view file (without .php)
     * @param array $data Data to pass to view
     */
    private function renderView($viewName, $data = []) {
        // Extract data for view variables
        extract($data);
        
        // Set page title from view data or default
        $pageTitle = $data['pageTitle'] ?? ucwords(str_replace('_', ' ', $viewName));
        
        // Include layout and view
        require __DIR__ . '/../general/_header.php';
        require __DIR__ . '/../general/_navbar.php';
        require __DIR__ . '/../views/product/' . $viewName . '.php';
        require __DIR__ . '/../general/_footer.php';
    }
    
    /**
     * Render error page
     * @param string $message Error message
     */
    private function renderError($message) {
        $pageTitle = 'Error';
        $error = htmlspecialchars($message);
        
        require __DIR__ . '/../general/_header.php';
        require __DIR__ . '/../general/_navbar.php';
        echo "<div style='max-width:1000px;margin:40px auto;padding:20px;'><h2 style='color:#dc2626;'>Error</h2><p>{$error}</p></div>";
        require __DIR__ . '/../general/_footer.php';
    }
}
