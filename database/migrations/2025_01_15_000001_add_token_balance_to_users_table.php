<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * GoKoncentrate Tokens (KT) - Virtual currency for in-app rewards
     * Stored in cents for precision (100 cents = 1 KT = $1.00)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('token_balance_cents')->default(0)->after('is_subscribe');
            $table->timestamp('token_updated_at')->nullable()->after('token_balance_cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['token_balance_cents', 'token_updated_at']);
        });
    }
};
