<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();

if (userRole() !== 'buyer') {
    http_response_code(403);
    die("Only buyers can view this page.");
}

// Fetch all orders for this buyer, with payment status
$stmt = $pdo->prepare(
    "SELECT o.*,
            u.full_name AS seller_name,
            COALESCE(pay.status, 'no_pop') AS payment_status
     FROM orders o
     JOIN users u ON o.seller_id = u.user_id
     LEFT JOIN payments pay ON pay.order_id = o.order_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$page_title = 'My Orders';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>My Orders</h1>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <p>You haven't placed any orders yet.</p>
        <p><a href="products.php">Browse products</a></p>
    </div>
<?php else: ?>
    <div class="orders-table-wrapper">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Seller</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>KC-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        <td><?= htmlspecialchars($order['seller_name']) ?></td>
                        <td>R <?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($order['payment_status'] === 'no_pop'): ?>
                                <span class="status-badge status-pending">No PoP</span>
                            <?php else: ?>
                                <span class="status-badge status-<?= htmlspecialchars($order['payment_status']) ?>">
                                    <?= htmlspecialchars($order['payment_status']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="order_success.php?order_id=<?= $order['order_id'] ?>" class="link-action">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>