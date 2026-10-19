<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_item_id')->constrained('catalog_items')->cascadeOnDelete();
            $table->integer('predicted_demand_qty'); // estimasi kebutuhan periode mendatang
            $table->integer('current_stock'); // stok saat prediksi dihitung
            $table->integer('recommended_restock_qty'); // saran jumlah restock
            $table->string('prediction_period')->default('7 Hari ke Depan');
            $table->decimal('daily_avg_sales', 8, 2)->default(0);
            $table->enum('status', ['aman', 'waspada', 'kritis'])->default('aman');
            $table->text('recommendation_note')->nullable();
            $table->decimal('confidence_score', 4, 2)->default(0.85);
            $table->timestamp('calculated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_predictions');
    }
};
