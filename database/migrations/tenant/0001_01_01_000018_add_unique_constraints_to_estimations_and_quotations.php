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
        Schema::table('estimations', function (Blueprint $table) {
            $table->unique('estimation_number', 'estimations_number_unique');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->unique('quotation_number', 'quotations_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique('quotations_number_unique');
        });

        Schema::table('estimations', function (Blueprint $table) {
            $table->dropUnique('estimations_number_unique');
        });
    }
};
