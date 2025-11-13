<?php
if (!isset($_SESSION['username'])) {
    header("Location: index.php?page=login");
    exit;
}

$user_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$user_stmt->bind_param("s", $_SESSION['username']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

// Get orders statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(price) as total_spent
    FROM orders
    WHERE user_id = ?
");
$stats_query->bind_param("i", $user['id']);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();
$stats_query->close();

// Get recent orders
$orders_query = $conn->prepare("
    SELECT * FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$orders_query->bind_param("i", $user['id']);
$orders_query->execute();
$orders = $orders_query->get_result();
$orders_query->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Zapedia</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        
        .welcome-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            font-weight: bold;
        }
        
        .welcome-text h1 {
            margin: 0 0 5px 0;
            font-size: 32px;
            color: #333;
        }
        
        .welcome-text p {
            margin: 0;
            color: #666;
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .stat-card.blue::before {
            background: #667eea;
        }
        
        .stat-card.green::before {
            background: #28a745;
        }
        
        .stat-card.purple::before {
            background: #764ba2;
        }
        
        .stat-card.orange::before {
            background: #ff6b6b;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 15px;
        }
        
        .stat-card.blue .stat-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card.green .stat-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }
        
        .stat-card.purple .stat-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .stat-card.orange .stat-icon {
            background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .orders-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .order-card:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .order-number {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
        }
        
        .order-body {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .order-product {
            font-weight: 600;
            color: #333;
        }
        
        .order-price {
            font-weight: bold;
            color: #28a745;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #999;
            margin-bottom: 20px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .quick-action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .quick-action-card i {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
        
        .quick-action-card.blue i {
            color: #667eea;
        }
        
        .quick-action-card.green i {
            color: #28a745;
        }
        
        .quick-action-card.orange i {
            color: #ff6b6b;
        }
        
        .quick-action-card h3 {
            margin: 0;
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="dashboard-page">
    <div class="dashboard-container">
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <div class="welcome-header">
                <div class="user-avatar">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
                <div class="welcome-text">
                    <h1>Halo, <?= h($user['username']) ?>! 👋</h1>
                    <p>Selamat datang kembali di Zapedia</p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="index.php?page=shop" class="quick-action-card blue">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Belanja Sekarang</h3>
                </a>
                
                <a href="index.php?page=orders" class="quick-action-card green">
                    <i class="fas fa-receipt"></i>
                    <h3>Lihat Pesanan</h3>
                </a>
                
                <a href="index.php?page=cart" class="quick-action-card orange">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Keranjang Saya</h3>
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
                <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-value"><?= $stats['total_orders'] ?? 0 ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?= $stats['processing_orders'] ?? 0 ?></div>
                <div class="stat-label">Diproses</div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= $stats['completed_orders'] ?? 0 ?></div>
                <div class="stat-label">Selesai</div>
            </div>
            
            <div class="stat-card purple">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-value" style="font-size: 24px;"><?= formatRupiah($stats['total_spent'] ?? 0) ?></div>
                <div class="stat-label">Total Belanja</div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="orders-section">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Pesanan Terbaru</h2>
                <a href="index.php?page=orders" class="btn btn-primary btn-sm">
                    Lihat Semua <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">
                                <i class="fas fa-receipt"></i> <?= h($order['order_number']) ?>
                            </div>
                            <div class="order-date">
                                <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-product">
                                <i class="fas fa-box"></i> <?= h($order['product_name']) ?>
                            </div>
                            <div class="order-price">
                                <?= formatRupiah($order['price']) ?>
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
                                    'pending' => 'badge-warning',
                                    'processing' => 'badge-info',
                                    'paid' => 'badge-info',
                                    'completed' => 'badge-success',
                                    'cancelled' => 'badge-danger'
                                ];
                                ?>
                                <span class="badge <?= $status_class[$order['status']] ?>">
                                    <?= $status_text[$order['status']] ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'completed'): ?>
                            <div class="order-footer">
                                <span style="color: #28a745;">
                                    <i class="fas fa-check-circle"></i> Pesanan selesai
                                </span>
                                <a href="index.php?page=orders" class="btn btn-outline btn-sm">
                                    Detail <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Belum Ada Pesanan</h3>
                    <p>Yuk mulai belanja produk premium di Zapedia!</p>
                    <a href="index.php?page=shop" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-cart"></i> Mulai Belanja
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

</body>
</html>