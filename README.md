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

- **Backend:** Laravel (PHP 8.3+)
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

Opsional: isi `PYTHON_BIN` dengan path Python yang sudah terpasang `pandas`, `scikit-learn`, dan `pymysql`
(`pip install -r python-model/requirements.txt`). Bila dikosongkan, aplikasi mencari Python sendiri dan menyimpan hasilnya.

Informasi rekening/WhatsApp pada halaman tagihan dan tanggal jatuh tempo diatur lewat variabel `KOS_*` di `.env`.

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

## Akun Demo Cepat (khusus development)

Di halaman login (`/login`), tombol **Quick Demo** langsung memasukkan Anda ke masing-masing peran tanpa mengetik sandi.
Rute `/demo-login/{role}` **hanya aktif saat `APP_ENV=local`** (atau `KOS_DEMO_LOGIN=true`); selain itu menjawab 404 dan
tombolnya disembunyikan. Jangan mengaktifkannya di server production.

Akun hasil seeder (sandi semuanya `password`):

| Peran | Email |
|---|---|
| Pengelola Kos (Pemilik) | `pengelola@kostiana.com` |
| Petugas Lapangan (Warkop & Layanan) | `petugas@kostiana.com` |
| Penghuni Kos (Kamar A-04) | `penghuni@kostiana.com` |

Penghuni baru yang didaftarkan pengelola mendapat **kata sandi sementara acak** (tampil sekali di halaman Data Penghuni)
dan wajib menggantinya saat masuk pertama. Pengelola juga dapat mereset sandi penghuni dari halaman yang sama.

---

## Menjalankan Terjadwal (Scheduler)

Fitur otomatis berikut berjalan lewat Laravel Scheduler (`routes/console.php`):

| Jadwal | Perintah | Fungsi |
|---|---|---|
| Harian 00:05 | `stok:prediksi` | Hitung ulang prediksi kebutuhan stok |
| Tanggal 1, 06:00 | `tagihan:terbitkan` | Terbitkan tagihan sewa bulan berjalan (aman diulang) |
| Harian 06:30 | `tagihan:tandai-menunggak` | Tandai tagihan lewat jatuh tempo sebagai menunggak |

Di server, aktifkan cron satu baris: `* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1`.
Saat development bisa dijalankan manual dengan `php artisan schedule:work`.

Tombol **Perbarui Hitungan Kebutuhan** di halaman Prediksi Stok menjalankan Python setelah respons dikirim, jadi tidak
perlu worker antrean tersendiri.

---

## Memperbarui Database yang Sudah Ada

Jika database development sudah terisi sebelum pembaruan ini, cukup jalankan migrasi tambahan (tidak menghapus data):

```bash
php artisan migrate
```

Migrasi menambahkan kolom `deleted_at` pada `catalog_items` (menu yang dihapus kini disembunyikan, riwayatnya tetap utuh)
dan `must_change_password` pada `users`. Bukti transfer baru disimpan privat di `storage/app/private/payment_proofs`;
berkas lama di disk publik tetap bisa dibuka lewat halaman tagihan.

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
- **Menerbitkan tagihan / menandai menunggak secara manual:**
  ```bash
  php artisan tagihan:terbitkan --month=11 --year=2026
  php artisan tagihan:tandai-menunggak
  ```
- **Menjalankan Seluruh Pengujian Otomatis (Testing):** memakai SQLite in-memory, tidak menyentuh database development.
  ```bash
  php artisan test
  python -m unittest discover -s python-model -v   # pengujian model prediksi
  ```
- **Membersihkan Cache Tampilan View:**
  ```bash
  php artisan view:clear
  ```

---

## Performa & Penghematan Sumber Daya

Hal yang sudah dioptimalkan di kode:

- **Aset ringan:** JavaScript utama 56 KB (19,7 KB gzip). Chart.js (62 KB gzip) dipisah dan hanya dimuat di dashboard, laporan, dan prediksi. Font dihosting sendiri (WOFF2, hanya bobot yang dipakai, `resources/fonts/`), tanpa permintaan ke Google.
- **HTML dipadatkan** 25-45% oleh middleware `MinifyHtml` (matikan dengan `KOS_MINIFY_HTML=false`).
- **Query efisien:** statistik dashboard/daftar memakai satu query agregat (bukan banyak `count`), tanpa N+1, dan indeks database untuk antrean, tagihan, notifikasi, dan laporan (`2026_11_09_000003_add_performance_indexes`). Filter tanggal memakai rentang waktu agar indeks terpakai.
- **Polling hemat:** antrean petugas dan pesanan penghuni hanya menanyakan "tanda perubahan" (JSON beberapa byte); HTML penuh diunduh hanya bila ada perubahan, dan polling berhenti saat tab tersembunyi.
- **Sesi/cache/antrean ringan:** default `file` dan `sync` (bukan tabel database), menghemat sekitar 20% query per halaman.

