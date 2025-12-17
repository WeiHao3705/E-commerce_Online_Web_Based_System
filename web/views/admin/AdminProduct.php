<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Only admins can access
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
	header('Location: ../../security/login.php');
	exit;
}

// Check if loaded via AJAX/iframe (no full page render needed)
$isFramed = isset($_GET['framed']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

require_once __DIR__ . '/../../../helpers.php';
require_once __DIR__ . '/../../database/connection.php';

// Base paths
$currentFileDir = __DIR__;
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
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

			$stmt = $conn->prepare('DELETE FROM product WHERE product_id = :id');
			$stmt->execute([':id' => $productId]);

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
	<link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}
		body {
			font-family: 'Poppins', sans-serif;
			background: transparent;
			color: #0f172a;
		}
		.page-container {
			max-width: 100%;
			margin: 0;
			padding: 20px;
		}
		.page-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 18px;
		}
		.page-title {
			margin: 0;
			font-size: 26px;
			letter-spacing: -0.3px;
		}
		.content-card {
			background: transparent;
			border: none;
			border-radius: 0;
			box-shadow: none;
			padding: 0;
		}
		.toolbar {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			align-items: center;
			margin-bottom: 14px;
		}
		.search-input {
			width: 240px;
			padding: 10px 12px;
			border: 1px solid #d7dde5;
			border-radius: 10px;
			font-size: 14px;
		}
		.select-input {
			padding: 10px 12px;
			border: 1px solid #d7dde5;
			border-radius: 10px;
			font-size: 14px;
			background: #fff;
		}
		.btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 10px 14px;
			border: none;
			border-radius: 10px;
			font-weight: 600;
			cursor: pointer;
			text-decoration: none;
		}
		.btn-primary { background: #ef8324; color: #fff; }
		.btn-secondary { background: #0ea5e9; color: #fff; }
		.btn-ghost { background: #f1f5f9; color: #0f172a; }
		.btn-danger { background: #ef4444; color: #fff; }
		.table-wrapper { overflow-x: auto; }
		table { width: 100%; border-collapse: collapse; }
		th, td { padding: 12px 10px; text-align: left; }
		thead th { font-size: 13px; color: #475569; border-bottom: 1px solid #e2e8f0; }
		tbody tr { border-bottom: 1px solid #e2e8f0; }
		tbody tr:hover { background: #f8fafc; }
		.product-cell { display: flex; align-items: center; gap: 10px; }
		.product-thumb {
			width: 52px;
			height: 52px;
			border-radius: 10px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			object-fit: cover;
		}
		.badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
		.badge-gray { background: #f1f5f9; color: #0f172a; }
		.badge-green { background: #dcfce7; color: #166534; }
		.badge-amber { background: #fff7ed; color: #c2410c; }
		.badge-red { background: #fef2f2; color: #b91c1c; }
		.message { padding: 12px 14px; border-radius: 10px; margin-bottom: 12px; font-weight: 600; }
		.message-success { background: #ecfdf3; color: #166534; border: 1px solid #bbf7d0; }
		.message-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecdd3; }
		.actions { display: flex; gap: 8px; }
		.pagination { display: flex; gap: 8px; align-items: center; margin-top: 16px; }
		.pagination a, .pagination span { padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; text-decoration: none; color: #0f172a; font-weight: 600; }
		.pagination .active { background: #ef8324; border-color: #ef8324; color: #fff; }
		.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.48); display: none; align-items: center; justify-content: center; z-index: 20; padding: 16px; }
		.modal { background: #fff; border-radius: 14px; max-width: 640px; width: 100%; padding: 20px; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25); }
		.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
		.modal h3 { margin: 0; }
		.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
		.form-group { display: flex; flex-direction: column; gap: 6px; }
		.form-group label { font-size: 13px; font-weight: 600; color: #334155; }
		.form-group input, .form-group textarea { padding: 10px 12px; border: 1px solid #d7dde5; border-radius: 10px; font-size: 14px; }
		.form-group textarea { min-height: 90px; resize: vertical; }
		.modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px; }
		@media (max-width: 720px) {
			.page-header { flex-direction: column; align-items: flex-start; gap: 8px; }
			.toolbar { flex-direction: column; align-items: flex-start; }
			.search-input { width: 100%; }
		}
	</style>
</head>

<body>
	<div class="page-container">
		<div class="page-header">
			<div>
				<p style="margin:0;color:#64748b;font-size:13px;font-weight:600;">Backoffice</p>
				<h1 class="page-title">Products</h1>
			</div>
			<button class="btn btn-primary" id="openCreateModal">
				<span class="material-symbols-outlined">add</span>
				Add product
			</button>
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

			<div class="table-wrapper">
				<table>
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
								<td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;font-weight:600;">No products found.</td>
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
											<img src="<?php echo html_escape($imageUrl); ?>" alt="Image" class="product-thumb">
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
										<div class="actions">
											<a class="btn btn-ghost" href="<?php echo $viewsBasePath; ?>product/ProductDetails.php?id=<?php echo (int)$product['product_id']; ?>" target="_blank">View</a>
											<form method="POST" action="AdminProduct.php" class="delete-form" style="margin:0;">
												<input type="hidden" name="action" value="delete_product">
												<input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
												<button type="submit" class="btn btn-danger">Delete</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

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

	<div class="modal-overlay" id="createModal">
		<div class="modal">
			<div class="modal-header">
				<h3>Add new product</h3>
				<button class="btn btn-ghost" id="closeCreateModal">Close</button>
			</div>
			<form method="POST" action="AdminProduct.php">
				<input type="hidden" name="action" value="create_product">
				<div class="form-grid">
					<div class="form-group">
						<label for="product_name">Product name</label>
						<input type="text" id="product_name" name="product_name" required>
					</div>
					<div class="form-group">
						<label for="category">Category</label>
						<input type="text" id="category" name="category" required>
					</div>
					<div class="form-group">
						<label for="cost">Cost (RM)</label>
						<input type="number" step="0.01" min="0" id="cost" name="cost" required>
					</div>
					<div class="form-group">
						<label for="original_price">Original price (RM)</label>
						<input type="number" step="0.01" min="0" id="original_price" name="original_price" required>
					</div>
					<div class="form-group">
						<label for="selling_price">Selling price (RM)</label>
						<input type="number" step="0.01" min="0" id="selling_price" name="selling_price" required>
					</div>
					<div class="form-group">
						<label for="image_path">Image URL (optional)</label>
						<input type="text" id="image_path" name="image_path" placeholder="/web/images/products/your-image.jpg">
					</div>
				</div>
				<div class="form-group" style="margin-top:10px;">
					<label for="description">Description</label>
					<textarea id="description" name="description" placeholder="Short description"></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-ghost" id="cancelCreate">Cancel</button>
					<button type="submit" class="btn btn-primary">Create</button>
				</div>
			</form>
		</div>
	</div>

	<script>
		(function() {
			var modal = document.getElementById('createModal');
			var openBtn = document.getElementById('openCreateModal');
			var closeBtn = document.getElementById('closeCreateModal');
			var cancelBtn = document.getElementById('cancelCreate');
			if (openBtn && modal) {
				openBtn.addEventListener('click', function() {
					modal.style.display = 'flex';
				});
			}
			[closeBtn, cancelBtn].forEach(function(btn) {
				if (btn) {
					btn.addEventListener('click', function(event) {
						event.preventDefault();
						modal.style.display = 'none';
					});
				}
			});
			if (modal) {
				modal.addEventListener('click', function(event) {
					if (event.target === modal) {
						modal.style.display = 'none';
					}
				});
			}

			var deleteForms = document.querySelectorAll('.delete-form');
			deleteForms.forEach(function(form) {
				form.addEventListener('submit', function(event) {
					var ok = confirm('Delete this product? This cannot be undone.');
					if (!ok) {
						event.preventDefault();
					}
				});
			});
		})();
	</script>
</body>

</html>
