<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole('admin');

// Gather quick stats
$stats = [
    'total_users'      => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_buyers'     => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'")->fetchColumn(),
    'total_sellers'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn(),
    'total_products'   => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'active_products'  => $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
    'total_orders'     => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending_pops'     => $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn(),
    'open_reports'     => $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'open'")->fetchColumn(),
    'total_revenue'    => $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status IN ('paid','shipped','delivered')")->fetchColumn(),
];

// Recent orders
$recent_orders = $pdo->query(
    "SELECT o.*, b.full_name AS buyer_name, s.full_name AS seller_name
     FROM orders o
     JOIN users b ON o.buyer_id = b.user_id
     JOIN users s ON o.seller_id = s.user_id
     ORDER BY o.created_at DESC LIMIT 10"
)->fetchAll();

$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <div class="admin-nav">
        <a href="admin_users.php" class="link-action">Manage Users</a>
        <a href="admin_products.php" class="link-action">Manage Products</a>
        <a href="admin_orders.php" class="link-action">All Orders</a>
        <a href="admin_reports.php" class="link-action">Reports</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <p class="stat-label">Total Users</p>
        <p class="stat-value"><?= (int)$stats['total_users'] ?></p>
        <p class="stat-sub"><?= (int)$stats['total_buyers'] ?> buyers · <?= (int)$stats['total_sellers'] ?> sellers</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Products Listed</p>
        <p class="stat-value"><?= (int)$stats['total_products'] ?></p>
        <p class="stat-sub"><?= (int)$stats['active_products'] ?> active</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Total Orders</p>
        <p class="stat-value"><?= (int)$stats['total_orders'] ?></p>
        <p class="stat-sub">All time</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Revenue</p>
        <p class="stat-value">R <?= number_format($stats['total_revenue'], 0) ?></p>
        <p class="stat-sub">Paid orders only</p>
    </div>
    <div class="stat-card stat-alert">
        <p class="stat-label">Pending PoPs</p>
        <p class="stat-value"><?= (int)$stats['pending_pops'] ?></p>
        <p class="stat-sub">Awaiting confirmation</p>
    </div>
    <a href="admin_reports.php" class="stat-card stat-alert" style="text-decoration:none;">
        <p class="stat-label">Open Reports</p>
        <p class="stat-value"><?= (int)$stats['open_reports'] ?></p>
        <p class="stat-sub">Need review</p>
    </a>
</div>

<h2 style="margin-top: 30px; color: #1a3a5c;">Recent Orders</h2>
<?php if (empty($recent_orders)): ?>
    <div class="empty-state"><p>No orders yet.</p></div>
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
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td><strong>KC-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                        <td><?= htmlspecialchars($order['seller_name']) ?></td>
                        <td>R <?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>