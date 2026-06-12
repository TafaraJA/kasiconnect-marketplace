<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';

    if (ctype_digit((string)$product_id) && in_array($new_status, ['active', 'inactive', 'pending_review'])) {
        $pdo->prepare("UPDATE products SET status = ? WHERE product_id = ?")
            ->execute([$new_status, $product_id]);
    }
    header('Location: admin_products.php');
    exit;
}

$products = $pdo->query(
    "SELECT p.*, c.name AS category_name, u.full_name AS seller_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     JOIN users u ON p.seller_id = u.user_id
     ORDER BY p.created_at DESC"
)->fetchAll();

$page_title = 'Manage Products';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Products</h1>
    <a href="admin_dashboard.php" class="link-action">← Dashboard</a>
</div>

<div class="orders-table-wrapper">
    <table class="orders-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Seller</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= (int)$product['product_id'] ?></td>
                    <td>
                        <a href="product.php?id=<?= $product['product_id'] ?>" target="_blank">
                            <?= htmlspecialchars($product['title']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($product['seller_name']) ?></td>
                    <td><?= htmlspecialchars($product['category_name']) ?></td>
                    <td>R <?= number_format($product['price'], 2) ?></td>
                    <td><?= (int)$product['stock'] ?></td>
                    <td>
                        <span class="status-badge status-<?= htmlspecialchars($product['status']) ?>">
                            <?= htmlspecialchars($product['status']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:flex; gap:4px; flex-wrap:wrap;">
                            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                            <?php if ($product['status'] !== 'active'): ?>
                                <button type="submit" name="new_status" value="active" class="link-action btn-small btn-confirm">Activate</button>
                            <?php endif; ?>
                            <?php if ($product['status'] !== 'inactive'): ?>
                                <button type="submit" name="new_status" value="inactive" class="link-action btn-small btn-reject">Deactivate</button>
                            <?php endif; ?>
                            <?php if ($product['status'] !== 'pending_review'): ?>
                                <button type="submit" name="new_status" value="pending_review" class="link-action btn-small">Flag</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>