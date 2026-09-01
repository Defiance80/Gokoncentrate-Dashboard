<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_candidate_rule_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('run_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->json('matched_terms')->nullable();
            $table->json('match_metadata_json')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'rule_id'], 'media_candidate_rule_unique');
            $table->index('rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_candidate_rule_matches');
    }
};
