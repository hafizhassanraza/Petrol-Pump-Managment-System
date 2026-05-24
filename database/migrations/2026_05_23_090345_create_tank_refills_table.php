<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tank_refills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tank_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('invoice_no')
                ->nullable();

            $table->decimal('quantity_liters', 12, 2);

            $table->decimal('purchase_rate', 12, 2);

            $table->decimal('total_amount', 14, 2);

            $table->dateTime('received_datetime');

            $table->text('notes')->nullable();

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
        Schema::dropIfExists('tank_refills');
    }
};
