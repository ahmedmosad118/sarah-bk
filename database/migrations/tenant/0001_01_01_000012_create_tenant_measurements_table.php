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
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opportunity_id')->index();
            $table->unsignedBigInteger('site_visit_id')->nullable()->index();
            $table->string('measurement_number', 50)->index();
            $table->unsignedInteger('version')->default(1)->index();
            $table->string('status', 50)->default('Draft')->index(); // Draft, Under Review, Approved, Superseded
            $table->unsignedBigInteger('measured_by')->nullable()->index();
            $table->date('measured_at')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->dateTime('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();
            $table->decimal('total_area', 15, 2)->default(0.00);
            $table->decimal('total_volume', 15, 2)->default(0.00);
            $table->decimal('total_linear', 15, 2)->default(0.00);
            $table->decimal('total_count', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // Foreign Key Constraints within Tenant DB
            $table->foreign('opportunity_id')
                ->references('id')
                ->on('opportunities')
                ->onDelete('cascade');

            $table->foreign('site_visit_id')
                ->references('id')
                ->on('site_visits')
                ->onDelete('set null');

            $table->foreign('measured_by')
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

        Schema::create('measurement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('measurement_id')->index();
            $table->string('room_name', 150)->index();
            $table->string('item_name', 250);
            $table->string('unit', 20)->index(); // m2, m3, lm, pcs
            $table->string('measurement_type', 30)->default('area')->index(); // area, volume, linear, count
            $table->decimal('count', 10, 2)->default(1.00);
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('deductions', 10, 2)->default(0.00);
            $table->decimal('gross_quantity', 15, 2)->default(0.00);
            $table->decimal('net_quantity', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('measurement_id')
                ->references('id')
                ->on('measurements')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_items');
        Schema::dropIfExists('measurements');
    }
};