Saat memasang di server produksi:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize          # cache config, route, event, dan view
```

- Aktifkan kompresi dan cache aset di web server. `public/.htaccess` sudah memuat aturan Apache (`mod_deflate`, `mod_headers`). Untuk Nginx: `gzip on; gzip_types text/css text/javascript application/json image/svg+xml;` dan `location /build/ { add_header Cache-Control "public, max-age=31536000, immutable"; }`.
- Aktifkan OPcache PHP (`opcache.enable=1`, `opcache.validate_timestamps=0` di produksi) dan set `APP_DEBUG=false`.
- Setelah mengubah kode di produksi jalankan `php artisan optimize:clear && php artisan optimize`.

---

## Kemudahan Penggunaan per Peran

- **Semua peran:** tombol kirim dikunci setelah diklik (tidak ada pesanan/pembayaran ganda; matikan dengan `data-no-lock` pada `<form>`), pesan kesalahan berbahasa Indonesia (`lang/id/validation.php`), dan toast yang bisa dipicu dari JavaScript lewat `window.kosToast(pesan, tipe)`.
- **Pengelola:** form di modal (kamar, menu, penghuni, tagihan massal) terbuka kembali dengan isian utuh dan pesan error di bawah kolom yang salah. Untuk form modal baru, beri atribut `data-restore`, `data-open-var`, input `_restore`, dan `<x-field-error>` seperti contoh di `resources/views/pengelola/kamar/index.blade.php`. Tagihan yang belum lunas punya tombol **Ingatkan WA** dengan pesan terisi (nominal, jatuh tempo, rekening).
- **Penghuni:** keranjang tersimpan di perangkat dan dipulihkan dengan harga/stok terbaru, status pesanan diperbarui otomatis tiap 20 detik, dan riwayat punya tombol **Pesan lagi**.
- **Petugas:** kartu antrean punya tombol Telepon/WhatsApp ke penghuni, tombol selesai menyebut nominal tunai yang harus diterima, dan pesanan baru memunculkan pemberitahuan (getar + judul tab).

---

## Panduan Desain & Aksesibilitas

Seluruh warna, ukuran teks, bayangan, dan komponen bersama didefinisikan di satu tempat: `resources/css/app.css`.

- **Warna:** semua pasangan teks/latar sudah diperiksa memenuhi WCAG AA (kontras >= 4,5:1). Gunakan `text-ink-faint` untuk teks pudar dan `text-ochre-ink` (bukan `text-ochre`) untuk teks beraksen emas.
- **Ukuran teks:** tidak ada teks di bawah 12px (`text-2xs`). Jangan memakai ukuran arbitrer seperti `text-[10px]`.
- **Tema per peran:** atribut `data-role` pada `<html>` mengganti `--color-brand*`. Pengelola dan penghuni memakai hijau teal; petugas memakai cokelat kopi agar langsung terasa "mode kerja".
- **Kerangka seragam:** pengelola memakai menu samping gelap (`.nav-dark`) dan pita judul otomatis di setiap halaman (isi `@section('page_title')`, `page_subtitle`, opsional `page_actions`; `no_page_band` untuk menonaktifkan). Penghuni dan petugas memakai bilah atas berwarna (`.app-bar`). Judul memakai font display Bricolage Grotesque (`font-display`), badge status berupa pil berwarna.
- **Komponen:** `.stat-card` (kartu angka berpenanda warna), `.action-tile` (ubin aksi cepat), `.bn-link` (navigasi bawah mobile), dan `.table-stack` (tabel yang menjadi daftar kartu di layar sempit; isi `data-label`/`data-primary`/`data-actions` pada `<td>`).
- **Aksesibilitas:** tautan "Lewati ke konten utama", fokus keyboard yang selalu terlihat, `aria-current` pada menu aktif, target sentuh minimal 44px, kolom isian 16px di ponsel (mencegah zoom otomatis iOS), dan menghormati `prefers-reduced-motion`. Pengguna boleh memperbesar halaman.

Setelah mengubah `app.css` atau kelas Tailwind di view, jalankan `npm run build` (atau biarkan `npm run dev` aktif).

---

## Struktur Direktori Utama

```text
kos-tiana-putra/
├── app/
│   ├── Http/Controllers/
│   │   ├── Pengelola/      # Fitur dashboard, kamar, tagihan, dan laporan
│   │   ├── Petugas/        # Fitur antrean tugas dan stok petugas
│   │   └── Penghuni/       # Fitur katalog, pesanan, dan tagihan penghuni
│   ├── Services/           # Logika bisnis bersama (OrderService, RentBillService, NotificationService)
│   ├── Jobs/               # RunStockPrediction (prediksi di latar belakang)
│   ├── Models/             # Model data (Order, Room, RentBill, StockPrediction, dll.)
│   └── Console/Commands/   # Perintah artisan (prediksi stok, tagihan, training data)
├── config/kos.php          # Identitas kos, rekening, metode bayar, flag demo, PYTHON_BIN
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
