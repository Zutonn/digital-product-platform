<?php
// File untuk cek akses
function cekLogin() {
    if (!isset($_SESSION['username'])) {
        header("Location: index.php?page=login");
        exit();
    }
}

function cekAkses($allowed_roles = []) {
    if (!isset($_SESSION['username'])) {
        header("Location: index.php?page=login");
        exit();
    }
    
    $role = $_SESSION['role'] ?? 'guest';
    
    if (!empty($allowed_roles) && !in_array($role, $allowed_roles)) {
        echo "<div class='container' style='margin-top: 50px;'>
                <div class='alert alert-danger'>
                    <h2>🚫 Akses Ditolak</h2>
                    <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                    <a href='index.php' class='btn btn-primary'>Kembali ke Home</a>
                </div>
              </div>";
        exit();
    }
}
?>