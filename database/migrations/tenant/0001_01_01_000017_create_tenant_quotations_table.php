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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opportunity_id')->index();
            $table->unsignedBigInteger('estimation_id')->index();
            $table->string('quotation_number', 50)->index();
            $table->unsignedInteger('version')->default(1)->index();

            // 1. Internal Approval Workflow Status (Draft, Under Review, Approved, Superseded)
            $table->string('status', 50)->default('Draft')->index();

            // 2. Client-Facing Status (Not Sent, Sent, Accepted, Rejected, Expired)
            $table->string('client_status', 50)->default('Not Sent')->index();

            $table->string('title', 255)->nullable();

            // Pricing & Financial Structure (Client Selling Prices ONLY)
            $table->decimal('markup_rate', 5, 2)->default(0.00); // Default document markup percentage
            $table->string('discount_type', 20)->default('percentage'); // 'percentage' or 'fixed'
            $table->decimal('discount_rate', 5, 2)->default(0.00); // discount %
            $table->decimal('discount_amount', 15, 2)->default(0.00); // fixed discount value
            $table->decimal('discount_value', 15, 2)->default(0.00); // calculated discount amount
            $table->decimal('tax_rate', 5, 2)->default(15.00); // tax/VAT % (e.g. 15%)
            $table->decimal('subtotal', 15, 2)->default(0.00); // sum of items selling prices
            $table->decimal('taxable_amount', 15, 2)->default(0.00); // subtotal - discount_value
            $table->decimal('tax_amount', 15, 2)->default(0.00); // taxable_amount * (tax_rate / 100)
            $table->decimal('grand_total', 15, 2)->default(0.00); // taxable_amount + tax_amount

            $table->date('valid_until')->nullable()->index();
            $table->text('payment_terms_text')->nullable();
            $table->text('notes')->nullable(); // Internal notes (confidential)
            $table->text('client_notes')->nullable(); // Visible commercial terms for client

            // Internal Workflow Sign-offs
            $table->unsignedBigInteger('prepared_by')->nullable()->index();
            $table->date('prepared_at')->nullable()->index();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->dateTime('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->dateTime('approved_at')->nullable();

            // Client Interaction Tracking
            $table->unsignedBigInteger('sent_by')->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->text('client_response_notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('opportunity_id')
                ->references('id')
                ->on('opportunities')
                ->onDelete('cascade');

            // Approved source Estimation: never cascade-delete historical truth
            $table->foreign('estimation_id')
                ->references('id')
                ->on('estimations')
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

            $table->foreign('sent_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->index();
            $table->unsignedBigInteger('estimation_item_id')->index();
            $table->string('item_code', 50)->nullable()->index();
            $table->text('description')->nullable();
            $table->string('unit', 20)->index();
            $table->decimal('quantity', 15, 2)->default(0.00);

            // Optional item-level markup override (null = inherits quotation.markup_rate)
            $table->decimal('markup_rate', 5, 2)->nullable();

            // Client Selling Price (ZERO internal cost columns stored here)
            $table->decimal('unit_selling_price', 15, 2)->default(0.00);
            $table->decimal('line_total', 15, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('quotation_id')
                ->references('id')
                ->on('quotations')
                ->onDelete('cascade');

            $table->foreign('estimation_item_id')
                ->references('id')
                ->on('estimation_items')
                ->onDelete('restrict');

            $table->unique(['quotation_id', 'estimation_item_id'], 'quot_est_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
