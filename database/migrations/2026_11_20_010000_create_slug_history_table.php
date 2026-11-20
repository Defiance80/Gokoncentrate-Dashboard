<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers every slug a title has ever had.
 *
 * Slugs are public URLs. People have shared them, the mobile app links to them,
 * and search engines will index them. Renaming one without a record turns a
 * shared link into a redirect to /login, which is the bug this project already
 * fixed once for app-shared links.
 *
 * With this table a retired slug 301s to the current one, so a slug can be
 * improved without costing anything that was already sent out.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('slug_history')) {
            return;
        }

        Schema::create('slug_history', function (Blueprint $table) {
            $table->id();
            // 'entertainment' | 'episode' | 'video' - which table entity_id is in.
            $table->string('entity_type', 32);
            $table->unsignedBigInteger('entity_id');
            $table->string('old_slug', 255);
            $table->timestamps();

            // One row per retired slug per type: the lookup is (type, old_slug)
            // and a slug must never point at two different titles.
            $table->unique(['entity_type', 'old_slug'], 'slug_history_type_slug_unique');
            $table->index(['entity_type', 'entity_id'], 'slug_history_type_entity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_history');
    }
};
