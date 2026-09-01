<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_editorial_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_id');
            $table->unsignedInteger('editor_id')->nullable();

            $table->string('decision');
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();

            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_editorial_decisions');
    }
};
