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

<h1 style="margin-top: 20px; margin-left:30px;">
	<?= htmlspecialchars($product->product_name) ?>
</h1>

<div style="display:flex; gap:40px; margin-top:20px; margin-left:10px;">

	<!-- Main Product Image & Variant Images -->
	<div style="width:35%;">
		<!-- Main Image -->
		<div style="margin-bottom: 20px;">
			<?php if ($initialImage): ?>
				<img id="mainImage" src="/<?= htmlspecialchars(ltrim($initialImage, '/')) ?>" width="100%" style="border-radius:5px;">
			<?php else: ?>
				<p>(No image available)</p>
			<?php endif; ?>
		</div>

		<!-- Variant Image Thumbnails -->
		<?php if ($variantsList): ?>
			<div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-start;">
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
						<div style="text-align: center;">
							<img 
								src="<?= $thumbSrcSafe ?>" 
								width="70" 
								height="70"
								class="thumb <?= ((int)$variant->variant_id === (int)($selectedVariant->variant_id ?? -1)) ? 'selected' : '' ?>"
								data-variant-id="<?= (int)$variant->variant_id ?>"
								data-image-path="<?= $dataImagePath ?>"
								style="border-radius:5px; cursor:pointer; object-fit: cover; display: block;"
								onclick="changeVariant(this)"
								alt="<?= $colorLabel ?>"
							>
							<p style="margin: 5px 0 0 0; font-size: 12px; color: #666;"><?= $colorLabel ?></p>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<!-- Product Details & Options -->
	<div style="width:55%;">
		<p><strong>Price: </strong>RM <?= htmlspecialchars($product->original_price) ?></p>
		<p><?= nl2br(htmlspecialchars($product->description)) ?></p>

		<br/>

		<!-- Add to Cart Form -->
		<form method="POST" action="../Cart_Order/cart.php">
			<input type="hidden" name="product_id" value="<?= $product->product_id ?>">
			<input type="hidden" id="selectedVariantId" name="variant_id" value="<?= htmlspecialchars($selectedVariant->variant_id ?? '') ?>">

			<h3>Select Options</h3>

			<!-- Size Selection -->
			<div style="margin-bottom: 20px;">
				<label><strong>Size:</strong></label><br>
				<select id="sizeSelect" name="size" onchange="updateSelectedSize()" style="padding: 8px; margin-top: 5px;">
					<option value="">-- Select Size --</option>
				</select>
			</div>

			<!-- Quantity Selection -->
			<div style="margin-bottom: 20px;">
				<label><strong>Quantity:</strong></label><br>
				<input type="number" name="quantity" value="1" min="1" max="99" style="padding: 8px; width: 80px; margin-top: 5px;">
			</div>

			<!-- Add to Cart Button -->
			<button type="submit" style="
				padding: 12px 30px; 
				background-color: #ef8324ff; 
				color: white; 
				border: none; 
				border-radius: 5px; 
				cursor: pointer; 
				font-size: 16px;
			">
				Add to Cart
			</button>
		</form>
	</div>

</div>

<script>
	// Sizes for each variant: { variant_id: [size1, size2, ...] }
	const variantSizes = <?= json_encode($variantSizes, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

	// inline styles for thumbs
	(function(){
		const s = document.createElement('style');
		s.innerText = '.thumb{border:2px solid #ddd;display:inline-block;} .thumb.selected{border:2px solid #333;}';
		document.head.appendChild(s);
	})();

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
		document.querySelectorAll('.thumb').forEach(t => t.classList.remove('selected'));
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
