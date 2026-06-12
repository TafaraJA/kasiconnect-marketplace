<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole(['seller', 'admin']);

// Handle PoP confirmation/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $payment_id = $_POST['payment_id'] ?? 0;

    if (ctype_digit((string)$payment_id) && in_array($action, ['confirm', 'reject'])) {
        // Verify this payment belongs to one of this seller's orders
        $stmt = $pdo->prepare(
            "SELECT pay.payment_id, pay.order_id
             FROM payments pay
             JOIN orders o ON pay.order_id = o.order_id
             WHERE pay.payment_id = ? AND o.seller_id = ?"
        );
        $stmt->execute([$payment_id, $_SESSION['user_id']]);
        $payment = $stmt->fetch();

        if ($payment) {
            try {
                $pdo->beginTransaction();

                if ($action === 'confirm') {
                    $stmt = $pdo->prepare(
                        "UPDATE payments SET status = 'confirmed', confirmed_at = NOW()
                         WHERE payment_id = ?"
                    );
                    $stmt->execute([$payment_id]);
                    // Order stays as 'paid' — seller can later mark it 'shipped'/'delivered'
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE payments SET status = 'rejected' WHERE payment_id = ?"
                    );
                    $stmt->execute([$payment_id]);

                    // Revert the order back to 'pending'
                    $stmt = $pdo->prepare(
                        "UPDATE orders SET status = 'pending' WHERE order_id = ?"
                    );
                    $stmt->execute([$payment['order_id']]);
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
            }
        }

        header('Location: my_sales.php');
        exit;
    }

    // Handle order status update (mark as shipped/delivered)
    $new_status = $_POST['new_status'] ?? '';
    $order_id   = $_POST['order_id']   ?? 0;
    if (ctype_digit((string)$order_id) && in_array($new_status, ['shipped', 'delivered', 'cancelled'])) {
        $stmt = $pdo->prepare(
            "UPDATE orders SET status = ?
             WHERE order_id = ? AND seller_id = ?"
        );
        $stmt->execute([$new_status, $order_id, $_SESSION['user_id']]);
        header('Location: my_sales.php');
        exit;
    }
}

// Fetch all orders where this user is the seller
$stmt = $pdo->prepare(
    "SELECT o.*,
            u.full_name AS buyer_name,
            u.phone AS buyer_phone,
            pay.payment_id, pay.status AS pop_status, pay.pop_file_path
     FROM orders o
     JOIN users u ON o.buyer_id = u.user_id
     LEFT JOIN payments pay ON pay.order_id = o.order_id
     WHERE o.seller_id = ?
     ORDER BY o.created_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$sales = $stmt->fetchAll();

$page_title = 'My Sales';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>My Sales</h1>
</div>

<?php if (empty($sales)): ?>
    <div class="empty-state">
        <p>No orders yet.</p>
    </div>
<?php else: ?>
    <div class="orders-table-wrapper">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Date</th>
                    <th>Buyer</th>
                    <th>Contact</th>
                    <th>Total</th>
                    <th>Order Status</th>
                    <th>PoP</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><strong>KC-<?= str_pad($sale['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td><?= date('d M Y', strtotime($sale['created_at'])) ?></td>
                        <td><?= htmlspecialchars($sale['buyer_name']) ?></td>
                        <td><?= htmlspecialchars($sale['buyer_phone']) ?></td>
                        <td>R <?= number_format($sale['total_amount'], 2) ?></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($sale['status']) ?>">
                                <?= htmlspecialchars($sale['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($sale['payment_id']): ?>
                                <a href="<?= htmlspecialchars($sale['pop_file_path']) ?>" target="_blank">View PoP</a>
                                <br>
                                <span class="status-badge status-<?= htmlspecialchars($sale['pop_status']) ?>">
                                    <?= htmlspecialchars($sale['pop_status']) ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-pending">Awaiting</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sale['payment_id'] && $sale['pop_status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="payment_id" value="<?= $sale['payment_id'] ?>">
                                    <input type="hidden" name="action" value="confirm">
                                    <button type="submit" class="link-action btn-confirm">Confirm</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="payment_id" value="<?= $sale['payment_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="link-action btn-reject">Reject</button>
                                </form>
                            <?php elseif ($sale['pop_status'] === 'confirmed' && $sale['status'] === 'paid'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $sale['order_id'] ?>">
                                    <input type="hidden" name="new_status" value="shipped">
                                    <button type="submit" class="link-action">Mark Shipped</button>
                                </form>
                            <?php elseif ($sale['status'] === 'shipped'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $sale['order_id'] ?>">
                                    <input type="hidden" name="new_status" value="delivered">
                                    <button type="submit" class="link-action">Mark Delivered</button>
                                </form>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>