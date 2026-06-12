<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole(['seller', 'admin']);

$product_id = $_GET['id'] ?? $_POST['product_id'] ?? 0;

if (!ctype_digit((string)$product_id)) {
    http_response_code(400);
    die("Invalid product ID.");
}

// Fetch the product — and verify ownership
$stmt = $pdo->prepare(
    "SELECT * FROM products WHERE product_id = ? AND seller_id = ?"
);
$stmt->execute([$product_id, $_SESSION['user_id']]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    die("Product not found or you don't own it.");
}

$errors = [];
$success = false;

// Load categories for dropdown
$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $stock       = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $status      = $_POST['status'] ?? 'active';

    // Validation
    if (strlen($title) < 3) {
        $errors[] = "Product title must be at least 3 characters.";
    }
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Price must be a positive number.";
    }
    if (!is_numeric($stock) || $stock < 0) {
        $errors[] = "Stock cannot be negative.";
    }
    if (!ctype_digit((string)$category_id)) {
        $errors[] = "Please select a valid category.";
    }
    if (!in_array($status, ['active', 'inactive'])) {
        $errors[] = "Invalid status.";
    }

    // Handle optional image replacement
    $image_path = $product['image_path']; // keep existing by default
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024;

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed.";
        } elseif (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Image must be JPG, PNG, or WEBP.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image must be under 2MB.";
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_', true) . '.' . strtolower($ext);
            $target = __DIR__ . '/uploads/products/' . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                // Delete the old image file if it exists
                if ($product['image_path'] && file_exists(__DIR__ . '/' . $product['image_path'])) {
                    @unlink(__DIR__ . '/' . $product['image_path']);
                }
                $image_path = 'uploads/products/' . $filename;
            } else {
                $errors[] = "Could not save uploaded image.";
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "UPDATE products
             SET title = ?, description = ?, price = ?, stock = ?,
                 category_id = ?, image_path = ?, status = ?
             WHERE product_id = ? AND seller_id = ?"
        );
        $stmt->execute([
            $title, $description, $price, $stock,
            $category_id, $image_path, $status,
            $product_id, $_SESSION['user_id']
        ]);

        // Re-fetch the updated product
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        $success = true;
    }
}

$page_title = 'Edit Product';
require_once '../includes/header.php';
?>

<div class="form-card">
    <h1>Edit Product</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Product updated successfully.
            <a href="my_products.php">Back to my listings</a>
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

    <form method="POST" action="edit_product.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

        <label for="title">Product Title</label>
        <input type="text" id="title" name="title"
               value="<?= htmlspecialchars($product['title']) ?>" required>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>"
                    <?= $cat['category_id'] == $product['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

        <label for="price">Price (ZAR)</label>
        <input type="number" id="price" name="price" min="0.01" step="0.01"
               value="<?= htmlspecialchars($product['price']) ?>" required>

        <label for="stock">Stock Quantity</label>
        <input type="number" id="stock" name="stock" min="0"
               value="<?= htmlspecialchars($product['stock']) ?>" required>

        <label for="status">Listing Status</label>
        <select id="status" name="status" required>
            <option value="active"   <?= $product['status'] === 'active'   ? 'selected' : '' ?>>Active (visible to buyers)</option>
            <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive (hidden)</option>
        </select>

        <?php if ($product['image_path']): ?>
            <label>Current Image</label>
            <img src="<?= htmlspecialchars($product['image_path']) ?>"
                 alt="Current image"
                 style="max-width: 200px; border-radius: 5px; margin-bottom: 10px;">
        <?php endif; ?>

        <label for="image">Replace Image (optional, max 2MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">

        <div class="checkout-actions">
            <a href="my_products.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>