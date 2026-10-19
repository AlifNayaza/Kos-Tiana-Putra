<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Indomie Goreng Telur", "Galon Aqua 19L"
            $table->enum('category', ['makanan', 'minuman', 'galon']);
            $table->unsignedInteger('price'); // Rp
            $table->integer('stock')->default(0);
            $table->integer('minimum_stock')->default(5);
            $table->string('unit')->default('porsi'); // porsi, gelas, galon, botol
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};
