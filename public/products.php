<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$category_filter = $_GET['category'] ?? '';

// Build the query
$sql = "SELECT p.*, c.name AS category_name, u.full_name AS seller_name
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        JOIN users u ON p.seller_id = u.user_id
        WHERE p.status = 'active' AND p.stock > 0";

$params = [];
if (ctype_digit($category_filter)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();

$page_title = 'Browse Products';
require_once '../includes/header.php';
?>

<?php if (empty($category_filter)): ?>
<section class="hero">
    <div class="hero-content">
        <span class="hero-tagline">South Africa's Township Marketplace</span>
        <h1>Buy and sell with confidence</h1>
        <p>A secure space for informal traders, township entrepreneurs and local communities to connect, trade, and grow together.</p>
    </div>
</section>
<?php endif; ?>

<div class="page-header">
    <h1><?= empty($category_filter) ? 'Featured Products' : 'Browse Products' ?></h1>
</div>

<div class="browse-layout">
    <aside class="filter-sidebar">
        <h3>Categories</h3>
        <ul class="category-list">
            <li><a href="products.php" class="<?= $category_filter === '' ? 'active' : '' ?>">All</a></li>
            <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="?category=<?= $cat['category_id'] ?>"
                       class="<?= $category_filter == $cat['category_id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <section class="product-list">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <p>No products available in this category yet.</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <a href="product.php?id=<?= $product['product_id'] ?>" class="product-card-link">
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
                                <p class="product-seller">by <?= htmlspecialchars($product['seller_name']) ?></p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>