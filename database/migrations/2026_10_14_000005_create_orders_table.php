<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique(); // e.g. "ORD-20261014-001"
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('room_number'); // e.g. "Kamar A-04"
            $table->enum('order_type', ['regular', 'quick_galon'])->default('regular');
            $table->unsignedInteger('total_amount'); // Rp
            $table->enum('status', ['menunggu', 'diproses', 'diantar', 'selesai', 'batal'])->default('menunggu');
            $table->enum('payment_status', ['belum_bayar', 'lunas'])->default('belum_bayar');
            $table->string('payment_method')->default('Tunai / Bayar di Kamar');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
