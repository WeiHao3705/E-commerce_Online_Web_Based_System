<?php
session_start();

// DB connection
require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

// Validate product_id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product.");
}

$product_id = (int)$_GET['id'];

// Include layout
$pageTitle = "Product Details";
require __DIR__ . '/../../general/_header.php';
require __DIR__ . '/../../general/_navbar.php';

// ------------------- FETCH PRODUCT INFO -------------------
$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        p.description,
        pr.original_price
    FROM product p
    LEFT JOIN product_price pr ON p.product_id = pr.product_id
    WHERE p.product_id = :id
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<h2>Product not found.</h2>";
    require __DIR__ . '/../../general/_footer.php';
    exit;
}

// ------------------- FETCH PRODUCT-LEVEL "MAIN" IMAGE (prioritise variant_id = 1) -------------------
$sqlMainImg = "
    SELECT image_path, variant_id
    FROM product_image
    WHERE product_id = :id
    ORDER BY CASE WHEN variant_id = 1 THEN 0 ELSE 1 END
    LIMIT 1
";
$stmtMain = $conn->prepare($sqlMainImg);
$stmtMain->execute([':id' => $product_id]);
$mainImageRow = $stmtMain->fetch(PDO::FETCH_ASSOC);

// ------------------- FETCH VARIANTS WITH ANY IMAGE (if present) -------------------
$sql2 = "
    SELECT 
        pv.variant_id,
        pv.size,
        pv.color,
        pi.image_path
    FROM product_variant pv
    LEFT JOIN product_image pi ON pv.variant_id = pi.variant_id
    WHERE pv.product_id = :id
    ORDER BY pv.color, pv.size
";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute([':id' => $product_id]);
$variantRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Build variant list ensuring one image per variant (first encountered)
$variants = [];
foreach ($variantRows as $r) {
    $vid = (int)$r['variant_id'];
    if (!isset($variants[$vid])) {
        $variants[$vid] = [
            'variant_id' => $vid,
            'size' => $r['size'],
            'color' => $r['color'],
            'image_path' => $r['image_path'] ?? null
        ];
    } else {
        // if not yet have image and this row has one, set it
        if (empty($variants[$vid]['image_path']) && !empty($r['image_path'])) {
            $variants[$vid]['image_path'] = $r['image_path'];
        }
    }
}
// Reindex to numeric array for dropdowns & JS
$variantsList = array_values($variants);

// Choose default variant:
// 1) If mainImageRow has variant_id and that variant exists -> use it
// 2) else prefer variant_id == 1 (if present for this product)
// 3) else first variant in list
$selectedVariant = null;
$initialImage = $mainImageRow['image_path'] ?? '';

if (!empty($mainImageRow['variant_id'])) {
    $mainVid = (int)$mainImageRow['variant_id'];
    foreach ($variantsList as $v) {
        if ((int)$v['variant_id'] === $mainVid) {
            $selectedVariant = $v;
            // prefer the variant image if available, else fallback to product-level image
            if (!empty($v['image_path'])) $initialImage = $v['image_path'];
            break;
        }
    }
}

if ($selectedVariant === null) {
    // prefer variant_id == 1 within this product
    foreach ($variantsList as $v) {
        if ((int)$v['variant_id'] === 1) {
            $selectedVariant = $v;
            if (!empty($v['image_path'])) $initialImage = $v['image_path'];
            break;
        }
    }
}

if ($selectedVariant === null && count($variantsList) > 0) {
    $selectedVariant = $variantsList[0];
    if (!empty($selectedVariant['image_path'])) $initialImage = $selectedVariant['image_path'];
}

// If still no initial image, clear string (will render "(No image available)")
$initialImage = $initialImage ?? '';
?>

<h1 style="margin-top: 20px; margin-left:30px;"><?= htmlspecialchars($product['product_name']) ?></h1>

