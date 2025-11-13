# digital-product-platform
Mid Exam. Web-based system for selling digital products with full admin dashboard management.
# Digital Product Selling System – UTS Project

Website e-commerce sederhana untuk menjual **produk digital** seperti Premiuim Apps.  
Sistem ini juga dilengkapi **Admin Dashboard** untuk mengelola produk, transaksi, user, dan pengaturan website.

---

## 🚀 Fitur Utama

### 🛒 **Fitur User / Pengunjung**
- Melihat daftar produk digital
- Detail produk
- Keranjang & checkout
- Pesanan User
- Registrasi & login
- Forgot Password
- Riwayat pesanan (fitur Filter Status Pesanan)

### Admin Dashboard
Halaman ini menampilkan ringkasan sistem berupa:
- Total produk aktif
- Total pesanan
- Total pendapatan dari pesanan yang berstatus *completed*
- Daftar pesanan terbaru

Dashboard membantu admin memantau performa penjualan produk digital secara cepat.

---

## 🧰 Teknologi yang Digunakan

Sesuaikan sesuai project kamu, contoh:

### **Frontend**
- HTML, CSS, JavaScript  
- Bootstrap / TailwindCSS

### **Backend**
- PHP Native

### **Database**
- MySQL

### **Tools**
- XAMPP
- VSCode

---

## 📁 Struktur Folder (Contoh)

```bash
UTS/
├── admin/
│   ├── master_dashboard.php
│   ├── master_orders.php
│   ├── master_product.php
│   └── master_user.php
│
├── assets/
│   ├── darkmode.js
│   ├── Logo.png
│   ├── main.js
│   └── style.css
│
├── includes/
│   ├── footer.php
│   ├── header.php
│   └── navbar.php
│
├── pages/
│   ├── support/
│   │   ├── cara_pembelian.php
│   │   ├── faq.php
│   │   ├── kebijakan_privasi.php
│   │   └── syarat_ketentuan.php
│   │
│   ├── cart.php
│   ├── checkout.php
│   ├── data.php
│   ├── forgot_password.php
│   ├── home.php
│   ├── product_detail.php
│   ├── register.php
│   └── shop.php
│
├── uploads/
│   └── products/ (gambar produk akan ke kirim kesini)
│       ├── product_1762886684_6913840d4d5bb.png
│       ├── product_1762886707_6913843d4ce2b.png
│       ├── product_1762888827_691b387c78cd74.jpeg
│       ├── product_1762888839_691b38cf57de6.jpeg
│       ├── product_1762889020_691b43d50d276.png
│       └── product_1762977528_6914fe683d61f.png
│
└── user/
    ├── dashboard.php
    ├── orders.php
    ├── profile.php
    ├── auth_check.php
    ├── config.php
    └── logout.php
