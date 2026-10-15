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
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opportunity_id')->index();
            $table->unsignedBigInteger('boq_id')->index();
            $table->string('estimation_number', 50)->index();
            $table->unsignedInteger('version')->default(1)->index();
            $table->string('status', 50)->default('Draft')->index(); // Draft, Under Review, Approved, Superseded
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('prepared_by')->nullable()->index();
            $table->date('prepared_at')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->dateTime('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();

            // Cost Summaries (calculated strictly server-side)
            $table->decimal('direct_cost', 15, 2)->default(0.00);
            $table->decimal('overhead_rate', 5, 2)->default(0.00); // e.g. 10.00 for 10%
            $table->decimal('overhead_amount', 15, 2)->default(0.00);
            $table->decimal('total_cost', 15, 2)->default(0.00);

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('opportunity_id')
                ->references('id')
                ->on('opportunities')
                ->onDelete('cascade');

            // Approved source BOQ revision: never cascade-delete historical truth
            $table->foreign('boq_id')
                ->references('id')
                ->on('boqs')
                ->onDelete('restrict');

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

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        Schema::create('estimation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estimation_id')->index();
            $table->unsignedBigInteger('boq_item_id')->index();
            $table->string('item_code', 50)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('unit', 20)->index();
            $table->decimal('quantity', 15, 2)->default(0.00);

            // Direct Cost Breakdown Subtotals per line
            $table->decimal('material_cost', 15, 2)->default(0.00);
            $table->decimal('labor_cost', 15, 2)->default(0.00);
            $table->decimal('equipment_cost', 15, 2)->default(0.00);
            $table->decimal('subcontractor_cost', 15, 2)->default(0.00);
            $table->decimal('other_cost', 15, 2)->default(0.00);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('total_cost', 15, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('estimation_id')
                ->references('id')
                ->on('estimations')
                ->onDelete('cascade');

            $table->foreign('boq_item_id')
                ->references('id')
                ->on('boq_items')
                ->onDelete('restrict');

            $table->unique(['estimation_id', 'boq_item_id'], 'est_boq_item_unique');
        });

        Schema::create('estimation_item_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estimation_item_id')->index();
            $table->string('cost_type', 30)->index(); // material, labor, equipment, subcontractor, other
            $table->string('description', 255)->nullable();
            $table->decimal('cost_rate', 15, 2)->default(0.00); // rate per source BOQ unit
            $table->decimal('amount', 15, 2)->default(0.00); // calculated: quantity * cost_rate
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('estimation_item_id')
                ->references('id')
                ->on('estimation_items')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimation_item_costs');
        Schema::dropIfExists('estimation_items');
        Schema::dropIfExists('estimations');
    }
};
