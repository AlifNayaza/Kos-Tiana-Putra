<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rent_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code')->unique(); // e.g. "TAG-202610-A01"
            $table->foreignId('tenant_profile_id')->constrained('tenant_profiles')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1 - 12
            $table->unsignedSmallInteger('year'); // e.g. 2026
            $table->unsignedInteger('amount'); // e.g. 850000
            $table->date('due_date'); // Tanggal jatuh tempo (e.g. tgl 10 setiap bulan)
            $table->enum('status', ['menunggu', 'lunas', 'menunggak'])->default('menunggu');
            $table->timestamp('payment_date')->nullable();
            $table->string('payment_method')->nullable(); // Transfer BCA, Tunai, QRIS
            $table->string('payment_proof')->nullable(); // Foto/Struk Bukti Transfer
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rent_bills');
    }
};
