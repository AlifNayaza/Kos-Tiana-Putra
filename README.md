# Kos Tiana Putra

Sistem informasi dan manajemen operasional terpadu untuk kos-kosan berbasis web. Aplikasi ini mengintegrasikan interaksi harian antara tiga pihak dalam satu platform: **Pemilik/Pengelola Kos**, **Petugas Layanan & Warkop**, serta **Penghuni Kos**.

Proyek ini dibangun menggunakan **Laravel**, **Tailwind CSS v4**, **Alpine.js**, dan model kecerdasan buatan berbasis **Python** untuk membantu perencanaan stok kebutuhan kamar.

---

## Gambaran Tiga Peran Pengguna

Aplikasi ini dirancang sesuai kebutuhan kerja nyata tiap peran:

### 1. Panel Pengelola Kos (Desktop-First)
Dirancang untuk pemilik atau pengelola kos agar dapat memantau dan mengambil keputusan operasional dengan cepat:
- **Ringkasan Keuangan & Okupansi:** Melihat pemasukan sewa kamar, omzet penjualan warkop/galon, serta status kamar (terisi, kosong, atau perbaikan).
- **Manajemen Kamar & Penghuni:** Mendata fasilitas kamar, tarif bulanan, dan profil penyewa beserta kontak daruratnya.
- **Pengelolaan Tagihan Sewa:** Menerbitkan tagihan bulanan otomatis, memantau jatuh tempo, memeriksa bukti transfer, dan menandai status lunas.
- **Rekomendasi Belanja & Prediksi Stok:** Memperkirakan kebutuhan galon air dan bahan warkop untuk 7 hari ke depan dengan model Machine Learning, dilengkapi daftar belanja yang siap dibagikan ke WhatsApp agen/petugas.

### 2. Panel Petugas Layanan (Mobile Work-Tool)
Dirancang khusus untuk ponsel pintar (*smartphone*) agar mudah dioperasikan satu tangan saat petugas berkeliling:
- **Antrean Tugas:** Menerima pesanan galon dan menu warkop secara langsung tanpa perlu memuat ulang halaman (*auto-refresh*).
- **Pengelolaan Stok Fisik:** Mencatat barang masuk, barang rusak, serta mencocokkan stok fisik (*stock opname*) dengan perhitungan selisih otomatis.
- **Riwayat Pengantaran:** Rekapitulasi pesanan selesai yang dikelompokkan per tanggal beserta pemisahan pembayaran tunai dan QRIS.

### 3. Aplikasi Penghuni Kos (Mobile-First)
Didesain ramah ponsel untuk kemudahan aktivitas harian anak kos:
- **Pesan Galon Cepat:** Tombol pesan ulang galon air dalam 1 ketukan agar kamar tidak kehabisan air minum.
- **Pesan Menu Warkop:** Memilih makanan, camilan, dan minuman dengan opsi catatan khusus (misalnya tingkat kepedasan atau es).
- **Pelacakan Status Pesanan:** Memantau pesanan dari tahap menunggu, diproses, sedang diantar ke kamar, hingga selesai.
- **Tagihan Sewa & Pembayaran:** Memeriksa rincian biaya sewa bulanan dan mengunggah foto bukti pembayaran secara praktis.

---

## Teknologi yang Digunakan

- **Backend:** Laravel (PHP 8.2+)
- **Frontend & Desain:** Tailwind CSS v4 dengan sistem token warna semantik
- **Reaktivitas Antarmuka:** Alpine.js (ringan, interaktif, tanpa instalasi framework JavaScript yang berat)
- **Database:** MySQL dengan transaksi data yang aman
- **Machine Learning (Prediksi Stok):** Python (Linear Regression dan Moving Average 7 Hari)

---

## Panduan Menjalankan Proyek di Komputer Lokal

Ikuti langkah-langkah berikut untuk menjalankan aplikasi di komputer Anda:

### 1. Clone Repository
```bash
git clone https://github.com/AlifNayaza/Kos-Tiana-Putra.git
cd Kos-Tiana-Putra
```

### 2. Pasang Dependensi
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
Salin berkas contoh konfigurasi:
```bash
cp .env.example .env
php artisan key:generate
```
Sesuaikan pengaturan database di berkas `.env` (misalnya nama database `kos_tiana_putra`, *username*, dan *password* MySQL Anda).

### 4. Siapkan Database dan Data Awal
Jalankan migrasi database beserta data awal percontohan:
```bash
php artisan migrate --seed
```
*Catatan: Seeder akan otomatis membuat kamar percontohan, akun pengguna, riwayat transaksi 30 hari terakhir, serta melatih model prediksi stok.*

### 5. Jalankan Server Lokal
Jalankan *asset bundler* di terminal pertama:
```bash
npm run dev
```

Buka terminal kedua dan jalankan server Laravel:
```bash
php artisan serve
```

Buka peramban (*browser*) Anda di alamat: **`http://localhost:8000`**

---

## Akun Demo Cepat (Quick Demo Switcher)

Di halaman login (`/login`), tersedia tombol **Quick Demo** untuk langsung masuk ke masing-masing peran tanpa perlu mengetik sandi:

1. **Pengelola Kos (Pemilik):**
   - Email: `pengelola@kostiana.com`
   - Sandi: `password`

2. **Petugas Lapangan (Warkop & Layanan):**
   - Email: `petugas@kostiana.com`
   - Sandi: `password`

3. **Penghuni Kos (Kamar A-04):**
   - Email: `dimas@kostiana.com`
   - Sandi: `password`

---

## Perintah Bermanfaat (Artisan CLI)

- **Menjalankan Prediksi Stok Machine Learning:**
  ```bash
  php artisan stok:prediksi
  ```
- **Membuat Tambahan Data Pelatihan Transaksi (Dummy):**
  ```bash
  php artisan data:training 50
  ```
- **Menjalankan Seluruh Pengujian Otomatis (Testing):**
  ```bash
  php vendor/bin/phpunit
  ```
- **Membersihkan Cache Tampilan View:**
  ```bash
  php artisan view:clear
  ```

---

## Struktur Direktori Utama

```text
kos-tiana-putra/
├── app/
│   ├── Http/Controllers/
│   │   ├── Pengelola/      # Fitur dashboard, kamar, tagihan, dan laporan
│   │   ├── Petugas/        # Fitur antrean tugas dan stok petugas
│   │   └── Penghuni/       # Fitur katalog, pesanan, dan tagihan penghuni
│   ├── Models/             # Model data (Order, Room, RentBill, StockPrediction, dll.)
│   └── Console/Commands/   # Perintah artisan (prediksi stok dan training data)
├── database/
│   ├── migrations/         # Struktur tabel database
│   └── seeders/            # Data percontohan realistis
├── python-model/           # Skrip Python prediksi kebutuhan stok
├── resources/
│   ├── css/app.css         # Tema dan token warna Tailwind v4
│   └── views/              # Template tampilan Blade (Pengelola, Petugas, Penghuni)
└── tests/
    └── Feature/            # Pengujian otomatis fitur dan integrasi
```
