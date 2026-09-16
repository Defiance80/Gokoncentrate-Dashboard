<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Podcasts reuse the TV-show engine (series -> episodes) but present in their
 * own "Media Series" section with a smaller card and a distinct detail view.
 * This flag distinguishes a Podcast from a normal TV show / VeeMag. It also
 * lets the section carry music videos as single-episode podcasts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entertainments') && ! Schema::hasColumn('entertainments', 'is_podcast')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->boolean('is_podcast')->default(0)->after('is_veemag');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entertainments', 'is_podcast')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->dropColumn('is_podcast');
            });
        }
    }
};
