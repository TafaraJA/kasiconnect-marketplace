<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and clean input
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'buyer';

    // Server-side validation
    if (strlen($full_name) < 2) {
        $errors[] = "Full name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (!preg_match('/^[0-9+\s\-]{10,15}$/', $phone)) {
        $errors[] = "A valid phone number is required (10-15 digits).";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }
    if (!in_array($role, ['buyer', 'seller'])) {
        $errors[] = "Invalid role selected.";
    }

    // Check if email already registered
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email already exists.";
        }
    }

    // Insert the new user
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, phone, password_hash, role)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$full_name, $email, $phone, $password_hash, $role]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - KasiConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h1>Create Your KasiConnect Account</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Registration successful! <a href="login.php">Login here</a>.
            </div>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                       maxlength="10"
                       placeholder="" required>

                <label for="role">I want to register as</label>
                <select id="role" name="role" required>
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>

                <label for="password">Password (min 8 characters)</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit">Register</button>
            </form>

            <p>Already have an account? <a href="login.php">Login</a></p>

        <?php endif; ?>
    </div>
</body>
</html>