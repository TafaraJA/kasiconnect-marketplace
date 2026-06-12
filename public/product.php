<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$product_id = $_GET['id'] ?? 0;

if (!ctype_digit((string)$product_id)) {
    http_response_code(400);
    die("Invalid product ID.");
}

// Fetch product with seller and category info
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name,
            u.full_name AS seller_name, u.user_id AS seller_id,
            u.phone AS seller_phone, u.is_verified AS seller_verified
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     JOIN users u ON p.seller_id = u.user_id
     WHERE p.product_id = ? AND p.status = 'active'"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $page_title = 'Product Not Found';
    require_once '../includes/header.php';
    echo '<div class="empty-state"><h2>Product not found</h2><p><a href="products.php">Back to browse</a></p></div>';
    require_once '../includes/footer.php';
    exit;
}

// Determine viewer's relationship to this product
$is_owner = isLoggedIn() && $_SESSION['user_id'] == $product['seller_id'];
$can_buy = isLoggedIn() && !$is_owner && userRole() === 'buyer' && $product['stock'] > 0;

$page_title = $product['title'];
require_once '../includes/header.php';
?>

<div class="product-detail">
    <div class="product-detail-image">
        <?php if ($product['image_path']): ?>
            <img src="<?= htmlspecialchars($product['image_path']) ?>"
                 alt="<?= htmlspecialchars($product['title']) ?>">
        <?php else: ?>
            <div class="product-image-placeholder large">No image available</div>
        <?php endif; ?>
    </div>

    <div class="product-detail-info">
        <p class="product-category"><?= htmlspecialchars($product['category_name']) ?></p>
        <h1><?= htmlspecialchars($product['title']) ?></h1>
        <p class="product-price-large">R <?= number_format($product['price'], 2) ?></p>

        <p class="product-stock-info">
            <?php if ($product['stock'] > 0): ?>
                <strong><?= (int)$product['stock'] ?></strong> in stock
            <?php else: ?>
                <span class="out-of-stock">Out of stock</span>
            <?php endif; ?>
        </p>

        <div class="seller-info">
            <h3>Seller</h3>
            <p>
                <?= htmlspecialchars($product['seller_name']) ?>
                <?php if ($product['seller_verified']): ?>
                    <span class="verified-badge">✓ Verified</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="product-description">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($product['description'] ?? 'No description provided.')) ?></p>
        </div>

        <?php if (isLoggedIn() && !$is_owner): ?>
            <p style="margin-top: 16px; font-size: 0.85rem;">
                <a href="report.php?type=product&id=<?= $product['product_id'] ?>" style="color: #c82333;">
                    ⚠ Report this listing
                </a>
            </p>
        <?php endif; ?>
        
        <div class="product-actions">
            <?php if ($is_owner): ?>
                <p class="info-note">This is your listing.
                    <a href="my_products.php">Manage your listings</a>
                </p>
            <?php elseif ($can_buy): ?>
                <form method="GET" action="checkout.php">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity"
                           min="1" max="<?= (int)$product['stock'] ?>" value="1" required>
                    <button type="submit" class="btn-primary">Buy Now</button>
                </form>
            <?php elseif (!isLoggedIn()): ?>
                <p class="info-note">
                    <a href="login.php">Login</a> or
                    <a href="register.php">register</a> as a buyer to purchase.
                </p>
            <?php elseif (userRole() !== 'buyer'): ?>
                <p class="info-note">Only buyers can purchase products.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>