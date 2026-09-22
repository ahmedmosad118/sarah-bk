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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_type', 30)->default('individual')->index(); // individual, company
            $table->string('name', 200)->index();
            $table->string('company_name', 200)->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('whatsapp', 50)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active')->index(); // active, inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
