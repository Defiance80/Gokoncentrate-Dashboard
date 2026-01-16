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
        Schema::create('token_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // Amount (signed - positive for credits, negative for debits)
            $table->bigInteger('amount_cents');

            // Transaction classification
            $table->string('type', 50); // earned, spent, admin_credit, admin_debit, admin_set, refund
            $table->string('source', 100)->nullable(); // focus_mode, free_video, admin_panel, purchase, etc.
            $table->string('reason', 255)->nullable(); // Human readable reason

            // Admin tracking
            $table->unsignedBigInteger('admin_id')->nullable(); // If admin-initiated

            // Additional context
            $table->json('metadata')->nullable();
            // Can include: content_id, duration_seconds, previous_balance, etc.

            // Balance snapshot
            $table->bigInteger('balance_after_cents');

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
            $table->index('admin_id');

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_ledgers');
    }
};
