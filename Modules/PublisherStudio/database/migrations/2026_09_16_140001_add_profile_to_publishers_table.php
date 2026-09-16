<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Publisher qualification profile: the answers collected at registration so an
 * admin can qualify the right candidates for video publishing (VeeMags,
 * podcasts, short films, music videos). Stored as one JSON blob to avoid a wide
 * table; a couple of high-value fields are promoted to columns for listing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            if (! Schema::hasColumn('publishers', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('publishers', 'content_focus')) {
                $table->string('content_focus')->nullable()->after('website');
            }
            if (! Schema::hasColumn('publishers', 'profile')) {
                $table->longText('profile')->nullable()->after('bio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            foreach (['website', 'content_focus', 'profile'] as $col) {
                if (Schema::hasColumn('publishers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
