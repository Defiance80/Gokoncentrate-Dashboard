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
        Schema::table('users', function (Blueprint $table) {
            // Note: token_balance_cents may already exist per INTEGRATIONS.md
            // Only adding if it doesn't exist
            if (!Schema::hasColumn('users', 'token_balance_cents')) {
                $table->unsignedBigInteger('token_balance_cents')->default(0)->after('is_subscribe');
            }

            // Add new token-related fields
            if (!Schema::hasColumn('users', 'earning_suspended')) {
                $table->boolean('earning_suspended')->default(false)->after('token_balance_cents');
            }

            if (!Schema::hasColumn('users', 'earning_suspended_reason')) {
                $table->string('earning_suspended_reason', 255)->nullable()->after('earning_suspended');
            }

            if (!Schema::hasColumn('users', 'last_earned_at')) {
                $table->timestamp('last_earned_at')->nullable()->after('earning_suspended_reason');
            }

            if (!Schema::hasColumn('users', 'last_spent_at')) {
                $table->timestamp('last_spent_at')->nullable()->after('last_earned_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only drop columns we added, don't touch token_balance_cents as it may have existed before
            $columnsToDrop = [];

            if (Schema::hasColumn('users', 'earning_suspended')) {
                $columnsToDrop[] = 'earning_suspended';
            }
            if (Schema::hasColumn('users', 'earning_suspended_reason')) {
                $columnsToDrop[] = 'earning_suspended_reason';
            }
            if (Schema::hasColumn('users', 'last_earned_at')) {
                $columnsToDrop[] = 'last_earned_at';
            }
            if (Schema::hasColumn('users', 'last_spent_at')) {
                $columnsToDrop[] = 'last_spent_at';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
