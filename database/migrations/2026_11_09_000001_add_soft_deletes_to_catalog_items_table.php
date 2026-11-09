<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menu yang "dihapus" disembunyikan, bukan dihapus fisik, supaya riwayat
     * pesanan, audit stok, dan laporan penjualan tidak ikut hilang.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
