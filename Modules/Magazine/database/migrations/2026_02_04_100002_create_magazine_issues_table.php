<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazine_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('magazine_id')->constrained('magazines')->cascadeOnDelete();
            $table->string('issue_number')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->date('release_date')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->unsignedBigInteger('trailer_video_id')->nullable();
            $table->text('summary')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, published
            $table->string('visibility')->default('public'); // public, subscribers, paid-tier
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['magazine_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazine_issues');
    }
};
