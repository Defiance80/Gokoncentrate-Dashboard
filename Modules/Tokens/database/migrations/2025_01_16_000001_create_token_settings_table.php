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
        Schema::create('token_settings', function (Blueprint $table) {
            $table->id();

            // Display settings
            $table->string('token_label', 10)->default('KT');
            $table->string('token_name', 100)->default('GoKoncentrate Tokens');

            // Value settings (super admin only)
            $table->unsignedInteger('token_usd_cents_per_token')->default(100); // 100 cents = $1 = 1 KT

            // Earning settings (super admin only)
            $table->unsignedInteger('earn_cents')->default(1);
            $table->unsignedInteger('earn_seconds')->default(10);

            // Cap settings
            $table->unsignedInteger('daily_cap_cents')->nullable();

            // Control settings
            $table->boolean('global_enabled')->default(true); // Kill switch
            $table->unsignedInteger('repeat_cooldown_seconds')->default(0);

            // Eligible content flags (JSON)
            $table->json('eligible_content_flags')->nullable();
            // Expected: {"free_video": true, "free_magazine": true, "focus_mode": true}

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_settings');
    }
};
