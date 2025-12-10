<?php

require_once __DIR__ . '/../repository/ProductRepository.php';

class ProductService {
    private $productRepository;
    
    public function __construct($conn) {
        $this->productRepository = new ProductRepository($conn);
    }
    
    public function getProductDetails($product_id) {
        $product = $this->productRepository->getProductById($product_id);
        
        if (!$product) {
            return null;
        }
        
        $mainImageRow = $this->productRepository->getMainImage($product_id);
        $variantsList = $this->productRepository->getVariantsByProductId($product_id);
        $variantSizes = $this->productRepository->getSizesByProductId($product_id);
        
        // Select default variant
        $selectedVariant = $this->selectDefaultVariant($variantsList, $mainImageRow);
        
        // Determine initial image
        $initialImage = $mainImageRow['image_path'] ?? '';
        
        // Get default size
        $selectedSize = '';
        if ($selectedVariant && isset($variantSizes[$selectedVariant->variant_id])) {
            $selectedSize = $variantSizes[$selectedVariant->variant_id][0] ?? '';
        }
        
        return [
            'product' => $product,
            'mainImageRow' => $mainImageRow,
            'variantsList' => $variantsList,
            'variantSizes' => $variantSizes,
            'selectedVariant' => $selectedVariant,
            'initialImage' => $initialImage,
            'selectedSize' => $selectedSize
        ];
    }
    
    private function selectDefaultVariant($variantsList, $mainImageRow) {
        $selectedVariant = null;
        
        // 1. If main image belongs to a variant, use that
        if (!empty($mainImageRow['variant_id'])) {
            $mainVid = (int)$mainImageRow['variant_id'];
            foreach ($variantsList as $v) {
                if ((int)$v->variant_id === $mainVid) {
                    $selectedVariant = $v;
                    break;
                }
            }
        }
        
        // 2. Else prefer variant_id = 1
        if ($selectedVariant === null) {
            foreach ($variantsList as $v) {
                if ((int)$v->variant_id === 1) {
                    $selectedVariant = $v;
                    break;
                }
            }
        }
        
        // 3. Else use first variant
        if ($selectedVariant === null && count($variantsList) > 0) {
            $selectedVariant = $variantsList[0];
        }
        
        return $selectedVariant;
    }
}
