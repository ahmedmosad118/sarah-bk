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
        Schema::table('scopes', function (Blueprint $table) {
            $table->dropForeign(['measurement_id']);
            $table->foreign('measurement_id')
                ->references('id')
                ->on('measurements')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scopes', function (Blueprint $table) {
            $table->dropForeign(['measurement_id']);
            $table->foreign('measurement_id')
                ->references('id')
                ->on('measurements')
                ->onDelete('cascade');
        });
    }
};
