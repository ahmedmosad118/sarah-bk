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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('title', 200)->index();
            $table->text('description')->nullable();
            $table->string('source', 50)->default('Other')->index(); // Facebook, Instagram, Google, Website, WhatsApp, Referral, Phone, Walk-in, Other
            $table->string('status', 50)->default('New')->index(); // New, Contacted, Qualified, Unqualified, Converted, Lost
            $table->decimal('estimated_value', 15, 2)->unsigned()->nullable();
            $table->date('expected_start_date')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign Key Constraints within Tenant DB
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

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
        Schema::dropIfExists('leads');
    }
};
