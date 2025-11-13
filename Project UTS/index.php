<?php
ob_start(); // Start output buffering
session_start();
include 'config.php';

// Routing system
$page = $_GET['page'] ?? 'home';
$page = preg_replace('/[^a-z_]/', '', $page);

$role = $_SESSION['role'] ?? 'guest';

// Access control
$access_control = [
    'guest' => ['home', 'shop', 'product_detail', 'login', 'register', 'forgot_password', 'faq', 'cara_pembelian', 'kebijakan_privasi', 'syarat_ketentuan'],
    'user' => ['home', 'shop', 'product_detail', 'cart', 'checkout', 'dashboard', 'orders', 'profile', 'faq', 'cara_pembelian', 'kebijakan_privasi', 'syarat_ketentuan'],
    'admin_utama' => ['home', 'shop', 'product_detail', 'dashboard', 'orders', 'admin_dashboard', 'admin_products', 'admin_orders', 'admin_users', 'faq', 'cara_pembelian', 'kebijakan_privasi', 'syarat_ketentuan'],
    'admin_product' => ['home', 'shop', 'dashboard', 'admin_dashboard', 'admin_products', 'admin_orders', 'faq', 'cara_pembelian', 'kebijakan_privasi', 'syarat_ketentuan'],
    'admin_user' => ['home', 'shop', 'dashboard', 'admin_dashboard', 'admin_users', 'faq', 'cara_pembelian', 'kebijakan_privasi', 'syarat_ketentuan'],
    'admin_supplier' => ['home', 'shop', 'dashboard', 'admin_dashboard', 'faq', 'cara_pembelian', 'kebijakan_privasi', 'syarat_ketentuan'],
];

$allowed = $access_control[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zapedia - Premium Digital Products</title>
    <link rel="icon" type="image/png" href="assets/Logo.png">
    
    <!-- FORCE HIDE SCROLLBAR -->
    <style>
        html, body {
            overflow-x: hidden;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        html::-webkit-scrollbar,
        body::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
        }
    </style>
    
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    
    <!-- Loading Screen -->
        <div class="page-loader hidden" id="pageLoader">
        <div class="loader-content">
            <div class="spinner"></div>
            <div class="loader-text">Memuat Zapedia...</div>
        </div>
    </div>
    
    <?php include 'includes/navbar.php'; ?>
    
    <main class="main-content">
        <?php
        if (in_array($page, $allowed)) {
            $file_map = [
                // Public pages
                'home' => 'pages/home.php',
                'shop' => 'pages/shop.php',
                'product_detail' => 'pages/product_detail.php',
                'login' => 'pages/login.php',
                'register' => 'pages/register.php',
                'forgot_password' => 'pages/forgot_password.php',
                
                // Support pages
                'faq' => 'pages/support/faq.php',
                'cara_pembelian' => 'pages/support/cara_pembelian.php',
                'kebijakan_privasi' => 'pages/support/kebijakan_privasi.php',
                'syarat_ketentuan' => 'pages/support/syarat_ketentuan.php',
                
                // User pages
                'cart' => 'pages/cart.php',
                'checkout' => 'pages/checkout.php',
                'dashboard' => 'user/dashboard.php',
                'orders' => 'user/orders.php',
                'profile' => 'user/profile.php',
                
                // Admin pages
                'admin_dashboard' => 'admin/master_dashboard.php',
                'admin_products' => 'admin/master_product.php',
                'admin_orders' => 'admin/master_orders.php',
                'admin_users' => 'admin/master_user.php',
            ];
            
            $file = $file_map[$page] ?? null;
            
            if ($file && file_exists($file)) {
                include $file;
            } else {
                echo "<div class='container'><h2>Halaman tidak ditemukan</h2></div>";
            }
        } else {
            if (!isset($_SESSION['username']) && !in_array($page, ['login', 'register'])) {
                echo "<div class='container alert alert-warning'>
                        <h3>⚠️ Silakan Login</h3>
                        <p>Anda harus login untuk mengakses halaman ini.</p>
                        <a href='index.php?page=login' class='btn btn-primary'>Login Sekarang</a>
                      </div>";
            } else {
                echo "<div class='container alert alert-danger'>
                        <h3>🚫 Akses Ditolak</h3>
                        <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                      </div>";
            }
        }
        ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
        <script src="assets/main.js"></script>
    <script src="assets/darkmode.js"></script>
    
    <script>
        document.getElementById('pageLoader').classList.add('hidden');
    </script>
</body>
</html>
<?php ob_end_flush(); ?>