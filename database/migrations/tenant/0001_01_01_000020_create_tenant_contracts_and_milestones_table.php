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
        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('opportunity_id')->index();
                $table->unsignedBigInteger('quotation_id')->index();
                $table->unsignedBigInteger('parent_contract_id')->nullable()->index();
                $table->string('contract_number', 50)->index();
                $table->unsignedInteger('version')->default(1)->index();

                // 1. Internal Approval Workflow Status (Draft, Under Review, Approved, Superseded)
                $table->string('status', 50)->default('Draft')->index();

                // 2. Independent Signature Status (Not Sent, Sent for Signature, Signed, Void)
                $table->string('signature_status', 50)->default('Not Sent')->index();

                // Financial Structure
                $table->decimal('contract_value', 15, 2)->default(0.00);
                $table->text('value_adjustment_reason')->nullable();

                // Term & Milestone Timeline
                $table->date('start_date')->nullable()->index();
                $table->date('expected_completion_date')->nullable()->index();

                // Legal Terms & Internal Notes
                $table->longText('terms_and_conditions')->nullable();
                $table->text('notes')->nullable();

                // Internal Workflow Sign-offs
                $table->unsignedBigInteger('prepared_by')->nullable()->index();
                $table->date('prepared_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->dateTime('reviewed_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->dateTime('approved_at')->nullable();

                // Official Legal Signing Sign-offs
                $table->unsignedBigInteger('signed_by')->nullable()->index();
                $table->dateTime('signed_at')->nullable();

                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                // Foreign Key Constraints
                $table->foreign('opportunity_id')
                    ->references('id')
                    ->on('opportunities')
                    ->onDelete('cascade');

                // Legal baseline Quotation: never cascade-delete historical offer
                $table->foreign('quotation_id')
                    ->references('id')
                    ->on('quotations')
                    ->onDelete('restrict');

                $table->foreign('parent_contract_id')
                    ->references('id')
                    ->on('contracts')
                    ->onDelete('set null');

                $table->foreign('prepared_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('reviewed_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('approved_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('signed_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');

                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }

        // Apply Unique Constraint on contract_number with safe duplicate pre-check
        $this->applyUniqueConstraintSafely('contracts', 'contract_number', 'contracts_number_unique');

        // Create Payment Milestones Table
        if (! Schema::hasTable('contract_payment_milestones')) {
            Schema::create('contract_payment_milestones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_id')->index();
                $table->string('milestone_label', 255);
                $table->decimal('percentage', 5, 2)->nullable();
                $table->decimal('fixed_amount', 15, 2)->nullable();
                $table->text('trigger_description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->foreign('contract_id')
                    ->references('id')
                    ->on('contracts')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Apply unique constraint safely with duplicate pre-check and idempotent index check.
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
        Schema::dropIfExists('contract_payment_milestones');
        Schema::dropIfExists('contracts');
    }
};
