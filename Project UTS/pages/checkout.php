<?php
if (!isset($_SESSION['username'])) {
    header("Location: index.php?page=login");
    exit;
}

// Get user
$user_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$user_stmt->bind_param("s", $_SESSION['username']);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
// Database sudah pakai 'id', tidak perlu alias
$user_stmt->close();

$message = '';
$success = false;

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get cart items
    $cart_query = $conn->prepare("
        SELECT c.id as cart_id, p.id, p.name, p.price, p.sale_price, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? AND p.is_active = 1
    ");
    $cart_query->bind_param("i", $user['id']);
    $cart_query->execute();
    $cart_items = $cart_query->get_result();
    
    if ($cart_items->num_rows === 0) {
        $message = "Keranjang belanja kosong!";
    } else {
        $conn->begin_transaction();
        
        try {
            while ($item = $cart_items->fetch_assoc()) {
                // Cek stock
                if ($item['stock'] <= 0) {
                    throw new Exception("Stock habis untuk produk: " . $item['name']);
                }
                
                $order_number = generateOrderNumber();
                $price = !empty($item['sale_price']) && $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
                
                // Create order
                $order_stmt = $conn->prepare("
                    INSERT INTO orders (user_id, product_id, order_number, product_name, price, status)
                    VALUES (?, ?, ?, ?, ?, 'processing')
                ");
                $order_stmt->bind_param("iissd", 
                    $user['id'], 
                    $item['id'], 
                    $order_number, 
                    $item['name'], 
                    $price
                );
                
                if (!$order_stmt->execute()) {
                    throw new Exception("Gagal membuat pesanan");
                }
                $order_stmt->close();
                
                // Kurangi stock
                $update_stock = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ? AND stock > 0");
                $update_stock->bind_param("i", $item['id']);
                $update_stock->execute();
                $update_stock->close();
                
                // Remove from cart
                $remove_cart = $conn->prepare("DELETE FROM cart WHERE id = ?");
                $remove_cart->bind_param("i", $item['cart_id']);
                $remove_cart->execute();
                $remove_cart->close();
            }
            
            $conn->commit();
            $success = true;
            $message = "Checkout berhasil! Terima kasih atas pembelian Anda.";
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Checkout gagal: " . $e->getMessage();
        }
    }
    $cart_query->close();
}

// Get cart for display
$display_cart = $conn->prepare("
    SELECT c.id, p.name, p.category, p.price, p.sale_price, p.image, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.is_active = 1
");
$display_cart->bind_param("i", $user['id']);
$display_cart->execute();
$items = $display_cart->get_result();

$total = 0;
?>

<div class="checkout-page">
    <div class="container">
        
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
                <?= h($message) ?>
                <?php if ($success): ?>
                    <a href="index.php?page=dashboard" class="btn btn-primary btn-sm">
                        Lihat Pesanan Saya
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($items->num_rows > 0 && !$success): ?>
            <form method="post" class="checkout-form">
                <div class="checkout-layout">
                    
                    <!-- Order Items -->
                    <div class="checkout-items">
                        <h2>Item Pesanan</h2>
                        
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <?php
                            $price = !empty($item['sale_price']) && $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
                            $total += $price;
                            
                            $out_of_stock = ($item['stock'] == 0);
                            ?>
                            
                            <div class="checkout-item <?= $out_of_stock ? 'out-of-stock' : '' ?>">
                                <div class="item-image">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image-small">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <h3><?= h($item['name']) ?></h3>
                                    <div class="item-category"><?= h($item['category']) ?></div>
                                    
                                    <?php if ($out_of_stock): ?>
                                        <div class="stock-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Stok Habis - Item akan dihapus
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-price">
                                    <?= formatRupiah($price) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="checkout-summary">
                        <h2>Ringkasan Pembayaran</h2>
                        
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span><?= formatRupiah($total) ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Biaya Admin</span>
                                <span>Rp 0</span>
                            </div>
                            
                            <hr>
                            
                            <div class="summary-row total">
                                <strong>Total Pembayaran</strong>
                                <strong><?= formatRupiah($total) ?></strong>
                            </div>
                        </div>
                        
                        <div class="payment-info">
                            <h3>Informasi Pembayaran</h3>
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <p>Setelah checkout, pesanan Anda akan diproses dan dapat dilihat di dashboard.</p>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg btn-block">
                            <i class="fas fa-check-circle"></i> Proses Checkout
                        </button>
                        
                        <a href="index.php?page=cart" class="btn btn-outline btn-block">
                            <i class="fas fa-arrow-left"></i> Kembali ke Keranjang
                        </a>
                    </div>
                    
                </div>
            </form>
        <?php elseif (!$success): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>Tidak Ada Item untuk Checkout</h3>
                <a href="index.php?page=shop" class="btn btn-primary">Mulai Belanja</a>
            </div>
        <?php endif; ?>
        
    </div>
</div>