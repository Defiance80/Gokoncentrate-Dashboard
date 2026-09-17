<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which GoKoncentrate section a Media Radar candidate publishes into. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('media_candidates') && ! Schema::hasColumn('media_candidates', 'target_section')) {
            Schema::table('media_candidates', function (Blueprint $table) {
                // short_film | veemag | tvshow | podcast | music
                $table->string('target_section')->default('short_film')->after('genre_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('media_candidates', 'target_section')) {
            Schema::table('media_candidates', function (Blueprint $table) {
                $table->dropColumn('target_section');
            });
        }
    }
};
