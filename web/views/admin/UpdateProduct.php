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
require_once __DIR__ . '/../../helpers/ActivityLogger.php';

// Base paths
$currentFileDir = __DIR__;
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relativePath = str_replace($docRoot, '', str_replace('\\', '/', $webRootDir));
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$jsBasePath = $webBasePath . 'js/';

$db = new Database();
$conn = $db->getConnection();
require_once __DIR__ . '/../../service/ProductService.php';
$productService = new ProductService($conn);

$flashSuccess = $_SESSION['success_message'] ?? '';
$flashError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
	$_SESSION['error_message'] = 'Invalid product id.';
	header('Location: AdminProduct.php');
	exit;
}

$redirectBack = function () use ($productId) {
	header('Location: UpdateProduct.php?id=' . $productId);
	exit;
};

// Handle POST actions specific to this product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	try {
		if ($action === 'update_product') {
			$productIdPosted = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
			if ($productIdPosted !== $productId) {
				throw new Exception('Mismatched product id.');
			}

			$name = trim($_POST['product_name'] ?? '');
			$category = trim($_POST['category'] ?? '');
			$description = trim($_POST['description'] ?? '');
			$cost = $_POST['cost'] ?? null;
			$originalPrice = $_POST['original_price'] ?? null;
			$sellingPrice = $_POST['selling_price'] ?? null;
			$enableVariant = isset($_POST['enable_variant']) && $_POST['enable_variant'] === '1';
			$variantColor = trim($_POST['variant_color'] ?? '');

			if ($name === '' || $category === '') {
				throw new Exception('Product name and category are required.');
			}

			foreach ([['Cost', $cost], ['Original price', $originalPrice], ['Selling price', $sellingPrice]] as [$label, $value]) {
				if ($value === null || $value === '' || !is_numeric($value) || (float)$value < 0) {
					throw new Exception($label . ' must be a non-negative number.');
				}
			}

			if ($enableVariant && $variantColor === '') {
				throw new Exception('Variant color is required when enabling variants.');
			}

			// Get old values for logging
			$oldStmt = $conn->prepare('SELECT p.product_name, p.category, p.description, pr.cost, pr.original_price, pr.selling_price FROM product p LEFT JOIN product_price pr ON p.product_id = pr.product_id WHERE p.product_id = :id');
			$oldStmt->execute([':id' => $productId]);
			$oldProduct = $oldStmt->fetch(PDO::FETCH_ASSOC);
			$oldValues = $oldProduct ? [
				'product_name' => $oldProduct['product_name'] ?? '',
				'category' => $oldProduct['category'] ?? '',
				'description' => $oldProduct['description'] ?? '',
				'cost' => $oldProduct['cost'] ?? '',
				'original_price' => $oldProduct['original_price'] ?? '',
				'selling_price' => $oldProduct['selling_price'] ?? ''
			] : null;

			$conn->beginTransaction();

			$stmt = $conn->prepare('UPDATE product SET product_name = :name, category = :category, description = :description WHERE product_id = :id');
			$stmt->execute([
				':name' => $name,
				':category' => $category,
				':description' => $description,
				':id' => $productId,
			]);

			$stmt = $conn->prepare('UPDATE product_price SET cost = :cost, original_price = :original, selling_price = :selling WHERE product_id = :id');
			$stmt->execute([
				':cost' => $cost,
				':original' => $originalPrice,
				':selling' => $sellingPrice,
				':id' => $productId,
			]);

			if ($enableVariant) {
				$checkStmt = $conn->prepare('SELECT variant_id FROM product_variant WHERE product_id = :pid AND color = :color');
				$checkStmt->execute([':pid' => $productId, ':color' => $variantColor]);
				$existingVariant = $checkStmt->fetch(PDO::FETCH_ASSOC);
				if ($existingVariant) {
					throw new Exception('Variant with color "' . html_escape($variantColor) . '" already exists for this product.');
				}

				$variantStmt = $conn->prepare('INSERT INTO product_variant (product_id, color) VALUES (:pid, :color)');
				$variantStmt->execute([':pid' => $productId, ':color' => $variantColor]);
			}

			$conn->commit();

			if ($oldValues) {
				$newValues = [
					'product_name' => $name,
					'category' => $category,
					'description' => $description,
					'cost' => $cost,
					'original_price' => $originalPrice,
					'selling_price' => $sellingPrice
				];
				ActivityLogger::logProductUpdate($productId, $name, $oldValues, $newValues);
			}

			$msg = 'Product updated successfully.';
			if ($enableVariant) {
				$msg .= ' Variant "' . html_escape($variantColor) . '" has been created.';
			}
			$_SESSION['success_message'] = $msg;
		}

		if ($action === 'delete_variant') {
			$variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
			if ($variantId <= 0) {
				throw new Exception('Invalid variant id.');
			}

			$conn->beginTransaction();

			$stmt = $conn->prepare('DELETE FROM inventory WHERE variant_id = :vid');
			$stmt->execute([':vid' => $variantId]);

			$stmt = $conn->prepare('DELETE FROM product_image WHERE variant_id = :vid');
			$stmt->execute([':vid' => $variantId]);

			$stmt = $conn->prepare('DELETE FROM cart_item WHERE variant_id = :vid');
			$stmt->execute([':vid' => $variantId]);

			$stmt = $conn->prepare('DELETE FROM product_variant WHERE variant_id = :vid AND product_id = :pid');
			$stmt->execute([':vid' => $variantId, ':pid' => $productId]);

			$conn->commit();
			$_SESSION['success_message'] = 'Variant deleted.';
		}

		if ($action === 'add_image') {
			$imageType = trim($_POST['image_type'] ?? 'main');
			$variantId = isset($_POST['image_variant_id']) ? (int)$_POST['image_variant_id'] : 0;
			$variantIdValue = $variantId > 0 ? $variantId : null;

			if (!isset($_FILES['image_file'])) {
				throw new Exception('Please select an image file to upload.');
			}

			if (!in_array($imageType, ['main', 'secondary'])) {
				$imageType = 'main';
			}

			// Resolve product name for filename
			$nameStmt = $conn->prepare('SELECT product_name FROM product WHERE product_id = :id');
			$nameStmt->execute([':id' => $productId]);
			$nameRow = $nameStmt->fetch(PDO::FETCH_ASSOC);
			$productNameForFile = $nameRow ? $nameRow['product_name'] : ('product_' . $productId);

			// Compute upload directory (absolute path under web/images/products)
			$uploadDir = rtrim(str_replace('\\', '/', $webRootDir), '/') . '/images/products/';

			// Perform upload via ProductService
			$savedPath = $productService->handleProductImageUpload($_FILES['image_file'], $productNameForFile, $uploadDir);

			$stmt = $conn->prepare('INSERT INTO product_image (product_id, variant_id, image_path, type) VALUES (:pid, :vid, :path, :type)');
			$stmt->execute([
				':pid' => $productId,
				':vid' => $variantIdValue,
				':path' => $savedPath,
				':type' => $imageType,
			]);

			$_SESSION['success_message'] = 'Image uploaded and added successfully.';
		}

		if ($action === 'delete_image') {
			$imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
			if ($imageId <= 0) {
				throw new Exception('Invalid image id.');
			}

			$stmt = $conn->prepare('DELETE FROM product_image WHERE id = :id AND product_id = :pid');
			$stmt->execute([':id' => $imageId, ':pid' => $productId]);
			$_SESSION['success_message'] = 'Image deleted.';
		}
	} catch (Exception $e) {
		if ($conn->inTransaction()) {
			$conn->rollBack();
		}
		$_SESSION['error_message'] = $e->getMessage();
	}

	$redirectBack();
}

