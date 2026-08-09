<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employee_shifts')
            ->whereNull('closed_date')
            ->whereIn('status', ['submitted', 'verified'])
            ->update(['closed_date' => DB::raw('assigned_date')]);
    }

    public function down(): void
    {
        // Intentionally left blank — cannot safely undo the backfill.
    }
};
