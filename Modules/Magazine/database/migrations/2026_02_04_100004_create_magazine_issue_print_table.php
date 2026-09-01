<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magazine_issue_print', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('magazine_issues')->cascadeOnDelete();
            $table->boolean('print_enabled')->default(false);
            $table->string('magcloud_product_url')->nullable();
            $table->string('magcloud_viewer_url')->nullable();
            $table->string('cta_label')->default('Order Print Copy');
            $table->text('notes_internal')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magazine_issue_print');
    }
};
