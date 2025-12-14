<?php
session_start();

// Dependencies
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../service/ProductService.php';

$db = new Database();
$conn = $db->getConnection();
$service = new ProductService($conn);

// Validate product_id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
	$pageTitle = "Error";
	require __DIR__ . '/../../general/_header.php';
	require __DIR__ . '/../../general/_navbar.php';
	echo "<h2>Invalid product.</h2>";
	require __DIR__ . '/../../general/_footer.php';
	exit;
}

$product_id = (int)$_GET['id'];

// Fetch product details via service
$data = $service->getProductDetails($product_id);

if (!$data || !$data['product']) {
	$pageTitle = "Error";
	require __DIR__ . '/../../general/_header.php';
	require __DIR__ . '/../../general/_navbar.php';
	echo "<h2>Product not found.</h2>";
	require __DIR__ . '/../../general/_footer.php';
	exit;
}

// Extract data for view
extract($data);

// Page title
$pageTitle = "Product Details";

require __DIR__ . '/../../general/_header.php';
require __DIR__ . '/../../general/_navbar.php';
?>

<style>
	.product-detail-container {
		max-width: 1000px;
		margin: 40px auto 60px;
		padding: 0 20px;
	}

	.product-detail-title {
		margin: 0 0 30px;
		font-size: 32px;
		font-weight: 700;
		color: #111827;
		letter-spacing: -0.5px;
	}

	.product-detail-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 40px;
		align-items: start;
	}

	/* Left side - Image gallery */
	.product-gallery {
		display: flex;
		flex-direction: column;
		gap: 16px;
	}

	.main-image-wrapper {
		width: 100%;
		background: #f8f9fa;
		border-radius: 12px;
		overflow: hidden;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		min-height: 300px;
	}

	.main-image-wrapper img {
		max-width: 100%;
		max-height: 500px;
		object-fit: contain;
		border-radius: 12px;
		display: block;
	}

	.thumbnail-gallery {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		align-items: flex-start;
	}

	.thumbnail-item {
		text-align: center;
		flex: 0 1 calc(25% - 8px);
	}

	.thumbnail-item img {
		width: 100%;
		aspect-ratio: 1;
		border: 2px solid #e5e7eb;
		border-radius: 10px;
		cursor: pointer;
		object-fit: cover;
		transition: all 140ms ease;
		display: block;
	}

	.thumbnail-item img:hover {
		border-color: #9ca3af;
		transform: scale(1.03);
	}

	.thumbnail-item img.selected {
		border-color: #ef8324;
		background: #fef3f0;
	}

	.thumbnail-label {
		margin: 6px 0 0;
		font-size: 12px;
		color: #6b7280;
		font-weight: 500;
	}

	/* Right side - Details & Form */
	.product-info {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	.price-badge {
		display: inline-block;
		font-size: 28px;
		font-weight: 700;
		color: #ef8324;
		background: #fef3f0;
		padding: 12px 18px;
		border-radius: 8px;
		width: fit-content;
	}

	.product-description {
		margin: 0;
		font-size: 15px;
		line-height: 1.6;
		color: #374151;
	}

	.options-section {
		padding-top: 20px;
		border-top: 1px solid #e5e7eb;
	}

	.options-section h3 {
		margin: 0 0 16px;
		font-size: 18px;
		font-weight: 600;
		color: #111827;
	}

	.form-group {
		margin-bottom: 18px;
	}

	.form-group label {
		display: block;
		margin-bottom: 6px;
		font-size: 14px;
		font-weight: 600;
		color: #374151;
	}

	.form-group select,
	.form-group input {
		width: 100%;
		padding: 10px 12px;
		font-size: 14px;
		border: 1px solid #d1d5db;
		border-radius: 8px;
		transition: border-color 140ms ease;
	}

	.form-group select:focus,
	.form-group input:focus {
		outline: none;
		border-color: #ef8324;
		box-shadow: 0 0 0 3px rgba(239, 131, 36, 0.1);
	}

	.form-group input[type="number"] {
		width: 120px;
	}

	.add-to-cart-btn {
		width: 100%;
		padding: 14px 24px;
		background-color: #ef8324;
		color: white;
		border: none;
		border-radius: 8px;
		cursor: pointer;
		font-size: 16px;
		font-weight: 600;
		transition: all 140ms ease;
		margin-top: 10px;
	}

	.add-to-cart-btn:hover {
		background-color: #d66a1a;
		transform: translateY(-2px);
		box-shadow: 0 8px 16px rgba(239, 131, 36, 0.3);
	}

	.add-to-cart-btn:active {
		transform: translateY(0);
	}

	@media (max-width: 768px) {
		.product-detail-grid {
			grid-template-columns: 1fr;
			gap: 24px;
		}

		.product-detail-container {
			margin: 20px auto 40px;
		}

		.product-detail-title {
			font-size: 24px;
			margin-bottom: 20px;
		}
	}
</style>

<div class="product-detail-container">
	<h1 class="product-detail-title">
		<?= htmlspecialchars($product->product_name) ?>
	</h1>

	<div class="product-detail-grid">
		<!-- Left: Product Gallery -->
		<div class="product-gallery">
			<!-- Main Image -->
			<div class="main-image-wrapper">
				<?php if ($initialImage): ?>
					<img id="mainImage" src="/<?= htmlspecialchars(ltrim($initialImage, '/')) ?>" alt="<?= htmlspecialchars($product->product_name) ?>">
				<?php else: ?>
					<div style="text-align: center; color: #9ca3af;">No image available</div>
				<?php endif; ?>
			</div>

			<!-- Variant Image Thumbnails -->
			<?php if ($variantsList): ?>
				<div class="thumbnail-gallery">
					<?php foreach ($variantsList as $variant): ?>
						<?php if (!empty($variant->variant_id)): ?>
							<?php 
								$hasImage = !empty($variant->image_path);
								$placeholder = 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="70" height="70"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="55%" font-size="12" fill="#999" text-anchor="middle">No Image</text></svg>');
								$thumbSrc = $hasImage ? '/' . ltrim($variant->image_path, '/') : $placeholder;
								$thumbSrcSafe = htmlspecialchars($thumbSrc, ENT_QUOTES, 'UTF-8');
								$dataImagePath = $hasImage ? htmlspecialchars($variant->image_path, ENT_QUOTES, 'UTF-8') : '';
								$colorLabel = htmlspecialchars($variant->color ?? 'Product', ENT_QUOTES, 'UTF-8');
							?>
							<div class="thumbnail-item">
								<img 
									src="<?= $thumbSrcSafe ?>" 
									class="thumb-image <?= ((int)$variant->variant_id === (int)($selectedVariant->variant_id ?? -1)) ? 'selected' : '' ?>"
									data-variant-id="<?= (int)$variant->variant_id ?>"
									data-image-path="<?= $dataImagePath ?>"
									onclick="changeVariant(this)"
									alt="<?= $colorLabel ?>"
								>
								<p class="thumbnail-label"><?= $colorLabel ?></p>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Right: Product Details & Options -->
		<div class="product-info">
			<div class="price-badge">RM <?= htmlspecialchars($product->original_price) ?></div>

			<p class="product-description"><?= nl2br(htmlspecialchars($product->description)) ?></p>

			<!-- Add to Cart Form -->
			<form method="POST" action="../Cart_Order/cart.php" class="options-section">
				<input type="hidden" name="product_id" value="<?= $product->product_id ?>">
				<input type="hidden" id="selectedVariantId" name="variant_id" value="<?= htmlspecialchars($selectedVariant->variant_id ?? '') ?>">

				<h3>Select Options</h3>

				<!-- Size Selection -->
				<div class="form-group">
					<label for="sizeSelect">Size</label>
					<select id="sizeSelect" name="size" onchange="updateSelectedSize()">
						<option value="">-- Select Size --</option>
					</select>
				</div>

				<!-- Quantity Selection -->
				<div class="form-group">
					<label for="quantityInput">Quantity</label>
					<input type="number" id="quantityInput" name="quantity" value="1" min="1" max="99">
				</div>

				<!-- Add to Cart Button -->
				<button type="submit" class="add-to-cart-btn">
					Add to Cart
				</button>
			</form>
		</div>
	</div>
</div>

<script>
	// Sizes for each variant: { variant_id: [size1, size2, ...] }
	const variantSizes = <?= json_encode($variantSizes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

	function changeVariant(el) {
		const imagePath = el.getAttribute('data-image-path') || '';
		const vid = el.getAttribute('data-variant-id');

		console.log('Clicked variant:', vid, 'Image path:', imagePath);

		if (imagePath && imagePath.trim() !== '') {
			const newSrc = '/' + imagePath.trim().replace(/^\/+/, '');
			console.log('Setting mainImage.src to:', newSrc);
			document.getElementById('mainImage').src = newSrc;
		} else {
			console.log('No image for this variant, keeping current image');
		}

		// Update selected thumbnail
		document.querySelectorAll('.thumb-image').forEach(t => t.classList.remove('selected'));
		el.classList.add('selected');

		// Update variant_id and load sizes
		if (vid) {
			document.getElementById('selectedVariantId').value = vid;
			updateSizeOptions();
		}
	}

	function updateSizeOptions() {
		const selectedVid = parseInt(document.getElementById('selectedVariantId').value) || null;
		const sizeSelect = document.getElementById('sizeSelect');
		
		sizeSelect.innerHTML = '<option value="">-- Select Size --</option>';

		if (!selectedVid) return;

		const sizes = variantSizes[selectedVid] || [];
		
		sizes.forEach(size => {
			const opt = document.createElement('option');
			opt.value = size;
			opt.textContent = size;
			sizeSelect.appendChild(opt);
		});

		// Auto-select first size
		if (sizes.length > 0) {
			sizeSelect.value = sizes[0];
		}
	}

	function updateSelectedSize() { /* select already carries value */ }

	// Initialize size options on page load
	window.addEventListener('DOMContentLoaded', updateSizeOptions);
</script>

<?php require __DIR__ . '/../../general/_footer.php'; ?>
