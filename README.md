# Kos Tiana Putra — Sistem Manajemen Operasional & Layanan Terpadu Kos-Kosan

> Aplikasi web terpadu berbasis **Laravel 13**, **Tailwind CSS v4**, dan **Alpine.js** yang mengintegrasikan tiga peran operasional kos: **Pengelola** (desktop-first), **Petugas Layanan** (mobile work-tool), dan **Penghuni Kos** (mobile-first web app).

---

## 🏢 Ikhtisar Sistem & Arsitektur

**Kos Tiana Putra** menghubungkan seluruh ekosistem operasional kos-kosan dalam satu aplikasi terpusat:
- **Pengelola (Desktop-First):** Dashboard eksekutif, rekapitulasi keuangan, manajemen sewa & kamar, inventaris stok, dan prediksi kebutuhan stok cerdas berbasis machine learning.
- **Petugas Lapangan (Mobile Work-Tool):** Antrean tugas harian (pengantaran galon & pesanan warkop), pencatatan audit stok (Barang Masuk, Rusak, Hitung Fisik), dan rekapitulasi setoran tunai & QRIS.
- **Penghuni Kos (Mobile-First):** Pemesanan galon instan 1-klik, pemesanan menu warkop, pelacakan status pesanan real-time, pengecekan tagihan sewa bulanan, dan unggah bukti transfer.

---

## 🛠️ Tech Stack & Arsitektur

- **Backend Framework:** Laravel 13 (PHP 8.3+)
- **Frontend & Styling:** Tailwind CSS v4 (`@theme` token tersentralisasi di `resources/css/app.css`)
- **Interaktivitas UI:** Alpine.js v3 (reaktif, tanpa bloatware frontend library)
- **Database:** MySQL / SQLite dengan integritas transaksi ACID
- **Machine Learning:** Python (Linear Regression / Time Series Moving Average untuk prediksi restock otomatis)
- **Komponen Blade:** `status-badge`, `price`, `order-stepper`, `qty-stepper`, `bottom-sheet`, `nota`, `auth/kos-scene`

---

## 🚀 Panduan Instalasi Cepat (Quick Start)

Untuk menjalankan proyek ini di lingkungan lokal:

```bash
# 1. Clone repository
git clone https://github.com/username/kos-tiana-putra.git
cd kos-tiana-putra

# 2. Install dependensi PHP & Node.js
composer install
npm install

# 3. Siapkan file konfigurasi environment
cp .env.example .env
php artisan key:generate

# 4. Setup Database (Pastikan database 'kos_tiana_putra' sudah dibuat di MySQL)
php artisan migrate --seed

# 5. Jalankan server lokal & asset bundler
npm run dev
# Buka terminal kedua:
php artisan serve
```

Aplikasi dapat diakses di browser melalui: **`http://localhost:8000`**

---

## 🔑 Akun Demo 1-Click (Quick Demo Switcher)

Untuk mempermudah eksplorasi fitur dan pengujian, halaman login (`/login`) dilengkapi tombol **Quick Demo**:

1. **Pengelola Kos (Pemilik):**
   - Email: `pengelola@kostiana.com` | Sandi: `password`
   - Fitur: Executive Dashboard, inventaris kamar, CRUD penghuni, verifikasi sewa, prediksi stok AI.
2. **Petugas Lapangan (Warkop):**
   - Email: `petugas@kostiana.com` | Sandi: `password`
   - Fitur: Antrean tugas pengantaran galon & warkop, audit stok fisik (Masuk/Rusak/Hitung), riwayat setoran tunai & QRIS.
3. **Penghuni Kos (Kamar A-04):**
   - Email: `dimas@kostiana.com` | Sandi: `password`
   - Fitur: Tagihan sewa & upload bukti transfer, pemesanan galon & menu warkop, pelacakan pesanan real-time.

---

## 🧪 Pengujian Otomatis (Testing)

Semua pengujian fitur dan integrasi sistem dijalankan menggunakan PHPUnit:

```bash
# Menjalankan seluruh test suite
php vendor/bin/phpunit

# Menjalankan pengujian spesifik dengan testdox
php vendor/bin/phpunit tests/Feature/LoginTest.php --testdox
php vendor/bin/phpunit tests/Feature/PetugasHistoryTest.php --testdox
php vendor/bin/phpunit tests/Feature/SystemIntegrationTest.php --testdox
```

---

## 🤖 Perintah Tambahan (Console & AI)

- **Prediksi Kebutuhan Stok (AI/ML):**
  ```bash
  php artisan stok:prediksi
  ```
- **Generate Data Latihan Stok:**
  ```bash
  php artisan stok:generate-data
  ```
- **Membersihkan Cache Tampilan View:**
  ```bash
  php artisan view:clear
  ```

---

## 📁 Struktur Direktori Penting

```text
kos-tiana-putra/
├── app/
│   ├── Http/Controllers/
│   │   ├── Pengelola/      # Controller operasional admin pengelola
│   │   ├── Petugas/        # Controller antrean & inventaris petugas
│   │   └── Penghuni/       # Controller tagihan & katalog menu penghuni
│   ├── Models/             # Eloquent Models (Order, Room, RentBill, dll.)
│   └── Console/Commands/   # Artisan commands (Prediksi ML)
├── database/
│   ├── migrations/         # Skema tabel database berurutan
│   └── seeders/            # Data awal realistis untuk demo
├── python-model/           # Skrip Python pemodelan prediksi stok
├── resources/
│   ├── css/app.css         # Token desain @theme Tailwind v4
│   └── views/
│       ├── auth/           # Halaman login & lorong kos ilustratif
│       ├── components/     # Blade components reusable
│       ├── layouts/        # Layout mobile & desktop
│       ├── pengelola/      # Tampilan panel pengelola
│       ├── petugas/        # Tampilan panel petugas
│       └── penghuni/       # Tampilan panel penghuni
└── tests/
    └── Feature/            # Integration & feature automated tests
```
