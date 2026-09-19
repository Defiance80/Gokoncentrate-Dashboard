<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Print companion for a VeeMag issue (spec v1.1, sections 29-37).
 *
 * A reader on an issue page can order the printed edition. The issue already
 * carries `print_enabled`; this adds the per-issue price override and the
 * order ledger that the Stripe checkout writes into.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('veemag_issues', function (Blueprint $table) {
            // Null means "use the platform default" (settings: veemag_print_price).
            if (! Schema::hasColumn('veemag_issues', 'print_price')) {
                $table->decimal('print_price', 8, 2)->nullable()->after('print_enabled');
            }
            if (! Schema::hasColumn('veemag_issues', 'print_sku')) {
                $table->string('print_sku')->nullable()->after('print_price');
            }
        });

        if (! Schema::hasTable('veemag_print_orders')) {
            Schema::create('veemag_print_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('issue_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email');
                // Stripe's checkout session id; also how we reconcile on return.
                $table->string('stripe_session_id')->nullable()->index();
                $table->string('checkout_url', 1024)->nullable();
                $table->decimal('amount', 8, 2)->default(0);
                $table->string('currency', 8)->default('USD');
                // pending | paid | cancelled | failed
                $table->string('status')->default('pending');
                // Shipping address as returned by Stripe once collected.
                $table->json('shipping')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('veemag_print_orders');

        Schema::table('veemag_issues', function (Blueprint $table) {
            if (Schema::hasColumn('veemag_issues', 'print_sku')) {
                $table->dropColumn('print_sku');
            }
            if (Schema::hasColumn('veemag_issues', 'print_price')) {
                $table->dropColumn('print_price');
            }
        });
    }
};
