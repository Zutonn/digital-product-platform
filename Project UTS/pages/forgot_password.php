<?php
$message = '';
$error = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_user'])) {
        // Step 1: Verify username & email
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user_data['id'];
            $step = 2;
            $message = "Verifikasi berhasil! Silakan masukkan password baru.";
        } else {
            $error = "Username dan email tidak cocok!";
        }
        $stmt->close();
    }
    
    if (isset($_POST['reset_password'])) {
        // Step 2: Update password
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($new_password) < 6) {
            $error = "Password minimal 6 karakter!";
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = "Password dan konfirmasi tidak sama!";
            $step = 2;
        } else {
            $user_id = $_SESSION['reset_user_id'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            
            if ($stmt->execute()) {
                unset($_SESSION['reset_user_id']);
                $message = "Password berhasil direset! Silakan login dengan password baru.";
                $step = 3;
            } else {
                $error = "Gagal reset password!";
                $step = 2;
            }
            $stmt->close();
        }
    }
}

if (isset($_SESSION['reset_user_id']) && !isset($_POST['verify_user'])) {
    $step = 2;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Zapedia</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #667eea;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .auth-header h1 img {
            height: 40px;
        }
        
        .auth-header p {
            color: #6c757d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-footer {
            margin-top: 20px;
            text-align: center;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        body.dark-mode .auth-card {
            background: #1e293b;
        }
        
        body.dark-mode .auth-header h1 {
            color: #a5b4fc;
        }
        
        body.dark-mode .auth-header p {
            color: #cbd5e1;
        }
        
        body.dark-mode .form-group label {
            color: #f1f5f9;
        }
        
        body.dark-mode .form-group input {
            background: #0f172a;
            color: #f1f5f9;
            border-color: #334155;
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <h1>
                <img src="assets/Logo.png" alt="Zapedia">
                Zapedia
            </h1>
            <p>
                <?php if ($step === 1): ?>
                    Lupa Password? Reset Sekarang!
                <?php elseif ($step === 2): ?>
                    Buat Password Baru
                <?php else: ?>
                    Password Berhasil Direset
                <?php endif; ?>
            </p>
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
        
        <?php if ($step === 1): ?>
            <!-- Step 1: Verify Username & Email -->
            <form method="post">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Masukkan username Anda" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="Masukkan email terdaftar" 
                           required>
                </div>
                
                <button type="submit" name="verify_user" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-arrow-right"></i> Lanjutkan
                </button>
                
                <div class="form-footer">
                    <p>Ingat password? <a href="index.php?page=login">Login di sini</a></p>
                    <p><a href="index.php">Kembali ke Home</a></p>
                </div>
            </form>
            
        <?php elseif ($step === 2): ?>
            <!-- Step 2: Input New Password -->
            <form method="post">
                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-lock"></i> Password Baru
                    </label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           placeholder="Minimal 6 karakter" 
                           minlength="6"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Konfirmasi Password
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           placeholder="Ulangi password baru" 
                           minlength="6"
                           required>
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary btn-lg btn-block">
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
            
        <?php else: ?>
            <!-- Step 3: Success -->
            <div style="text-align: center;">
                <i class="fas fa-check-circle" style="font-size: 64px; color: #10b981; margin-bottom: 20px;"></i>
                <p style="font-size: 16px; color: #666; margin-bottom: 30px;">
                    Password Anda berhasil direset! Silakan login dengan password baru.
                </p>
                <a href="index.php?page=login" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Login Sekarang
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

</body>
</html>