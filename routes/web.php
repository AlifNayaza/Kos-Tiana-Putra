<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\Pengelola;
use App\Http\Controllers\Penghuni;
use App\Http\Controllers\Petugas;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect root to login or appropriate role dashboard
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    return match (Auth::user()->role) {
        'pengelola' => redirect()->route('pengelola.dashboard'),
        'petugas'   => redirect()->route('petugas.antrean'),
        'penghuni'  => redirect()->route('penghuni.beranda'),
        default     => redirect()->route('login'),
    };
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Akun demo untuk development. Controller menjawab 404 kecuali config('kos.demo_login') aktif
// (default: hanya saat APP_ENV=local).
Route::get('/demo-login/{role}', [AuthController::class, 'quickLogin'])->name('demo.login');

// Ganti kata sandi (semua peran) & bukti pembayaran (pengelola / penghuni pemilik tagihan)
Route::middleware('auth')->group(function () {
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profil.password');
    Route::get('/tagihan/{bill}/bukti', [PaymentProofController::class, 'show'])->name('tagihan.proof');
});

// =========================================================================
// MODUL PENGELOLA KOS (ADMIN DASHBOARD)
// =========================================================================
Route::middleware(['auth', 'role:pengelola'])->prefix('pengelola')->name('pengelola.')->group(function () {
    // Executive Dashboard (FR-11)
    Route::get('/dashboard', [Pengelola\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');

    // CRUD Kamar (FR-03)
    Route::get('/kamar', [Pengelola\RoomTenantController::class, 'roomsIndex'])->name('kamar.index');
    Route::post('/kamar', [Pengelola\RoomTenantController::class, 'storeRoom'])->name('kamar.store');
    Route::put('/kamar/{room}', [Pengelola\RoomTenantController::class, 'updateRoom'])->name('kamar.update');
    Route::delete('/kamar/{room}', [Pengelola\RoomTenantController::class, 'destroyRoom'])->name('kamar.destroy');

    // CRUD Penghuni Kos (FR-02)
    Route::get('/penghuni', [Pengelola\RoomTenantController::class, 'tenantsIndex'])->name('penghuni.index');
    Route::post('/penghuni', [Pengelola\RoomTenantController::class, 'storeTenant'])->name('penghuni.store');
    Route::put('/penghuni/{tenant}', [Pengelola\RoomTenantController::class, 'updateTenant'])->name('penghuni.update');
    Route::post('/penghuni/{tenant}/reset-password', [Pengelola\RoomTenantController::class, 'resetTenantPassword'])->name('penghuni.reset_password');
    Route::post('/penghuni/{tenant}/checkout', [Pengelola\RoomTenantController::class, 'checkoutTenant'])->name('penghuni.checkout');

    // Manajemen Tagihan Sewa & Konfirmasi Pembayaran (FR-04 & FR-05)
    Route::get('/tagihan', [Pengelola\RentManagementController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/generate', [Pengelola\RentManagementController::class, 'generateMonthlyBills'])->name('tagihan.generate');
    Route::post('/tagihan/{bill}/confirm', [Pengelola\RentManagementController::class, 'confirmPayment'])->name('tagihan.confirm');
    Route::post('/tagihan/{bill}/overdue', [Pengelola\RentManagementController::class, 'markOverdue'])->name('tagihan.overdue');

    // CRUD Katalog Menu & Galon (FR-06)
    Route::get('/katalog', [Pengelola\CatalogManagementController::class, 'index'])->name('katalog.index');
    Route::post('/katalog', [Pengelola\CatalogManagementController::class, 'store'])->name('katalog.store');
    Route::put('/katalog/{katalog}', [Pengelola\CatalogManagementController::class, 'update'])->name('katalog.update');
    Route::delete('/katalog/{katalog}', [Pengelola\CatalogManagementController::class, 'destroy'])->name('katalog.destroy');

    // Audit Log Mutasi Stok (FR-10)
    Route::get('/stok', [Pengelola\CatalogManagementController::class, 'stockLogsIndex'])->name('stok.index');

    // Rekapitulasi Laporan Keuangan & Grafik (FR-11)
    Route::get('/laporan', [Pengelola\ReportPredictionController::class, 'reportsIndex'])->name('laporan.index');

    // Prediksi Kebutuhan Stok AI / ML & Eksekusi Python (FR-13)
    Route::get('/prediksi-stok', [Pengelola\ReportPredictionController::class, 'predictionsIndex'])->name('prediksi.index');
    Route::post('/prediksi-stok/calculate', [Pengelola\ReportPredictionController::class, 'calculatePredictions'])->name('prediksi.calculate');
    Route::post('/prediksi-stok/restock/{prediction}', [Pengelola\ReportPredictionController::class, 'applyRestock'])->name('prediksi.restock');
});

// =========================================================================
// MODUL PETUGAS LAYANAN (MOBILE-FIRST)
// =========================================================================
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/dashboard', [Petugas\TaskQueueController::class, 'index'])->name('dashboard');
    Route::get('/antrean', [Petugas\TaskQueueController::class, 'index'])->name('antrean');
    Route::get('/antrean/sinyal', [Petugas\TaskQueueController::class, 'signal'])->name('antrean.signal');
    Route::patch('/orders/{order}/status', [Petugas\TaskQueueController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/history', [Petugas\TaskQueueController::class, 'history'])->name('history');
    Route::get('/stocks', [Petugas\StockManagementController::class, 'index'])->name('stocks.index');
    Route::post('/stocks/adjust', [Petugas\StockManagementController::class, 'adjustStock'])->name('stocks.adjust');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
});

// =========================================================================
// MODUL PENGHUNI KOS (MOBILE-FIRST)
// =========================================================================
Route::middleware(['auth', 'role:penghuni'])->prefix('penghuni')->name('penghuni.')->group(function () {
    Route::get('/beranda', [Penghuni\DashboardController::class, 'index'])->name('beranda');
    Route::get('/katalog', [Penghuni\CatalogController::class, 'index'])->name('katalog');
    Route::post('/pesan', [Penghuni\CatalogController::class, 'store'])->name('order.store');
    Route::post('/quick-galon', [Penghuni\CatalogController::class, 'quickGalon'])->name('quick_galon');
    Route::get('/pesanan', [Penghuni\OrderHistoryController::class, 'index'])->name('pesanan');
    Route::get('/pesanan/sinyal', [Penghuni\OrderHistoryController::class, 'signal'])->name('pesanan.signal');
    Route::post('/pesanan/{id}/batal', [Penghuni\OrderHistoryController::class, 'cancel'])->name('pesanan.batal');
    Route::get('/tagihan', [Penghuni\RentBillController::class, 'index'])->name('tagihan');
    Route::post('/tagihan/{id}/bukti', [Penghuni\RentBillController::class, 'uploadProof'])->name('tagihan.upload_proof');
    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
});

// =========================================================================
// FITUR NOTIFIKASI TERPADU (SEMUA ROLE: PENGELOLA, PETUGAS, PENGHUNI)
// =========================================================================
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/read-all', [NotificationController::class, 'readAll'])->name('read_all');
    Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::get('/{id}/open', [NotificationController::class, 'openAndRead'])->name('open');
});
