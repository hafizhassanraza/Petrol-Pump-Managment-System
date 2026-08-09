<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('cnic')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('agency_fuel_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('nozzle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('liters', 12, 2);
            $table->decimal('price_per_liter', 12, 2);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->string('status', 20)->default('open'); // open|partial|paid
            $table->dateTime('credit_datetime');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('agency_fuel_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_fuel_credit_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 20)->default('cash'); // cash|online
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_fuel_payments');
        Schema::dropIfExists('agency_fuel_credits');
        Schema::dropIfExists('agency_customers');
    }
};
