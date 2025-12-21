<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    $_SESSION['error_message'] = 'Please login to view your wishlist';
    header('Location: views/security/login.php');
    exit;
}

$prefix = '';
$pageTitle = 'My Wishlist';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGEAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/wishlist.css">
</head>
<body>
    <?php include 'general/_navbar.php'; ?>

    <div class="wishlist-container">
        <div class="wishlist-header">
            <h1><i class="fas fa-heart"></i> My Wishlist</h1>
            <p>Save your favorite items for later</p>
        </div>

        <div class="wishlist-stats">
            <div class="wishlist-stat">
                <div class="wishlist-stat-value" id="wishlist-count">0</div>
                <div class="wishlist-stat-label">Items</div>
            </div>
            <div class="wishlist-stat">
                <div class="wishlist-stat-value" id="wishlist-value">RM 0.00</div>
                <div class="wishlist-stat-label">Total Value</div>
            </div>
        </div>

        <div id="wishlist-content">
            <div class="wishlist-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading your wishlist...</p>
            </div>
        </div>
    </div>

    <?php include 'general/_footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            loadWishlist();

            function loadWishlist() {
                $.ajax({
                    url: 'controller/WishlistController.php?action=list',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Wishlist response:', response);
                        if (response.success) {
                            displayWishlist(response.items);
                        } else {
                            showError('Failed to load wishlist: ' + (response.message || 'Unknown error'));
                            console.error('Wishlist error:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', xhr.responseText);
                        showError('Error loading wishlist: ' + error);
                        $('#wishlist-content').html(`
                            <div class="wishlist-empty">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h2>Error Loading Wishlist</h2>
                                <p>${xhr.responseText || error}</p>
                                <button class="btn btn-primary btn-large" onclick="location.reload()">
                                    <i class="fas fa-redo"></i> Retry
                                </button>
                            </div>
                        `);
                    }
                });
            }

            function displayWishlist(items) {
                if (items.length === 0) {
                    $('#wishlist-content').html(`
                        <div class="wishlist-empty">
                            <i class="fas fa-heart-broken"></i>
                            <h2>Your wishlist is empty</h2>
                            <p>Start adding products you love to your wishlist!</p>
                            <a href="views/Product_Page/ProductPage.php" class="btn btn-primary btn-large">
                                <i class="fas fa-shopping-bag"></i> Browse Products
                            </a>
                        </div>
                    `);
                    $('#wishlist-count').text('0');
                    $('#wishlist-value').text('RM 0.00');
                    return;
                }

                let totalValue = 0;
                let html = '<div class="wishlist-grid">';

                items.forEach(function(item) {
                    const originalPrice = parseFloat(item.original_price) || 0;
                    const sellingPrice = parseFloat(item.selling_price) || originalPrice;
                    // Use original_price as the display price (consistent with product pages)
                    const displayPrice = originalPrice;
                    
                    totalValue += displayPrice;
                    
                    // Image path is stored as full path (e.g., "web/images/products/file.jpg")
                    // Use same pattern as ProductPage.php - prepend with / and use as-is
                    let imagePath = '/web/images/products/default.jpg';
                    if (item.image_path) {
                        // Remove leading slashes and prepend with single slash
                        const cleanPath = item.image_path.replace(/^\/+/, '');
                        imagePath = '/' + cleanPath;
                    }
                    const stock = parseInt(item.stock_quantity || 0);
                    let stockClass = 'stock-out';
                    let stockText = 'Out of Stock';
                    let stockIcon = 'fa-times-circle';
                    
                    if (stock > 10) {
                        stockClass = 'stock-available';
                        stockText = 'In Stock';
                        stockIcon = 'fa-check-circle';
                    } else if (stock > 0) {
                        stockClass = 'stock-low';
                        stockText = `Only ${stock} left`;
                        stockIcon = 'fa-exclamation-circle';
                    }

                    html += `
                        <div class="wishlist-item" data-product-id="${item.product_id}">
                            <button class="btn-remove" onclick="removeFromWishlist(${item.product_id})" title="Remove from wishlist">
                                <i class="fas fa-trash"></i>
                            </button>
                            <img src="${imagePath}" alt="${item.product_name}" class="wishlist-item-image">
                            <div class="wishlist-item-content">
                                <h3 class="wishlist-item-name">${item.product_name}</h3>
                                <p class="wishlist-item-description">${item.description || ''}</p>
                                <div class="wishlist-item-price">
                                    <span class="wishlist-current-price">RM ${displayPrice.toFixed(2)}</span>
                                </div>
                                <div class="wishlist-stock-status ${stockClass}">
                                    <i class="fas ${stockIcon}"></i>
                                    <span>${stockText}</span>
                                </div>
                                <div class="wishlist-item-actions">
                                    <a href="views/product/ProductDetails.php?id=${item.product_id}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    ${stock > 0 ? `
                                        <button class="btn btn-secondary" onclick="addToCart(${item.product_id})">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                html += `
                    <div class="wishlist-actions">
                        <a href="views/product/ProductPage.php" class="btn btn-secondary btn-large">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                `;

                $('#wishlist-content').html(html);
                $('#wishlist-count').text(items.length);
                $('#wishlist-value').text('RM ' + totalValue.toFixed(2));
            }

            // Make functions globally accessible
            window.removeFromWishlist = function(productId) {
                if (!confirm('Remove this item from your wishlist?')) {
                    return;
                }

                $.ajax({
                    url: 'controller/WishlistController.php',
                    method: 'POST',
                    data: { action: 'remove', product_id: productId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            loadWishlist();
                            showSuccess('Item removed from wishlist');
                        } else {
                            showError(response.message || 'Failed to remove item');
                        }
                    },
                    error: function() {
                        showError('Error removing item');
                    }
                });
            };

            window.addToCart = function(productId) {
                $.ajax({
                    url: 'views/Cart_Order/cart.php',
                    method: 'POST',
                    data: { 
                        product_id: productId,
                        quantity: 1
                    },
                    success: function() {
                        showSuccess('Added to cart!');
                        // Optionally remove from wishlist after adding to cart
                        // removeFromWishlist(productId);
                    },
                    error: function() {
                        showError('Failed to add to cart');
                    }
                });
            };

            function showSuccess(message) {
                alert('✓ ' + message);
            }

            function showError(message) {
                alert('✗ ' + message);
            }
        });
    </script>
</body>
</html>
