<?php
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo "<div class='container'><div class='alert alert-danger'>Produk tidak ditemukan</div></div>";
    exit;
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND is_active = 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "<div class='container'><div class='alert alert-danger'>Produk tidak ditemukan</div></div>";
    exit;
}

// Get related products
$related_stmt = $conn->prepare("
    SELECT * 
    FROM products
    WHERE category = ? AND product_id != ? AND is_active = 1
    ORDER BY RAND()
    LIMIT 4
");
$related_stmt->bind_param("si", $product['category'], $product_id);
$related_stmt->execute();
$related = $related_stmt->get_result();
$related_stmt->close();
?>

<div class="product-detail-page">
    <div class="container">
        
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="index.php?page=shop">Shop</a>
            <i class="fas fa-chevron-right"></i>
            <span><?= h($product['name']) ?></span>
        </div>
        
        <!-- Product Detail -->
        <div class="product-detail-grid">
            
            <!-- Product Image -->
            <div class="product-image-section">
                <div class="product-image-main">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= h($product['image']) ?>" alt="<?= h($product['name']) ?>">
                    <?php else: ?>
                        <div class="placeholder-image-large">
                            <i class="fas fa-image"></i>
                            <p>No Image</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="product-info-section">
                <div class="product-category-badge"><?= h($product['category']) ?></div>
                
                <h1 class="product-title"><?= h($product['name']) ?></h1>
                
                <div class="product-meta-row">
                    <?php if (!empty($product['platform'])): ?>
                        <span class="meta-badge">
                            <i class="fas fa-desktop"></i> <?= h($product['platform']) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['region'])): ?>
                        <span class="meta-badge">
                            <i class="fas fa-globe"></i> <?= h($product['region']) ?>
                        </span>
                    <?php endif; ?>
                    
                    <span class="meta-badge">
                        <i class="fas fa-truck"></i> <?= h(ucwords(str_replace('_', ' ', $product['delivery_method']))) ?>
                    </span>
                </div>
                
                <div class="product-price-box">
                    <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                        <div class="price-row">
                            <span class="price-label">Harga Normal:</span>
                            <span class="price-old-large"><?= formatRupiah($product['price']) ?></span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Harga Diskon:</span>
                            <span class="price-sale-large"><?= formatRupiah($product['sale_price']) ?></span>
                            <span class="discount-percentage">
                                Hemat <?= round((1 - $product['sale_price']/$product['price']) * 100) ?>%
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="price-row">
                            <span class="price-label">Harga:</span>
                            <span class="price-normal-large"><?= formatRupiah($product['price']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['delivery_method'] === 'license_code'): ?>
                    <div class="stock-info">
                        <?php
                        $stock = (int)$product['stock'];
                        if ($stock > 10) {
                            echo '<span class="stock-badge-large stock-ok">
                                    <i class="fas fa-check-circle"></i> Stok Tersedia (' . $stock . ')
                                  </span>';
                        } elseif ($stock > 0) {
                            echo '<span class="stock-badge-large stock-low">
                                    <i class="fas fa-exclamation-circle"></i> Stok Terbatas (' . $stock . ')
                                  </span>';
                        } else {
                            echo '<span class="stock-badge-large stock-out">
                                    <i class="fas fa-times-circle"></i> Stok Habis
                                  </span>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="product-actions-box">
                    <?php if ($product['delivery_method'] === 'license_code' && $stock == 0): ?>
                        <button class="btn btn-secondary btn-lg btn-block" disabled>
                            <i class="fas fa-ban"></i> Stok Habis
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-lg btn-block" onclick="addToCart(<?= $product['product_id'] ?>)"
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                        <button class="btn btn-success btn-lg btn-block" onclick="buyNow(<?= $product['id'] ?>)">
                            <i class="fas fa-bolt"></i> Beli Sekarang
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="product-features">
                    <div class="feature-item">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <strong>100% Original</strong>
                            <p>Produk dijamin original dan dapat digunakan</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-bolt"></i>
                        <div>
                            <strong>Instant Delivery</strong>
                            <p>Pengiriman otomatis setelah pembayaran</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-headset"></i>
                        <div>
                            <strong>24/7 Support</strong>
                            <p>Customer service siap membantu</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Description -->
        <?php if (!empty($product['description'])): ?>
            <div class="product-description-section">
                <h2><i class="fas fa-info-circle"></i> Deskripsi Produk</h2>
                <div class="description-content">
                    <?= nl2br(h($product['description'])) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Related Products -->
        <?php if ($related->num_rows > 0): ?>
            <div class="related-products-section">
                <h2><i class="fas fa-th"></i> Produk Terkait</h2>
                <div class="products-grid">
                    <?php while ($rel = $related->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($rel['image'])): ?>
                                    <img src="<?= h($rel['image']) ?>" alt="<?= h($rel['name']) ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-category"><?= h($rel['category']) ?></div>
                                <h3 class="product-name"><?= h($rel['name']) ?></h3>
                                
                                <div class="product-price">
                                    <?php if (!empty($rel['sale_price']) && $rel['sale_price'] > 0): ?>
                                        <span class="price-old"><?= formatRupiah($rel['price']) ?></span>
                                        <span class="price-sale"><?= formatRupiah($rel['sale_price']) ?></span>
                                    <?php else: ?>
                                        <span class="price-normal"><?= formatRupiah($rel['price']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="index.php?page=product_detail&id=<?= $rel['id'] ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                    <button class="btn btn-primary btn-sm" onclick="addToCart(<?= $rel['id'] ?>)">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>