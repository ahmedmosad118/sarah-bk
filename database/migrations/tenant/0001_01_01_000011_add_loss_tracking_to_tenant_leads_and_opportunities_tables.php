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
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (!Schema::hasColumn('leads', 'loss_reason')) {
                    $table->string('loss_reason', 100)->nullable()->index()->after('status');
                }
                if (!Schema::hasColumn('leads', 'competitor_name')) {
                    $table->string('competitor_name', 200)->nullable()->after('loss_reason');
                }
                if (!Schema::hasColumn('leads', 'loss_notes')) {
                    $table->text('loss_notes')->nullable()->after('competitor_name');
                }
            });
        }

        if (Schema::hasTable('opportunities')) {
            Schema::table('opportunities', function (Blueprint $table) {
                if (!Schema::hasColumn('opportunities', 'loss_reason')) {
                    $table->string('loss_reason', 100)->nullable()->index()->after('stage');
                }
                if (!Schema::hasColumn('opportunities', 'competitor_name')) {
                    $table->string('competitor_name', 200)->nullable()->after('loss_reason');
                }
                if (!Schema::hasColumn('opportunities', 'loss_notes')) {
                    $table->text('loss_notes')->nullable()->after('competitor_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn(['loss_reason', 'competitor_name', 'loss_notes']);
            });
        }

        if (Schema::hasTable('opportunities')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->dropColumn(['loss_reason', 'competitor_name', 'loss_notes']);
            });
        }
    }
};
