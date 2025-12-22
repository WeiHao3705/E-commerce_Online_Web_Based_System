<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Only admins can access
if (!isset($_SESSION['user'])) {
	header('Location: ../../security/login.php');
	exit;
}

if ($_SESSION['user']->role !== 'admin') {
	$_SESSION['error_message'] = 'Access denied. Admin privileges required.';
	header('Location: ../../index.php');
	exit;
}

require_once __DIR__ . '/../../../helpers.php';
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../service/ProductService.php';

// Initialize
$db = new Database();
$conn = $db->getConnection();
$productService = new ProductService($conn);

// Get base paths
$currentFileDir = __DIR__;
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$jsBasePath = $webBasePath . 'js/';

// Get flash messages
$flashSuccess = $_SESSION['success_message'] ?? '';
$flashError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	try {
		$formType = $_POST['form_type'] ?? 'product';

		if ($formType === 'product') {
			// Collect form data
			$productData = [
				'name' => trim($_POST['product_name'] ?? ''),
				'category' => trim($_POST['category'] ?? ''),
				'description' => trim($_POST['description'] ?? ''),
				'cost' => $_POST['cost'] ?? null,
				'original_price' => $_POST['original_price'] ?? null,
				'selling_price' => $_POST['selling_price'] ?? null,
			];

			$hasVariants = isset($_POST['has_variants']) && $_POST['has_variants'] === '1';
			$initialVariantColor = trim($_POST['initial_variant_color'] ?? '');

			// Handle file upload via service (multiple images)
			$imagePaths = [];
			$mainIndex = null;
			if (isset($_FILES['product_images'])) {
				$uploadDir = $webRootDir . '/images/products/';
				$imagePaths = $productService->handleMultipleProductImageUpload(
					$_FILES['product_images'],
					$productData['name'],
					$uploadDir
				);
				// Main image index from form (optional)
				if (isset($_POST['main_image_index'])) {
					$mainIndex = (int)($_POST['main_image_index']);
				}
			}

			// Create product via service
			$productId = $productService->createProduct($productData, $imagePaths, $conn, $mainIndex);

			// If variants enabled, create initial variant with provided color
			if ($hasVariants) {
				if ($initialVariantColor === '') {
					throw new Exception('Please specify an initial variant color.');
				}
				$variantId = $productService->createVariant([
					'product_id' => (int)$productId,
					'color' => $initialVariantColor,
				], [], null);
				
				// Log variant creation only if current user is admin
				if (isset($_SESSION['user']) && $_SESSION['user']->role === 'admin') {
					require_once __DIR__ . '/../../helpers/ActivityLogger.php';
					ActivityLogger::logVariantCreate($variantId, (int)$productId, $initialVariantColor);
				}
			}

			// Success: set message and redirect
			$_SESSION['success_message'] = 'Product created successfully.';
			header('Location: AdminProduct.php');
			exit;
		} elseif ($formType === 'variant') {
			$variantData = [
				'product_id' => (int)($_POST['variant_product_id'] ?? 0),
				'color' => trim($_POST['variant_color'] ?? ''),
			];

			$imagePaths = [];
			$mainIndex = null;
			if (isset($_FILES['variant_images'])) {
				$uploadDir = $webRootDir . '/images/products/';
				$imagePaths = $productService->handleMultipleProductImageUpload(
					$_FILES['variant_images'],
					$variantData['color'] ?: 'variant',
					$uploadDir
				);
				if (isset($_POST['variant_main_image_index'])) {
					$mainIndex = (int)($_POST['variant_main_image_index']);
				}
			}

			$variantId = $productService->createVariant($variantData, $imagePaths, $mainIndex);

			// Log variant creation only if current user is admin
			if (isset($_SESSION['user']) && $_SESSION['user']->role === 'admin') {
				require_once __DIR__ . '/../../helpers/ActivityLogger.php';
				ActivityLogger::logVariantCreate($variantId, (int)$variantData['product_id'], $variantData['color']);
			}

			$_SESSION['success_message'] = 'Variant added successfully.';
			header('Location: AdminProduct.php');
			exit;
		}
	} catch (Exception $e) {
		// Store error in variable for display
		$flashError = $e->getMessage();
	}
}

// Get categories for form
$categories = $productService->getCategories();
$productList = $productService->getAllProducts()['products'] ?? [];

$pageTitle = 'Add Product';
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo html_escape($pageTitle); ?> - NGEAR</title>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>AddProduct.css?v=<?php echo filemtime(__DIR__ . '/../../css/AddProduct.css'); ?>">
</head>

