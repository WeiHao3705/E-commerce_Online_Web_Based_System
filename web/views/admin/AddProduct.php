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
		// Collect form data
		$productData = [
			'name' => trim($_POST['product_name'] ?? ''),
			'category' => trim($_POST['category'] ?? ''),
			'description' => trim($_POST['description'] ?? ''),
			'cost' => $_POST['cost'] ?? null,
			'original_price' => $_POST['original_price'] ?? null,
			'selling_price' => $_POST['selling_price'] ?? null,
		];

		// Handle file upload via service
		$imagePath = '';
		if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = $webRootDir . '/images/products/';
			$imagePath = $productService->handleProductImageUpload(
				$_FILES['product_image'],
				$productData['name'],
				$uploadDir
			);
		}

		// Create product via service
		$productId = $productService->createProduct($productData, $imagePath, $conn);

		// Success: set message and redirect
		$_SESSION['success_message'] = 'Product created successfully.';
		header('Location: AdminProduct.php');
		exit;
	} catch (Exception $e) {
		// Store error in variable for display
		$flashError = $e->getMessage();
	}
}

// Get categories for form
$categories = $productService->getCategories();

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
			<div class="form-hint">
				<span class="material-symbols-outlined">info</span>
				<p>After creating the product, you can add variants, inventory, and additional images from the product management page.</p>
			</div>

			<form method="POST" action="AddProduct.php" enctype="multipart/form-data">
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
						<label for="product_image">
							<span class="material-symbols-outlined">image</span>
							Product Image
						</label>
						<input type="file" id="product_image" name="product_image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
						<small>Optional - JPG, PNG, GIF, WebP (max 5MB)</small>
						<div id="imagePreview" style="display:none;margin-top:10px;">
							<img id="previewImg" style="max-width:100%;max-height:200px;border-radius:8px;border:1px solid #e2e8f0;" alt="Preview">
						</div>
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
		</div>
	</div>

	<script src="<?php echo $jsBasePath; ?>addProduct.js?v=<?php echo filemtime(__DIR__ . '/../../js/addProduct.js'); ?>"></script>
</body>

</html>
