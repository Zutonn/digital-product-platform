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
if (!in_array($role, ['admin_utama', 'admin_user'])) {
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

// ==================== HAPUS USER ====================
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    
        $current_user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $current_user_stmt->bind_param("s", $_SESSION['username']);
        $current_user_stmt->execute();
        $current_user_id = $current_user_stmt->get_result()->fetch_assoc()['id'];
        $current_user_stmt->close();

if ($id > 0 && $id != $current_user_id) {
        // Delete related data
        $conn->query("DELETE FROM cart WHERE user_id = $id");
        $conn->query("DELETE FROM orders WHERE user_id = $id");
        
        // Delete user
        if ($role === 'admin_utama') {
            $conn->query("DELETE FROM users WHERE id = $id");
        } else {
            $conn->query("DELETE FROM users WHERE id = $id AND role = 'user'");
        }
        
        // Redirect
        header("Location: index.php?page=admin_users&deleted=1");
        exit;
    }
}
// Success message
if (isset($_GET['deleted'])) {
    $message = "User berhasil dihapus!";
}
// ==================== TAMBAH USER ====================
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $role_new = $_POST['role'];
    
    // Validasi
    if (!$email) {
        $error = "Email tidak valid!";
    } elseif (strlen($username) < 3) {
        $error = "Username minimal 3 karakter!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek username sudah ada
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role_new);
            
            if ($stmt->execute()) {
                $message = "User berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan user!";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// ==================== UPDATE USER ====================
if (isset($_POST['update_user'])) {
    $id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $role_new = $_POST['role'];
    $password = $_POST['password'];
    
    if (!$email) {
        $error = "Email tidak valid!";
    } else {
        if (!empty($password)) {
            // Update dengan password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role_new, $id);
        } else {
            // Update tanpa password
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $role_new, $id);
        }
        
        if ($stmt->execute()) {
            $message = "User berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate user!";
        }
        $stmt->close();
    }
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY id DESC";
$users = $conn->query($users_query);

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as regular_users,
        SUM(CASE WHEN role LIKE 'admin%' THEN 1 ELSE 0 END) as admins
    FROM users
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master User - Admin Panel</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .admin-header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 14px;
            color: #666;
        }
        
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .table-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .table-header {
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 2px solid #e1e8ed;
        }
        
        .table-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e8ed;
        }
        
        th {
            font-weight: 600;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            color: #555;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-primary {
            background: #cfe2ff;
            color: #084298;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-icon {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            background: none;
            border: none;
            line-height: 1;
        }
        
        .modal-close:hover {
            color: #333;
        }
    </style>
</head>
<body>

<div class="admin-container">
    
    <!-- Header -->
    <div class="admin-header">
        <h1><i class="fas fa-users"></i> Master User</h1>
        <div class="admin-actions">
            <a href="index.php?page=admin_dashboard" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Lihat Website
            </a>
        </div>
    </div>
    
    <!-- Alert Messages -->
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
    
    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-box">
            <h3>Total Users</h3>
            <div class="value"><?= $stats['total'] ?></div>
        </div>
        
        <div class="stat-box">
            <h3>Regular Users</h3>
            <div class="value" style="color: #28a745;"><?= $stats['regular_users'] ?></div>
        </div>
        
        <div class="stat-box">
            <h3>Admins</h3>
            <div class="value" style="color: #dc3545;"><?= $stats['admins'] ?></div>
        </div>
    </div>
    
    <!-- Form Tambah User -->
    <div class="form-section">
        <h3><i class="fas fa-user-plus"></i> Tambah User Baru</h3>
        
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" placeholder="john_doe" minlength="3" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="user@email.com" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <option value="user">User</option>
                        <?php if ($role === 'admin_utama'): ?>
                            <option value="admin_utama">Admin Utama</option>
                            <option value="admin_product">Admin Product</option>
                            <option value="admin_user">Admin User</option>
                            <option value="admin_supplier">Admin Supplier</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="add_user" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah User
            </button>
        </form>
    </div>
    
    <!-- Tabel Users -->
    <div class="table-section">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> Daftar User</h3>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users->num_rows > 0): ?>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($user['username']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php
                                $role_badges = [
                                    'user' => ['class' => 'badge-primary', 'text' => 'User'],
                                    'admin_utama' => ['class' => 'badge-danger', 'text' => 'Admin Utama'],
                                    'admin_product' => ['class' => 'badge-warning', 'text' => 'Admin Product'],
                                    'admin_user' => ['class' => 'badge-success', 'text' => 'Admin User'],
                                    'admin_supplier' => ['class' => 'badge-warning', 'text' => 'Admin Supplier'],
                                ];
                                $badge = $role_badges[$user['role']] ?? ['class' => 'badge-primary', 'text' => ucfirst($user['role'])];
                                ?>
                                <span class="badge <?= $badge['class'] ?>">
                                    <?= $badge['text'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($user['created_at'])): ?>
                                    <?= date('d M Y', strtotime($user['created_at'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-primary btn-sm btn-icon" 
                                            onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($user['username'] !== $_SESSION['username']): ?>
                                        <a href="index.php?page=admin_users&delete=<?= $user['id'] ?>" 
                                        class="btn btn-danger btn-sm btn-icon"
                                        onclick="return confirm('⚠️ YAKIN HAPUS USER INI?\n\nUsername: <?= h($user['username']) ?>\n\nData tidak bisa dikembalikan!')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm btn-icon" disabled title="Tidak bisa hapus akun sendiri">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <i class="fas fa-users" style="font-size: 48px; color: #ccc;"></i>
                            <p style="margin-top: 10px; color: #999;">Belum ada user</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
</div>

<!-- Modal Edit User -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeEditModal()">&times;</button>
        <h2><i class="fas fa-user-edit"></i> Edit User</h2>
        
        <form method="post">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" id="edit_username" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="edit_role" required>
                        <option value="user">User</option>
                        <?php if ($role === 'admin_utama'): ?>
                            <option value="admin_utama">Admin Utama</option>
                            <option value="admin_product">Admin Product</option>
                            <option value="admin_user">Admin User</option>
                            <option value="admin_supplier">Admin Supplier</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" name="password" id="edit_password" placeholder="Kosongkan jika tidak ingin mengubah">
                    <small style="color: #999; display: block; margin-top: 5px;">
                        Kosongkan jika tidak ingin mengubah password
                    </small>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" name="update_user" class="btn btn-primary">
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
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = '';
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
}
</script>

</body>
</html>
