<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VeeMag core data model (spec v1.0, section 37): the purpose-built
 * Publication -> Issue -> Section hierarchy. Video is stored via the same
 * upload_type/url mechanism the rest of the platform uses, so the player
 * reuses proven playback; a full MediaAsset/transcoding layer is a later phase.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('veemag_publications')) {
            Schema::create('veemag_publications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_id')->nullable();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('logo')->nullable();
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->json('brand_settings')->nullable();
                $table->tinyInteger('status')->default(1); // 1 active, 0 hidden
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('veemag_issues')) {
            Schema::create('veemag_issues', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('publication_id')->index();
                $table->unsignedInteger('volume')->nullable();
                $table->unsignedInteger('issue_number')->nullable();
                $table->string('title');
                $table->string('subtitle')->nullable();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->date('release_date')->nullable();
                $table->string('cover_url')->nullable();
                $table->string('hero_url')->nullable();
                $table->string('hero_type')->default('image'); // image | video
                $table->string('trailer_url')->nullable();
                $table->unsignedInteger('runtime_seconds')->default(0);
                // draft|in_review|changes_requested|approved|scheduled|published|unpublished|archived
                $table->string('status')->default('draft');
                $table->string('visibility')->default('public'); // public | subscribers
                $table->boolean('print_enabled')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('veemag_sections')) {
            Schema::create('veemag_sections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('issue_id')->index();
                // opening|interview|video_article|profile|day_in_the_life|short_documentary
                // |conversation|performance|short|editors_note|last_word|advertisement|custom
                $table->string('type')->default('video_article');
                $table->string('custom_label')->nullable();
                $table->string('title')->nullable();
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('order_index')->default(0);
                $table->unsignedInteger('runtime_seconds')->default(0);
                $table->string('video_upload_type')->nullable(); // YouTube|Vimeo|Local|Embedded
                $table->text('video_url_input')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->string('transition_style')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['issue_id', 'order_index']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('veemag_sections');
        Schema::dropIfExists('veemag_issues');
        Schema::dropIfExists('veemag_publications');
    }
};
