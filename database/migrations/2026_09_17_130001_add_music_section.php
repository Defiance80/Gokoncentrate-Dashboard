<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Music section: single music videos (movie engine) flagged is_music, shown in
 * their own section alongside Media Series. Music sub-genres (Hip Hop, R&B, ...)
 * are genres flagged is_music_genre so the importer can offer them separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entertainments') && ! Schema::hasColumn('entertainments', 'is_music')) {
            Schema::table('entertainments', function (Blueprint $table) {
                $table->boolean('is_music')->default(0)->after('is_podcast');
            });
        }
        if (Schema::hasTable('genres') && ! Schema::hasColumn('genres', 'is_music_genre')) {
            Schema::table('genres', function (Blueprint $table) {
                $table->boolean('is_music_genre')->default(0)->after('is_primary');
            });
        }

        $music = ['Hip Hop', 'R&B', 'Rock & Roll', 'Jazz', 'Country', 'Soul', 'Punk', 'Funk',
            'Alternative', 'West Coast Rap', 'Reggae', 'Pop', 'Blues', 'EDM'];
        foreach ($music as $name) {
            $existing = DB::table('genres')->whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if ($existing) {
                DB::table('genres')->where('id', $existing->id)->update(['is_music_genre' => 1]);
            } else {
                DB::table('genres')->insert([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'status' => 1,
                    'is_music_genre' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entertainments', 'is_music')) {
            Schema::table('entertainments', fn (Blueprint $t) => $t->dropColumn('is_music'));
        }
        if (Schema::hasColumn('genres', 'is_music_genre')) {
            Schema::table('genres', fn (Blueprint $t) => $t->dropColumn('is_music_genre'));
        }
    }
};
