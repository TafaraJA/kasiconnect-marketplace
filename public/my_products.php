<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole(['seller', 'admin']);

// Fetch this seller's products with category names
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     WHERE p.seller_id = ?
     ORDER BY p.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();

$page_title = 'My Listings';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>My Listings</h1>
    <a href="add_product.php" class="btn-primary">+ Add Product</a>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <p>You haven't listed any products yet.</p>
        <p><a href="add_product.php">Add your first product</a></p>
    </div>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <?php if ($product['image_path']): ?>
                    <img src="<?= htmlspecialchars($product['image_path']) ?>"
                         alt="<?= htmlspecialchars($product['title']) ?>"
                         class="product-image">
                <?php else: ?>
                    <div class="product-image-placeholder">No image</div>
                <?php endif; ?>

                <div class="product-info">
                    <h3><?= htmlspecialchars($product['title']) ?></h3>
                    <p class="product-category"><?= htmlspecialchars($product['category_name']) ?></p>
                    <p class="product-price">R <?= number_format($product['price'], 2) ?></p>
                    <p class="product-stock">Stock: <?= (int)$product['stock'] ?></p>
                    <span class="status-badge status-<?= htmlspecialchars($product['status']) ?>">
                        <?= htmlspecialchars($product['status']) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>