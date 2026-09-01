<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_trusted_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('publication_id')->default(1);
            $table->string('provider');
            $table->string('provider_creator_id');
            $table->string('creator_name')->nullable();
            $table->text('creator_url')->nullable();

            $table->boolean('enabled')->default(true);

            $table->unsignedBigInteger('default_genre_id')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('auto_analyze')->default(true);

            // approval_required, auto_publish or discovery_only
            $table->string('publishing_mode')->default('approval_required');

            $table->unsignedInteger('approval_count')->default(0);
            $table->unsignedInteger('rejection_count')->default(0);

            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['publication_id', 'provider', 'provider_creator_id'], 'media_trusted_sources_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_trusted_sources');
    }
};