<body>
	<div class="page-container">
		<a href="AdminProduct.php" class="back-link">
			<span class="material-symbols-outlined">arrow_back</span>
			Back to Products
		</a>

		<div class="page-header">
			<h1>Add New Product</h1>
			<p>Fill in the details below to add a new product to your catalog</p>
		</div>

		<?php if ($flashSuccess): ?>
			<div class="message message-success"><?php echo html_escape($flashSuccess); ?></div>
		<?php endif; ?>
		<?php if ($flashError): ?>
			<div class="message message-error"><?php echo html_escape($flashError); ?></div>
		<?php endif; ?>

		<div class="content-card">
			<div class="tab-bar">
				<button type="button" class="tab-btn active" id="tab-product">New Product</button>
				<button type="button" class="tab-btn" id="tab-variant">New Variant</button>
			</div>

			<div class="form-hint">
				<span class="material-symbols-outlined">info</span>
				<p>After creating the product, you can add variants, inventory, and additional images from the product management page.</p>
			</div>

			<form id="productForm" class="tab-content" data-tab="product" method="POST" action="AddProduct.php" enctype="multipart/form-data">
				<input type="hidden" name="form_type" value="product">
				<div class="form-grid">
					<div class="form-group">
						<label for="product_name">
							<span class="material-symbols-outlined">inventory_2</span>
							Product Name
							<span class="required">*</span>
						</label>
						<input type="text" id="product_name" name="product_name" required placeholder="e.g., Nike Air Max 270">
					</div>

					<div class="form-group">
						<label for="category">
							<span class="material-symbols-outlined">category</span>
							Category
							<span class="required">*</span>
						</label>
						<input type="text" id="category" name="category" list="categoryList" required placeholder="e.g., Shoes">
						<datalist id="categoryList">
							<?php foreach ($categories as $cat): ?>
								<option value="<?php echo html_escape($cat); ?>">
							<?php endforeach; ?>
						</datalist>
					</div>

					<div class="form-group">
						<label for="cost">
							<span class="material-symbols-outlined">price_change</span>
							Cost (RM)
							<span class="required">*</span>
						</label>
						<input type="number" step="0.01" min="0" id="cost" name="cost" required placeholder="0.00">
						<small>Your purchasing cost</small>
					</div>

					<div class="form-group">
						<label for="original_price">
							<span class="material-symbols-outlined">payments</span>
							Original Price (RM)
							<span class="required">*</span>
						</label>
						<input type="number" step="0.01" min="0" id="original_price" name="original_price" required placeholder="0.00">
						<small>Regular retail price</small>
					</div>

					<div class="form-group">
						<label for="selling_price">
							<span class="material-symbols-outlined">sell</span>
							Selling Price (RM)
							<span class="required">*</span>
						</label>
						<input type="number" step="0.01" min="0" id="selling_price" name="selling_price" required placeholder="0.00">
						<small>Current price shown to customers</small>
					</div>

					<div class="form-group">
						<label for="product_images">
							<span class="material-symbols-outlined">image</span>
							Product Images
						</label>
						<input type="file" id="product_images" name="product_images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
						<small>Optional - Select multiple images. Choose one as Main (max 5MB each)</small>
						<input type="hidden" name="main_image_index" id="main_image_index" value="0">
						<div id="imagesPreview" class="images-preview" style="display:none;margin-top:10px;"></div>
					</div>
				</div>

				<!-- Variants Option -->
				<div class="form-grid">
					<div class="form-group">
						<label for="has_variants">
							<span class="material-symbols-outlined">tune</span>
							This product has variants?
						</label>
						<label style="display:inline-flex;align-items:center;gap:8px;">
							<input type="checkbox" id="has_variants" name="has_variants" value="1">
							Enable variants
						</label>
						<small>When enabled, specify the color to create the first variant.</small>
					</div>

					<div class="form-group" id="initial_variant_color_group" style="display:none;">
						<label for="initial_variant_color">
							<span class="material-symbols-outlined">palette</span>
							Initial Variant Color
							<span class="required">*</span>
						</label>
						<input type="text" id="initial_variant_color" name="initial_variant_color" placeholder="e.g., Black / Navy">
						<small>This will create a variant in product_variant table.</small>
					</div>
				</div>

				<div class="form-group">
					<label for="description">
						<span class="material-symbols-outlined">description</span>
						Description
					</label>
					<textarea id="description" name="description" placeholder="Short description of the product (optional)"></textarea>
				</div>

				<div class="form-actions">
					<a href="AdminProduct.php" class="btn btn-secondary">
						<span class="material-symbols-outlined">close</span>
						Cancel
					</a>
					<button type="submit" class="btn btn-primary">
						<span class="material-symbols-outlined">add</span>
						Create Product
					</button>
				</div>
			</form>

			<form id="variantForm" class="tab-content hidden" data-tab="variant" method="POST" action="AddProduct.php" enctype="multipart/form-data">
				<input type="hidden" name="form_type" value="variant">
				<div class="form-grid">
					<div class="form-group">
						<label for="variant_product_id">
							<span class="material-symbols-outlined">inventory</span>
							Select Product
							<span class="required">*</span>
						</label>
						<select id="variant_product_id" name="variant_product_id" required>
							<option value="">-- Choose a product --</option>
							<?php foreach ($productList as $p): ?>
								<option value="<?php echo (int)$p['product_id']; ?>"><?php echo html_escape($p['product_name']); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="form-group">
						<label for="variant_color">
							<span class="material-symbols-outlined">palette</span>
							Color
							<span class="required">*</span>
						</label>
						<input type="text" id="variant_color" name="variant_color" required placeholder="e.g., Red / Midnight Navy">
					</div>

					<div class="form-group">
						<label for="variant_images">
							<span class="material-symbols-outlined">image</span>
							Variant Images
						</label>
						<input type="file" id="variant_images" name="variant_images[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
						<small>Optional - Select multiple images. Choose one as Main (max 5MB each)</small>
						<input type="hidden" name="variant_main_image_index" id="variant_main_image_index" value="0">
						<div id="variantImagesPreview" class="images-preview" style="display:none;margin-top:10px;"></div>
					</div>
				</div>

				<div class="form-actions">
					<a href="AdminProduct.php" class="btn btn-secondary">
						<span class="material-symbols-outlined">close</span>
						Cancel
					</a>
					<button type="submit" class="btn btn-primary">
						<span class="material-symbols-outlined">add</span>
						Add Variant
					</button>
				</div>
			</form>
		</div>
	</div>

	<script src="<?php echo $jsBasePath; ?>addProduct.js?v=<?php echo filemtime(__DIR__ . '/../../js/addProduct.js'); ?>"></script>
</body>

</html>
