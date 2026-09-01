<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_discovery_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publication_id')->default(1);

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);

            // ---- Required search parameters -------------------------------
            // Platforms, stored as a JSON array of provider slugs.
            $table->json('providers');
            // Destination genre. Also used as the fallback search term.
            $table->unsignedBigInteger('genre_id');

            // ---- Optional search parameters -------------------------------
            $table->json('secondary_genre_ids')->nullable();
            $table->json('search_terms')->nullable();
            $table->json('excluded_terms')->nullable();
            $table->json('keywords')->nullable();
            $table->json('actors')->nullable();

            $table->string('content_type')->nullable();
            $table->unsignedInteger('min_duration_seconds')->nullable();
            $table->unsignedInteger('max_duration_seconds')->nullable();
            $table->unsignedSmallInteger('release_year_from')->nullable();
            $table->unsignedSmallInteger('release_year_to')->nullable();
            $table->string('min_quality')->nullable();
            $table->boolean('quality_strict')->default(false);
            $table->string('language')->nullable();
            $table->string('region')->nullable();
            $table->unsignedSmallInteger('published_within_days')->nullable();
            $table->unsignedBigInteger('min_view_count')->nullable();
            $table->unsignedTinyInteger('minimum_editorial_score')->default(60);

            $table->json('preferred_creator_ids')->nullable();
            $table->json('blocked_creator_ids')->nullable();

            // NULL means inherit the global Media Radar auto-approval switch.
            $table->boolean('auto_approve')->nullable();

            // ---- Publishing destination -----------------------------------
            $table->string('destination_movie_access')->default('free');
            $table->unsignedBigInteger('destination_plan_id')->nullable();
            $table->boolean('destination_is_restricted')->default(false);

            $table->integer('priority')->default(0);

            // interval, cron or manual
            $table->string('schedule_type')->default('interval');
            $table->string('schedule_expression')->nullable();
            $table->unsignedSmallInteger('interval_hours')->default(6);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('last_error')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enabled', 'next_run_at']);
            $table->index('publication_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_discovery_rules');
    }
};
