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
$error = '';

// ==================== FUNGSI UPLOAD GAMBAR ====================
function uploadProductImage($file) {
    $upload_dir = 'uploads/products/';
    
    // Auto create folder
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validasi file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak valid. Gunakan JPG, PNG, atau WEBP'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 5MB'];
    }
    
    // Generate unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'product_' . time() . '_' . uniqid() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
}

// ==================== HAPUS PRODUCT ====================
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    
    if ($id > 0) {
        // Get image path
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            // Delete product
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            
            if ($delete_stmt->execute()) {
                // Delete image file
                if (!empty($product['image']) && file_exists($product['image'])) {
                    unlink($product['image']);
                }
                $message = "Produk berhasil dihapus!";
            } else {
                $error = "Gagal menghapus produk!";
            }
            $delete_stmt->close();
        }
        $stmt->close();
    }
}

// ==================== TAMBAH PRODUCT ====================
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $platform = trim($_POST['platform']);
    $region = trim($_POST['region']);
    $description = trim($_POST['description']);
    $stock = (int)$_POST['stock'];
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadProductImage($_FILES['product_image']);
        
        if ($upload_result['success']) {
            $image_path = $upload_result['filepath'];
        } else {
            $error = $upload_result['message'];
        }
    } elseif (!empty($_POST['image_url'])) {
        $image_path = trim($_POST['image_url']);
    }
    
    if (empty($error)) {
        $stmt = $conn->prepare("
            INSERT INTO products (name, category, price, sale_price, platform, region, description, image, stock, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("ssddssssi", $name, $category, $price, $sale_price, $platform, $region, $description, $image_path, $stock);
        
        if ($stmt->execute()) {
            $message = "Produk berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan produk!";
        }
        $stmt->close();
    }
}

// ==================== UPDATE PRODUCT ====================
if (isset($_POST['update_product'])) {
    $id = (int)$_POST['product_id'];
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $platform = trim($_POST['platform']);
    $region = trim($_POST['region']);
    $description = trim($_POST['description']);
    $stock = (int)$_POST['stock'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Get current image
    $current_stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $current_stmt->bind_param("i", $id);
    $current_stmt->execute();
    $current = $current_stmt->get_result()->fetch_assoc();
    $image_path = $current['image'];
    $current_stmt->close();
    
    // Handle new image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadProductImage($_FILES['product_image']);
        
        if ($upload_result['success']) {
            // Delete old image
            if (!empty($image_path) && file_exists($image_path)) {
                unlink($image_path);
            }
            $image_path = $upload_result['filepath'];
        } else {
            $error = $upload_result['message'];
        }
    } elseif (!empty($_POST['image_url'])) {
        $image_path = trim($_POST['image_url']);
    }
    
    if (empty($error)) {
        // UPDATE dengan 10 kolom
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, category = ?, price = ?, sale_price = ?, 
                platform = ?, region = ?, description = ?, image = ?, stock = ?, is_active = ?
            WHERE id = ?
        ");
        
        // Bind 11 parameter (10 kolom + 1 WHERE)
        $stmt->bind_param("ssddssssiii", 
            $name,          // s = string
            $category,      // s = string
            $price,         // d = double
            $sale_price,    // d = double
            $platform,      // s = string
            $region,        // s = string
            $description,   // s = string
            $image_path,    // s = string
            $stock,         // i = integer
            $is_active,     // i = integer
            $id             // i = integer (WHERE)
        );
        
        if ($stmt->execute()) {
            $message = "Produk berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate produk: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Product - Admin Panel</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f5f7fa; }
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .admin-header h1 { display: flex; align-items: center; gap: 10px; margin: 0; }
        .admin-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .form-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .form-section h3 { margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 14px; font-family: inherit; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-checkbox { display: flex; align-items: center; gap: 8px; }
        .form-checkbox input[type="checkbox"] { width: auto; cursor: pointer; }
        .image-upload-section { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px dashed #dee2e6; margin-bottom: 20px; }
        .image-preview { margin-top: 15px; text-align: center; }
        .image-preview img { max-width: 300px; max-height: 200px; border-radius: 8px; border: 2px solid #dee2e6; }
        .upload-info { font-size: 12px; color: #666; margin-top: 8px; }
        .table-section { background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); overflow: hidden; }
        .table-header { padding: 20px 25px; background: #f8f9fa; border-bottom: 2px solid #e1e8ed; }
        .table-header h3 { margin: 0; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f8f9fa; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #e1e8ed; }
        th { font-weight: 600; color: #333; font-size: 14px; text-transform: uppercase; }
        tbody tr:hover { background: #f8f9fa; }
        .product-image-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid #e1e8ed; }
        .no-image { width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px; color: white; font-size: 20px; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-icon { padding: 8px 12px; font-size: 14px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; position: relative; }
        .modal-close { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: #999; background: none; border: none; }
        .modal-close:hover { color: #333; }
    </style>
</head>
<body>

<div class="admin-container">
    
    <div class="admin-header">
        <h1><i class="fas fa-box"></i> Master Product</h1>
        <div class="admin-actions">
            <a href="index.php?page=admin_dashboard" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Lihat Website
            </a>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Form Tambah Product -->
    <div class="form-section">
        <h3><i class="fas fa-plus-circle"></i> Tambah Produk Baru</h3>
        
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Produk *</label>
                    <input type="text" name="name" placeholder="Netflix Premium 1 Bulan" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori *</label>
                    <input type="text" name="category" placeholder="Streaming" required>
                </div>
                
                <div class="form-group">
                    <label>Harga Normal (Rp) *</label>
                    <input type="number" name="price" placeholder="50000" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Harga Diskon (Rp)</label>
                    <input type="number" name="sale_price" placeholder="40000" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Platform</label>
                    <input type="text" name="platform" placeholder="Web, Mobile, Desktop">
                </div>
                
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" name="region" placeholder="Global / Indonesia">
                </div>
                
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" placeholder="10" min="0" value="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi Produk</label>
                <textarea name="description" placeholder="Jelaskan detail produk..."></textarea>
            </div>
            
            <!-- Image Upload Section -->
            <div class="image-upload-section">
                <h4><i class="fas fa-image"></i> Upload Gambar Produk</h4>
                
                <div class="form-group">
                    <label>Upload File (JPG, PNG, WEBP - Max 5MB)</label>
                    <input type="file" name="product_image" accept="image/*" onchange="previewImage(this, 'add-preview')">
                    <div class="upload-info">
                        <i class="fas fa-info-circle"></i> Ukuran rekomendasi: 300x200px atau rasio 3:2
                    </div>
                </div>
                
                <div class="image-preview" id="add-preview"></div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>ATAU Gunakan URL Gambar</label>
                    <input type="text" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
            </div>
            
            <button type="submit" name="add_product" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Produk
            </button>
        </form>
    </div>
    
    <!-- Tabel Products -->
    <div class="table-section">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> Daftar Produk</h3>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products->num_rows > 0): ?>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?= htmlspecialchars($product['image']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="product-image-thumb">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($product['name']) ?></strong>
                                <br>
                                <small style="color: #999;">
                                    <?= htmlspecialchars($product['platform']) ?> 
                                    <?php if (!empty($product['region'])): ?>
                                        | <?= htmlspecialchars($product['region']) ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td>
                                <?php if (!empty($product['sale_price']) && $product['sale_price'] > 0): ?>
                                    <span style="text-decoration: line-through; color: #999; font-size: 12px;">
                                        <?= formatRupiah($product['price']) ?>
                                    </span>
                                    <br>
                                    <strong style="color: #dc3545;">
                                        <?= formatRupiah($product['sale_price']) ?>
                                    </strong>
                                <?php else: ?>
                                    <strong><?= formatRupiah($product['price']) ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $stock = (int)$product['stock'];
                                if ($stock > 10) {
                                    echo '<span class="badge badge-success">Stock: ' . $stock . '</span>';
                                } elseif ($stock > 0) {
                                    echo '<span class="badge badge-warning">Stock: ' . $stock . '</span>';
                                } else {
                                    echo '<span class="badge badge-danger">Habis</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-primary btn-sm btn-icon" 
                                            onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <a href="index.php?page=admin_products&delete=<?= $product['id'] ?>" 
                                       class="btn btn-danger btn-sm btn-icon"
                                       onclick="return confirm('Yakin hapus produk ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <i class="fas fa-box-open" style="font-size: 48px; color: #ccc;"></i>
                            <p style="margin-top: 10px; color: #999;">Belum ada produk</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
</div>

<!-- Modal Edit Product -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <h2><i class="fas fa-edit"></i> Edit Produk</h2>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_id" id="edit_product_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Produk *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori *</label>
                    <input type="text" name="category" id="edit_category" required>
                </div>
                
                <div class="form-group">
                    <label>Harga Normal (Rp) *</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>Harga Diskon (Rp)</label>
                    <input type="number" name="sale_price" id="edit_sale_price" step="0.01">
                </div>
                
                <div class="form-group">
                    <label>Platform</label>
                    <input type="text" name="platform" id="edit_platform">
                </div>
                
                <div class="form-group">
                    <label>Region</label>
                    <input type="text" name="region" id="edit_region">
                </div>
                
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" id="edit_stock" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Deskripsi Produk</label>
                <textarea name="description" id="edit_description"></textarea>
            </div>
            
            <!-- Image Upload Section -->
            <div class="image-upload-section">
                <h4><i class="fas fa-image"></i> Update Gambar Produk</h4>
                
                <div class="image-preview" id="edit-current-image"></div>
                
                <div class="form-group">
                    <label>Upload Gambar Baru</label>
                    <input type="file" name="product_image" accept="image/*" onchange="previewImage(this, 'edit-preview')">
                </div>
                
                <div class="image-preview" id="edit-preview"></div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>ATAU Update dengan URL</label>
                    <input type="text" name="image_url" id="edit_image">
                </div>
            </div>
            
            <div class="form-group form-checkbox">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                <label for="edit_is_active" style="margin: 0;">Produk Aktif</label>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="update_product" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 2px solid #dee2e6;">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function editProduct(product) {
    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_category').value = product.category;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_sale_price').value = product.sale_price || '';
    document.getElementById('edit_platform').value = product.platform || '';
    document.getElementById('edit_region').value = product.region || '';
    document.getElementById('edit_stock').value = product.stock || 0;
    document.getElementById('edit_description').value = product.description || '';
    document.getElementById('edit_image').value = product.image || '';
    document.getElementById('edit_is_active').checked = product.is_active == 1;
    
    const currentImageDiv = document.getElementById('edit-current-image');
    if (product.image) {
        currentImageDiv.innerHTML = '<h4>Gambar Saat Ini:</h4><img src="' + product.image + '" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 2px solid #dee2e6; margin-bottom: 15px;">';
    } else {
        currentImageDiv.innerHTML = '<p style="color: #999;">Belum ada gambar</p>';
    }
    
    document.getElementById('edit-preview').innerHTML = '';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>

</body>
</html>