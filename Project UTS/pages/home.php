<?php
// Ambil produk featured (8 produk terbaru)
$featured_query = "
    SELECT * 
    FROM products 
    WHERE is_active = 1
    ORDER BY id DESC
    LIMIT 8
";
$featured_result = $conn->query($featured_query);

// Ambil kategori unik
$categories_result = $conn->query("SELECT DISTINCT category FROM products WHERE is_active = 1 AND category IS NOT NULL ORDER BY category ASC");
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">🎮 Zapedia - Premium Digital Products</h1>
            <p class="hero-subtitle">Netflix, Spotify, YouTube Premium & Lebih Banyak Lagi!</p>
            <p class="hero-description">Dapatkan akses premium dengan harga terjangkau. Pengiriman instan!</p>
            <div class="hero-buttons">
                <a href="index.php?page=shop" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i> Mulai Belanja
                </a>
                <?php if (!isset($_SESSION['username'])): ?>
                    <a href="index.php?page=register" class="btn btn-outline btn-lg">
                        <i class="fas fa-user-plus"></i> Daftar Gratis
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section">
    <div class="container">
        <h2 class="section-title">📂 Kategori Produk</h2>
        <div class="categories-grid">
            <?php while ($cat = $categories_result->fetch_assoc()): ?>
                <a href="index.php?page=shop&category=<?= urlencode($cat['category']) ?>" class="category-card">
                    <i class="fas fa-folder"></i>
                    <span><?= h($cat['category']) ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🔥 Produk Terpopuler</h2>
            <a href="index.php?page=shop" class="btn btn-outline">Lihat Semua</a>
        </div>
        
        <div class="products-grid">
            <?php while ($product = $featured_result->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= htmlspecialchars($product['image']) ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                            <span class="badge-discount">
                                -<?= round((1 - $product['sale_price']/$product['price']) * 100) ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <div class="product-category"><?= h($product['category']) ?></div>
                        <h3 class="product-name"><?= h($product['name']) ?></h3>
                        
                        <div class="product-meta">
                            <?php if (!empty($product['platform'])): ?>
                                <span class="meta-item">
                                    <i class="fas fa-desktop"></i> <?= h($product['platform']) ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['region'])): ?>
                                <span class="meta-item">
                                    <i class="fas fa-globe"></i> <?= h($product['region']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-price">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                <span class="price-old"><?= formatRupiah($product['price']) ?></span>
                                <span class="price-sale"><?= formatRupiah($product['sale_price']) ?></span>
                            <?php else: ?>
                                <span class="price-normal"><?= formatRupiah($product['price']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-stock">
                            <?php
                            $stock = (int)$product['stock'];
                            if ($stock > 10) {
                                echo '<span class="stock-badge stock-ok"><i class="fas fa-check-circle"></i> Stok: ' . $stock . '</span>';
                            } elseif ($stock > 0) {
                                echo '<span class="stock-badge stock-low"><i class="fas fa-exclamation-circle"></i> Stok: ' . $stock . '</span>';
                            } else {
                                echo '<span class="stock-badge stock-out"><i class="fas fa-times-circle"></i> Stok Habis</span>';
                            }
                            ?>
                        </div>
                        
                        <div class="product-actions">
                            <a href="index.php?page=product_detail&id=<?= $product['id'] ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-info-circle"></i> Detail
                            </a>
                            
                            <?php if ($stock == 0): ?>
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-ban"></i> Habis
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary btn-sm" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus"></i> Keranjang
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title">✨ Kenapa Pilih Kami?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-bolt"></i>
                <h3>Pengiriman Instan</h3>
                <p>Dapatkan kode lisensi langsung setelah pembayaran dikonfirmasi</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>100% Aman</h3>
                <p>Semua produk dijamin original dan dapat digunakan</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-tags"></i>
                <h3>Harga Terjangkau</h3>
                <p>Dapatkan harga terbaik untuk produk premium</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>Support 24/7</h3>
                <p>Tim kami siap membantu kapan saja</p>
            </div>
        </div>
    </div>
</section>