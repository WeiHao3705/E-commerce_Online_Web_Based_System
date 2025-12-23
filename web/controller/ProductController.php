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
        $action = isset($_GET['action']) ? $_GET['action'] : 'showAll';
        
        switch ($action) {
            case 'showDetails':
                $this->showProductDetails();
                break;
            case 'showAll':
                $this->showAllProducts();
                break;
            case 'getFilteredProducts':
                $this->getFilteredProductsJSON();
                break;
            case 'getVariantImages':
                $this->getVariantImagesJSON();
                break;
            default:
                $this->showAllProducts();
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
            // Get filter parameters from request
            $category = $_GET['category'] ?? null;
            $minPrice = $_GET['min_price'] ?? null;
            $maxPrice = $_GET['max_price'] ?? null;
            $colors = isset($_GET['colors']) && is_array($_GET['colors']) ? $_GET['colors'] : [];
            $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;

            // Get all available filters for sidebar
            $allCategories = $this->productService->getAllCategories();
            $allColors = $this->productService->getAllColors();

            // Get filtered products from service (with search support)
            $productsData = $this->productService->getFilteredProducts($category, $minPrice, $maxPrice, $colors, $searchQuery);
            
            // Add filter info to data
            $data = array_merge($productsData, [
                'allCategories' => $allCategories,
                'allColors' => $allColors,
                'selectedCategory' => $category,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'selectedColors' => $colors,
                'searchQuery' => $searchQuery
            ]);

            // Render view with data
            $this->renderView('ProductPage', $data);
        } catch (Exception $e) {
            $this->renderError($e->getMessage());
        }
    }

    /**
     * Get filtered products as JSON (for AJAX requests)
     */
    public function getFilteredProductsJSON() {
        header('Content-Type: application/json');
        
        try {
            // Get filter parameters from request
            $category = $_GET['category'] ?? null;
            $minPrice = $_GET['min_price'] ?? null;
            $maxPrice = $_GET['max_price'] ?? null;
            $colors = isset($_GET['colors']) && is_array($_GET['colors']) ? $_GET['colors'] : [];
            $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : null;

            // Get filtered products from service (with search support)
            $productsData = $this->productService->getFilteredProducts($category, $minPrice, $maxPrice, $colors, $searchQuery);
            
            // Return JSON response
            echo json_encode([
                'success' => true,
                'grouped' => $productsData['grouped'],
                'products' => $productsData['products']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Get variant images as JSON (for AJAX requests)
     */
    public function getVariantImagesJSON() {
        header('Content-Type: application/json');
        
        try {
            // Validate variant_id
            if (!isset($_GET['variant_id']) || !is_numeric($_GET['variant_id'])) {
                throw new Exception('Invalid variant ID.');
            }
            
            $variant_id = (int)$_GET['variant_id'];
            
            // Get variant images from service
            $images = $this->productService->getVariantImages($variant_id);
            
            // Return JSON response
            echo json_encode([
                'success' => true,
                'images' => $images
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
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

// Handle direct requests to this controller
if (basename($_SERVER['PHP_SELF']) === 'ProductController.php') {
    $controller = new ProductController();
    $controller->handleRequest();
}
