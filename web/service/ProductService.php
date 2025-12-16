<?php

require_once __DIR__ . '/../repository/ProductRepository.php';

class ProductService {
    private $productRepository;
    
    public function __construct($conn) {
        $this->productRepository = new ProductRepository($conn);
    }
    
    /**
     * Get detailed product information including variants, prices, and images
     * @param int $product_id Product ID
     * @return array|null Product details array or null if not found
     */
    public function getProductDetails($product_id) {
        // Fetch product from repository
        $product = $this->productRepository->getProductById($product_id);
        
        if (!$product) {
            return null;
        }
        
        // Fetch related data from repository
        $mainImageRow = $this->productRepository->getMainImage($product_id);
        $variantsList = $this->productRepository->getVariantsByProductId($product_id);
        $variantSizes = $this->productRepository->getSizesByProductId($product_id);
        
        // Apply business logic: determine default variant
        $selectedVariant = $this->selectDefaultVariant($variantsList, $mainImageRow);
        
        // Determine initial image
        $initialImage = $mainImageRow['image_path'] ?? '';
        
        // Get default size for selected variant
        $selectedSize = '';
        if ($selectedVariant && isset($variantSizes[$selectedVariant->variant_id])) {
            $selectedSize = $variantSizes[$selectedVariant->variant_id][0] ?? '';
        }
        
        // Return formatted data for controller/view
        return [
            'pageTitle' => $product->product_name,
            'product' => $product,
            'mainImageRow' => $mainImageRow,
            'variantsList' => $variantsList,
            'variantSizes' => $variantSizes,
            'selectedVariant' => $selectedVariant,
            'initialImage' => $initialImage,
            'selectedSize' => $selectedSize
        ];
    }
    
    /**
     * Get all products grouped by category
     * @return array Products data formatted for catalog view
     */
    public function getAllProducts() {
        // Fetch all products from repository
        $products = $this->productRepository->getAllProducts();
        
        // Group products by category (business logic)
        $grouped = $this->groupProductsByCategory($products);
        
        return [
            'pageTitle' => 'Products',
            'products' => $products,
            'grouped' => $grouped
        ];
    }
    
    /**
     * Group products by category
     * @param array $products Array of product data
     * @return array Products grouped by category
     */
    private function groupProductsByCategory($products) {
        $grouped = [];
        
        foreach ($products as $product) {
            $category = $product['category'] ?? 'Other';
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $product;
        }
        
        return $grouped;
    }
    
    /**
     * Select the default variant for a product
     * Priority: main image variant > variant_id 1 > first variant
     * @param array $variantsList List of variants
     * @param array $mainImageRow Main image data
     * @return object|null Selected variant
     */
    private function selectDefaultVariant($variantsList, $mainImageRow) {
        if (empty($variantsList)) {
            return null;
        }
        
        // 1. If main image belongs to a variant, use that
        if (!empty($mainImageRow['variant_id'])) {
            $mainVid = (int)$mainImageRow['variant_id'];
            foreach ($variantsList as $variant) {
                if ((int)$variant->variant_id === $mainVid) {
                    return $variant;
                }
            }
        }
        
        // 2. Prefer variant_id = 1
        foreach ($variantsList as $variant) {
            if ((int)$variant->variant_id === 1) {
                return $variant;
            }
        }
        
        // 3. Use first variant as fallback
        return $variantsList[0];
    }
}
