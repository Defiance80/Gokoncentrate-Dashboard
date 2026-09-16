<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            // pending = awaiting admin approval to publish, approved = can submit,
            // suspended = blocked. Publishers may register but stay pending.
            $table->string('status')->default('pending'); // pending, approved, suspended
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishers');
    }
};
