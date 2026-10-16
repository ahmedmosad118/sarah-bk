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
        if (Schema::hasTable('scopes')) {
            Schema::table('scopes', function (Blueprint $table) {
                $table->unique('scope_number', 'scopes_number_unique');
            });
        }

        // 3. Add unique constraint to boqs table
        if (Schema::hasTable('boqs')) {
            Schema::table('boqs', function (Blueprint $table) {
                $table->unique('boq_number', 'boqs_number_unique');
            });
        }

        // 4. Add unique constraint to measurements table (safe guard for existing duplicates)
        if (Schema::hasTable('measurements')) {
            $hasMeasurementDupes = DB::table('measurements')
                ->select('measurement_number')
                ->groupBy('measurement_number')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (! $hasMeasurementDupes) {
                Schema::table('measurements', function (Blueprint $table) {
                    $table->unique('measurement_number', 'measurements_number_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('measurements')) {
            try {
                Schema::table('measurements', function (Blueprint $table) {
                    $table->dropUnique('measurements_number_unique');
                });
            } catch (Throwable $e) {
                // Ignore if unique index was skipped due to existing duplicates
            }
        }

        if (Schema::hasTable('boqs')) {
            Schema::table('boqs', function (Blueprint $table) {
                $table->dropUnique('boqs_number_unique');
            });
        }

        if (Schema::hasTable('scopes')) {
            Schema::table('scopes', function (Blueprint $table) {
                $table->dropUnique('scopes_number_unique');
            });
        }

        if (Schema::hasTable('estimations') && Schema::hasColumn('estimations', 'zero_cost_reason')) {
            Schema::table('estimations', function (Blueprint $table) {
                $table->dropColumn('zero_cost_reason');
            });
        }
    }
};
