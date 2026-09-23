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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('title', 200)->index();
            $table->text('description')->nullable();
            $table->string('stage', 50)->default('New')->index(); // New, Qualified, Proposal, Negotiation, Won, Lost
            $table->string('loss_reason', 100)->nullable()->index();
            $table->string('competitor_name', 200)->nullable();
            $table->text('loss_notes')->nullable();
            $table->decimal('estimated_value', 15, 2)->unsigned()->nullable();
            $table->date('expected_start_date')->nullable();
            $table->date('expected_close_date')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign Key Constraints within Tenant DB
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('set null');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
