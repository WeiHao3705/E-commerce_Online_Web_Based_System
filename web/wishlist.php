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

    <!-- Custom Confirmation Modal -->
    <div id="wishlist-confirm-modal" class="wishlist-modal">
        <div class="wishlist-modal-overlay"></div>
        <div class="wishlist-modal-content">
            <div class="wishlist-modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="wishlist-modal-title">Remove from Wishlist?</h3>
            <p class="wishlist-modal-message">Are you sure you want to remove this item from your wishlist?</p>
            <div class="wishlist-modal-actions">
                <button class="btn btn-secondary wishlist-modal-cancel">Cancel</button>
                <button class="btn btn-primary wishlist-modal-confirm">Remove</button>
            </div>
        </div>
    </div>

    <!-- Custom Notification Toast -->
    <div id="wishlist-notification" class="wishlist-notification">
        <div class="wishlist-notification-content">
            <i class="wishlist-notification-icon"></i>
            <span class="wishlist-notification-message"></span>
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
                    
                    // Variant and size information
                    const variantColor = item.variant_color || null;
                    const variantId = item.variant_id || null;
                    const size = item.size || null;
                    const stock = parseInt(item.stock_quantity || 0);
                    const isOutOfStock = stock <= 0;
                    const isSizeSpecific = size !== null && size !== '';
                    
                    // Build view URL with variant if available
                    let viewUrl = `views/product/ProductDetails.php?id=${item.product_id}`;
                    if (variantId) {
                        viewUrl += `&variant=${variantId}`;
                    }

                    // Determine badge text and button behavior
                    let badgeText = '';
                    let showAddToCart = true;
                    let addToCartText = 'Add to Cart';
                    let addToCartTitle = '';
                    
                    if (isOutOfStock) {
                        if (isSizeSpecific) {
                            // Only this specific size is out of stock, other sizes may be available
                            badgeText = `Size ${size} Out of Stock`;
                            showAddToCart = false; // Disable for this specific size
                            addToCartTitle = `Size ${size} is out of stock. Click View to select another size.`;
                        } else {
                            // Entire variant/product is out of stock
                            badgeText = 'Out of Stock';
                            showAddToCart = false;
                            addToCartTitle = 'This item is out of stock';
                        }
                    }

                    html += `
                        <div class="wishlist-item" data-product-id="${item.product_id}" data-wishlist-id="${item.wishlist_id}">
                            <button class="btn-remove" onclick="removeFromWishlist(${item.wishlist_id})" title="Remove from wishlist">
                                <i class="fas fa-trash"></i>
                            </button>
                            ${badgeText ? `<div class="wishlist-out-of-stock-badge">${badgeText}</div>` : ''}
                            <img src="${imagePath}" alt="${item.product_name}" class="wishlist-item-image">
                            <div class="wishlist-item-content">
                                <h3 class="wishlist-item-name">${item.product_name}</h3>
                                ${variantColor ? `<p class="wishlist-item-variant"><i class="fas fa-palette"></i> Color: ${variantColor}</p>` : ''}
                                ${size ? `<p class="wishlist-item-size"><i class="fas fa-ruler"></i> Size: ${size}</p>` : ''}
                                <p class="wishlist-item-description">${item.description || ''}</p>
                                <div class="wishlist-item-price">
                                    <span class="wishlist-current-price">RM ${displayPrice.toFixed(2)}</span>
                                </div>
                                <div class="wishlist-item-actions">
                                    <a href="${viewUrl}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    ${showAddToCart ? `
                                        <button class="btn btn-secondary" onclick="addToCart(${item.product_id}${variantId ? ', ' + variantId : ''}${size ? ', \'' + size + '\'' : ''})">
                                            <i class="fas fa-shopping-cart"></i> ${addToCartText}
                                        </button>
                                    ` : `
                                        <button class="btn btn-secondary" disabled title="${addToCartTitle}">
                                            <i class="fas fa-shopping-cart"></i> ${isSizeSpecific ? 'Size Out of Stock' : 'Out of Stock'}
                                        </button>
                                    `}
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

            // Custom confirmation modal functions
            let pendingWishlistId = null;

            function showConfirmModal(wishlistId) {
                pendingWishlistId = wishlistId;
                $('#wishlist-confirm-modal').addClass('active');
            }

            function hideConfirmModal() {
                $('#wishlist-confirm-modal').removeClass('active');
                pendingWishlistId = null;
            }

            // Modal event handlers
            $('.wishlist-modal-cancel, .wishlist-modal-overlay').on('click', function() {
                hideConfirmModal();
            });

            $('.wishlist-modal-confirm').on('click', function() {
                if (pendingWishlistId) {
                    const wishlistId = pendingWishlistId;
                    hideConfirmModal();
                    performRemove(wishlistId);
                }
            });

            // Close modal on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#wishlist-confirm-modal').hasClass('active')) {
                    hideConfirmModal();
                }
            });

            function performRemove(wishlistId) {
                $.ajax({
                    url: 'controller/WishlistController.php',
                    method: 'POST',
                    data: { action: 'remove', wishlist_id: wishlistId },
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
            }

            // Make functions globally accessible
            window.removeFromWishlist = function(wishlistId) {
                showConfirmModal(wishlistId);
            };

            window.addToCart = function(productId, variantId, size) {
                const data = { 
                    product_id: productId,
                    quantity: 1
                };
                if (variantId) {
                    data.variant_id = variantId;
                }
                if (size) {
                    data.size = size;
                }
                
                $.ajax({
                    url: 'views/Cart_Order/cart.php',
                    method: 'POST',
                    data: data,
                    success: function() {
                        showSuccess('Added to cart!');
                        // Optionally remove from wishlist after adding to cart
                        // removeFromWishlist(wishlistId);
                    },
                    error: function() {
                        showError('Failed to add to cart');
                    }
                });
            };

            function showSuccess(message) {
                showNotification(message, 'success');
            }

            function showError(message) {
                showNotification(message, 'error');
            }

            function showNotification(message, type) {
                const notification = $('#wishlist-notification');
                const notificationContent = notification.find('.wishlist-notification-content');
                const icon = notification.find('.wishlist-notification-icon');
                const messageSpan = notification.find('.wishlist-notification-message');
                
                // Set icon and class based on type
                if (type === 'success') {
                    icon.removeClass('fa-times-circle fa-exclamation-circle').addClass('fas fa-check-circle');
                    notification.removeClass('error').addClass('success');
                } else {
                    icon.removeClass('fa-check-circle fa-exclamation-circle').addClass('fas fa-times-circle');
                    notification.removeClass('success').addClass('error');
                }
                
                // Set message
                messageSpan.text(message);
                
                // Show notification
                notification.addClass('active');
                
                // Auto hide after 3 seconds
                setTimeout(function() {
                    notification.removeClass('active');
                }, 3000);
            }
        });
    </script>
</body>
</html>
