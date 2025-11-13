<?php
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username_or_email) || empty($password)) {
        $error = "Username/Email dan password harus diisi!";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Username/Email tidak ditemukan!";
        } else {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_id'] = $user['id'];
                
                if (in_array($user['role'], ['admin_utama', 'admin_product', 'admin_user', 'admin_supplier'])) {
                    header("Location: index.php?page=admin_dashboard");
                } else {
                    header("Location: index.php?page=home");
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Zapedia</title>
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
        
        body.dark-mode .form-group input::placeholder {
            color: #64748b;
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
            <p>Masuk ke akun Anda</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user"></i> Username atau Email
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="Masukkan username atau email" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i> Password
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Masukkan password" 
                       required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg btn-block">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <div class="form-footer">
            <p><a href="index.php?page=forgot_password">Lupa Password?</a></p>
            <p>Belum punya akun? <a href="index.php?page=register">Daftar Sekarang</a></p>
            <p><a href="index.php">Kembali ke Home</a></p>
        </div>
        </form>
    </div>
</div>

</body>
</html>