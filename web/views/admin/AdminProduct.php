<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Only admins can access
if (!isset($_SESSION['user'])) {
	// Not logged in - redirect to login
	header('Location: ../../security/login.php');
	exit;
}

// Check if user has admin role
if ($_SESSION['user']->role !== 'admin') {
	// User is logged in but not admin - redirect to member home
	$_SESSION['error_message'] = 'Access denied. Admin privileges required.';
	header('Location: ../../index.php');
	exit;
}

// Check if loaded via AJAX/iframe (no full page render needed)
$isFramed = isset($_GET['framed']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

require_once __DIR__ . '/../../../helpers.php';
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../helpers/ActivityLogger.php';

// Base paths
$currentFileDir = __DIR__;
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$jsBasePath = $webBasePath . 'js/';
$viewsBasePath = $webBasePath . 'views/';

$db = new Database();
$conn = $db->getConnection();

$flashSuccess = $_SESSION['success_message'] ?? '';
$flashError = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle AJAX requests for getting product variants
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_variants') {
	header('Content-Type: application/json');
	$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
	
	if ($productId <= 0) {
		echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
		exit;
	}
	
	try {
		$stmt = $conn->prepare('SELECT variant_id, color FROM product_variant WHERE product_id = :id ORDER BY variant_id');
		$stmt->execute([':id' => $productId]);
		$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode(['success' => true, 'variants' => $variants]);
	} catch (Exception $e) {
		echo json_encode(['success' => false, 'message' => $e->getMessage()]);
	}
	exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	$redirect = function () {
		header('Location: AdminProduct.php');
		exit;
	};

	if ($action === 'update_product') {
		$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
		$name = trim($_POST['product_name'] ?? '');
		$category = trim($_POST['category'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$cost = $_POST['cost'] ?? null;
		$originalPrice = $_POST['original_price'] ?? null;
		$sellingPrice = $_POST['selling_price'] ?? null;
		$enableVariant = isset($_POST['enable_variant']) && $_POST['enable_variant'] === '1';
		$variantColor = trim($_POST['variant_color'] ?? '');

		try {
			if ($productId <= 0) {
				throw new Exception('Invalid product ID.');
			}

			if ($name === '' || $category === '') {
				throw new Exception('Product name and category are required.');
			}

			if ($enableVariant && $variantColor === '') {
				throw new Exception('Variant color is required when enabling variants.');
			}

			foreach ([['Cost', $cost], ['Original price', $originalPrice], ['Selling price', $sellingPrice]] as [$label, $value]) {
				if ($value === null || $value === '' || !is_numeric($value) || (float)$value < 0) {
					throw new Exception($label . ' must be a non-negative number.');
				}
			}

			// Get old values for logging
			$oldStmt = $conn->prepare('SELECT p.product_name, p.category, p.description, pr.cost, pr.original_price, pr.selling_price 
				FROM product p 
				LEFT JOIN product_price pr ON p.product_id = pr.product_id 
				WHERE p.product_id = :id');
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

			// Update product
			$stmt = $conn->prepare('UPDATE product SET product_name = :name, category = :category, description = :description WHERE product_id = :id');
			$stmt->execute([
				':name' => $name,
				':category' => $category,
				':description' => $description,
				':id' => $productId,
			]);

			// Update product price
			$stmt = $conn->prepare('UPDATE product_price SET cost = :cost, original_price = :original, selling_price = :selling WHERE product_id = :id');
			$stmt->execute([
				':cost' => $cost,
				':original' => $originalPrice,
				':selling' => $sellingPrice,
				':id' => $productId,
			]);

			// Handle variant creation if enabled
			if ($enableVariant) {
				// Check if variant already exists for this product and color
				$checkStmt = $conn->prepare('SELECT variant_id FROM product_variant WHERE product_id = :pid AND color = :color');
				$checkStmt->execute([':pid' => $productId, ':color' => $variantColor]);
				$existingVariant = $checkStmt->fetch(PDO::FETCH_ASSOC);

				if ($existingVariant) {
					throw new Exception('Variant with color "' . html_escape($variantColor) . '" already exists for this product.');
				}

				// Create new variant
				$variantStmt = $conn->prepare('INSERT INTO product_variant (product_id, color) VALUES (:pid, :color)');
				$variantStmt->execute([':pid' => $productId, ':color' => $variantColor]);
			}

			$conn->commit();
			
			// Log product update
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
			
			$successMsg = 'Product updated successfully.';
			if ($enableVariant) {
				$successMsg .= ' Variant "' . html_escape($variantColor) . '" has been created.';
			}
			$_SESSION['success_message'] = $successMsg;
		} catch (Exception $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error_message'] = $e->getMessage();
		}

		$redirect();
	}

	if ($action === 'create_product') {
		$name = trim($_POST['product_name'] ?? '');
		$category = trim($_POST['category'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$cost = $_POST['cost'] ?? null;
		$originalPrice = $_POST['original_price'] ?? null;
		$sellingPrice = $_POST['selling_price'] ?? null;
		$imagePath = trim($_POST['image_path'] ?? '');

		try {
			if ($name === '' || $category === '') {
				throw new Exception('Product name and category are required.');
			}

			foreach ([['Cost', $cost], ['Original price', $originalPrice], ['Selling price', $sellingPrice]] as [$label, $value]) {
				if ($value === null || $value === '' || !is_numeric($value) || (float)$value < 0) {
					throw new Exception($label . ' must be a non-negative number.');
				}
			}

			$conn->beginTransaction();

			$stmt = $conn->prepare('INSERT INTO product (product_name, category, description) VALUES (:name, :category, :description)');
			$stmt->execute([
				':name' => $name,
				':category' => $category,
				':description' => $description,
			]);

			$productId = (int)$conn->lastInsertId();

			$stmt = $conn->prepare('INSERT INTO product_price (product_id, cost, original_price, selling_price) VALUES (:id, :cost, :original, :selling)');
			$stmt->execute([
				':id' => $productId,
				':cost' => $cost,
				':original' => $originalPrice,
				':selling' => $sellingPrice,
			]);

			if ($imagePath !== '') {
				$stmt = $conn->prepare('INSERT INTO product_image (product_id, variant_id, image_path, type) VALUES (:id, NULL, :path, :type)');
				$stmt->execute([
					':id' => $productId,
					':path' => $imagePath,
					':type' => 'main',
				]);
			}

			$conn->commit();
			
			// Log product creation
			ActivityLogger::logProductCreate($productId, $name);
			
			$_SESSION['success_message'] = 'Product created successfully.';
		} catch (Exception $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error_message'] = $e->getMessage();
		}

		$redirect();
	}

	if ($action === 'delete_product') {
		$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

		try {
			if ($productId <= 0) {
				throw new Exception('Invalid product id.');
			}

			$conn->beginTransaction();

			// Delete related records in correct order (child tables first)
			// 1. Delete inventory for variants of this product
			$stmt = $conn->prepare('DELETE FROM inventory WHERE variant_id IN (SELECT variant_id FROM product_variant WHERE product_id = :id)');
			$stmt->execute([':id' => $productId]);

			// 2. Delete inventory directly linked to product (no variant)
			$stmt = $conn->prepare('DELETE FROM inventory WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			// 3. Delete product images (for variants)
			$stmt = $conn->prepare('DELETE FROM product_image WHERE variant_id IN (SELECT variant_id FROM product_variant WHERE product_id = :id)');
			$stmt->execute([':id' => $productId]);

			// 4. Delete product images (product-level)
			$stmt = $conn->prepare('DELETE FROM product_image WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			// 5. Delete cart items referencing this product
			$stmt = $conn->prepare('DELETE FROM cart_item WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			// 6. Delete product variants
			$stmt = $conn->prepare('DELETE FROM product_variant WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			// 7. Delete product price
			$stmt = $conn->prepare('DELETE FROM product_price WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			// 8. Finally delete the product
			$stmt = $conn->prepare('DELETE FROM product WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			$conn->commit();
			$_SESSION['success_message'] = 'Product deleted.';
		} catch (Exception $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error_message'] = $e->getMessage();
		}

		$redirect();
	}

	if ($action === 'batch_delete') {
		$productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];

		try {
			if (empty($productIds)) {
				throw new Exception('No products selected for deletion.');
			}

			$conn->beginTransaction();

			$deletedCount = 0;
			foreach ($productIds as $productId) {
				$productId = (int)$productId;
				if ($productId <= 0) continue;

				// Delete related records in correct order (child tables first)
				$stmt = $conn->prepare('DELETE FROM inventory WHERE variant_id IN (SELECT variant_id FROM product_variant WHERE product_id = :id)');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM inventory WHERE product_id = :id');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM product_image WHERE variant_id IN (SELECT variant_id FROM product_variant WHERE product_id = :id)');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM product_image WHERE product_id = :id');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM cart_item WHERE product_id = :id');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM product_variant WHERE product_id = :id');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM product_price WHERE product_id = :id');
				$stmt->execute([':id' => $productId]);

				$stmt = $conn->prepare('DELETE FROM product WHERE product_id = :id');
				$stmt->execute([':id' => $productId]);

				$deletedCount++;
			}

			$conn->commit();
			$_SESSION['success_message'] = $deletedCount . ' product(s) deleted successfully.';
		} catch (Exception $e) {
			if ($conn->inTransaction()) {
				$conn->rollBack();
			}
			$_SESSION['error_message'] = $e->getMessage();
		}

		$redirect();
	}
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterCategory = isset($_GET['category']) ? trim($_GET['category']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'id';
$sortOrder = isset($_GET['sortOrder']) && strtoupper($_GET['sortOrder']) === 'ASC' ? 'ASC' : 'DESC';

if ($page < 1) {
	$page = 1;
}
if ($limit < 5 || $limit > 50) {
	$limit = 10;
}

$offset = ($page - 1) * $limit;

$sortMap = [
	'name' => 'p.product_name',
	'category' => 'p.category',
	'price' => 'pr.selling_price',
	'variants' => 'variant_count',
	'stock' => 'stock_quantity',
	'id' => 'p.product_id',
];
$sortColumn = $sortMap[$sortBy] ?? $sortMap['id'];

$where = [];
$params = [];
if ($search !== '') {
	$where[] = '(p.product_name LIKE :search OR p.category LIKE :search)';
	$params[':search'] = '%' . $search . '%';
}
if ($filterCategory !== '') {
	$where[] = 'p.category = :category';
	$params[':category'] = $filterCategory;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = 'SELECT COUNT(*) FROM product p ' . $whereSql;
$countStmt = $conn->prepare($countSql);
foreach ($params as $key => $value) {
	$countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = $totalRows > 0 ? (int)ceil($totalRows / $limit) : 1;

$sql = "
	SELECT
		p.product_id,
		p.product_name,
		p.category,
		p.description,
		pr.cost,
		pr.original_price,
		pr.selling_price,
		COALESCE((
			SELECT pi2.image_path
			FROM product_image pi2
			WHERE pi2.product_id = p.product_id
			ORDER BY CASE WHEN pi2.type = 'main' THEN 0 ELSE 1 END, pi2.id
			LIMIT 1
		), '') AS image_path,
		COALESCE((
			SELECT COUNT(*) FROM product_variant pv2 WHERE pv2.product_id = p.product_id
		), 0) AS variant_count,
		COALESCE((
			SELECT SUM(i2.stock_quantity)
			FROM inventory i2
			WHERE i2.product_id = p.product_id OR i2.variant_id IN (
				SELECT variant_id FROM product_variant WHERE product_id = p.product_id
			)
		), 0) AS stock_quantity
	FROM product p
	LEFT JOIN product_price pr ON pr.product_id = p.product_id
	$whereSql
	ORDER BY $sortColumn $sortOrder
	LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
	$stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryStmt = $conn->query('SELECT DISTINCT category FROM product ORDER BY category');
$categories = $categoryStmt ? $categoryStmt->fetchAll(PDO::FETCH_COLUMN) : [];

$pageTitle = 'Admin Products';
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
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminOrder.css">
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminProduct.css?v=<?php echo filemtime(__DIR__ . '/../../css/AdminProduct.css'); ?>">
	<style>
		/* Lightweight toast styles (scoped to admin page) */
		.toast-container{position:fixed;top:20px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:10px;z-index:9999}
		.toast{min-width:260px;max-width:420px;padding:12px 14px;border-radius:10px;color:#0b1220;box-shadow:0 10px 25px rgba(2,6,23,.15);background:#fff;display:flex;align-items:flex-start;gap:10px;border:1px solid #e5e7eb;opacity:0;transform:translateY(-6px);animation:toast-in .18s ease-out forwards}
		.toast.success{border-color:#86efac;background:#f0fdf4}
		.toast.error{border-color:#fca5a5;background:#fef2f2}
		.toast .title{font-weight:700;margin-bottom:2px}
		.toast .msg{font-size:13px;color:#374151}
		.toast .close{margin-left:auto;background:transparent;border:none;font-size:16px;cursor:pointer;color:#64748b}
		@keyframes toast-in{to{opacity:1;transform:translateY(0)}}

		/* Toggle Switch Styles */
		.toggle-switch-wrapper{position:relative;display:inline-block}
		.toggle-switch-input{display:none}
		.toggle-switch-label{display:block;width:50px;height:26px;background-color:#cbd5e0;border-radius:26px;position:relative;cursor:pointer;transition:background-color 0.3s ease;margin:0}
		.toggle-switch-label:hover{background-color:#a0aec0}
		.toggle-switch-input:checked + .toggle-switch-label{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%)}
		.toggle-switch-input:checked + .toggle-switch-label:hover{background:linear-gradient(135deg, #5568d3 0%, #6a4190 100%)}
		.toggle-switch-switch{position:absolute;top:2px;left:2px;width:22px;height:22px;background-color:white;border-radius:50%;transition:transform 0.3s ease;box-shadow:0 2px 4px rgba(0, 0, 0, 0.2)}
		.toggle-switch-input:checked + .toggle-switch-label .toggle-switch-switch{transform:translateX(24px)}
		.toggle-switch-input:disabled + .toggle-switch-label{opacity:0.6;cursor:not-allowed;background-color:#e2e8f0}
		.toggle-switch-input:disabled:checked + .toggle-switch-label{background:linear-gradient(135deg, #a5b4fc 0%, #c4b5fd 100%);opacity:0.7}
		.toggle-switch-input:disabled + .toggle-switch-label:hover{background-color:#e2e8f0}
		.toggle-switch-input:disabled:checked + .toggle-switch-label:hover{background:linear-gradient(135deg, #a5b4fc 0%, #c4b5fd 100%)}
	</style>
</head>

<body>
	<div class="page-container">
		<div class="page-header">
			<div style="display:flex;align-items:center;gap:12px;">
				<span class="material-symbols-outlined" style="font-size:32px;color:#FF523B;">inventory_2</span>
				<h1 class="page-title">Product Management</h1>
			</div>
			<div style="display:flex;gap:10px;align-items:center;">
			<a href="AddProduct.php" class="btn btn-primary">
				<span class="material-symbols-outlined">add</span>
				Add Product/Variant
			</a>
			<a href="Restock.php" class="btn btn-secondary">
				<span class="material-symbols-outlined">inventory_2</span>
				Restock
			</a>
			</div>
		</div>

		<?php /* Toasts handle feedback; keeping old divs hidden intentionally */ ?>

		<div class="content-card">
			<!-- Filters Section -->
			<section class="filters-section">
				<form method="GET" action="AdminProduct.php" class="filters-form" id="filterForm">
					<div class="filter-group">
						<label><i class="fas fa-search"></i> Search</label>
						<input type="text" name="search" id="filterSearch" placeholder="Product name, category..." value="<?php echo html_escape($search); ?>">
					</div>

					<div class="filter-group">
						<label><i class="fas fa-filter"></i> Category</label>
						<select name="category" id="filterCategory">
							<option value="">All Categories</option>
							<?php foreach ($categories as $cat): ?>
								<option value="<?php echo html_escape($cat); ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>><?php echo html_escape($cat); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="filter-group">
						<label><i class="fas fa-sort"></i> Sort By</label>
						<select name="sortBy" id="filterSortBy">
							<option value="id" <?php echo $sortBy === 'id' ? 'selected' : ''; ?>>Date Added</option>
							<option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
							<option value="category" <?php echo $sortBy === 'category' ? 'selected' : ''; ?>>Category</option>
							<option value="price" <?php echo $sortBy === 'price' ? 'selected' : ''; ?>>Price</option>
							<option value="variants" <?php echo $sortBy === 'variants' ? 'selected' : ''; ?>>Variants</option>
							<option value="stock" <?php echo $sortBy === 'stock' ? 'selected' : ''; ?>>Stock</option>
						</select>
					</div>

					<div class="filter-group">
						<label><i class="fas fa-arrow-up"></i> Order</label>
						<select name="sortOrder" id="filterSortOrder">
							<option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
							<option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
						</select>
					</div>

					<div class="filter-actions">
						<a href="AdminProduct.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
					</div>
				</form>
			</section>

			<!-- Bulk Actions Section -->
			<div class="bulk-actions-section" style="display: none;" id="bulkActionsSection">
				<button type="button" class="btn btn-danger" id="bulkDeleteBtn">
					<span class="material-symbols-outlined">delete</span>
					<span>Delete Selected (<span id="selectedCount">0</span>)</span>
				</button>
				<button type="button" class="btn btn-ghost" id="clearSelectionBtn">
					<span class="material-symbols-outlined">close</span>
					<span>Clear Selection</span>
				</button>
			</div>

			<section class="table-container">
				<table class="orders-table">
					<thead>
						<tr>
							<th class="col-checkbox"><input type="checkbox" id="selectAllCheckbox" title="Select all"></th>
							<th>Product</th>
							<th>Category</th>
							<th>Price</th>
							<th>Variants</th>
							<th>Stock</th>
							<th style="text-align:right;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($products)): ?>
							<tr>
								<td colspan="7" class="text-center" style="padding:30px;">
									<div class="empty-state">
										<i class="fas fa-inbox"></i>
										<p>No products found</p>
									</div>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($products as $product): ?>
								<?php
									$priceLabel = $product['selling_price'] !== null ? (float)$product['selling_price'] : ($product['original_price'] !== null ? (float)$product['original_price'] : 0);
									$stock = (int)$product['stock_quantity'];
									$stockBadge = $stock > 20 ? 'badge-green' : ($stock > 0 ? 'badge-amber' : 'badge-red');
									$stockText = $stock > 20 ? 'In stock' : ($stock > 0 ? 'Low stock' : 'Out of stock');
									$imageUrl = $product['image_path'] !== '' ? $product['image_path'] : $webBasePath . 'images/products/placeholder.png';
								?>
								<tr>
									<td class="col-checkbox">
										<input type="checkbox" class="product-checkbox" value="<?php echo (int)$product['product_id']; ?>" data-product-name="<?php echo html_escape($product['product_name']); ?>">
									</td>
									<td>
										<div class="product-cell">
											<!-- <img src="<?php echo html_escape($imageUrl); ?>" alt="Image" class="product-thumb"> -->
											<div>
												<div style="font-weight:700;"><?php echo html_escape($product['product_name']); ?></div>
												<div style="font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px;">
													<?php echo html_escape($product['description'] ?? ''); ?>
												</div>
											</div>
										</div>
									</td>
									<td><span class="badge badge-gray"><?php echo html_escape($product['category']); ?></span></td>
									<td>RM <?php echo number_format($priceLabel, 2); ?></td>
									<td><span class="badge badge-gray"><?php echo (int)$product['variant_count']; ?> variants</span></td>
									<td><span class="badge <?php echo $stockBadge; ?>"><?php echo $stockText; ?> (<?php echo $stock; ?>)</span></td>
									<td style="text-align:right;">
										<div class="action-buttons">
											<a class="action-btn btn-view" href="ViewProduct.php?id=<?php echo (int)$product['product_id']; ?>" title="View">
												<i class="fas fa-eye"></i>
											</a>
											<a class="action-btn btn-edit" href="UpdateProduct.php?id=<?php echo (int)$product['product_id']; ?>" title="Edit Products">
												<i class="fas fa-edit"></i>
											</a>
											<form method="POST" action="AdminProduct.php" class="delete-form" style="margin:0;display:inline;">
												<input type="hidden" name="action" value="delete_product">
												<input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
												<button type="submit" class="action-btn btn-delete" title="Delete">
													<i class="fas fa-trash"></i>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				</section>

			<?php if ($totalPages > 1): ?>
				<div class="pagination">
					<?php for ($p = 1; $p <= $totalPages; $p++): ?>
						<?php
							$query = $_GET;
							$query['page'] = $p;
							$url = 'AdminProduct.php?' . http_build_query($query);
						?>
						<?php if ($p === $page): ?>
							<span class="active"><?php echo $p; ?></span>
						<?php else: ?>
							<a href="<?php echo $url; ?>"><?php echo $p; ?></a>
						<?php endif; ?>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Toast mount point -->
	<div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

	<!-- Delete Confirmation Modal -->
	<div class="modal-overlay" id="deleteConfirmModal" style="display: none;">
		<div class="modal" style="max-width: 500px;">
			<div class="modal-header">
				<h3 style="color: #ef4444; display: flex; align-items: center; gap: 8px; margin: 0;">
					<i class="fas fa-exclamation-triangle"></i>
					Delete Product
				</h3>
			</div>
			<div class="modal-body" style="max-height: 400px; overflow-y: auto;">
				<!-- Product Details -->
				<div style="background-color: #f8fafc; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
					<p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b;">Product to delete:</p>
					<p id="deleteProductName" style="margin: 0; font-size: 16px; font-weight: 600; color: #1e293b;">-</p>
				</div>
				
				<!-- Variants Section -->
				<div id="deleteVariantsSection" style="display: none;">
					<p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; font-weight: 600;">Variants:</p>
					<div id="deleteVariantsList" style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 12px; margin-bottom: 16px;">
						<div id="variantItems"></div>
					</div>
					<p style="margin: 0 0 12px 0; font-size: 12px; color: #64748b;">
						<strong>Warning:</strong> Deleting the product will also delete all associated variants and their inventory data.
					</p>
				</div>
				
				<p id="deleteConfirmMessage" style="font-size: 14px; line-height: 1.5; color: #334155; margin: 0;">
					Are you sure you want to delete this product? This action cannot be undone.
				</p>
			</div>
			<div class="modal-footer" style="padding-bottom: 12px;">
				<button type="button" class="btn btn-ghost" id="cancelDeleteBtn" style="flex: 1;">Cancel</button>
				<button type="button" class="btn btn-primary" id="confirmDeleteBtn" style="flex: 1; background-color: #ef4444; border-color: #ef4444;">
					<i class="fas fa-trash"></i> Delete Product
				</button>
			</div>
		</div>
	</div>

	<div class="modal-overlay" id="editModal">
		<div class="modal">
			<div class="modal-header">
				<h3>Edit Product</h3>
				<button class="btn btn-ghost" id="closeEditModal">Close</button>
			</div>
			<form method="POST" action="AdminProduct.php">
				<input type="hidden" name="action" value="update_product">
				<input type="hidden" name="product_id" id="edit_product_id">
				<div class="form-grid">
					<div class="form-group">
						<label for="edit_product_name">Product name</label>
						<input type="text" id="edit_product_name" name="product_name" required>
					</div>
					<div class="form-group">
						<label for="edit_category">Category</label>
						<input type="text" id="edit_category" name="category" list="editCategoryList" required>
						<datalist id="editCategoryList">
							<?php foreach ($categories as $cat): ?>
								<option value="<?php echo html_escape($cat); ?>">
							<?php endforeach; ?>
						</datalist>
					</div>
					<div class="form-group">
						<label for="edit_cost">Cost (RM)</label>
						<input type="number" step="0.01" min="0" id="edit_cost" name="cost" required>
					</div>
					<div class="form-group">
						<label for="edit_original_price">Original price (RM)</label>
						<input type="number" step="0.01" min="0" id="edit_original_price" name="original_price" required>
					</div>
					<div class="form-group">
						<label for="edit_selling_price">Selling price (RM)</label>
						<input type="number" step="0.01" min="0" id="edit_selling_price" name="selling_price" required>
					</div>
				</div>
				<div class="form-group" style="margin-top:10px;">
					<label for="edit_description">Description</label>
					<textarea id="edit_description" name="description" placeholder="Short description"></textarea>
				</div>			<div class="form-group" style="margin-top:10px;">
				<label style="display: flex; align-items: center; justify-content: space-between;">
					<span>Enable variant for this product</span>
					<div class="toggle-switch-wrapper">
						<input type="checkbox" id="edit_enable_variant" name="enable_variant" value="1" class="toggle-switch-input">
						<label for="edit_enable_variant" class="toggle-switch-label">
							<span class="toggle-switch-inner"></span>
							<span class="toggle-switch-switch"></span>
						</label>
					</div>
				</label>
				<small id="edit_variant_status" style="color:#6c757d; display:block; margin-top:4px;"></small>
			</div>
			<div class="form-group" id="edit_variant_color_group" style="margin-top:10px; display:none;">
				<label for="edit_variant_color">Variant Color <span style="color:#dc3545;">*</span></label>
				<input type="text" id="edit_variant_color" name="variant_color" placeholder="e.g. Red, Blue, Black">
				<small style="color:#6c757d; display:block; margin-top:4px;">This will create a new variant with the specified color.</small>
			</div>				<div class="modal-footer">
					<button type="button" class="btn btn-ghost" id="cancelEdit">Cancel</button>
					<button type="submit" class="btn btn-primary">Update</button>
				</div>
			</form>
		</div>
	</div>

	<script>
	(function(){
		function showToast(type, title, message){
			var container = document.getElementById('toastContainer');
			if(!container){return}
			var el = document.createElement('div');
			el.className = 'toast ' + (type||'success');
			el.innerHTML = '<div><div class="title">'+(title||'Notice')+'</div><div class="msg">'+(message||'')+'</div></div>'+
				'<button class="close" aria-label="Close">×</button>';
			container.appendChild(el);
			var timer = setTimeout(function(){dismiss()}, 3500);
			function dismiss(){ if(!el) return; el.style.opacity='0'; el.style.transform='translateY(-6px)'; setTimeout(function(){ if(el&&el.parentNode){el.parentNode.removeChild(el)} }, 180); }
			el.querySelector('.close').addEventListener('click', function(){ clearTimeout(timer); dismiss(); });
		}
		// Expose for other scripts if needed
		window.__adminShowToast = showToast;
		// Server-provided flash messages
		<?php if (!empty($flashSuccess)): ?>
		showToast('success','Success', <?php echo json_encode($flashSuccess, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>);
		<?php endif; ?>
		<?php if (!empty($flashError)): ?>
		showToast('error','Error', <?php echo json_encode($flashError, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>);
		<?php endif; ?>
	})();
	</script>
	<script src="<?php echo $jsBasePath; ?>adminProduct.js?v=<?php echo filemtime(__DIR__ . '/../../js/adminProduct.js'); ?>"></script>
</body>

</html>
