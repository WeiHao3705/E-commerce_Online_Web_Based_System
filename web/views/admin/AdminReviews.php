<?php
// Calculate base path
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$controllerBasePath = $webBasePath . 'controller/';
$viewsBasePath = $webBasePath . 'views/';

// Get filter parameters
$product_filter = $_GET['product_id'] ?? '';
$user_filter = $_GET['user_id'] ?? '';
$rating_filter = $_GET['rating'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <header class="header-actions">
            <h1 style="font-size: 2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-star" style="color: #FF523B;"></i> Product Reviews
            </h1>
        </header>

        <!-- Filters -->
        <section class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label><i class="fas fa-box"></i> Product ID</label>
                    <input type="number" name="product_id" placeholder="Filter by Product ID" value="<?= htmlspecialchars($product_filter) ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-user"></i> User ID</label>
                    <input type="number" name="user_id" placeholder="Filter by User ID" value="<?= htmlspecialchars($user_filter) ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-star"></i> Rating</label>
                    <select name="rating">
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
                                        <small style="display: block; color: #6b7280;">ID: <?= $review->product_id ?></small>
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
</body>
</html>