// Fetch data for view
$stmt = $conn->prepare('SELECT p.product_id, p.product_name, p.category, p.description, pr.cost, pr.original_price, pr.selling_price FROM product p LEFT JOIN product_price pr ON pr.product_id = p.product_id WHERE p.product_id = :id');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
	$_SESSION['error_message'] = 'Product not found.';
	header('Location: AdminProduct.php');
	exit;
}

$variantStmt = $conn->prepare('SELECT variant_id, color FROM product_variant WHERE product_id = :id ORDER BY variant_id');
$variantStmt->execute([':id' => $productId]);
$variants = $variantStmt->fetchAll(PDO::FETCH_ASSOC);

$imageStmt = $conn->prepare('SELECT id, image_path, variant_id, type FROM product_image WHERE product_id = :id ORDER BY variant_id, id');
$imageStmt->execute([':id' => $productId]);
$images = $imageStmt->fetchAll(PDO::FETCH_ASSOC);

// Group images by variant_id (null => product-level)
$imagesByVariant = [];
foreach ($images as $img) {
	$key = $img['variant_id'] === null ? 'product' : (string)$img['variant_id'];
	if (!isset($imagesByVariant[$key])) {
		$imagesByVariant[$key] = [];
	}
	$imagesByVariant[$key][] = $img;
}

