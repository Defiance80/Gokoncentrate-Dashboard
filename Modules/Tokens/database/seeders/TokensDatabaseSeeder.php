<?php

namespace Modules\Tokens\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Tokens\Models\TokenSetting;

class TokensDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default token settings
        TokenSetting::firstOrCreate(
            ['id' => 1],
            [
                'token_label' => 'KT',
                'token_name' => 'GoKoncentrate Tokens',
                'token_usd_cents_per_token' => 100,
                'earn_cents' => 1,
                'earn_seconds' => 10,
                'daily_cap_cents' => null,
                'global_enabled' => true,
                'repeat_cooldown_seconds' => 0,
                'eligible_content_flags' => [
                    'free_video' => true,
                    'free_magazine' => true,
                    'focus_mode' => true,
                ],
            ]
        );

        $this->command->info('Token settings seeded successfully.');
    }
}
