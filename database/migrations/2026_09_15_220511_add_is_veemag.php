<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VeeMags reuse the TV-show engine but present as periodicals (volumes/issues)
 * in their own section. This flag distinguishes a VeeMag from a normal TV show.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entertainments') && ! Schema::hasColumn('entertainments', 'is_veemag')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->boolean('is_veemag')->default(0)->after('type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entertainments', 'is_veemag')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->dropColumn('is_veemag');
            });
        }
    }
};
