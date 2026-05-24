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
        Schema::create('owner_fuel_usages', function (Blueprint $table) {
            $table->id();
             $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('nozzle_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('vehicle_no')
                ->nullable();

            $table->string('person_name')
                ->nullable();

            $table->string('purpose')
                ->nullable();

            $table->decimal('liters', 12, 2);

            $table->decimal('price_per_liter', 12, 2);

            $table->decimal('total_amount', 14, 2);

            $table->dateTime('usage_datetime');

            $table->text('notes')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_fuel_usages');
    }
};
