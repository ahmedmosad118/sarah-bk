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
        Schema::create('boqs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opportunity_id')->index();
            $table->unsignedBigInteger('scope_id')->index();
            $table->string('boq_number', 50)->index();
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
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // Foreign Key Constraints within Tenant DB
            $table->foreign('opportunity_id')
                ->references('id')
                ->on('opportunities')
                ->onDelete('cascade');

            // Approved source Scope revision: never cascade-delete historical truth
            $table->foreign('scope_id')
                ->references('id')
                ->on('scopes')
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

        Schema::create('boq_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boq_id')->index();
            $table->unsignedBigInteger('scope_item_id')->index();
            $table->string('item_code', 20)->nullable()->index(); // 1.1, 1.2, 2.1...
            $table->text('description')->nullable(); // Inherited from ScopeItem (item_name + specification)
            $table->string('unit', 20)->index(); // m2, m3, lm, pcs (homogeneous per line)
            $table->decimal('quantity', 15, 2)->default(0.00); // Sum of linked MeasurementItem net_quantity
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('boq_id')
                ->references('id')
                ->on('boqs')
                ->onDelete('cascade');

            $table->foreign('scope_item_id')
                ->references('id')
                ->on('scope_items')
                ->onDelete('restrict');

            $table->unique(['boq_id', 'scope_item_id'], 'boq_scope_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boq_items');
        Schema::dropIfExists('boqs');
    }
};
