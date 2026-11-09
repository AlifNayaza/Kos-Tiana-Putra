<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Indeks untuk pola kueri yang paling sering dipakai: antrean (status), riwayat & laporan (tanggal),
     * tagihan (status + jatuh tempo, periode), notifikasi belum dibaca, dan audit stok.
     * Aman dijalankan pada data yang sudah ada; hanya menambah indeks.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'orders_status_created_idx');       // antrean aktif & laporan bulanan
            $table->index(['user_id', 'status'], 'orders_user_status_idx');             // pesanan milik penghuni
            $table->index('completed_at', 'orders_completed_at_idx');                   // riwayat & rekap hari ini
        });

        Schema::table('rent_bills', function (Blueprint $table) {
            $table->index(['status', 'due_date'], 'rent_bills_status_due_idx');         // menunggak / jatuh tempo
            $table->index(['year', 'month', 'status'], 'rent_bills_period_status_idx'); // laporan & ringkasan periode
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read'], 'notifications_user_read_idx');         // lonceng: jumlah belum dibaca
            $table->index(['user_id', 'created_at'], 'notifications_user_created_idx'); // daftar notifikasi terbaru
        });

        Schema::table('stock_logs', function (Blueprint $table) {
            $table->index('created_at', 'stock_logs_created_idx');                      // audit stok urut waktu
        });

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->index(['category', 'is_available'], 'catalog_category_available_idx'); // katalog penghuni
        });

        Schema::table('tenant_profiles', function (Blueprint $table) {
            $table->index(['is_active', 'start_date'], 'tenant_profiles_active_idx');   // daftar penghuni aktif
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_user_status_idx');
            $table->dropIndex('orders_completed_at_idx');
        });
        Schema::table('rent_bills', function (Blueprint $table) {
            $table->dropIndex('rent_bills_status_due_idx');
            $table->dropIndex('rent_bills_period_status_idx');
        });
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_idx');
            $table->dropIndex('notifications_user_created_idx');
        });
        Schema::table('stock_logs', fn (Blueprint $table) => $table->dropIndex('stock_logs_created_idx'));
        Schema::table('catalog_items', fn (Blueprint $table) => $table->dropIndex('catalog_category_available_idx'));
        Schema::table('tenant_profiles', fn (Blueprint $table) => $table->dropIndex('tenant_profiles_active_idx'));
    }
};
