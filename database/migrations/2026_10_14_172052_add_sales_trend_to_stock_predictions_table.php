<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_predictions', function (Blueprint $table) {
            $table->integer('target_stock')->default(0)->after('predicted_demand_qty');
            $table->integer('safety_stock')->default(0)->after('daily_avg_sales');
            $table->string('sales_trend', 20)->default('stabil')->after('safety_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_predictions', function (Blueprint $table) {
            $table->dropColumn(['target_stock', 'safety_stock', 'sales_trend']);
        });
    }
};
