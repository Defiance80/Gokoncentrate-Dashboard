<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hero slider rotation: lets slides be auto-refreshed from trending content
 * while curated slides stay put.
 *
 * `is_locked`    - a curated slide. Rotation never touches it.
 * `auto_managed` - rotation may replace this slot's content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            if (! Schema::hasColumn('banners', 'is_locked')) {
                $table->boolean('is_locked')->default(0)->after('status');
            }
            if (! Schema::hasColumn('banners', 'auto_managed')) {
                $table->boolean('auto_managed')->default(0)->after('is_locked');
            }
            if (! Schema::hasColumn('banners', 'last_rotated_at')) {
                $table->timestamp('last_rotated_at')->nullable()->after('auto_managed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            foreach (['last_rotated_at', 'auto_managed', 'is_locked'] as $col) {
                if (Schema::hasColumn('banners', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
