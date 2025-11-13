<?php
// File sudah di-include dari index.php
// Session sudah dimulai di index.php

// Cek login
if (!isset($_SESSION['username'])) {
    echo "<div class='container' style='margin-top: 50px;'>
            <div class='alert alert-danger'>
                <h2>🚫 Akses Ditolak</h2>
                <p>Silakan login terlebih dahulu.</p>
                <a href='index.php?page=login' class='btn btn-primary'>Login</a>
            </div>
          </div>";
    exit();
}

$role = $_SESSION['role'];

// Cek akses admin
if (!in_array($role, ['admin_utama', 'admin_product', 'admin_user', 'admin_supplier'])) {
    echo "<div class='container' style='margin-top: 50px;'>
            <div class='alert alert-danger'>
                <h2>🚫 Akses Ditolak</h2>
                <p>Anda tidak memiliki izin untuk mengakses halaman admin.</p>
                <a href='index.php' class='btn btn-primary'>Kembali ke Home</a>
            </div>
          </div>";
    exit();
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(price) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0;

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, u.username
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-dashboard {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        .card-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .card-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .card-icon.purple { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        
        .card-content h3 {
            margin: 0;
            font-size: 14px;
            color: #666;
        }
        
        .card-content .value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quick-link {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .quick-link:hover {
            transform: translateY(-5px);
        }
        
        .quick-link i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }
        .orders-table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        body.dark-mode .orders-table-container {
            background: #1e293b !important;
        }

        body.dark-mode .orders-table-container table {
            background: transparent !important;
        }

        body.dark-mode .orders-table-container tr {
            border-color: #334155 !important;
        }

        body.dark-mode .orders-table-container td {
            color: #e2e8f0 !important;
        }

        body.dark-mode .quick-link {
            background: #1e293b !important;
            color: #f1f5f9 !important;
            border: 1px solid #334155 !important;
        }

        body.dark-mode .quick-link h3 {
            color: #ffffff !important;
        }

        body.dark-mode .quick-link i {
            color: #a5b4fc !important;
        }                    
    </style>
</head>
<body>

<div class="admin-dashboard">
    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
    <p>Selamat datang, <strong><?= h($_SESSION['username']) ?></strong> (<?= h($_SESSION['role']) ?>)</p>
    
    <!-- Statistics Cards -->
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-content">
                <h3>Total Users</h3>
                <div class="value"><?= $total_users ?></div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon green">
                <i class="fas fa-box"></i>
            </div>
            <div class="card-content">
                <h3>Total Produk</h3>
                <div class="value"><?= $total_products ?></div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon orange">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-content">
                <h3>Total Orders</h3>
                <div class="value"><?= $total_orders ?></div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon purple">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="card-content">
                <h3>Total Revenue</h3>
                <div class="value" style="font-size: 18px;"><?= formatRupiah($total_revenue) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Links -->
    <h2>Quick Access</h2>
    <div class="quick-links">
        <?php if (in_array($_SESSION['role'], ['admin_utama', 'admin_product'])): ?>
            <a href="index.php?page=admin_products" class="quick-link">
                <i class="fas fa-box"></i>
                <h3>Master Product</h3>
            </a>
        <?php endif; ?>
        
        <?php if (in_array($_SESSION['role'], ['admin_utama', 'admin_user'])): ?>
            <a href="index.php?page=admin_users" class="quick-link">
                <i class="fas fa-users"></i>
                <h3>Master User</h3>
            </a>
        <?php endif; ?>
        
        <?php if (in_array($_SESSION['role'], ['admin_utama', 'admin_product'])): ?>
            <a href="index.php?page=admin_orders" class="quick-link">
                <i class="fas fa-clipboard-list"></i>
                <h3>Master Orders</h3>
            </a>
        <?php endif; ?>
        
        <a href="index.php?page=home" class="quick-link">
            <i class="fas fa-home"></i>
            <h3>Lihat Website</h3>
        </a>
    </div>
    
    <!-- Recent Orders -->
    <h2>Pesanan Terbaru</h2>
    <div class="orders-table-container">
        <?php if ($recent_orders->num_rows > 0): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee;">
                        <th style="padding: 10px; text-align: left;">Order #</th>
                        <th style="padding: 10px; text-align: left;">Customer</th>
                        <th style="padding: 10px; text-align: left;">Produk</th>
                        <th style="padding: 10px; text-align: left;">Harga</th>
                        <th style="padding: 10px; text-align: left;">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><code><?= h($order['order_number']) ?></code></td>
                            <td style="padding: 10px;"><?= h($order['username']) ?></td>
                            <td style="padding: 10px;"><?= h($order['product_name']) ?></td>
                            <td style="padding: 10px;"><?= formatRupiah($order['price']) ?></td>
                            <td style="padding: 10px;"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 48px; display: block; margin-bottom: 10px;"></i>
                Belum ada pesanan
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
