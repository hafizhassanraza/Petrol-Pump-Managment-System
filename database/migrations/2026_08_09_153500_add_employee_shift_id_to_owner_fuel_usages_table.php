<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_fuel_usages', function (Blueprint $table) {
            $table->foreignId('employee_shift_id')
                ->nullable()
                ->after('nozzle_id')
                ->constrained('employee_shifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('owner_fuel_usages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_shift_id');
        });
    }
};
