<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add zero_cost_reason column to estimations table
        if (Schema::hasTable('estimations') && ! Schema::hasColumn('estimations', 'zero_cost_reason')) {
            Schema::table('estimations', function (Blueprint $table) {
                $table->string('zero_cost_reason', 100)->nullable()->after('notes');
            });
        }

        // 2. Add unique constraint to scopes table
        $this->applyUniqueConstraintSafely('scopes', 'scope_number', 'scopes_number_unique');

        // 3. Add unique constraint to boqs table
        $this->applyUniqueConstraintSafely('boqs', 'boq_number', 'boqs_number_unique');

        // 4. Add unique constraint to measurements table
        $this->applyUniqueConstraintSafely('measurements', 'measurement_number', 'measurements_number_unique');
    }

    /**
     * Apply a unique constraint safely with duplicate pre-check and idempotent index check.
     */
    protected function applyUniqueConstraintSafely(string $table, string $column, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        // 1. Check if the unique constraint already exists (idempotent retry)
        $indexes = collect(DB::select("SHOW INDEXES FROM `{$table}` WHERE Key_name = ?", [$indexName]));
        if ($indexes->isNotEmpty()) {
            return;
        }

        // 2. Pre-check for duplicate document numbers before applying constraint
        $duplicates = DB::table($table)
            ->select($column, DB::raw('COUNT(*) as dupe_count'))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $tenantDb = DB::connection()->getDatabaseName();
            $dupeDetails = $duplicates->map(fn ($d) => "{$d->$column} (x{$d->dupe_count})")->implode(', ');

            throw new RuntimeException(
                "Cannot apply unique constraint '{$indexName}' on '{$table}.{$column}'. Existing duplicates found in tenant database '{$tenantDb}': {$dupeDetails}. Please correct duplicate numbers deliberately before running migrations."
            );
        }

        // 3. Apply unique constraint
        Schema::table($table, function (Blueprint $table) use ($column, $indexName) {
            $table->unique($column, $indexName);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropUniqueConstraintSafely('measurements', 'measurements_number_unique');
        $this->dropUniqueConstraintSafely('boqs', 'boqs_number_unique');
        $this->dropUniqueConstraintSafely('scopes', 'scopes_number_unique');

        if (Schema::hasTable('estimations') && Schema::hasColumn('estimations', 'zero_cost_reason')) {
            Schema::table('estimations', function (Blueprint $table) {
                $table->dropColumn('zero_cost_reason');
            });
        }
    }

    /**
     * Drop unique constraint safely if it exists.
     */
    protected function dropUniqueConstraintSafely(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $indexes = collect(DB::select("SHOW INDEXES FROM `{$table}` WHERE Key_name = ?", [$indexName]));
        if ($indexes->isNotEmpty()) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }
    }
};
