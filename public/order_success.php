<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();

$order_id = $_GET['order_id'] ?? 0;
if (!ctype_digit((string)$order_id)) {
    http_response_code(400);
    die("Invalid order.");
}

// Fetch the order — only if it belongs to this buyer
$stmt = $pdo->prepare(
    "SELECT o.*, u.full_name AS seller_name, u.phone AS seller_phone
     FROM orders o
     JOIN users u ON o.seller_id = u.user_id
     WHERE o.order_id = ? AND o.buyer_id = ?"
);
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    die("Order not found.");
}

// Fetch order items
$stmt = $pdo->prepare(
    "SELECT oi.*, p.title
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?"
);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Check if PoP already uploaded
$stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);
$payment = $stmt->fetch();

$page_title = 'Order Confirmed';
require_once '../includes/header.php';
?>

<div class="form-card">
    <div class="alert alert-success">
        ✅ Order placed successfully! Order reference: <strong>#KC-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong>
    </div>

    <h2>Order Details</h2>
    <table class="summary-table">
        <tr>
            <td>Order Reference</td>
            <td><strong>KC-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
        </tr>
        <tr>
            <td>Status</td>
            <td><span class="status-badge status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars($order['status']) ?></span></td>
        </tr>
        <tr>
            <td>Seller</td>
            <td><?= htmlspecialchars($order['seller_name']) ?></td>
        </tr>
        <tr>
            <td>Seller Contact</td>
            <td><?= htmlspecialchars($order['seller_phone']) ?></td>
        </tr>
        <tr class="total-row">
            <td>Total to pay</td>
            <td><strong>R <?= number_format($order['total_amount'], 2) ?></strong></td>
        </tr>
    </table>

    <h3 style="margin-top: 24px;">Items</h3>
    <table class="summary-table">
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['title']) ?> &times; <?= (int)$item['quantity'] ?></td>
                <td>R <?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="payment-instructions">
        <h3>How to Pay</h3>
        <p>Please transfer the total amount using the seller's preferred method, then upload your proof of payment below.</p>
        <p><strong>Use your order reference (KC-<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>) as the payment reference.</strong></p>
        <p>Contact the seller using the number above to confirm their banking details or arrange collection/delivery.</p>
    </div>

    <?php if ($payment): ?>
        <div class="alert alert-success" style="margin-top: 20px;">
            <strong>Proof of payment uploaded.</strong>
            Status: <?= htmlspecialchars($payment['status']) ?>.
            The seller will confirm payment shortly.
        </div>
    <?php else: ?>
        <form method="POST" action="upload_pop.php" enctype="multipart/form-data" style="margin-top: 24px;">
            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">

            <h3>Upload Proof of Payment</h3>
            <label for="pop_file">PoP file (PDF, JPG, or PNG — max 5MB)</label>
            <input type="file" id="pop_file" name="pop_file"
                   accept="application/pdf,image/jpeg,image/png" required>

            <button type="submit" class="btn-primary" style="margin-top: 16px;">Upload PoP</button>
        </form>
    <?php endif; ?>

    <p style="margin-top: 24px; text-align: center;">
        <a href="my_orders.php">View all my orders</a>
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>