<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restores the "clips" feature that a template update referenced but never
 * fully installed: the clips table, and the enable_clips flag on entertainments
 * (videos already has it). Columns match the existing Clip::create() calls.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('clips')) {
            Schema::create('clips', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('content_id')->index();
                $table->string('content_type')->default('video'); // video | movie | tv_show
                $table->string('type')->nullable();               // Local | Embedded | YouTube | Vimeo
                $table->text('url')->nullable();
                $table->string('poster_url')->nullable();
                $table->string('tv_poster_url')->nullable();
                $table->string('title')->nullable();
                $table->timestamps();

                $table->index(['content_id', 'content_type']);
            });
        }

        if (Schema::hasTable('entertainments') && ! Schema::hasColumn('entertainments', 'enable_clips')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->boolean('enable_clips')->default(0)->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clips');
        if (Schema::hasTable('entertainments') && Schema::hasColumn('entertainments', 'enable_clips')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->dropColumn('enable_clips');
            });
        }
    }
};
