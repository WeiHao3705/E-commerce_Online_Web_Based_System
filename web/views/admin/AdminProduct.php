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

		try {
			if ($productId <= 0) {
				throw new Exception('Invalid product ID.');
			}

			if ($name === '' || $category === '') {
				throw new Exception('Product name and category are required.');
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
			
			$_SESSION['success_message'] = 'Product updated successfully.';
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

			// Get product name before delete for logging
			$nameStmt = $conn->prepare('SELECT product_name FROM product WHERE product_id = :id');
			$nameStmt->execute([':id' => $productId]);
			$product = $nameStmt->fetch(PDO::FETCH_ASSOC);
			$productName = $product ? ($product['product_name'] ?? 'Unknown') : 'Unknown';

			$stmt = $conn->prepare('DELETE FROM product WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

			// Log product deletion
			ActivityLogger::logProductDelete($productId, $productName);

			$_SESSION['success_message'] = 'Product deleted.';
		} catch (Exception $e) {
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
</head>

<body>
	<div class="page-container">
		<div class="page-header">
			<div>
				<p style="margin:0;color:#64748b;font-size:13px;font-weight:600;">Backoffice</p>
				<h1 class="page-title">Products</h1>
			</div>
			<div style="display:flex;gap:10px;align-items:center;">
			<a href="AddProduct.php" class="btn btn-primary">
				<span class="material-symbols-outlined">add</span>
				Add product
			</a>
			<a href="Restock.php" class="btn btn-secondary">
				<span class="material-symbols-outlined">inventory_2</span>
				Restock
			</a>
			</div>
		</div>

		<?php if ($flashSuccess): ?>
			<div class="message message-success"><?php echo html_escape($flashSuccess); ?></div>
		<?php endif; ?>
		<?php if ($flashError): ?>
			<div class="message message-error"><?php echo html_escape($flashError); ?></div>
		<?php endif; ?>

		<div class="content-card">
			<div class="toolbar">
				<form method="GET" action="AdminProduct.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
					<input type="text" name="search" class="search-input" placeholder="Search by name or category" value="<?php echo html_escape($search); ?>">
					<select name="category" class="select-input">
						<option value="">All categories</option>
						<?php foreach ($categories as $cat): ?>
							<option value="<?php echo html_escape($cat); ?>" <?php echo $filterCategory === $cat ? 'selected' : ''; ?>><?php echo html_escape($cat); ?></option>
						<?php endforeach; ?>
					</select>
					<select name="sortBy" class="select-input">
						<option value="id" <?php echo $sortBy === 'id' ? 'selected' : ''; ?>>Newest</option>
						<option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name</option>
						<option value="category" <?php echo $sortBy === 'category' ? 'selected' : ''; ?>>Category</option>
						<option value="price" <?php echo $sortBy === 'price' ? 'selected' : ''; ?>>Price</option>
						<option value="variants" <?php echo $sortBy === 'variants' ? 'selected' : ''; ?>>Variants</option>
						<option value="stock" <?php echo $sortBy === 'stock' ? 'selected' : ''; ?>>Stock</option>
					</select>
					<select name="sortOrder" class="select-input">
						<option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Desc</option>
						<option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Asc</option>
					</select>
					<select name="limit" class="select-input">
						<?php foreach ([10, 20, 50] as $opt): ?>
							<option value="<?php echo $opt; ?>" <?php echo $limit === $opt ? 'selected' : ''; ?>><?php echo $opt; ?>/page</option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="btn btn-secondary">
						<span class="material-symbols-outlined">search</span>
						Filter
					</button>
					<?php if ($search || $filterCategory): ?>
						<a class="btn btn-ghost" href="AdminProduct.php">Clear</a>
					<?php endif; ?>
				</form>
			</div>

			<section class="table-container">
				<table class="orders-table">
					<thead>
						<tr>
							<th>ID</th>
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
									<td><?php echo (int)$product['product_id']; ?></td>
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
											<a class="action-btn btn-view" href="<?php echo $viewsBasePath; ?>product/ProductDetails.php?id=<?php echo (int)$product['product_id']; ?>" target="_blank" title="View">
												<i class="fas fa-eye"></i>
											</a>										<button class="action-btn btn-edit" 
											data-id="<?php echo (int)$product['product_id']; ?>"
											data-name="<?php echo html_escape($product['product_name']); ?>"
											data-category="<?php echo html_escape($product['category']); ?>"
											data-description="<?php echo html_escape($product['description'] ?? ''); ?>"
											data-cost="<?php echo $product['cost'] ?? ''; ?>"
											data-original="<?php echo $product['original_price'] ?? ''; ?>"
											data-selling="<?php echo $product['selling_price'] ?? ''; ?>"
											title="Edit">
											<i class="fas fa-edit"></i>
										</button>											<form method="POST" action="AdminProduct.php" class="delete-form" style="margin:0;display:inline;">
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
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-ghost" id="cancelEdit">Cancel</button>
					<button type="submit" class="btn btn-primary">Update</button>
				</div>
			</form>
		</div>
	</div>

    <script src="<?php echo $jsBasePath; ?>adminProduct.js?v=<?php echo filemtime(__DIR__ . '/../../js/adminProduct.js'); ?>"></script>
</body>

</html>
