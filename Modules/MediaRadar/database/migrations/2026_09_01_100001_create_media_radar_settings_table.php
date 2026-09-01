<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_radar_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publication_id')->default(1);

            $table->boolean('enabled')->default(true);

            // Master approval switch. false = every candidate waits for a human.
            $table->boolean('auto_approve')->default(false);
            $table->unsignedTinyInteger('auto_approve_min_score')->default(75);
            $table->boolean('auto_publish_after_approval')->default(false);

            // Defaults applied to the published movie record. A published
            // candidate goes live because a human (or the auto-approval switch)
            // already approved it; set to false to land it as a draft instead.
            $table->boolean('default_publish_status')->default(true);
            $table->string('default_movie_access')->default('free');
            $table->unsignedBigInteger('default_plan_id')->nullable();
            $table->boolean('default_is_restricted')->default(false);

            // provider_link or cropped_local
            $table->string('cover_art_mode')->default('provider_link');
            $table->string('cover_crop_ratio')->default('2:3');

            $table->boolean('ai_enabled')->default(true);
            $table->unsignedSmallInteger('candidate_expiration_days')->default(60);

            $table->boolean('youtube_enabled')->default(true);
            $table->boolean('vimeo_enabled')->default(true);

            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_radar_settings');
    }
};
