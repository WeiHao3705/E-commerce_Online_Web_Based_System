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

// Asset prefix from this view to /web root (../../css, ../../js)
$assetPrefix = '../../';
$loginUrl = '../../account.php';

require __DIR__ . '/../../general/_header.php';
require __DIR__ . '/../../general/_navbar.php';
?>

<link rel="stylesheet" href="<?= $assetPrefix ?>css/ProductDetails.css?v=<?= filemtime(__DIR__ . '/../../css/ProductDetails.css'); ?>">
<link rel="stylesheet" href="<?= $assetPrefix ?>css/reviews.css?v=<?= filemtime(__DIR__ . '/../../css/reviews.css'); ?>">

<div class="product-detail-container" id="productDetailRoot" data-variant-sizes='<?= htmlspecialchars(json_encode($variantSizes), ENT_QUOTES, 'UTF-8') ?>' data-login-url="<?= htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') ?>">
	<a class="back-link" href="ProductPage.php">&#8592; Back</a>
	<h1 class="product-detail-title">
		<?= htmlspecialchars($product->product_name) ?>
	</h1>

	<div class="product-detail-grid">
		<!-- Left: Product Gallery -->
		<div class="product-gallery">
			<!-- Main Image/Gallery -->
			<div class="main-image-wrapper">
				<?php if (!empty($displayImages)): ?>
					<div class="main-images-grid">
						<?php foreach ($displayImages as $idx => $img): ?>
							<?php $src = '/' . ltrim($img['image_path'] ?? '', '/'); ?>
							<?php if ($idx === 0): ?>
								<img id="mainImage" src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($product->product_name) ?>">
							<?php else: ?>
								<img class="extra-image" src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($product->product_name) ?>">
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<?php if ($initialImage): ?>
						<img id="mainImage" src="/<?= htmlspecialchars(ltrim($initialImage, '/')) ?>" alt="<?= htmlspecialchars($product->product_name) ?>">
					<?php else: ?>
						<div class="no-image-placeholder">No image available</div>
					<?php endif; ?>
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
					<select id="sizeSelect" name="size">
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
	
	<!-- Reviews Section -->
	<div class="reviews-section">
		<div class="reviews-header">
			<h2>Customer Reviews</h2>
		</div>
		
		<!-- Reviews Summary -->
		<?php if ($review_count > 0): ?>
			<div class="reviews-summary">
				<div class="average-rating">
					<div class="average-rating-value"><?= number_format($average_rating, 1) ?></div>
					<div class="star-rating" data-rating="<?= $average_rating ?>"></div>
					<div class="average-rating-count"><?= $review_count ?> review<?= $review_count !== 1 ? 's' : '' ?></div>
				</div>
				<div class="review-count-text">
					Based on <?= $review_count ?> customer review<?= $review_count !== 1 ? 's' : '' ?>
				</div>
			</div>
		<?php endif; ?>
		
		<!-- Review Form (if user can review) -->
		<?php if (isset($can_review) && $can_review && !empty($eligible_order_items)): ?>
			<div class="review-form-container">
				<h3>Write a Review</h3>
				<div id="reviewMessage" class="review-message" style="display: none;"></div>
				<form id="reviewForm" class="review-form" method="POST">
					<input type="hidden" name="product_id" value="<?= $product->product_id ?>">
					<input type="hidden" id="reviewRating" name="rating" value="0">
					
					<div class="form-group">
						<label for="orderItemSelect">Select Order Item</label>
						<select id="orderItemSelect" name="order_item_id" required>
							<option value="">-- Select Order Item --</option>
							<?php foreach ($eligible_order_items as $item): ?>
								<?php if ($item['already_reviewed'] == 0): ?>
									<option value="<?= $item['order_item_id'] ?>" data-order-id="<?= $item['order_id'] ?>">
										Order #<?= str_pad($item['order_id'], 6, '0', STR_PAD_LEFT) ?> - 
										<?= htmlspecialchars($item['product_name_snapshot']) ?> 
										(Qty: <?= $item['quantity'] ?>) - 
										<?= date('M d, Y', strtotime($item['order_date'])) ?>
									</option>
								<?php endif; ?>
							<?php endforeach; ?>
						</select>
						<input type="hidden" id="reviewOrderId" name="order_id" value="">
					</div>
					
					<div class="form-group">
						<label>Rating</label>
						<div class="star-rating-input">
							<span class="star" data-rating="1">★</span>
							<span class="star" data-rating="2">★</span>
							<span class="star" data-rating="3">★</span>
							<span class="star" data-rating="4">★</span>
							<span class="star" data-rating="5">★</span>
						</div>
						<div class="rating-label"></div>
					</div>
					
					<div class="form-group">
						<label for="reviewComment">Comment (Optional)</label>
						<textarea id="reviewComment" name="comment" rows="4" placeholder="Share your experience with this product..."></textarea>
					</div>
					
					<button type="submit" class="submit-review-btn">Submit Review</button>
				</form>
			</div>
		<?php endif; ?>
		
		<!-- Reviews List -->
		<div class="reviews-list">
			<?php if (empty($reviews)): ?>
				<div class="no-reviews">
					<div class="no-reviews-icon">⭐</div>
					<div class="no-reviews-text">No reviews yet. Be the first to review this product!</div>
				</div>
			<?php else: ?>
				<?php foreach ($reviews as $review): ?>
					<div class="review-item">
						<div class="review-header">
							<div class="review-user">
								<div class="review-user-name"><?= htmlspecialchars($review->full_name ?: $review->username) ?></div>
								<div class="review-date"><?= date('M d, Y', strtotime($review->created_at)) ?></div>
							</div>
							<div class="review-rating">
								<div class="star-rating" data-rating="<?= $review->rating ?>"></div>
							</div>
						</div>
						<?php if (!empty($review->comment)): ?>
							<div class="review-comment"><?= nl2br(htmlspecialchars($review->comment)) ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
<!-- Login required modal -->
<div class="login-modal" id="loginModal" aria-hidden="true" role="dialog" aria-modal="true">
	<div class="login-modal__backdrop" id="loginModalBackdrop"></div>
	<div class="login-modal__dialog" role="document">
		<div class="login-modal__header">
			<h3 class="login-modal__title">Login required</h3>
		</div>
		<div class="login-modal__body">
			<p>You need to log in to add items to your cart.</p>
		</div>
		<div class="login-modal__actions">
			<button type="button" class="login-modal__btn login-modal__btn--ghost" id="loginModalCancel">Back</button>
			<button type="button" class="login-modal__btn login-modal__btn--primary" id="loginModalLogin">Login now</button>
		</div>
	</div>
</div>

<script src="<?= $assetPrefix ?>js/productDetails.js?v=<?= filemtime(__DIR__ . '/../../js/productDetails.js'); ?>"></script>
<script src="<?= $assetPrefix ?>js/reviews.js?v=<?= filemtime(__DIR__ . '/../../js/reviews.js'); ?>"></script>

<?php require __DIR__ . '/../../general/_footer.php'; ?>
