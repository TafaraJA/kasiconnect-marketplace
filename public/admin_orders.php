<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole('admin');

$orders = $pdo->query(
    "SELECT o.*,
            b.full_name AS buyer_name,
            s.full_name AS seller_name,
            pay.status AS pop_status
     FROM orders o
     JOIN users b ON o.buyer_id = b.user_id
     JOIN users s ON o.seller_id = s.user_id
     LEFT JOIN payments pay ON pay.order_id = o.order_id
     ORDER BY o.created_at DESC"
)->fetchAll();

$page_title = 'All Orders';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>All Orders</h1>
    <a href="admin_dashboard.php" class="link-action">← Dashboard</a>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state"><p>No orders in the system yet.</p></div>
<?php else: ?>
    <div class="orders-table-wrapper">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>PoP Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>KC-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                        <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                        <td><?= htmlspecialchars($order['seller_name']) ?></td>
                        <td>R <?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($order['pop_status']): ?>
                                <span class="status-badge status-<?= htmlspecialchars($order['pop_status']) ?>">
                                    <?= htmlspecialchars($order['pop_status']) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-pending">No PoP</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>