<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_discovery_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rule_id')->nullable();
            $table->string('provider');
            // queued, running, completed, partial or failed
            $table->string('status')->default('queued');
            // schedule, manual or watchlist
            $table->string('trigger')->default('schedule');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('results_received')->default(0);
            $table->unsignedInteger('new_candidates')->default(0);
            $table->unsignedInteger('existing_candidates')->default(0);
            $table->unsignedInteger('filtered_out')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->unsignedInteger('provider_request_count')->default(0);
            $table->text('error_summary')->nullable();

            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['rule_id', 'created_at']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_discovery_runs');
    }
};