<div style="display:flex; gap:40px; margin-top:20px; margin-left:10px;">

    <!-- Main Product Image & Variant Images -->
    <div style="width:35%;">
        <!-- Main Image -->
        <div style="margin-bottom: 20px;">
            <?php if ($initialImage): ?>
                <img id="mainImage" src="/<?= htmlspecialchars($initialImage) ?>" width="100%" style="border-radius:5px;">
            <?php else: ?>
                <p>(No image available)</p>
            <?php endif; ?>
        </div>

        <!-- Variant Image Thumbnails -->
        <?php if ($variantsList): ?>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php foreach ($variantsList as $v): ?>
                    <?php $img = $v['image_path'] ?? ''; ?>
                    <?php if ($img): ?>
                        <img 
                            src="/<?= htmlspecialchars($img) ?>" 
                            width="70" 
                            height="70"
                            class="thumb <?= ((int)$v['variant_id'] === (int)($selectedVariant['variant_id'] ?? -1)) ? 'selected' : '' ?>"
                            data-variant-id="<?= (int)$v['variant_id'] ?>"
                            data-image-path="<?= htmlspecialchars($img) ?>"
                            style="border-radius:5px; cursor:pointer; object-fit: cover;"
                            onclick="changeImage(this)"
                            alt="<?= htmlspecialchars($v['color']) ?> - <?= htmlspecialchars($v['size']) ?>"
                        >
                    <?php else: ?>
                        <!-- optional: show placeholder for variants without images -->
                        <div 
                            class="thumb <?= ((int)$v['variant_id'] === (int)($selectedVariant['variant_id'] ?? -1)) ? 'selected' : '' ?>"
                            data-variant-id="<?= (int)$v['variant_id'] ?>"
                            data-image-path=""
                            style="width:70px;height:70px;border-radius:5px;display:flex;align-items:center;justify-content:center;background:#f5f5f5;color:#888;cursor:pointer;"
                            onclick="changeImage(this)"
                        >
                            <?= htmlspecialchars($v['color']) ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Product Details & Options -->
    <div style="width:55%;">
        <p><strong>Price: </strong>RM <?= htmlspecialchars($product['original_price']) ?></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <br/>

        <!-- Add to Cart Form -->
        <!-- send directly to cart page so cart.php can display the added item -->
        <form method="POST" action="../Cart_Order/cart.php">
             <input type="hidden" name="product_id" value="<?= $product_id ?>">
             <input type="hidden" id="selectedVariantId" name="variant_id" value="<?= htmlspecialchars($selectedVariant['variant_id'] ?? '') ?>">

            <h3>Select Options</h3>

            <!-- Color Selection -->
            <div style="margin-bottom: 20px;">
                <label><strong>Color:</strong></label><br>
                <select id="colorSelect" onchange="updateVariantOptions()" style="padding: 8px; margin-top: 5px;">
                    <?php 
                    $colors = array_values(array_unique(array_map(fn($v) => $v['color'], $variantsList)));
                    foreach ($colors as $color): 
                    ?>
                        <option value="<?= htmlspecialchars($color) ?>" <?= $color === ($selectedVariant['color'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($color) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Size Selection -->
            <div style="margin-bottom: 20px;">
                <label><strong>Size:</strong></label><br>
                <select id="sizeSelect" name="size" onchange="updateVariantOptions()" style="padding: 8px; margin-top: 5px;">
                    <?php 
                    $sizes = array_values(array_unique(array_map(fn($v) => $v['size'], $variantsList)));
                    foreach ($sizes as $size): 
                    ?>
                        <option value="<?= htmlspecialchars($size) ?>" <?= $size === ($selectedVariant['size'] ?? '') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($size) ?>
                        </option>
                    <?php endforeach; ?>
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
    // variants map: variant_id -> variant object
    const variants = <?= json_encode($variantsList, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;

    // inline styles for thumbs
    (function(){
        const s = document.createElement('style');
        s.innerText = '.thumb{border:2px solid #ddd;display:inline-block;} .thumb.selected{border:2px solid #333;}';
        document.head.appendChild(s);
    })();

    function changeImage(el) {
        // data-image-path may be on <img> or wrapper div
        const imagePath = el.getAttribute('data-image-path') || '';
        const vid = el.getAttribute('data-variant-id');

        if (imagePath) {
            document.getElementById('mainImage').src = '/' + imagePath;
        }

        if (vid) {
            document.getElementById('selectedVariantId').value = vid;
        }

        document.querySelectorAll('.thumb').forEach(t => t.classList.remove('selected'));
        el.classList.add('selected');

        // set color/size selects to match chosen variant (if present)
        const chosen = variants.find(v => String(v.variant_id) === String(vid));
        if (chosen) {
            const colorSelect = document.getElementById('colorSelect');
            const sizeSelect = document.getElementById('sizeSelect');

            if (colorSelect) {
                colorSelect.value = chosen.color || colorSelect.value;
                // ensure any change handlers run (keeps size/options in sync)
                colorSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (sizeSelect) {
                sizeSelect.value = chosen.size || sizeSelect.value;
                sizeSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    function updateVariantOptions() {
        const color = document.getElementById('colorSelect').value;
        const size = document.getElementById('sizeSelect').value;

        // find variant that matches both color and size
        const match = variants.find(v => v.color === color && v.size === size);
        if (match) {
            document.getElementById('selectedVariantId').value = match.variant_id;
            if (match.image_path) {
                document.getElementById('mainImage').src = '/' + match.image_path;
            }
            // update thumbnail highlight if exists
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('selected'));
            const thumb = document.querySelector('.thumb[data-variant-id="'+match.variant_id+'"]');
            if (thumb) thumb.classList.add('selected');
        }
    }
</script>

<?php require __DIR__ . '/../../general/_footer.php'; ?>
