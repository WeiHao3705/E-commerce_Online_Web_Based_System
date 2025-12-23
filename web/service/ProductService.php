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
        
        // Collect product-level and variant-level images
        $productImages = $this->productRepository->getImagesForProduct($product_id);
        $variantImages = [];
        if (!empty($selectedVariant) && !empty($selectedVariant->variant_id)) {
            $variantImages = $this->productRepository->getImagesForVariant((int)$selectedVariant->variant_id);
        }
        // Prefer variant images if available, otherwise product images
        $displayImages = !empty($variantImages) ? $variantImages : $productImages;
        
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
        
        // Calculate total stock for the product
        $totalStock = $this->getProductTotalStock($product_id);
        
        // Get stock per variant
        $variantStock = $this->getVariantStock($product_id);
        
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
            'displayImages' => $displayImages,
            'reviews' => $reviewsData['reviews'],
            'average_rating' => $reviewsData['average_rating'],
            'review_count' => $reviewsData['review_count'],
            'can_review' => $canReview,
            'eligible_order_items' => $eligibleOrderItems,
            'total_stock' => $totalStock,
            'is_out_of_stock' => $totalStock <= 0,
            'variant_stock' => $variantStock
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
    public function handleMultipleProductImageUpload($files, $productName, $uploadDir, $excludeIndices = []) {
        $paths = [];
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $paths;
        }

        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if (is_array($excludeIndices) && in_array($i, $excludeIndices, true)) {
                continue;
            }
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
            $stmt = $conn->prepare('INSERT INTO product (product_name, category, description, has_size) VALUES (:name, :category, :description, :has_size)');
            $stmt->execute([
                ':name' => $productData['name'],
                ':category' => $productData['category'],
                ':description' => $productData['description'] ?? '',
                ':has_size' => isset($productData['has_size']) ? (int)$productData['has_size'] : 0,
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
     * Get total stock quantity for a product (sum of all inventory entries)
     * @param int $product_id Product ID
     * @return int Total stock quantity
     */
    public function getProductTotalStock($product_id) {
        $sql = "
            SELECT COALESCE(SUM(i.stock_quantity), 0) as total_stock
            FROM inventory i
            WHERE i.product_id = :product_id 
               OR i.variant_id IN (
                   SELECT variant_id FROM product_variant WHERE product_id = :product_id
               )
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_stock'] ?? 0);
    }

    /**
     * Get stock quantity per variant
     * @param int $product_id Product ID
     * @return array Array of variant_id => total_stock
     */
    public function getVariantStock($product_id) {
        $sql = "
            SELECT 
                i.variant_id,
                COALESCE(SUM(i.stock_quantity), 0) as total_stock
            FROM inventory i
            WHERE i.variant_id IN (
                SELECT variant_id FROM product_variant WHERE product_id = :product_id
            )
            GROUP BY i.variant_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $product_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $variantStock = [];
        foreach ($results as $row) {
            $variantStock[(int)$row['variant_id']] = (int)$row['total_stock'];
        }
        
        // Also include variants with no inventory (0 stock)
        $variantsList = $this->productRepository->getVariantsByProductId($product_id);
        foreach ($variantsList as $variant) {
            $vid = (int)$variant->variant_id;
            if (!isset($variantStock[$vid])) {
                $variantStock[$vid] = 0;
            }
        }
        
        return $variantStock;
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

    /**
     * Get all available categories
     * @return array List of all categories
     */
    public function getAllCategories() {
        $sql = "SELECT DISTINCT category FROM product ORDER BY category";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all available colors
     * @return array List of all colors
     */
    public function getAllColors() {
        $sql = "SELECT DISTINCT color FROM product_variant ORDER BY color";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get a product name by id
     * @param int $productId
     * @return string|null Product name or null if not found
     */
    public function getProductNameById($productId) {
        $product = $this->productRepository->getProductById((int)$productId);
        if (!$product) {
            return null;
        }
        return $product->product_name;
    }

    /**
     * Get products that have at least one variant
     * @return array List of products with variants (product_id, product_name)
     */
    public function getProductsWithVariants() {
        $sql = "
            SELECT p.product_id, p.product_name, p.category, p.description
            FROM product p
            WHERE EXISTS (
                SELECT 1 FROM product_variant pv WHERE pv.product_id = p.product_id
            )
            ORDER BY p.product_name
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get filtered products based on category, price, color, and search query
     * @param string|null $category Filter by category
     * @param float|null $minPrice Filter by minimum price
     * @param float|null $maxPrice Filter by maximum price
     * @param array $colors Filter by colors (array of color names)
     * @param string|null $searchQuery Search query for product name, category, or color
     * @return array Filtered products grouped by category
     */
    public function getFilteredProducts($category = null, $minPrice = null, $maxPrice = null, $colors = [], $searchQuery = null) {
        $sql = "
            SELECT 
                p.product_id, 
                p.product_name, 
                p.category, 
                p.description,
                pi.image_path,
                pr.original_price,
                pr.selling_price,
                GROUP_CONCAT(DISTINCT pv.color SEPARATOR ', ') AS colors,
                COALESCE((
                    SELECT AVG(rating) 
                    FROM product_review 
                    WHERE product_id = p.product_id
                ), 0) AS average_rating,
                COALESCE((
                    SELECT COUNT(*) 
                    FROM product_review 
                    WHERE product_id = p.product_id
                ), 0) AS review_count
            FROM product p
            LEFT JOIN product_image pi ON p.product_id = pi.product_id AND pi.type = 'main'
            LEFT JOIN product_price pr ON p.product_id = pr.product_id
            LEFT JOIN product_variant pv ON p.product_id = pv.product_id
            WHERE 1=1
        ";

        $params = [];

        // Filter by search query
        if (!empty($searchQuery)) {
            $searchPattern = '%' . $searchQuery . '%';
            $sql .= " AND (p.product_name LIKE :search OR p.category LIKE :search OR pv.color LIKE :search)";
            $params[':search'] = $searchPattern;
        }

        // Filter by category
        if (!empty($category)) {
            $sql .= " AND p.category = :category";
            $params[':category'] = $category;
        }

        // Filter by price range
        if (!empty($minPrice) && is_numeric($minPrice)) {
            $sql .= " AND pr.original_price >= :min_price";
            $params[':min_price'] = (float)$minPrice;
        }
        if (!empty($maxPrice) && is_numeric($maxPrice)) {
            $sql .= " AND pr.original_price <= :max_price";
            $params[':max_price'] = (float)$maxPrice;
        }

        // Filter by colors
        if (!empty($colors) && is_array($colors)) {
            $colorPlaceholders = [];
            foreach ($colors as $idx => $color) {
                $placeholder = ':color_' . $idx;
                $colorPlaceholders[] = $placeholder;
                $params[$placeholder] = $color;
            }
            $sql .= " AND pv.color IN (" . implode(',', $colorPlaceholders) . ")";
        }

        $sql .= " GROUP BY p.product_id ORDER BY p.category, p.product_name";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by category
        $grouped = $this->groupProductsByCategory($rows);

        return [
            'pageTitle' => 'Products',
            'products' => $rows,
            'grouped' => $grouped
        ];
    }

    /**
     * Get comprehensive product details for admin view
     * Includes all variants, images, inventory, and pricing info
     * @param int $product_id Product ID
     * @return array|null Detailed product information or null if not found
     */
    public function getProductDetailsForAdmin($product_id) {
        // Get basic product info
        $product = $this->productRepository->getProductById($product_id);
        
        if (!$product) {
            return null;
        }

        // Get pricing info
        $sql = "SELECT cost, original_price, selling_price FROM product_price WHERE product_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        $pricing = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get all variants with detailed info
        $variantsList = $this->productRepository->getVariantsByProductId($product_id);
        
        // Get images for each variant
        $variantsWithImages = [];
        foreach ($variantsList as $variant) {
            $variantImages = $this->productRepository->getImagesForVariant((int)$variant->variant_id);
            $variantsWithImages[] = [
                'variant' => $variant,
                'images' => $variantImages
            ];
        }

        // Get product-level images
        $productImages = $this->productRepository->getImagesForProduct($product_id);

        // Get inventory for all variants
        $inventorySql = "
            SELECT 
                i.id,
                i.variant_id,
                i.size,
                i.stock_quantity,
                pv.color
            FROM inventory i
            LEFT JOIN product_variant pv ON i.variant_id = pv.variant_id
            WHERE i.product_id = :id OR i.variant_id IN (
                SELECT variant_id FROM product_variant WHERE product_id = :id
            )
            ORDER BY pv.color, i.size
        ";
        $stmt = $this->conn->prepare($inventorySql);
        $stmt->execute([':id' => $product_id]);
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get sizes per variant
        $variantSizes = $this->productRepository->getSizesByProductId($product_id);

        // Calculate total stock
        $totalStock = $this->getProductTotalStock($product_id);
        
        // Get variant stock
        $variantStock = $this->getVariantStock($product_id);

        return [
            'pageTitle' => $product->product_name . ' - Details',
            'product' => $product,
            'pricing' => $pricing,
            'variants' => $variantsWithImages,
            'productImages' => $productImages,
            'inventory' => $inventory,
            'variantSizes' => $variantSizes,
            'total_stock' => $totalStock,
            'variant_stock' => $variantStock
        ];
    }
}
