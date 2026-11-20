<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs schema drift on `entertainments`.
 *
 * 2025_07_11_110337_add_columns_to_entertainments_table and
 * 2025_09_16_054414_add_skip_intro_fields_to_entertainments_table are both
 * recorded as run (batch 5), and both applied cleanly to `episodes` and
 * `videos` - those tables have every column. `entertainments` has none of
 * them, so that table alone never received its half of the change.
 *
 * The consequence: any save from the admin edit form writes start_time,
 * end_time and the SEO fields, so the UPDATE dies with
 * "Unknown column 'start_time' in 'SET'" and the whole row is rolled back.
 * The form reports nothing, the record silently reverts.
 *
 * Every column below is copied verbatim from the two migrations named above -
 * nothing here is invented. Each add is guarded by hasColumn, so this is safe
 * to run against a database that has some of them already.
 */
return new class extends Migration
{
    private array $columns = [
        'meta_title',
        'meta_keywords',
        'meta_description',
        'seo_image',
        'google_site_verification',
        'canonical_url',
        'short_description',
        'start_time',
        'end_time',
        'bunny_trailer_url',
        'bunny_video_url',
    ];

    public function up(): void
    {
        Schema::table('entertainments', function (Blueprint $table) {
            // from 2025_07_11_110337
            if (!Schema::hasColumn('entertainments', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'seo_image')) {
                $table->string('seo_image')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'google_site_verification')) {
                $table->string('google_site_verification')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'short_description')) {
                $table->text('short_description')->nullable();
            }

            // from 2025_09_16_054414
            if (!Schema::hasColumn('entertainments', 'start_time')) {
                $table->time('start_time')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('entertainments', 'end_time')) {
                $table->time('end_time')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'bunny_trailer_url')) {
                $table->text('bunny_trailer_url')->nullable();
            }
            if (!Schema::hasColumn('entertainments', 'bunny_video_url')) {
                $table->text('bunny_video_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        $present = array_filter(
            $this->columns,
            fn ($c) => Schema::hasColumn('entertainments', $c)
        );

        if ($present !== []) {
            Schema::table('entertainments', function (Blueprint $table) use ($present) {
                $table->dropColumn(array_values($present));
            });
        }
    }
};
