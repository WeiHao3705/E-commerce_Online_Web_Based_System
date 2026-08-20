<?php
// Calculate base path - use absolute path from document root
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relativePath = str_replace($docRoot, '', str_replace('\\', '/', $webRootDir));
$webBasePath = str_replace('\\', '/', $relativePath);
// Ensure path starts with /
if (substr($webBasePath, 0, 1) !== '/') {
    $webBasePath = '/' . $webBasePath;
}
// Remove trailing slash and add it back to ensure consistency
$webBasePath = rtrim($webBasePath, '/') . '/';
$cssBasePath = $webBasePath . 'css/';
$controllerBasePath = $webBasePath . 'controller/';
$viewsBasePath = $webBasePath . 'views/';

// Get filter parameters
$product_filter = $_GET['product_name'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?php echo $webBasePath; ?>">
    <title><?php echo $pageTitle; ?> - NGEAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>reviews.css">
    <style>
        .reviews-admin-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .reviews-admin-table thead {
            background: #f8f9fa;
        }
        .reviews-admin-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .reviews-admin-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .reviews-admin-table tbody tr:hover {
            background: #f9fafb;
        }
        .star-rating-admin {
            display: inline-flex;
            gap: 2px;
        }
        .star-rating-admin .star {
            font-size: 18px;
            color: #d1d5db;
        }
        .star-rating-admin .star.filled {
            color: #fbbf24;
        }
        .filter-actions .btn {
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            min-width: 100px;
            width: 100px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }
        .product-search-wrapper {
            position: relative;
        }
        .product-autocomplete {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            margin-top: 4px;
        }
        .product-autocomplete.show {
            display: block;
        }
        .product-autocomplete-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            transition: background-color 0.2s;
        }
        .product-autocomplete-item:last-child {
            border-bottom: none;
        }
        .product-autocomplete-item:hover {
            background-color: #f3f4f6;
        }
        .product-autocomplete-item.selected {
            background-color: #ef8324;
            color: white;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Filters -->
        <section class="filters-section">
            <form method="GET" class="filters-form" id="reviewFilterForm">
                <input type="hidden" name="controller" value="review">
                <input type="hidden" name="action" value="viewAll">
                
                <div class="filter-group">
                    <label><i class="fas fa-box"></i> Product Name</label>
                    <div class="product-search-wrapper">
                        <input type="text" 
                               id="productNameInput" 
                               name="product_name" 
                               placeholder="Search by Product Name" 
                               value="<?= htmlspecialchars($product_filter) ?>"
                               autocomplete="off">
                        <div id="productAutocomplete" class="product-autocomplete"></div>
                    </div>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-star"></i> Rating</label>
                    <select name="rating" id="ratingFilter">
                        <option value="">All Ratings</option>
                        <option value="5" <?= $rating_filter === '5' ? 'selected' : '' ?>>5 Stars</option>
                        <option value="4" <?= $rating_filter === '4' ? 'selected' : '' ?>>4 Stars</option>
                        <option value="3" <?= $rating_filter === '3' ? 'selected' : '' ?>>3 Stars</option>
                        <option value="2" <?= $rating_filter === '2' ? 'selected' : '' ?>>2 Stars</option>
                        <option value="1" <?= $rating_filter === '1' ? 'selected' : '' ?>>1 Star</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="?controller=review&action=viewAll" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </section>

        <!-- Reviews Table -->
        <section class="table-container">
            <table class="reviews-admin-table">
                <thead>
                    <tr>
                        <th>Review ID</th>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-star"></i>
                                    <p>No reviews found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><strong>#<?= str_pad($review->review_id, 6, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($review->product_name) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($review->full_name ?: $review->username) ?></strong>
                                        <small style="display: block; color: #6b7280;">@<?= htmlspecialchars($review->username) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="star-rating-admin" data-rating="<?= $review->rating ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $review->rating ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <small style="display: block; color: #6b7280; margin-top: 4px;"><?= $review->rating ?>/5</small>
                                </td>
                                <td>
                                    <?php if (!empty($review->comment)): ?>
                                        <div style="max-width: 300px; word-wrap: break-word;">
                                            <?= nl2br(htmlspecialchars($review->comment)) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #9ca3af; font-style: italic;">No comment</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($review->created_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const productInput = $('#productNameInput');
            const autocomplete = $('#productAutocomplete');
            let searchTimeout;
            let selectedIndex = -1;
            let products = [];
            
            // Get controller base path
            const controllerBasePath = '<?php echo $controllerBasePath; ?>';
            const reviewControllerUrl = controllerBasePath + 'ReviewController.php';
            
            // Handle input for autocomplete
            productInput.on('input', function() {
                const searchTerm = $(this).val().trim();
                clearTimeout(searchTimeout);
                
                if (searchTerm.length < 2) {
                    autocomplete.removeClass('show').empty();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    searchProducts(searchTerm);
                }, 300);
            });
            
            // Handle keyboard navigation
            productInput.on('keydown', function(e) {
                if (!autocomplete.hasClass('show') || products.length === 0) {
                    return;
                }
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, products.length - 1);
                    updateSelection();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && selectedIndex < products.length) {
                        selectProduct(products[selectedIndex]);
                    } else {
                        // Submit form if no selection
                        $('#reviewFilterForm').submit();
                    }
                } else if (e.key === 'Escape') {
                    autocomplete.removeClass('show').empty();
                    selectedIndex = -1;
                }
            });
            
            // Handle click outside to close autocomplete
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.product-search-wrapper').length) {
                    autocomplete.removeClass('show');
                }
            });
            
            function searchProducts(searchTerm) {
                $.ajax({
                    url: reviewControllerUrl,
                    method: 'GET',
                    data: {
                        action: 'searchProducts',
                        search: searchTerm,
                        limit: 10
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.products.length > 0) {
                            products = response.products;
                            renderAutocomplete(response.products);
                            selectedIndex = -1;
                        } else {
                            autocomplete.removeClass('show').empty();
                        }
                    },
                    error: function() {
                        autocomplete.removeClass('show').empty();
                    }
                });
            }
            
            function renderAutocomplete(productsList) {
                autocomplete.empty();
                
                productsList.forEach(function(product, index) {
                    const item = $('<div>')
                        .addClass('product-autocomplete-item')
                        .attr('data-index', index)
                        .text(product.product_name)
                        .on('click', function() {
                            selectProduct(product);
                        })
                        .on('mouseenter', function() {
                            selectedIndex = index;
                            updateSelection();
                        });
                    
                    autocomplete.append(item);
                });
                
                autocomplete.addClass('show');
            }
            
            function updateSelection() {
                autocomplete.find('.product-autocomplete-item').removeClass('selected');
                if (selectedIndex >= 0 && selectedIndex < products.length) {
                    autocomplete.find('.product-autocomplete-item[data-index="' + selectedIndex + '"]')
                        .addClass('selected');
                }
            }
            
            function selectProduct(product) {
                productInput.val(product.product_name);
                autocomplete.removeClass('show').empty();
                selectedIndex = -1;
            }
        });
    </script>
</body>
</html>