// Helper to build public URL from stored path
$makeImageUrl = function($path) use ($webBasePath) {
	if (!$path) return '';
	$clean = str_replace('\\', '/', $path);
	if (strpos($clean, 'http://') === 0 || strpos($clean, 'https://') === 0) return $clean;
	if (strpos($clean, 'web/') === 0) return $webBasePath . substr($clean, 4);
	return $webBasePath . ltrim($clean, '/');
};

$pageTitle = 'Manage Variants & Images';
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
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminProduct.css?v=<?php echo filemtime(__DIR__ . '/../../css/AdminProduct.css'); ?>">
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>UpdateProduct.css?v=<?php echo filemtime(__DIR__ . '/../../css/UpdateProduct.css'); ?>">
</head>

<body>
	<div class="page-container">
		<div class="page-header">
			<div style="display:flex;align-items:center;gap:12px;">
				<span class="material-symbols-outlined" style="font-size:32px;color:#FF523B;">inventory_2</span>
				<h1 class="page-title">Manage Product</h1>
			</div>
			<div style="display:flex;gap:10px;align-items:center;">
				<a href="AdminProduct.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to list</a>
			</div>
		</div>

		<?php /* Toasts handle feedback */ ?>

		<div class="content-card">
			<div style="margin-bottom:12px;">
				<div class="badge-soft">Product ID: <?php echo (int)$productId; ?></div>
				<div class="muted" style="margin-top:4px;">Editing product and its variants/images within admin dashboard iframe.</div>
			</div>

			<section class="section-card">
				<h2 class="section-title"><span class="material-symbols-outlined">edit</span>Product Details</h2>
				<form method="POST" action="UpdateProduct.php?id=<?php echo (int)$productId; ?>" class="form-grid">
					<input type="hidden" name="action" value="update_product">
					<input type="hidden" name="product_id" value="<?php echo (int)$productId; ?>">
					<div class="form-group">
						<label for="product_name">Product name</label>
						<input type="text" id="product_name" name="product_name" value="<?php echo html_escape($product['product_name']); ?>" required>
					</div>
					<div class="form-group">
						<label for="category">Category</label>
						<input type="text" id="category" name="category" value="<?php echo html_escape($product['category']); ?>" required>
					</div>
					<div class="form-group">
						<label for="cost">Cost (RM)</label>
						<input type="number" step="0.01" min="0" id="cost" name="cost" value="<?php echo html_escape($product['cost']); ?>" required>
					</div>
					<div class="form-group">
						<label for="original_price">Original price (RM)</label>
						<input type="number" step="0.01" min="0" id="original_price" name="original_price" value="<?php echo html_escape($product['original_price']); ?>" required>
					</div>
					<div class="form-group">
						<label for="selling_price">Selling price (RM)</label>
						<input type="number" step="0.01" min="0" id="selling_price" name="selling_price" value="<?php echo html_escape($product['selling_price']); ?>" required>
					</div>
					<div class="form-group" style="grid-column:1 / -1;">
						<label for="description">Description</label>
						<textarea id="description" name="description" placeholder="Short description" rows="3"><?php echo html_escape($product['description'] ?? ''); ?></textarea>
					</div>

					<div class="form-group" style="grid-column:1 / -1; margin-top:8px;">
						<label style="display:flex;align-items:center;justify-content:space-between;">
							<span>Create new variant for this product</span>
							<div class="toggle-switch-wrapper">
								<input type="checkbox" id="enable_variant" name="enable_variant" value="1" class="toggle-switch-input">
								<label for="enable_variant" class="toggle-switch-label">
									<span class="toggle-switch-inner"></span>
									<span class="toggle-switch-switch"></span>
								</label>
							</div>
						</label>
						<small id="variant_hint" class="muted">If the product already has variants, keep this off unless you need to add a new color.</small>
					</div>
					<div class="form-group" id="variant_color_group" style="grid-column:1 / -1; display:none;">
						<label for="variant_color">Variant Color <span style="color:#dc3545;">*</span></label>
						<input type="text" id="variant_color" name="variant_color" placeholder="e.g. Red, Blue, Black">
						<small class="muted">This will create a new variant with the specified color.</small>
					</div>
					<div style="grid-column:1 / -1; display:flex; justify-content:flex-end; gap:10px;">
						<a href="AdminProduct.php" class="btn btn-ghost">Cancel</a>
						<button type="submit" class="btn btn-primary">Save Changes</button>
					</div>
				</form>
			</section>

			<section class="section-card">
				<h2 class="section-title"><span class="material-symbols-outlined">palette</span>Variants</h2>
				<?php if (empty($variants)): ?>
					<p class="muted">No variants yet. Use the toggle above to add the first variant.</p>
				<?php else: ?>
					<table class="orders-table variants-table">
						<thead>
							<tr>
								<th style="width:80px;">ID</th>
								<th>Color</th>
								<th style="width:140px;text-align:right;">Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($variants as $variant): ?>
							<tr>
								<td>#<?php echo (int)$variant['variant_id']; ?></td>
								<td><?php echo html_escape($variant['color'] ?? ''); ?></td>
								<td style="text-align:right;">
								<form method="POST" action="UpdateProduct.php?id=<?php echo (int)$productId; ?>" class="delete-variant-form" style="display:inline;">
									<input type="hidden" name="action" value="delete_variant">
									<input type="hidden" name="variant_id" value="<?php echo (int)$variant['variant_id']; ?>">
									<button type="button" class="action-btn btn-delete delete-variant-btn" data-variant-id="<?php echo (int)$variant['variant_id']; ?>" data-variant-color="<?php echo html_escape($variant['color'] ?? ''); ?>" title="Delete variant">
											<i class="fas fa-trash"></i>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="section-card">
				<h2 class="section-title"><span class="material-symbols-outlined">photo</span>Images</h2>
				<div style="margin-bottom:12px;">
					<form method="POST" action="UpdateProduct.php?id=<?php echo (int)$productId; ?>" class="form-inline" enctype="multipart/form-data">
						<input type="hidden" name="action" value="add_image">
						<div class="form-group">
							<label>Upload image</label>
							<input type="file" name="image_file" accept="image/*" required>
						</div>
						<div class="form-group">
							<label>Type</label>
							<select name="image_type">
								<option value="main">Main</option>
								<option value="secondary">Secondary</option>
							</select>
						</div>
						<div class="form-group">
							<label>Variant (optional)</label>
							<select name="image_variant_id">
								<option value="0">Product level</option>
								<?php foreach ($variants as $variant): ?>
									<option value="<?php echo (int)$variant['variant_id']; ?>"><?php echo html_escape($variant['color']); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<button type="submit" class="btn btn-primary">Add Image</button>
						</div>
					</form>
				</div>

				<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
					<div>
						<h4 style="margin:0 0 8px 0;">Product images</h4>
						<?php if (!isset($imagesByVariant['product']) || empty($imagesByVariant['product'])): ?>
							<p class="muted">No product-level images.</p>
						<?php else: ?>
							<?php foreach ($imagesByVariant['product'] as $img): ?>
								<div class="image-pill">
									<img class="image-thumb" src="<?php echo html_escape($makeImageUrl($img['image_path'])); ?>" alt="image">
									<div>
										<div><?php echo html_escape(basename($img['image_path'])); ?></div>
										<small><?php echo html_escape($img['type']); ?></small>
									</div>
									<form method="POST" action="UpdateProduct.php?id=<?php echo (int)$productId; ?>" class="delete-image-form" style="margin-left:auto;">
										<input type="hidden" name="action" value="delete_image">
										<input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
										<button type="button" class="action-btn btn-delete delete-image-btn" data-image-id="<?php echo (int)$img['id']; ?>" data-image-name="<?php echo html_escape(basename($img['image_path'])); ?>" title="Delete image"><i class="fas fa-trash"></i></button>
									</form>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<?php foreach ($variants as $variant): ?>
						<div>
							<h4 style="margin:0 0 8px 0;">Variant: <?php echo html_escape($variant['color']); ?> (ID #<?php echo (int)$variant['variant_id']; ?>)</h4>
							<?php $vidKey = (string)$variant['variant_id']; ?>
							<?php if (!isset($imagesByVariant[$vidKey]) || empty($imagesByVariant[$vidKey])): ?>
								<p class="muted">No images for this variant.</p>
							<?php else: ?>
								<?php foreach ($imagesByVariant[$vidKey] as $img): ?>
									<div class="image-pill">
											<img class="image-thumb" src="<?php echo html_escape($makeImageUrl($img['image_path'])); ?>" alt="image">
											<div>
												<div><?php echo html_escape(basename($img['image_path'])); ?></div>
											<small><?php echo html_escape($img['type']); ?></small>
										</div>
											<form method="POST" action="UpdateProduct.php?id=<?php echo (int)$productId; ?>" class="delete-image-form" style="margin-left:auto;">
												<input type="hidden" name="action" value="delete_image">
												<input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
												<button type="button" class="action-btn btn-delete delete-image-btn" data-image-id="<?php echo (int)$img['id']; ?>" data-image-name="<?php echo html_escape(basename($img['image_path'])); ?>" title="Delete image"><i class="fas fa-trash"></i></button>
										</form>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
	</div>

	<div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

	<!-- Delete Variant Confirmation Modal -->
	<div class="modal-overlay" id="deleteVariantModal" style="display: none;">
		<div class="modal" style="max-width: 450px;">
			<div class="modal-header">
				<h3 style="color: #ef4444; display: flex; align-items: center; gap: 8px; margin: 0;">
					<i class="fas fa-exclamation-triangle"></i>
					Delete Variant
				</h3>
			</div>
			<div class="modal-body">
				<div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
					<p style="margin: 0 0 4px 0; font-size: 13px; color: #64748b;">Variant to delete:</p>
					<p id="deleteVariantColor" style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">-</p>
				</div>
				<p style="font-size: 14px; line-height: 1.5; color: #334155; margin: 0;">
					This will delete the variant and all associated inventory and images. This action cannot be undone.
				</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-ghost" id="cancelDeleteVariantBtn" style="flex: 1;">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmDeleteVariantBtn" style="flex: 1; background-color: #ef4444; border-color: #ef4444;">
					<i class="fas fa-trash"></i> Delete Variant
				</button>
			</div>
		</div>
	</div>

	<!-- Delete Image Confirmation Modal -->
	<div class="modal-overlay" id="deleteImageModal" style="display: none;">
		<div class="modal" style="max-width: 450px;">
			<div class="modal-header">
				<h3 style="color: #ef4444; display: flex; align-items: center; gap: 8px; margin: 0;">
					<i class="fas fa-exclamation-triangle"></i>
					Delete Image
				</h3>
			</div>
			<div class="modal-body">
				<div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
					<p style="margin: 0 0 4px 0; font-size: 13px; color: #64748b;">Image to delete:</p>
					<p id="deleteImageName" style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">-</p>
				</div>
				<p style="font-size: 14px; line-height: 1.5; color: #334155; margin: 0;">
					Are you sure you want to delete this image? This action cannot be undone.
				</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-ghost" id="cancelDeleteImageBtn" style="flex: 1;">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmDeleteImageBtn" style="flex: 1; background-color: #ef4444; border-color: #ef4444;">
					<i class="fas fa-trash"></i> Delete Image
				</button>
			</div>
		</div>
	</div>

	<script src="<?php echo $jsBasePath; ?>updateProduct.js?v=<?php echo filemtime(__DIR__ . '/../../js/updateProduct.js'); ?>"></script>
	<script>
	document.addEventListener('DOMContentLoaded', function(){
		<?php if (!empty($flashSuccess)): ?>
		if (window.__adminShowToast) {
			window.__adminShowToast('success', 'Success', <?php echo json_encode($flashSuccess, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>);
		}
		<?php endif; ?>
		<?php if (!empty($flashError)): ?>
		if (window.__adminShowToast) {
			window.__adminShowToast('error', 'Error', <?php echo json_encode($flashError, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>);
		}
		<?php endif; ?>
	});
	</script>
</body>

</html>
