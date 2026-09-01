<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_candidate_analysis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedSmallInteger('analysis_version')->default(1);

            $table->unsignedTinyInteger('editorial_score')->nullable();
            $table->unsignedTinyInteger('relevance_score')->nullable();
            $table->unsignedTinyInteger('source_quality_score')->nullable();
            $table->unsignedTinyInteger('production_quality_score')->nullable();
            $table->unsignedTinyInteger('recency_score')->nullable();
            $table->unsignedTinyInteger('audience_interest_score')->nullable();
            $table->unsignedTinyInteger('originality_score')->nullable();
            $table->unsignedTinyInteger('brand_fit_score')->nullable();

            $table->unsignedBigInteger('suggested_genre_id')->nullable();
            $table->json('secondary_genre_ids')->nullable();

            $table->text('suggested_title')->nullable();
            $table->longText('suggested_description')->nullable();
            $table->text('suggested_summary')->nullable();

            $table->json('suggested_tags_json')->nullable();
            $table->json('topic_entities_json')->nullable();
            $table->json('risk_flags_json')->nullable();

            $table->longText('explanation')->nullable();
            $table->string('model_reference')->nullable();
            // deterministic, ai or ai_failed
            $table->string('source')->default('deterministic');

            $table->timestamps();

            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_candidate_analysis');
    }
};
