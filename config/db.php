<?php

$host = 'localhost';
$dbname = 'kasiconnect';  
$username = 'root';        
$password = '';            

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    // Don't expose sensitive error details to users
    die("Database connection failed. Please contact admin.");
}
?>