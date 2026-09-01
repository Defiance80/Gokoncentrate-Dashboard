<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazine_issue_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('magazine_issues')->cascadeOnDelete();
            $table->string('asset_type'); // video, article, playlist, external_link, etc.
            $table->unsignedBigInteger('asset_id')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazine_issue_assets');
    }
};
