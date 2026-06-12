<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$order_id = $_POST['order_id'] ?? 0;
if (!ctype_digit((string)$order_id)) {
    die("Invalid order.");
}

// Verify the order belongs to this buyer
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    http_response_code(404);
    die("Order not found.");
}

// Check no PoP already exists
$stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);
if ($stmt->fetch()) {
    die("A proof of payment has already been uploaded for this order.");
}

// Validate file upload
if (empty($_FILES['pop_file']['name']) || $_FILES['pop_file']['error'] !== UPLOAD_ERR_OK) {
    die("No file uploaded or upload error.");
}

$allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['pop_file']['type'], $allowed_types)) {
    die("File must be PDF, JPG, or PNG.");
}

if ($_FILES['pop_file']['size'] > $max_size) {
    die("File must be under 5MB.");
}

// Save the file
$ext = pathinfo($_FILES['pop_file']['name'], PATHINFO_EXTENSION);
$filename = 'pop_' . $order_id . '_' . uniqid() . '.' . strtolower($ext);
$target = __DIR__ . '/uploads/pops/' . $filename;

if (!move_uploaded_file($_FILES['pop_file']['tmp_name'], $target)) {
    die("Could not save uploaded file.");
}

$pop_path = 'uploads/pops/' . $filename;

// Insert payment record + update order status
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO payments (order_id, pop_file_path, status)
         VALUES (?, ?, 'pending')"
    );
    $stmt->execute([$order_id, $pop_path]);

    $stmt = $pdo->prepare("UPDATE orders SET status = 'paid' WHERE order_id = ?");
    $stmt->execute([$order_id]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Could not record payment. Please try again.");
}

// Redirect back to order page
header("Location: order_success.php?order_id={$order_id}");
exit;
?>