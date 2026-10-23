<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two additions for the VeeMag reader:
 *
 * 1. veemag_sections.synopsis - a short blurb shown under the player while a
 *    section plays. Kept separate from `description` so generating one can
 *    never overwrite copy a publisher wrote by hand.
 *
 * 2. veemag_leads - advertiser enquiries raised from the house ad slots in
 *    the reader. Stored as well as emailed, so an enquiry is never lost to a
 *    mail failure.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('veemag_sections', 'synopsis')) {
            Schema::table('veemag_sections', function (Blueprint $table) {
                $table->string('synopsis', 600)->nullable()->after('description');
            });
        }

        if (! Schema::hasTable('veemag_leads')) {
            Schema::create('veemag_leads', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->string('business_name', 191)->nullable();
                $table->string('phone', 64)->nullable();
                $table->string('address', 500)->nullable();
                // Where the enquiry came from, so we can tell which slot works.
                $table->unsignedBigInteger('issue_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->string('placement', 64)->nullable();
                $table->string('source', 32)->default('app');
                $table->boolean('mail_sent')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('veemag_sections', 'synopsis')) {
            Schema::table('veemag_sections', function (Blueprint $table) {
                $table->dropColumn('synopsis');
            });
        }

        Schema::dropIfExists('veemag_leads');
    }
};
