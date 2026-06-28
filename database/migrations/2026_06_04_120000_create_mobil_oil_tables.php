<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobil_oil_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit')->default('bottle');
            $table->decimal('current_stock_qty', 12, 2)->default(0);
            $table->decimal('minimum_level', 12, 2)->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('mobil_oil_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobil_oil_product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->dateTime('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mobil_oil_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobil_oil_product_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('purchase_rate', 10, 2);
            $table->decimal('total_amount', 14, 2);
            $table->dateTime('received_datetime');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mobil_oil_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobil_oil_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 14, 2);
            $table->string('payment_method')->default('cash');
            $table->dateTime('sold_datetime');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobil_oil_sales');
        Schema::dropIfExists('mobil_oil_purchases');
        Schema::dropIfExists('mobil_oil_prices');
        Schema::dropIfExists('mobil_oil_products');
    }
};
