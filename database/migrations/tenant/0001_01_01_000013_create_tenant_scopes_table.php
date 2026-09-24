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
        Schema::create('scopes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opportunity_id')->index();
            $table->unsignedBigInteger('measurement_id')->index();
            $table->string('scope_number', 50)->index();
            $table->unsignedInteger('version')->default(1)->index();
            $table->string('status', 50)->default('Draft')->index(); // Draft, Under Review, Approved, Superseded
            $table->string('title', 255)->nullable();
            $table->text('general_inclusions')->nullable();
            $table->text('general_exclusions')->nullable();
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

            $table->foreign('measurement_id')
                ->references('id')
                ->on('measurements')
                ->onDelete('cascade');

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

        Schema::create('scope_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scope_id')->index();
            $table->string('trade_category', 100)->index(); // أعمال الأرضيات، الدهانات، الكهرباء، السباكة، النجارة، الديكور...
            $table->string('item_name', 250); // اسم بند الشغل
            $table->text('specification')->nullable(); // المواصفات الفنية التفصيلية وطريقة التنفيذ
            $table->text('inclusions')->nullable(); // ما يشمله هذا البند تحديداً
            $table->text('exclusions')->nullable(); // ما يستثنى من هذا البند لتفادي النزاعات
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('scope_id')
                ->references('id')
                ->on('scopes')
                ->onDelete('cascade');
        });

        Schema::create('scope_item_measurement_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scope_item_id')->index();
            $table->unsignedBigInteger('measurement_item_id')->index();
            $table->timestamps();

            $table->foreign('scope_item_id')
                ->references('id')
                ->on('scope_items')
                ->onDelete('cascade');

            $table->foreign('measurement_item_id')
                ->references('id')
                ->on('measurement_items')
                ->onDelete('cascade');

            $table->unique(['scope_item_id', 'measurement_item_id'], 'scope_meas_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope_item_measurement_item');
        Schema::dropIfExists('scope_items');
        Schema::dropIfExists('scopes');
    }
};
