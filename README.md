# 🛒 HappyPOS — Point of Sale Laravel + HPY

> Sistem kasir modern berbasis Laravel 10, database MariaDB, UI terinspirasi Google,
> dilengkapi sinkronisasi penuh ke **HPY**.

---

## 📋 DAFTAR ISI

1. [Persyaratan Sistem](#persyaratan-sistem)
2. [Langkah Instalasi Lengkap](#langkah-instalasi-lengkap)
3. [Konfigurasi Database](#konfigurasi-database)
4. [Menjalankan Aplikasi](#menjalankan-aplikasi)
5. [Login Pertama Kali](#login-pertama-kali)
6. [Konfigurasi ERPNext](#konfigurasi-erpnext)
7. [Fitur-Fitur Utama](#fitur-fitur-utama)
8. [Struktur Project](#struktur-project)
9. [Troubleshooting](#troubleshooting)

---

## ✅ PERSYARATAN SISTEM

| Komponen      | Versi Minimum | Cek Perintah          |
|---------------|---------------|-----------------------|
| PHP           | 8.1+          | `php -v`              |
| Composer      | 2.x           | `composer --version`  |
| MariaDB/MySQL | 10.3+ / 8.0+  | `mysql --version`     |
| Node.js       | 16+           | `node -v`             |
| NPM           | 8+            | `npm -v`              |

### Ekstensi PHP yang dibutuhkan:
```
php-mbstring  php-xml  php-curl  php-json
php-zip       php-pdo  php-mysql php-fileinfo
```

---

## 🚀 LANGKAH INSTALASI LENGKAP

### LANGKAH 1 — Clone / Ekstrak Project

```bash
# Jika dari ZIP, ekstrak ke folder Anda, lalu masuk:
cd laravel-pos

# Atau jika dari Git:
git clone <repo-url> laravel-pos
cd laravel-pos
```

---

### LANGKAH 2 — Install Dependensi PHP

```bash
composer install
```

> ⏳ Proses ini membutuhkan koneksi internet dan mungkin memakan waktu 2–5 menit.

---

### LANGKAH 3 — Salin File Konfigurasi

```bash
cp .env.example .env
```

---

### LANGKAH 4 — Generate Application Key

```bash
php artisan key:generate
```

Output yang diharapkan:
```
Application key set successfully.
```

---

### LANGKAH 5 — Buat Database di MariaDB

Buka terminal MariaDB/MySQL Anda:

```bash
# Login ke MariaDB
mysql -u root -p

# Di dalam MySQL prompt:
CREATE DATABASE larapos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'larapos_user'@'localhost' IDENTIFIED BY 'larapos_password123';
GRANT ALL PRIVILEGES ON larapos.* TO 'larapos_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### LANGKAH 6 — Konfigurasi Database di .env

Buka file `.env` dengan text editor, ubah bagian ini:

```env
APP_NAME="LaraPos"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larapos
DB_USERNAME=larapos_user
DB_PASSWORD=larapos_password123
```

> 💡 Jika menggunakan XAMPP/WAMP, `DB_USERNAME=root` dan `DB_PASSWORD=` (kosong)

---

### LANGKAH 7 — Jalankan Migrasi Database

```bash
php artisan migrate
```

Output yang diharapkan:
```
  INFO  Running migrations.

  2024_01_01_000001_create_users_table ..................... 12ms DONE
  2024_01_01_000002_create_categories_table ................ 8ms DONE
  2024_01_01_000003_create_products_table .................. 9ms DONE
  2024_01_01_000004_create_customers_table ................. 7ms DONE
  2024_01_01_000005_create_transactions_table .............. 11ms DONE
  2024_01_01_000006_create_supporting_tables ............... 10ms DONE
```

---

### LANGKAH 8 — Isi Data Demo (Opsional tapi Direkomendasikan)

```bash
php artisan db:seed
```

Seeder akan membuat:
- ✅ **3 akun user** (admin, kasir, manager)
- ✅ **6 kategori** produk
- ✅ **26 produk** demo (minuman, makanan, snack, dll)
- ✅ **8 customer** demo
- ✅ **~300 transaksi** demo selama 30 hari terakhir
- ✅ **Pengaturan** default toko

---

### LANGKAH 9 — Jalankan Aplikasi

```bash
php artisan serve
```

Buka browser dan akses: **http://localhost:8000**

---

## 🔐 LOGIN PERTAMA KALI

Setelah seeder berjalan, gunakan akun berikut:

| Role    | Email                   | Password  |
|---------|-------------------------|-----------|
| Admin   | `admin@larapos.com`     | `password` |
| Kasir   | `kasir@larapos.com`     | `password` |
| Manager | `manager@larapos.com`   | `password` |

> ⚠️ **PENTING:** Ganti password segera setelah login pertama di production!

---

## 🔗 KONFIGURASI ERPNEXT

### Di Sisi ERPNext (lakukan dulu):

**1. Buat API Key:**
```
ERPNext → Settings → My Settings → API Access
→ Klik "Generate Keys"
→ Salin API Key dan API Secret
```

**2. Buat POS Profile:**
```
ERPNext → Point of Sale → POS Profile → New
→ Isi nama, company, warehouse, payment methods
→ Simpan
```

**3. Buat Customer "Walk-in Customer"** (jika belum ada):
```
ERPNext → Selling → Customer → New
→ Customer Name: "Walk-in Customer"
→ Customer Type: Individual
→ Simpan
```

**4. Pastikan item ada di ERPNext** dengan field `is_sales_item = Yes`

---

### Di Sisi LaraPos:

Buka menu **Sync ERPNext** → isi form konfigurasi:

```
ERPNext URL      : http://your-erpnext-domain.com
API Key          : (dari langkah di atas)
API Secret       : (dari langkah di atas)
Company          : Nama perusahaan Anda di ERPNext
POS Profile      : Nama POS Profile yang sudah dibuat
```

Klik **"Test Koneksi"** → harus muncul status **"Terhubung ✅"**

Klik **"Simpan"**, lalu:
- **"Pull Produk"** → import semua item dari ERPNext
- **"Pull Customer"** → import semua customer dari ERPNext
- **"Sync Pending"** → kirim transaksi yang belum tersync

---

## 🎯 FITUR-FITUR UTAMA

### 💰 Kasir (POS)
- ✅ Grid produk dengan pencarian real-time
- ✅ Filter per kategori
- ✅ Support barcode scanner (tekan **F3** untuk fokus ke input barcode)
- ✅ Manajemen keranjang (tambah/kurang/hapus item)
- ✅ Diskon nominal (Rp) dan persentase (%)
- ✅ Kalkulasi pajak per produk
- ✅ 4 metode pembayaran: Tunai / Kartu / Transfer / QRIS
- ✅ Hitung kembalian otomatis
- ✅ Tombol nominal cepat
- ✅ Pilih / tambah customer
- ✅ Struk digital dan cetak struk

### 📊 Dashboard
- ✅ Statistik penjualan hari ini
- ✅ Grafik penjualan 7 hari (Chart.js)
- ✅ 5 produk terlaris (30 hari)
- ✅ Transaksi terbaru
- ✅ Alert stok menipis

### 📦 Manajemen Produk
- ✅ CRUD produk lengkap
- ✅ Manajemen stok dengan alert minimum
- ✅ Kategori dengan warna dan icon
- ✅ Barcode support
- ✅ Pajak per produk

### 👥 Manajemen Customer
- ✅ CRUD customer
- ✅ Tracking total pembelian
- ✅ Push customer ke ERPNext

### 🧾 Riwayat Transaksi
- ✅ Filter tanggal, status, metode bayar
- ✅ Detail transaksi lengkap
- ✅ Cetak ulang struk
- ✅ Batalkan transaksi (restore stok)

### 🔄 Sinkronisasi ERPNext v13
- ✅ Test koneksi real-time
- ✅ Pull produk dari ERPNext
- ✅ Pull customer dari ERPNext
- ✅ Push transaksi sebagai **POS Invoice** (auto-submit)
- ✅ Push customer baru ke ERPNext
- ✅ Retry transaksi gagal
- ✅ Log sinkronisasi lengkap
- ✅ Badge notifikasi pending sync

---

## 🗂️ STRUKTUR PROJECT

```
laravel-pos/
├── app/
│   ├── Http/Controllers/
│   │   ├── DashboardController.php    ← Dashboard & statistik
│   │   ├── PosController.php          ← Kasir & checkout
│   │   ├── ProductController.php      ← CRUD produk
│   │   ├── CustomerController.php     ← CRUD customer
│   │   ├── TransactionController.php  ← Riwayat transaksi
│   │   └── ErpSyncController.php      ← Sinkronisasi ERPNext
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Customer.php
│   │   ├── Transaction.php
│   │   ├── TransactionItem.php
│   │   ├── ErpSyncLog.php
│   │   └── Setting.php
│   └── Services/
│       └── ErpNextService.php         ← Semua API call ke ERPNext
├── config/
│   └── erpnext.php                    ← Konfigurasi ERPNext
├── database/
│   ├── migrations/                    ← 6 file migrasi
│   ├── seeders/
│   │   └── DatabaseSeeder.php         ← Data demo lengkap
│   └── larapos_schema.sql             ← SQL dump manual (backup)
├── resources/views/
│   ├── layouts/app.blade.php          ← Layout utama (Google-style)
│   ├── auth/login.blade.php           ← Halaman login
│   ├── dashboard.blade.php            ← Dashboard
│   ├── pos/
│   │   ├── index.blade.php            ← 🌟 Halaman kasir utama
│   │   ├── receipt.blade.php          ← Struk digital
│   │   └── print-receipt.blade.php    ← Struk cetak thermal
│   ├── products/                      ← Manajemen produk
│   ├── customers/                     ← Manajemen customer
│   ├── transactions/                  ← Riwayat transaksi
│   └── sync/index.blade.php           ← 🔄 Halaman sync ERPNext
└── routes/web.php                     ← Semua route
```

---

## 🛠️ TROUBLESHOOTING

### ❌ Error: `SQLSTATE[HY000] [2002] Connection refused`
**Solusi:** Pastikan MariaDB/MySQL berjalan.
```bash
# Linux/Mac:
sudo systemctl start mariadb
# atau:
sudo service mysql start

# XAMPP: Start MySQL dari XAMPP Control Panel
```

### ❌ Error: `Class 'App\Models\Setting' not found` saat load config
**Solusi:** Jalankan migrasi dulu sebelum memuat config ERPNext.
```bash
php artisan migrate
php artisan config:clear
php artisan cache:clear
```

### ❌ Error: `storage/logs` tidak bisa ditulis
**Solusi:**
```bash
chmod -R 775 storage bootstrap/cache
```

### ❌ Error: `php artisan key:generate` tidak bisa
**Solusi:** Pastikan file `.env` sudah ada:
```bash
cp .env.example .env
php artisan key:generate
```

### ❌ Gambar/CSS tidak muncul
**Solusi:**
```bash
php artisan storage:link
```

### ❌ ERPNext: `401 Unauthorized`
- Pastikan API Key dan Secret benar
- Cek apakah user ERPNext memiliki role yang cukup (System Manager atau Sales User)
- Coba regenerate API keys di ERPNext

### ❌ ERPNext: `POS Profile not found`
- Nama POS Profile harus **sama persis** (case-sensitive) dengan yang ada di ERPNext
- Pastikan POS Profile sudah di-**enable** di ERPNext

### ❌ ERPNext: `Customer does not exist`
- Buat customer "Walk-in Customer" di ERPNext
- Atau pull customer dulu: **Sync ERPNext → Pull Customer**

---

## ⚙️ PERINTAH BERGUNA

```bash
# Reset database dan isi ulang data demo
php artisan migrate:fresh --seed

# Bersihkan cache
php artisan cache:clear && php artisan config:clear && php artisan route:clear

# Lihat semua route
php artisan route:list

# Jalankan di port berbeda
php artisan serve --port=9000

# Optimasi untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🌐 PRODUCTION DEPLOYMENT

Jika ingin deploy ke server (Nginx + PHP-FPM):

```bash
# 1. Set environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.yourdomain.com

# 2. Optimasi
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Permission
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

**Nginx config:**
```nginx
server {
    listen 80;
    server_name pos.yourdomain.com;
    root /var/www/laravel-pos/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 📞 AKUN DEFAULT SETELAH SEEDER

```
┌─────────────────────────────────────────────┐
│  👤 ADMIN                                   │
│  Email    : admin@larapos.com               │
│  Password : password                        │
│  PIN      : 123456                          │
├─────────────────────────────────────────────┤
│  🧑‍💼 KASIR                                  │
│  Email    : kasir@larapos.com               │
│  Password : password                        │
│  PIN      : 654321                          │
├─────────────────────────────────────────────┤
│  👔 MANAGER                                 │
│  Email    : manager@larapos.com             │
│  Password : password                        │
│  PIN      : 111222                          │
└─────────────────────────────────────────────┘
```

---

*LaraPos — Dibuat dengan ❤️ menggunakan Laravel 10 + ERPNext v13*
