<?php
// File sudah di-include dari index.php

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

// Get user
$user_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$user_stmt->bind_param("s", $_SESSION['username']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user['id']];
$types = "i";

if ($status_filter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// Get statistics
// Get statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(price) as total_spent
    FROM orders
    WHERE user_id = ?
");
$stats_query->bind_param("i", $user['id']);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
$stats_query->close();

// DEBUG - Cek hasil query
echo "<!-- DEBUG: Total=" . $stats['total'] . ", Processing=" . ($stats['processing'] ?? 'NULL') . ", Completed=" . $stats['completed'] . " -->";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan - Zapedia</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .orders-page {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }
        
        .filter-tab:hover {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-tab.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .order-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .order-number {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
        }
        
        .order-date {
            color: #999;
            font-size: 14px;
        }
        
        .order-body {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .order-product {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .license-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border: 2px dashed #667eea;
        }
        
        .license-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .license-code {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            word-break: break-all;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .status-processing {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
}
        body.dark-mode .status-processing {
            background: rgba(59, 130, 246, 0.25);
            color: #93c5fd;
        }

        .filter-tab.active {
            background: #667eea;
            color: white;
        }

        body.dark-mode .filter-tab {
            background: #1e293b;
            color: #cbd5e1;
            border: 1px solid #334155;
        }

        body.dark-mode .filter-tab:hover {
            background: #334155;
        }

        body.dark-mode .filter-tab.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>

<div class="orders-page">
    
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-clipboard-list"></i> Riwayat Pesanan</h1>
        <p>Semua pesanan Anda di satu tempat</p>
    </div>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Pesanan</h3>
            <div class="value"><?= $stats['total'] ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Diproses</h3>
            <div class="value" style="color: #3b82f6;"><?= $stats['processing'] ?? 0 ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Selesai</h3>
            <div class="value" style="color: #10b981;"><?= $stats['completed'] ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Pending</h3>
            <div class="value" style="color: #f59e0b;"><?= $stats['pending'] ?></div>
        </div>
        
        <div class="stat-card">
            <h3>Total Belanja</h3>
            <div class="value" style="font-size: 20px;"><?= formatRupiah($stats['total_spent'] ?? 0) ?></div>
        </div>
    </div>
    
    <!-- Filter -->
    <div class="filter-section">
        <h3 style="margin-bottom: 15px;">Filter Status</h3>
        <div class="filter-tabs">
            <a href="index.php?page=orders" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> Semua
            </a>
            <a href="index.php?page=orders&status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Menunggu
            </a>
            <a href="index.php?page=orders&status=processing" class="filter-tab <?= $status_filter === 'processing' ? 'active' : '' ?>">
                <i class="fas fa-hourglass-half"></i> Diproses
            </a>
            <a href="index.php?page=orders&status=paid" class="filter-tab <?= $status_filter === 'paid' ? 'active' : '' ?>">
                <i class="fas fa-credit-card"></i> Dibayar
            </a>
            <a href="index.php?page=orders&status=completed" class="filter-tab <?= $status_filter === 'completed' ? 'active' : '' ?>">
                <i class="fas fa-check-circle"></i> Selesai
            </a>
            <a href="index.php?page=orders&status=cancelled" class="filter-tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">
                <i class="fas fa-times-circle"></i> Dibatalkan
            </a>
        </div>
    </div>
    
    <!-- Orders List -->
    <div class="orders-list">
        <?php if ($orders->num_rows > 0): ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">
                                <i class="fas fa-receipt"></i> <?= h($order['order_number']) ?>
                            </div>
                            <div class="order-date">
                                <i class="fas fa-calendar"></i> <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        
                        <div>
                            <?php
                            $status_text = [
                                'pending' => 'Menunggu',
                                'processing' => 'Diproses',
                                'paid' => 'Dibayar',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan'
                            ];

                            $status_class = [
                                'pending' => 'status-pending',
                                'processing' => 'status-processing',
                                'paid' => 'status-paid',
                                'completed' => 'status-completed',
                                'cancelled' => 'status-cancelled'
                            ];
                            ?>
                            <span class="status-badge <?= $status_class[$order['status']] ?>">
                                <?= $status_text[$order['status']] ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-product">
                            <div class="product-name"><?= h($order['product_name']) ?></div>
                            <div class="product-price"><?= formatRupiah($order['price']) ?></div>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] === 'completed' && !empty($order['license_code'])): ?>
                        <div class="license-box">
                            <div class="license-label">
                                <i class="fas fa-key"></i> Kode Lisensi Anda:
                            </div>
                            <div class="license-code"><?= h($order['license_code']) ?></div>
                            <button class="btn btn-primary btn-sm" style="margin-top: 10px;" onclick="copyCode('<?= h($order['license_code']) ?>')">
                                <i class="fas fa-copy"></i> Copy Kode
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Anda belum memiliki pesanan<?= $status_filter !== 'all' ? ' dengan status ini' : '' ?>.</p>
                <a href="index.php?page=shop" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-shopping-cart"></i> Mulai Belanja
                </a>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Kode lisensi berhasil dicopy!');
    }).catch(() => {
        alert('Gagal copy kode. Silakan copy manual.');
    });
}
</script>

</body>
</html>