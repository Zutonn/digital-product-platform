<?php
if (!isset($_SESSION['username'])) {
    header("Location: index.php?page=login");
    exit;
}

// Get user ID
$user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$user_stmt->bind_param("s", $_SESSION['username']);
$user_stmt->execute();
$user_id = $user_stmt->get_result()->fetch_assoc()['id'];
$user_stmt->close();

$message = '';

// Handle add to cart via GET
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    
    // Check stock
    $stock_check = $conn->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
    $stock_check->bind_param("i", $product_id);
    $stock_check->execute();
    $stock_result = $stock_check->get_result();
    
    if ($stock_result->num_rows > 0) {
        $stock_data = $stock_result->fetch_assoc();
        
        if ($stock_data['stock'] > 0) {
            // Check if already in cart
            $check = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
            $check->bind_param("ii", $user_id, $product_id);
            $check->execute();
            
            if ($check->get_result()->num_rows === 0) {
                // Add to cart
                $insert = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?)");
                $insert->bind_param("ii", $user_id, $product_id);
                
                if ($insert->execute()) {
                    $message = "Produk berhasil ditambahkan ke keranjang!";
                    
                    // Redirect to checkout if requested
                    if (isset($_GET['checkout'])) {
                        header("Location: index.php?page=checkout");
                        exit;
                    }
                } else {
                    $message = "Gagal menambahkan ke keranjang!";
                }
                $insert->close();
            } else {
                $message = "Produk sudah ada di keranjang!";
            }
            $check->close();
        } else {
            $message = "Maaf, produk ini stok habis!";
        }
    }
    $stock_check->close();
}

// Handle remove from cart
if (isset($_POST['remove_from_cart'])) {
    $cart_id = (int)$_POST['cart_id'];
    $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $cart_id, $user_id);
    $delete->execute();
    $delete->close();
    
    header("Location: index.php?page=cart");
    exit;
}

// Get cart items
$cart_query = $conn->prepare("
    SELECT c.id as cart_id, c.quantity,
           p.id, p.name, p.category, p.price, p.sale_price, p.image, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.is_active = 1
");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_items = $cart_query->get_result();
$cart_query->close();

// Calculate totals
$subtotal = 0;
$items_count = 0;
?>

<div class="cart-page">
    <div class="container">
        
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Keranjang Belanja</h1>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= h($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($cart_items->num_rows > 0): ?>
            <div class="cart-layout">
                
                <!-- Cart Items -->
                <div class="cart-items-section">
                    <?php while ($item = $cart_items->fetch_assoc()): ?>
                        <?php
                        $price = !empty($item['sale_price']) && $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
                        $subtotal += $price * $item['quantity'];
                        $items_count++;
                        
                        $out_of_stock = ($item['stock'] == 0);
                        ?>
                        
                        <div class="cart-item <?= $out_of_stock ? 'out-of-stock' : '' ?>">
                            <div class="cart-item-image">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= h($item['image']) ?>" alt="<?= h($item['name']) ?>">
                                <?php else: ?>
                                    <div class="placeholder-image-small">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-info">
                                <h3 class="cart-item-name"><?= h($item['name']) ?></h3>
                                <div class="cart-item-category"><?= h($item['category']) ?></div>
                                
                                <?php if ($out_of_stock): ?>
                                    <div class="stock-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Stok Habis
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-price">
                                <?= formatRupiah($price) ?>
                            </div>
                            
                            <div class="cart-item-actions">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-danger btn-sm" onclick="return confirm('Hapus dari keranjang?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Cart Summary -->
                <div class="cart-summary-section">
                    <div class="cart-summary">
                        <h2>Ringkasan Belanja</h2>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?= $items_count ?> item)</span>
                            <span><?= formatRupiah($subtotal) ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Biaya Admin</span>
                            <span>Rp 0</span>
                        </div>
                        
                        <hr>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <span><?= formatRupiah($subtotal) ?></span>
                        </div>
                        
                        <a href="index.php?page=checkout" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-credit-card"></i> Checkout
                        </a>
                        
                        <a href="index.php?page=shop" class="btn btn-outline btn-block">
                            <i class="fas fa-arrow-left"></i> Lanjut Belanja
                        </a>
                    </div>
                    
                    <div class="payment-methods">
                        <h3>Metode Pembayaran</h3>
                        <div class="payment-icons">
                            <i class="fas fa-university" title="Transfer Bank"></i>
                            <i class="fas fa-wallet" title="E-Wallet"></i>
                            <i class="fas fa-credit-card" title="Kartu Kredit"></i>
                        </div>
                    </div>
                </div>
                
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>Keranjang Kosong</h3>
                <p>Belum ada produk di keranjang Anda</p>
                <a href="index.php?page=shop" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</div>