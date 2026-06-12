<?php
require_once '../config/db.php';
require_once '../includes/auth.php';

requireRole('admin');

// Handle role change / verification toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $action  = $_POST['action']  ?? '';

    if (ctype_digit((string)$user_id)) {
        // Prevent admin demoting themselves (don't lock yourself out)
        if ($user_id == $_SESSION['user_id'] && in_array($action, ['set_buyer', 'set_seller'])) {
            $error = "You cannot change your own role.";
        } else {
            switch ($action) {
                case 'set_buyer':
                    $pdo->prepare("UPDATE users SET role = 'buyer' WHERE user_id = ?")->execute([$user_id]);
                    break;
                case 'set_seller':
                    $pdo->prepare("UPDATE users SET role = 'seller' WHERE user_id = ?")->execute([$user_id]);
                    break;
                case 'set_admin':
                    $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$user_id]);
                    break;
                case 'toggle_verify':
                    $pdo->prepare("UPDATE users SET is_verified = NOT is_verified WHERE user_id = ?")->execute([$user_id]);
                    break;
            }
            header('Location: admin_users.php');
            exit;
        }
    }
}

$users = $pdo->query(
    "SELECT user_id, full_name, email, phone, role, is_verified, created_at
     FROM users ORDER BY created_at DESC"
)->fetchAll();

$page_title = 'Manage Users';
require_once '../includes/header.php';
?>

<div class="page-header">
    <h1>Manage Users</h1>
    <a href="admin_dashboard.php" class="link-action">← Dashboard</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="orders-table-wrapper">
    <table class="orders-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Verified</th>
                <th>Joined</th>
                <th>Change Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= (int)$user['user_id'] ?></td>
                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['phone']) ?></td>
                    <td>
                        <span class="status-badge status-<?= htmlspecialchars($user['role']) ?>">
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?= $user['is_verified']
                            ? '<span class="verified-badge">✓ Yes</span>'
                            : '<span class="status-badge status-pending">No</span>' ?>
                    </td>
                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:4px; flex-wrap:wrap;">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <?php if ($user['role'] !== 'buyer'): ?>
                                <button type="submit" name="action" value="set_buyer" class="link-action btn-small">Buyer</button>
                            <?php endif; ?>
                            <?php if ($user['role'] !== 'seller'): ?>
                                <button type="submit" name="action" value="set_seller" class="link-action btn-small">Seller</button>
                            <?php endif; ?>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <button type="submit" name="action" value="set_admin" class="link-action btn-small">Admin</button>
                            <?php endif; ?>
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                            <button type="submit" name="action" value="toggle_verify" class="link-action btn-small">
                                <?= $user['is_verified'] ? 'Unverify' : 'Verify' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>