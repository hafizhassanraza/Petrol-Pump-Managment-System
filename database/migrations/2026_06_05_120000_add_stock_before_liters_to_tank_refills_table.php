<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tank_refills', function (Blueprint $table) {
            $table->decimal('stock_before_liters', 12, 2)->nullable()->after('quantity_liters');
        });
    }

    public function down(): void
    {
        Schema::table('tank_refills', function (Blueprint $table) {
            $table->dropColumn('stock_before_liters');
        });
    }
};
