<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Default entry for `php artisan db:seed`.
 *
 * - Production / staging → ProdSeeder only
 * - Local / testing → DevSeeder (demo data)
 *
 * Force production layout only:
 *   php artisan db:seed --class=ProdSeeder
 *
 * Force demo data only:
 *   php artisan db:seed --class=DevSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->shouldSeedDev()) {
            $this->call(DevSeeder::class);

            return;
        }

        $this->call(ProdSeeder::class);
    }

    private function shouldSeedDev(): bool
    {
        if (filter_var(env('SEED_DEV_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return app()->environment(['local', 'testing']);
    }
}
