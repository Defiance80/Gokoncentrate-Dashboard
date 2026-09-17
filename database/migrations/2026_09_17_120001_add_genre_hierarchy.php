<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Two-tier genres: top-level "type" genres (Drama, Action, Horror, ...) are the
 * primary genre; the existing granular genres (Interview, Lifecast, ...) become
 * sub-genres. `is_primary` distinguishes the two tiers; `parent_id` allows true
 * nesting later.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('genres')) {
            return;
        }
        Schema::table('genres', function (Blueprint $table) {
            if (! Schema::hasColumn('genres', 'is_primary')) {
                $table->boolean('is_primary')->default(0)->after('status');
            }
            if (! Schema::hasColumn('genres', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('is_primary');
            }
        });

        // Seed the top-level type genres (idempotent).
        $types = ['Drama', 'Documentary', 'Action', 'Horror', 'Sci-Fi', 'Romance', 'Thriller', 'Suspense'];
        foreach ($types as $name) {
            $exists = DB::table('genres')->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($exists) {
                DB::table('genres')->where('id', $exists->id)->update(['is_primary' => 1]);
            } else {
                DB::table('genres')->insert([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'status' => 1,
                    'is_primary' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('genres')) {
            Schema::table('genres', function (Blueprint $table) {
                foreach (['is_primary', 'parent_id'] as $c) {
                    if (Schema::hasColumn('genres', $c)) {
                        $table->dropColumn($c);
                    }
                }
            });
        }
    }
};
