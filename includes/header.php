<?php
// Shared header — include at the top of every page after auth.php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>KasiConnect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <a href="products.php">KasiConnect</a>
    </div>
    <ul class="nav-links">
        <li><a href="products.php">Browse</a></li>

        <?php if (isLoggedIn()): ?>
            <?php if (userRole() === 'buyer'): ?>
                <li><a href="my_orders.php">My Orders</a></li>
            <?php endif; ?>

            <?php if (userRole() === 'seller'): ?>
                <li><a href="my_products.php">My Listings</a></li>
                <li><a href="add_product.php">+ Add Product</a></li>
                <li><a href="my_sales.php">My Sales</a></li>
            <?php endif; ?>

            <?php if (userRole() === 'admin'): ?>
                <li><a href="admin_dashboard.php">Admin</a></li>
            <?php endif; ?>

            <li class="nav-user">
                Hi, <?= htmlspecialchars($_SESSION['full_name']) ?>
                (<?= htmlspecialchars($_SESSION['role']) ?>)
            </li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
<main class="container"></main>