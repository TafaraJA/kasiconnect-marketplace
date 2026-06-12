<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole(['seller', 'admin']);

$errors = [];
$success = false;

// Load categories for the dropdown
$categories = $pdo->query("SELECT category_id, name FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = $_POST['price'] ?? '';
    $stock       = $_POST['stock'] ?? '';
    $category_id = $_POST['category_id'] ?? '';

    // Validation
    if (strlen($title) < 3) {
        $errors[] = "Product title must be at least 3 characters.";
    }
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Price must be a positive number.";
    }
    if (!is_numeric($stock) || $stock < 1) {
        $errors[] = "Stock must be at least 1.";
    }
    if (!ctype_digit((string)$category_id)) {
        $errors[] = "Please select a valid category.";
    }

    // Handle image upload (optional)
    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Image upload failed. Please try again.";
        } elseif (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Image must be JPG, PNG, or WEBP.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image must be under 2MB.";
        } else {
            // Generate a safe unique filename
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('prod_', true) . '.' . strtolower($ext);
            $target = __DIR__ . '/uploads/products/' . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = 'uploads/products/' . $filename;
            } else {
                $errors[] = "Could not save uploaded image.";
            }
        }
    }

    // Insert product
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO products (seller_id, category_id, title, description, price, stock, image_path)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $category_id,
            $title,
            $description,
            $price,
            $stock,
            $image_path
        ]);

        $success = true;
        // Clear the form
        $_POST = [];
    }
}

$page_title = 'Add Product';
require_once '../includes/header.php';
?>

<div class="form-card">
    <h1>Add a New Product</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ Product added successfully!
            <a href="my_products.php">View my listings</a> or add another below.
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

    <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <label for="title">Product Title</label>
        <input type="text" id="title" name="title"
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>

        <label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
            <option value="">-- Select a category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['category_id'] ?>"
                    <?= (($_POST['category_id'] ?? '') == $cat['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

        <label for="price">Price (ZAR)</label>
        <input type="number" id="price" name="price" min="0.01" step="0.01"
               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>

        <label for="stock">Stock Quantity</label>
        <input type="number" id="stock" name="stock" min="1"
               value="<?= htmlspecialchars($_POST['stock'] ?? '1') ?>" required>

        <label for="image">Product Image (optional, max 2MB)</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">

        <button type="submit">Add Product</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>