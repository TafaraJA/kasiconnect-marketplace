<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();

$errors = [];
$success = false;

// Pre-fill from URL parameters (e.g. ?type=product&id=5 from a product page)
$prefill_type = $_GET['type'] ?? '';
$prefill_id   = $_GET['id']   ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_type = $_POST['target_type'] ?? '';
    $target_id   = $_POST['target_id']   ?? '';
    $reason      = trim($_POST['reason'] ?? '');

    // Validation
    if (!in_array($target_type, ['user', 'product', 'order'])) {
        $errors[] = "Please select what you're reporting.";
    }
    if (!ctype_digit((string)$target_id) || $target_id < 1) {
        $errors[] = "Please enter a valid ID for the target.";
    }
    if (strlen($reason) < 10) {
        $errors[] = "Please provide a reason (at least 10 characters).";
    }

    // Verify the target actually exists
    if (empty($errors)) {
        $table = [
            'user'    => ['table' => 'users',    'pk' => 'user_id'],
            'product' => ['table' => 'products', 'pk' => 'product_id'],
            'order'   => ['table' => 'orders',   'pk' => 'order_id'],
        ][$target_type];

        $stmt = $pdo->prepare("SELECT {$table['pk']} FROM {$table['table']} WHERE {$table['pk']} = ?");
        $stmt->execute([$target_id]);
        if (!$stmt->fetch()) {
            $errors[] = "The {$target_type} with ID {$target_id} doesn't exist.";
        }
    }

    // Prevent reporting yourself
    if (empty($errors) && $target_type === 'user' && $target_id == $_SESSION['user_id']) {
        $errors[] = "You can't report yourself.";
    }

    // Insert the report
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO reports (reporter_id, target_type, target_id, reason, status)
             VALUES (?, ?, ?, ?, 'open')"
        );
        $stmt->execute([$_SESSION['user_id'], $target_type, $target_id, $reason]);
        $success = true;
        $_POST = []; // clear form
    }
}

$page_title = 'Submit a Report';
require_once '../includes/header.php';
?>

<div class="form-card">
    <h1>Report a Problem</h1>

    <p class="info-note" style="margin-bottom: 20px;">
        Use this form to flag suspicious users, fraudulent products, or problematic orders.
        Reports are reviewed by an administrator.
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Report submitted. An admin will review it shortly. Thank you for helping keep KasiConnect safe.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="report.php">
        <label for="target_type">What are you reporting?</label>
        <select id="target_type" name="target_type" required>
            <option value="">-- Select --</option>
            <option value="product" <?= ($prefill_type === 'product' || ($_POST['target_type'] ?? '') === 'product') ? 'selected' : '' ?>>A product listing</option>
            <option value="user"    <?= ($prefill_type === 'user'    || ($_POST['target_type'] ?? '') === 'user')    ? 'selected' : '' ?>>A user (seller/buyer)</option>
            <option value="order"   <?= ($prefill_type === 'order'   || ($_POST['target_type'] ?? '') === 'order')   ? 'selected' : '' ?>>An order</option>
        </select>

        <label for="target_id">ID of the item being reported</label>
        <input type="number" id="target_id" name="target_id" min="1"
               value="<?= htmlspecialchars($_POST['target_id'] ?? $prefill_id) ?>"
               placeholder="e.g. product ID 5, user ID 3, order ID 1" required>
        <small style="color: #666;">You can find this in the URL or order reference (e.g. KC-000001 = order ID 1).</small>

        <label for="reason">Reason</label>
        <textarea id="reason" name="reason" rows="5"
                  placeholder="Explain what's wrong — fake product, scam attempt, fake PoP, etc." required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>

        <button type="submit" class="btn-primary">Submit Report</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>