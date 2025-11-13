<?php
// File sudah di-include dari index.php
// Session dan config sudah tersedia

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
if (!in_array($role, ['admin_utama', 'admin_product'])) {
    echo "<div class='container' style='margin-top: 50px;'>
            <div class='alert alert-danger'>
                <h2>🚫 Akses Ditolak</h2>
                <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href='index.php' class='btn btn-primary'>Kembali ke Home</a>
            </div>
          </div>";
    exit();
}

$message = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();
    
    $message = "Status pesanan berhasil diupdate!";
}

// Get all orders
$orders_query = "
    SELECT o.*, u.username, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
";
$orders = $conn->query($orders_query);

// Statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(price) as total_revenue
    FROM orders
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Orders</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-orders {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-box h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
            color: #007BFF;
        }
        
        .orders-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #FFC107; color: #000; }
        .status-paid { background: #17A2B8; color: #fff; }
        .status-completed { background: #28A745; color: #fff; }
        .status-cancelled { background: #DC3545; color: #fff; }
    </style>
</head>
<body>

<div class="admin-orders">
    <div class="admin-header">
        <h1><i class="fas fa-clipboard-list"></i> Master Orders</h1>
        <a href="index.php?page=admin_dashboard" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= h($message) ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-box">
            <h3>Total Pesanan</h3>
            <div class="value"><?= $stats['total_orders'] ?></div>
        </div>
        
        <div class="stat-box">
            <h3>Selesai</h3>
            <div class="value" style="color: #28A745;"><?= $stats['completed'] ?></div>
        </div>
        
        <div class="stat-box">
            <h3>Pending</h3>
            <div class="value" style="color: #FFC107;"><?= $stats['pending'] ?></div>
        </div>
        
        <div class="stat-box">
            <h3>Total Pendapatan</h3>
            <div class="value" style="color: #28A745;"><?= formatRupiah($stats['total_revenue']) ?></div>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="orders-table">
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td><code><?= h($order['order_number']) ?></code></td>
                            <td>
                                <strong><?= h($order['username']) ?></strong><br>
                                <small><?= h($order['email']) ?></small>
                            </td>
                            <td><?= h($order['product_name']) ?></td>
                            <td><?= formatRupiah($order['price']) ?></td>
                            <td>
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
                                <span class="status-badge <?= $status_class[$order['status']] ?>">
                                    <?= $status_text[$order['status']] ?>
                                </span>
                            </td>
                            <td><?= date('d M Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Menunggu</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Diproses</option>
                                        <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>Dibayar</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Selesai</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ccc;"></i>
                            <p style="margin-top: 10px; color: #999;">Belum ada pesanan</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>