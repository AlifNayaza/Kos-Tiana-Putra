<?php

use App\Http\Controllers\AuthController;
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

// Authentication & Demo Switcher
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/demo-login/{role}', [AuthController::class, 'quickLogin'])->name('demo.login');

// =========================================================================
// MODUL PENGELOLA KOS (ADMIN DASHBOARD)
// =========================================================================
Route::middleware(['auth', 'role:pengelola'])->prefix('pengelola')->name('pengelola.')->group(function () {
    // Executive Dashboard (FR-11)
    Route::get('/dashboard', [\App\Http\Controllers\Pengelola\DashboardController::class, 'index'])->name('dashboard');

    // CRUD Kamar (FR-03)
    Route::get('/kamar', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'roomsIndex'])->name('kamar.index');
    Route::post('/kamar', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'storeRoom'])->name('kamar.store');
    Route::put('/kamar/{room}', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'updateRoom'])->name('kamar.update');
    Route::delete('/kamar/{room}', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'destroyRoom'])->name('kamar.destroy');

    // CRUD Penghuni Kos (FR-02)
    Route::get('/penghuni', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'tenantsIndex'])->name('penghuni.index');
    Route::post('/penghuni', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'storeTenant'])->name('penghuni.store');
    Route::put('/penghuni/{tenant}', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'updateTenant'])->name('penghuni.update');
    Route::post('/penghuni/{tenant}/checkout', [\App\Http\Controllers\Pengelola\RoomTenantController::class, 'checkoutTenant'])->name('penghuni.checkout');

    // Manajemen Tagihan Sewa & Konfirmasi Pembayaran (FR-04 & FR-05)
    Route::get('/tagihan', [\App\Http\Controllers\Pengelola\RentManagementController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/{bill}/confirm', [\App\Http\Controllers\Pengelola\RentManagementController::class, 'confirmPayment'])->name('tagihan.confirm');
    Route::post('/tagihan/{bill}/overdue', [\App\Http\Controllers\Pengelola\RentManagementController::class, 'markOverdue'])->name('tagihan.overdue');
    Route::post('/tagihan/generate', [\App\Http\Controllers\Pengelola\RentManagementController::class, 'generateMonthlyBills'])->name('tagihan.generate');

    // CRUD Katalog Menu & Galon (FR-06)
    Route::get('/katalog', [\App\Http\Controllers\Pengelola\CatalogManagementController::class, 'index'])->name('katalog.index');
    Route::post('/katalog', [\App\Http\Controllers\Pengelola\CatalogManagementController::class, 'store'])->name('katalog.store');
    Route::put('/katalog/{katalog}', [\App\Http\Controllers\Pengelola\CatalogManagementController::class, 'update'])->name('katalog.update');
    Route::delete('/katalog/{katalog}', [\App\Http\Controllers\Pengelola\CatalogManagementController::class, 'destroy'])->name('katalog.destroy');

    // Audit Log Mutasi Stok (FR-10)
    Route::get('/stok', [\App\Http\Controllers\Pengelola\CatalogManagementController::class, 'stockLogsIndex'])->name('stok.index');

    // Rekapitulasi Laporan Keuangan & Grafik (FR-11)
    Route::get('/laporan', [\App\Http\Controllers\Pengelola\ReportPredictionController::class, 'reportsIndex'])->name('laporan.index');

    // Prediksi Kebutuhan Stok AI / ML & Eksekusi Python (FR-13)
    Route::get('/prediksi-stok', [\App\Http\Controllers\Pengelola\ReportPredictionController::class, 'predictionsIndex'])->name('prediksi.index');
    Route::post('/prediksi-stok/calculate', [\App\Http\Controllers\Pengelola\ReportPredictionController::class, 'calculatePredictions'])->name('prediksi.calculate');
    Route::post('/prediksi-stok/restock/{prediction}', [\App\Http\Controllers\Pengelola\ReportPredictionController::class, 'applyRestock'])->name('prediksi.restock');
});

// =========================================================================
// MODUL PETUGAS LAYANAN (MOBILE-FIRST)
// =========================================================================
Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Petugas\TaskQueueController::class, 'index'])->name('dashboard');
    Route::get('/antrean', [\App\Http\Controllers\Petugas\TaskQueueController::class, 'index'])->name('antrean');
    Route::patch('/orders/{order}/status', [\App\Http\Controllers\Petugas\TaskQueueController::class, 'updateStatus'])->name('orders.update-status');
    Route::get('/history', [\App\Http\Controllers\Petugas\TaskQueueController::class, 'history'])->name('history');
    Route::get('/riwayat', [\App\Http\Controllers\Petugas\TaskQueueController::class, 'history'])->name('riwayat');
    Route::get('/stocks', [\App\Http\Controllers\Petugas\StockManagementController::class, 'index'])->name('stocks.index');
    Route::get('/stok', [\App\Http\Controllers\Petugas\StockManagementController::class, 'index'])->name('stok');
    Route::post('/stocks/adjust', [\App\Http\Controllers\Petugas\StockManagementController::class, 'adjustStock'])->name('stocks.adjust');
    Route::get('/profil', [\App\Http\Controllers\Petugas\TaskQueueController::class, 'history'])->name('profil');
});

// =========================================================================
// MODUL PENGHUNI KOS (MOBILE-FIRST)
// =========================================================================
Route::middleware(['auth', 'role:penghuni'])->prefix('penghuni')->name('penghuni.')->group(function () {
    Route::get('/beranda', [\App\Http\Controllers\Penghuni\DashboardController::class, 'index'])->name('beranda');
    Route::get('/katalog', [\App\Http\Controllers\Penghuni\CatalogController::class, 'index'])->name('katalog');
    Route::get('/menu', [\App\Http\Controllers\Penghuni\CatalogController::class, 'index'])->name('menu');
    Route::post('/pesan', [\App\Http\Controllers\Penghuni\CatalogController::class, 'store'])->name('order.store');
    Route::post('/quick-galon', [\App\Http\Controllers\Penghuni\CatalogController::class, 'quickGalon'])->name('quick_galon');
    Route::get('/pesanan', [\App\Http\Controllers\Penghuni\OrderHistoryController::class, 'index'])->name('pesanan');
    Route::post('/pesanan/{id}/batal', [\App\Http\Controllers\Penghuni\OrderHistoryController::class, 'cancel'])->name('pesanan.batal');
    Route::get('/tagihan', [\App\Http\Controllers\Penghuni\RentBillController::class, 'index'])->name('tagihan');
    Route::post('/tagihan/{id}/bukti', [\App\Http\Controllers\Penghuni\RentBillController::class, 'uploadProof'])->name('tagihan.upload_proof');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.read_all');
    Route::get('/profil', [\App\Http\Controllers\Penghuni\RentBillController::class, 'index'])->name('profil');
});

// =========================================================================
// FITUR NOTIFIKASI TERPADU (SEMUA ROLE: PENGELOLA, PETUGAS, PENGHUNI)
// =========================================================================
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('read_all');
    Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
    Route::get('/{id}/open', [\App\Http\Controllers\NotificationController::class, 'openAndRead'])->name('open');
});

