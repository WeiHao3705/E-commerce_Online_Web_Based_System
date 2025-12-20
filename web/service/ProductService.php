<?php

require_once __DIR__ . '/../repository/ProductRepository.php';
require_once __DIR__ . '/ReviewService.php';

class ProductService {
    private $productRepository;
    private $conn;
    private $reviewService;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->productRepository = new ProductRepository($conn);
        $this->reviewService = new ReviewService($conn);
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
        
        // Get reviews data
        $reviewsData = $this->reviewService->getProductReviews($product_id);
        
        // Check if current user can review (if logged in)
        $canReview = false;
        $eligibleOrderItems = [];
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user']) && isset($_SESSION['user']->user_id)) {
            $userId = $_SESSION['user']->user_id;
            $canReview = $this->reviewService->canUserReviewProduct($userId, $product_id);
            if ($canReview) {
                $eligibleOrderItems = $this->reviewService->getUserEligibleOrderItems($userId, $product_id);
            }
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
            'selectedSize' => $selectedSize,
            'reviews' => $reviewsData['reviews'],
            'average_rating' => $reviewsData['average_rating'],
            'review_count' => $reviewsData['review_count'],
            'can_review' => $canReview,
            'eligible_order_items' => $eligibleOrderItems
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
     * Handle single file upload and return relative path
     * @param array $file $_FILES array element
     * @param string $productName Product name to use in filename
     * @param string $uploadDir Directory to save file (with trailing slash)
     * @return string Relative path to saved file
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
     * Handle multiple image uploads
     * @param array $files The $_FILES['product_images'] array
     * @param string $productName Product name to use in filenames
     * @param string $uploadDir Directory to save files (with trailing slash)
     * @return array List of relative paths to saved files
     * @throws Exception If none of the files are valid/uploads fail
     */
    public function handleMultipleProductImageUpload($files, $productName, $uploadDir) {
        $paths = [];
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $paths;
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $paths[] = $this->handleProductImageUpload($file, $productName, $uploadDir);
            }
        }

        return $paths;
    }

    /**
     * Create a new product with its price and optional image
     * @param array $productData ['name', 'category', 'description', 'cost', 'original_price', 'selling_price']
     * @param string|null $imagePath Optional image path
     * @param PDO $conn Database connection
     * @return int|null Product ID on success, null on failure
     * @throws Exception On database or validation errors
     */
    public function createProduct($productData, $images = null, $conn, $mainIndex = null) {
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

            // Insert product images if provided
            if (!empty($images)) {
                // If a single string was provided (backward compatibility), store as main
                if (is_string($images)) {
                    $stmt = $conn->prepare('INSERT INTO product_image (product_id, variant_id, image_path, type) VALUES (:id, NULL, :path, :type)');
                    $stmt->execute([
                        ':id' => $productId,
                        ':path' => $images,
                        ':type' => 'main',
                    ]);
                } elseif (is_array($images)) {
                    // Determine main index (default 0)
                    $mainIdx = 0;
                    if ($mainIndex !== null && is_int($mainIndex) && $mainIndex >= 0 && $mainIndex < count($images)) {
                        $mainIdx = $mainIndex;
                    }

                    // Insert main image
                    if (!empty($images[$mainIdx])) {
                        $stmt = $conn->prepare('INSERT INTO product_image (product_id, variant_id, image_path, type) VALUES (:id, NULL, :path, :type)');
                        $stmt->execute([
                            ':id' => $productId,
                            ':path' => $images[$mainIdx],
                            ':type' => 'main',
                        ]);
                    }

                    // Insert gallery images (others)
                    foreach ($images as $idx => $imgPath) {
                        if ($idx === $mainIdx) {
                            continue;
                        }
                        if (empty($imgPath)) {
                            continue;
                        }
                        $stmt = $conn->prepare('INSERT INTO product_image (product_id, variant_id, image_path, type) VALUES (:id, NULL, :path, :type)');
                        $stmt->execute([
                            ':id' => $productId,
                            ':path' => $imgPath,
                            ':type' => 'gallery',
                        ]);
                    }
                }
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

    /**
     * Create a variant with optional images
     * @param array $variantData ['product_id', 'color']
     * @param array|string|null $images List of image paths or single path
     * @param int|null $mainIndex Index of main image in $images array
     * @return int Variant ID
     * @throws Exception On validation or database errors
     */
    public function createVariant($variantData, $images = null, $mainIndex = null) {
        $productId = (int)($variantData['product_id'] ?? 0);
        $color = trim($variantData['color'] ?? '');

        if ($productId <= 0) {
            throw new Exception('Please select a valid product.');
        }
        if ($color === '') {
            throw new Exception('Color is required.');
        }

        // Ensure product exists
        $product = $this->productRepository->getProductById($productId);
        if (!$product) {
            throw new Exception('Product not found.');
        }

        try {
            $this->conn->beginTransaction();

            $variantId = $this->productRepository->createVariant($productId, $color);

            // Insert images if provided
            if (!empty($images)) {
                if (is_string($images)) {
                    $this->productRepository->insertProductImage($productId, $variantId, $images, 'main');
                } elseif (is_array($images)) {
                    $mainIdx = 0;
                    if ($mainIndex !== null && is_int($mainIndex) && $mainIndex >= 0 && $mainIndex < count($images)) {
                        $mainIdx = $mainIndex;
                    }

                    if (!empty($images[$mainIdx])) {
                        $this->productRepository->insertProductImage($productId, $variantId, $images[$mainIdx], 'main');
                    }

                    foreach ($images as $idx => $imgPath) {
                        if ($idx === $mainIdx || empty($imgPath)) {
                            continue;
                        }
                        $this->productRepository->insertProductImage($productId, $variantId, $imgPath, 'gallery');
                    }
                }
            }

            $this->conn->commit();
            return $variantId;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
