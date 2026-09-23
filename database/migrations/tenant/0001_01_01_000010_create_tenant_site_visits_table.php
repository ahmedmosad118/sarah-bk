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
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('opportunity_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->string('status', 50)->default('Scheduled')->index(); // Scheduled, Completed, Cancelled, Rescheduled
            $table->date('scheduled_date')->nullable()->index();
            $table->time('scheduled_time')->nullable();
            $table->date('visit_date')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->text('general_assessment')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // Foreign Key Constraints within Tenant DB
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            $table->foreign('opportunity_id')
                ->references('id')
                ->on('opportunities')
                ->onDelete('set null');

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

        Schema::create('site_visit_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_visit_id')->index();
            $table->string('room_name', 150);
            $table->decimal('estimated_area', 10, 2)->unsigned()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('site_visit_id')
                ->references('id')
                ->on('site_visits')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visit_rooms');
        Schema::dropIfExists('site_visits');
    }
};
