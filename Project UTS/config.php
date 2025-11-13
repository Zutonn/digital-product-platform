<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_users";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    error_log("DB Error: " . $conn->connect_error);
    die("Database connection error.");
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Helper functions
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
}
?>