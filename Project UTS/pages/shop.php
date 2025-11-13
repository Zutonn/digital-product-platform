<?php
// Filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$where = ["is_active = 1"];
$params = [];
$types = "";

if ($search) {
    $where[] = "(name LIKE ? OR category LIKE ? OR platform LIKE ?)";
    $search_param = "%$search%";
    $params[] = &$search_param;
    $params[] = &$search_param;
    $params[] = &$search_param;
    $types .= "sss";
}

if ($category) {
    $where[] = "category = ?";
    $params[] = &$category;
    $types .= "s";
}

$where_clause = implode(" AND ", $where);

// Sorting
$order_map = [
    'newest' => 'id DESC',
    'oldest' => 'id ASC',
    'price_low' => 'COALESCE(sale_price, price) ASC',
    'price_high' => 'COALESCE(sale_price, price) DESC',
    'name' => 'name ASC',
];
$order_by = $order_map[$sort] ?? 'id DESC';

$query = "
    SELECT * 
    FROM products
    WHERE $where_clause
    ORDER BY $order_by
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM products WHERE is_active = 1 AND category IS NOT NULL ORDER BY category ASC");
?>

<div class="shop-page">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-shopping-bag"></i> Semua Produk</h1>
            <p>Temukan produk digital premium favorit Anda</p>
        </div>
        
        <!-- Filters -->
        <div class="shop-filters">
            <form method="get" action="index.php" class="filter-form">
                <input type="hidden" name="page" value="shop">
                
                <div class="filter-group">
                    <input type="text" 
                           name="search" 
                           placeholder="Cari produk..." 
                           value="<?= h($search) ?>"
                           class="form-control">
                </div>
                
                <div class="filter-group">
                    <select name="category" class="form-control">
                        <option value="">Semua Kategori</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= h($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                <?= h($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="sort" class="form-control">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Harga Terendah</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Harga Tertinggi</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nama A-Z</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filter
                </button>
                
                <a href="index.php?page=shop" class="btn btn-outline">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>
        
        <!-- Products Grid -->
        <div class="products-grid">
            <?php if ($products->num_rows > 0): ?>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= h($product['image']) ?>" alt="<?= h($product['name']) ?>">
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
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>Tidak Ada Produk</h3>
                    <p>Maaf, tidak ada produk yang sesuai dengan pencarian Anda.</p>
                    <a href="index.php?page=shop" class="btn btn-primary">Lihat Semua Produk</a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>