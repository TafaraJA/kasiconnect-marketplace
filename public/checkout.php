<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

// Only logged-in buyers can checkout
requireLogin();

if (userRole() !== 'buyer') {
    http_response_code(403);
    die("Only buyers can place orders.");
}

$product_id = $_GET['product_id'] ?? $_POST['product_id'] ?? 0;
$quantity   = $_GET['quantity']   ?? $_POST['quantity']   ?? 1;

if (!ctype_digit((string)$product_id) || !ctype_digit((string)$quantity) || $quantity < 1) {
    http_response_code(400);
    die("Invalid order details.");
}

// Fetch the product with seller info
$stmt = $pdo->prepare(
    "SELECT p.*, u.full_name AS seller_name, u.user_id AS seller_id
     FROM products p
     JOIN users u ON p.seller_id = u.user_id
     WHERE p.product_id = ? AND p.status = 'active'"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    die("Product not found.");
}

// Prevent self-purchase
if ($product['seller_id'] == $_SESSION['user_id']) {
    die("You cannot buy your own product.");
}

// Make sure stock is enough
if ($quantity > $product['stock']) {
    $error = "Sorry, only {$product['stock']} item(s) in stock.";
    $quantity = $product['stock'];
}

$total = $product['price'] * $quantity;

// Handle "Confirm Order" submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {

    // Use a transaction so order + items + stock update all succeed together
    try {
        $pdo->beginTransaction();

        // 1. Create the order
        $stmt = $pdo->prepare(
            "INSERT INTO orders (buyer_id, seller_id, total_amount, status)
             VALUES (?, ?, ?, 'pending')"
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $product['seller_id'],
            $total
        ]);
        $order_id = $pdo->lastInsertId();

        // 2. Add the line item (price locked at time of purchase)
        $stmt = $pdo->prepare(
            "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $order_id,
            $product['product_id'],
            $quantity,
            $product['price']
        ]);

        // 3. Reduce stock
        $stmt = $pdo->prepare(
            "UPDATE products SET stock = stock - ? WHERE product_id = ?"
        );
        $stmt->execute([$quantity, $product['product_id']]);

        $pdo->commit();

        // Redirect to success page
        header("Location: order_success.php?order_id={$order_id}");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Could not place order. Please try again.";
    }
}

$page_title = 'Checkout';
require_once '../includes/header.php';
?>

<div class="form-card">
    <h1>Confirm Your Order</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <table class="summary-table">
            <tr>
                <td>Product</td>
                <td><strong><?= htmlspecialchars($product['title']) ?></strong></td>
            </tr>
            <tr>
                <td>Seller</td>
                <td><?= htmlspecialchars($product['seller_name']) ?></td>
            </tr>
            <tr>
                <td>Unit price</td>
                <td>R <?= number_format($product['price'], 2) ?></td>
            </tr>
            <tr>
                <td>Quantity</td>
                <td><?= (int)$quantity ?></td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td><strong>R <?= number_format($total, 2) ?></strong></td>
            </tr>
        </table>
    </div>

    <form method="POST" action="checkout.php">
        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
        <input type="hidden" name="quantity" value="<?= (int)$quantity ?>">
        <input type="hidden" name="confirm" value="1">

        <div class="checkout-actions">
            <a href="product.php?id=<?= $product['product_id'] ?>" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Place Order</button>
        </div>
    </form>

    <p class="info-note" style="margin-top: 20px;">
        After placing your order you'll receive payment instructions.
        You'll need to upload a proof of payment to complete the transaction.
    </p>
</div>

<?php require_once '../includes/footer.php'; ?>