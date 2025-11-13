<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <a href="index.php" class="brand-logo">
                <img src="assets/logo.png" alt="" class="logo-img">
                <span class="brand-text">Zapedia</span>
            </a>
        </div>
        
        <ul class="nav-menu">
            <li><a href="index.php?page=home"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="index.php?page=shop"><i class="fas fa-shopping-bag"></i> Shop</a></li>
            
            <?php if (isset($_SESSION['username'])): ?>
                <?php
                $role = $_SESSION['role'];
                
                // Get user_id safely
                $user_id = $_SESSION['user_id'] ?? null;
                
                if (!$user_id) {
                    // If user_id not in session, get from database
                    $user_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                    $user_stmt->bind_param("s", $_SESSION['username']);
                    $user_stmt->execute();
                    $user_result = $user_stmt->get_result();
                    
                    if ($user_result->num_rows > 0) {
                        $user_data = $user_result->fetch_assoc();
                        $user_id = $user_data['id'];
                        $_SESSION['user_id'] = $user_id; // Save to session
                    }
                    $user_stmt->close();
                }
                
                // Cart count (only for regular users)
                $cart_count = 0;
                if ($user_id && $role === 'user') {
                    $cart_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
                    $cart_stmt->bind_param("i", $user_id);
                    $cart_stmt->execute();
                    $cart_result = $cart_stmt->get_result();
                    $cart_count = $cart_result->fetch_assoc()['count'];
                    $cart_stmt->close();
                }
                ?>
                
                <!-- Cart (only show for regular users) -->
                <?php if ($role === 'user'): ?>
                    <li>
                        <a href="index.php?page=cart">
                            <i class="fas fa-shopping-cart"></i> Keranjang
                            <?php if ($cart_count > 0): ?>
                                <span class="badge"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=orders">
                            <i class="fas fa-receipt"></i> Pesanan Saya
                        </a>
                    </li>
                <?php endif; ?>
                
                <!-- Dashboard Link -->
                <li>
                    <?php if (in_array($role, ['admin_utama', 'admin_product', 'admin_user', 'admin_supplier'])): ?>
                        <a href="index.php?page=admin_dashboard">
                            <i class="fas fa-tachometer-alt"></i> Admin Panel
                        </a>
                    <?php else: ?>
                        <a href="index.php?page=dashboard">
                            <i class="fas fa-user"></i> Dashboard
                        </a>
                    <?php endif; ?>
                </li>
                <!-- Logout -->
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
                
            <?php else: ?>
                <li><a href="index.php?page=login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <li><a href="index.php?page=register"><i class="fas fa-user-plus"></i> Register</a></li>
            <?php endif; ?>
        </ul>
        
        <div class="mobile-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.logo-img {
    height: 40px;
    width: auto;
    object-fit: contain;
}

.brand-text {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

@media (max-width: 768px) {
    .logo-img {
        height: 32px;
    }
    
    .brand-text {
        font-size: 18px;
    }
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 25px;
    align-items: center;
    margin: 0;
    padding: 0;
}

.nav-menu a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.nav-menu a:hover {
    color: #667eea;
}

.badge {
    background: #dc3545;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: bold;
    margin-left: 5px;
}

.mobile-toggle {
    display: none;
    font-size: 24px;
    cursor: pointer;
    color: #333;
}

@media (max-width: 768px) {
    .nav-menu {
        display: none;
    }
    
    .mobile-toggle {
        display: block;
    }
    
    .nav-menu.active {
        display: flex;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        padding: 20px;
        gap: 15px;
    }
}
</style>