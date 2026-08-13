<?php

namespace App\Services\DataManagement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataBackupService
{
    public const VERSION = 1;

    /**
     * Ordered for FK-safe restore (parents first).
     *
     * @return list<string>
     */
    public static function tables(): array
    {
        return [
            'users',
            'products',
            'product_prices',
            'tanks',
            'dispensers',
            'nozzles',
            'shifts',
            'employees',
            'agency_customers',
            'mobil_oil_products',
            'mobil_oil_prices',
            'employee_shifts',
            'owner_fuel_usages',
            'agency_fuel_credits',
            'agency_fuel_payments',
            'tank_refills',
            'tank_dip_readings',
            'expenses',
            'cash_transactions',
            'employee_attendances',
            'employee_salaries',
            'mobil_oil_purchases',
            'mobil_oil_sales',
            'audit_logs',
        ];
    }

    /**
     * @return array{meta: array<string, mixed>, tables: array<string, list<array<string, mixed>>>}
     */
    public function export(): array
    {
        $tables = [];

        foreach (self::tables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $tables[$table] = DB::table($table)->orderBy('id')->get()->map(function ($row) {
                return (array) $row;
            })->all();
        }

        return [
            'meta' => [
                'version' => self::VERSION,
                'app' => 'fuel-station',
                'exported_at' => now()->toIso8601String(),
                'tables' => array_keys($tables),
                'row_counts' => collect($tables)->map(fn ($rows) => count($rows))->all(),
            ],
            'tables' => $tables,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{tables: int, rows: int}
     */
    public function import(array $payload): array
    {
        if (($payload['meta']['app'] ?? null) !== 'fuel-station') {
            throw new \InvalidArgumentException('Invalid backup file: unrecognized app marker.');
        }

        if ((int) ($payload['meta']['version'] ?? 0) !== self::VERSION) {
            throw new \InvalidArgumentException('Unsupported backup version.');
        }

        if (! isset($payload['tables']) || ! is_array($payload['tables'])) {
            throw new \InvalidArgumentException('Invalid backup file: missing tables.');
        }

        $importedRows = 0;
        $importedTables = 0;

        DB::transaction(function () use ($payload, &$importedRows, &$importedTables) {
            Schema::disableForeignKeyConstraints();

            try {
                foreach (array_reverse(self::tables()) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->delete();
                    }
                }

                foreach (self::tables() as $table) {
                    if (! Schema::hasTable($table) || ! isset($payload['tables'][$table])) {
                        continue;
                    }

                    $rows = $payload['tables'][$table];
                    if (! is_array($rows) || $rows === []) {
                        $importedTables++;
                        continue;
                    }

                    foreach (array_chunk($rows, 200) as $chunk) {
                        $clean = array_map(function ($row) {
                            return is_array($row) ? $row : (array) $row;
                        }, $chunk);
                        DB::table($table)->insert($clean);
                        $importedRows += count($clean);
                    }

                    $importedTables++;
                }
            } finally {
                Schema::enableForeignKeyConstraints();
            }
        });

        return [
            'tables' => $importedTables,
            'rows' => $importedRows,
        ];
    }
}
