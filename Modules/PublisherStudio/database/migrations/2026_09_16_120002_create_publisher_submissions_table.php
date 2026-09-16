<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publisher_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publisher_id');
            // veemag | podcast | short_film
            $table->string('type')->default('veemag');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('category')->nullable();
            // Type-specific structured data (sections/episodes, media links, etc.)
            $table->longText('payload')->nullable();
            // draft | submitted | approved | rejected | changes_requested
            $table->string('status')->default('draft');
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            // Reference to the content record created on approval (e.g. veemag issue id).
            $table->string('published_ref_type')->nullable();
            $table->unsignedBigInteger('published_ref_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('publisher_id');
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publisher_submissions');
    }
};
