<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_blocked_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publication_id')->default(1);
            $table->string('provider');
            $table->string('provider_creator_id');
            $table->string('creator_name')->nullable();
            $table->string('reason')->nullable();
            $table->unsignedInteger('blocked_by')->nullable();
            $table->timestamps();

            $table->unique(['publication_id', 'provider', 'provider_creator_id'], 'media_blocked_sources_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_blocked_sources');
    }
};
