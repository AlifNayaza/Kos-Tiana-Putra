<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique(); // e.g. "Kamar A-01"
            $table->unsignedTinyInteger('floor')->default(1);
            $table->string('type')->default('Standar Single');
            $table->unsignedInteger('monthly_price'); // e.g. 850000
            $table->text('facilities'); // Kamar Mandi Dalam, Kasur Springbed, Meja Belajar, WiFi 50Mbps
            $table->enum('status', ['tersedia', 'terisi', 'perbaikan'])->default('tersedia');
            $table->string('size')->nullable()->default('3 x 4 m');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
