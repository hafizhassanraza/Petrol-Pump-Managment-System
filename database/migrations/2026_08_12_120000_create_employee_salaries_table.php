<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->date('salary_month');
            $table->string('payment_method', 20)->default('cash');
            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payment_date']);
            $table->index(['salary_month']);
            $table->index(['type']);
            $table->index(['employee_id', 'salary_month']);
        });

        // Historical "Salary" expenses are no longer used; drop them so totals stay clean.
        if (Schema::hasTable('expenses')) {
            DB::table('expenses')->where('expense_type', 'Salary')->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};
