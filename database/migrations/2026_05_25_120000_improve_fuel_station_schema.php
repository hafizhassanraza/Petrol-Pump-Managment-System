<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->decimal('price_per_liter', 10, 2)->nullable()->after('total_liters');
        });

        Schema::table('tank_dip_readings', function (Blueprint $table) {
            $table->decimal('system_stock_liters', 14, 2)->nullable()->after('measured_liters');
            $table->decimal('difference_liters', 14, 2)->nullable()->after('system_stock_liters');
            $table->boolean('stock_reconciled')->default(false)->after('difference_liters');
        });
    }

    public function down(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->dropColumn('price_per_liter');
        });

        Schema::table('tank_dip_readings', function (Blueprint $table) {
            $table->dropColumn(['system_stock_liters', 'difference_liters', 'stock_reconciled']);
        });
    }
};
