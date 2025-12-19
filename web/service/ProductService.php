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

    /**
     * Handle file upload and return relative path
     * @param array $file $_FILES array element
     * @param string $productName Product name to use in filename
     * @param string $uploadDir Directory to save file (with trailing slash)
     * @return string|null Relative path to saved file or null if upload failed
     * @throws Exception On validation or file system errors
     */
    public function handleProductImageUpload($file, $productName, $uploadDir) {
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        // Validate file upload status
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed with error code: ' . $file['error']);
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception('Invalid image type. Only JPG, PNG, GIF, and WebP are allowed.');
        }

        // Validate file size
        if ($file['size'] > $maxFileSize) {
            throw new Exception('Image size must be less than 5MB.');
        }

        // Get and validate file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $extension = 'jpg'; // Default fallback
        }

        // Generate safe filename: product_name_with_underscores.ext
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $productName);
        $safeName = preg_replace('/_+/', '_', $safeName); // Remove duplicate underscores
        $safeName = trim($safeName, '_');
        $fileName = $safeName . '.' . $extension;

        // Ensure unique filename if it already exists
        $targetPath = $uploadDir . $fileName;
        $counter = 1;
        while (file_exists($targetPath)) {
            $fileName = $safeName . '_' . $counter . '.' . $extension;
            $targetPath = $uploadDir . $fileName;
            $counter++;
        }

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }

        // Move uploaded file to destination
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to upload image. Please try again.');
        }

        // Return relative path for database storage
        return 'web/images/products/' . $fileName;
    }

    /**
     * Create a new product with its price and optional image
     * @param array $productData ['name', 'category', 'description', 'cost', 'original_price', 'selling_price']
     * @param string|null $imagePath Optional image path
     * @param PDO $conn Database connection
     * @return int|null Product ID on success, null on failure
     * @throws Exception On database or validation errors
     */
    public function createProduct($productData, $imagePath = null, $conn) {
        // Validate required fields
        $required = ['name', 'category', 'cost', 'original_price', 'selling_price'];
        foreach ($required as $field) {
            if (empty($productData[$field])) {
                throw new Exception('Missing required field: ' . $field);
            }
        }

        // Validate prices are numeric and non-negative
        foreach (['cost', 'original_price', 'selling_price'] as $priceField) {
            if (!is_numeric($productData[$priceField]) || (float)$productData[$priceField] < 0) {
                throw new Exception(ucfirst($priceField) . ' must be a non-negative number.');
            }
        }

        try {
            $conn->beginTransaction();

            // Insert product
            $stmt = $conn->prepare('INSERT INTO product (product_name, category, description) VALUES (:name, :category, :description)');
            $stmt->execute([
                ':name' => $productData['name'],
                ':category' => $productData['category'],
                ':description' => $productData['description'] ?? '',
            ]);

            $productId = (int)$conn->lastInsertId();

            // Insert product price
            $stmt = $conn->prepare('INSERT INTO product_price (product_id, cost, original_price, selling_price) VALUES (:id, :cost, :original, :selling)');
            $stmt->execute([
                ':id' => $productId,
                ':cost' => $productData['cost'],
                ':original' => $productData['original_price'],
                ':selling' => $productData['selling_price'],
            ]);

            // Insert product image if provided
            if (!empty($imagePath)) {
                $stmt = $conn->prepare('INSERT INTO product_image (product_id, variant_id, image_path, type) VALUES (:id, NULL, :path, :type)');
                $stmt->execute([
                    ':id' => $productId,
                    ':path' => $imagePath,
                    ':type' => 'main',
                ]);
            }

            $conn->commit();
            return $productId;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get all distinct product categories
     * @return array List of category names
     */
    public function getCategories() {
        return $this->productRepository->getAllCategories();
    }
}
