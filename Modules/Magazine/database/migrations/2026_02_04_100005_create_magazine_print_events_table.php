<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazine_print_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('issue_id')->constrained('magazine_issues')->cascadeOnDelete();
            $table->string('event'); // click_print, return_from_magcloud, copied_link
            $table->string('session_id')->nullable();
            $table->string('device')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazine_print_events');
    }
};
