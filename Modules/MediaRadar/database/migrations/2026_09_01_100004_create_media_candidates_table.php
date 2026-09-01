<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publication_id')->default(1);

            // ---- Original provider identity, never overwritten ------------
            $table->string('provider');
            $table->string('provider_video_id');
            $table->text('provider_url')->nullable();

            $table->string('provider_creator_id')->nullable();
            $table->string('creator_name')->nullable();
            $table->text('creator_url')->nullable();

            $table->text('original_title')->nullable();
            $table->longText('original_description')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('language')->nullable();

            // Highest resolution the provider reports for this video.
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('quality_label')->nullable();
            $table->boolean('quality_verified')->default(false);

            $table->unsignedBigInteger('view_count')->nullable();
            $table->unsignedBigInteger('like_count')->nullable();
            $table->unsignedBigInteger('comment_count')->nullable();

            $table->boolean('embeddable')->default(true);

            // ---- Cover art -------------------------------------------------
            // Remote provider link, or a local filename when a crop was made.
            $table->text('thumbnail_url')->nullable();
            $table->text('poster_url')->nullable();
            // provider_link or cropped_local
            $table->string('cover_art_source')->nullable();
            $table->text('cover_art_origin_url')->nullable();

            // ---- Editorial payload ready for publishing -------------------
            $table->string('editorial_title')->nullable();
            $table->longText('editorial_description')->nullable();
            $table->text('editorial_summary')->nullable();
            $table->json('editorial_tags')->nullable();
            $table->unsignedBigInteger('genre_id')->nullable();
            $table->json('secondary_genre_ids')->nullable();
            $table->json('matched_actor_names')->nullable();

            $table->unsignedTinyInteger('editorial_score')->nullable();

            $table->string('status')->default('DISCOVERED');
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();

            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();

            $table->timestamp('scheduled_for')->nullable();

            $table->unsignedBigInteger('published_entertainment_id')->nullable();
            $table->timestamp('published_at_local')->nullable();

            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('failed_attempts')->default(0);

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['publication_id', 'provider', 'provider_video_id'], 'media_candidates_provider_unique');
            $table->index(['status', 'editorial_score']);
            $table->index(['provider', 'provider_creator_id']);
            $table->index('discovered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_candidates');
    }
};
