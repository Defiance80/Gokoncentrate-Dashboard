<?php

namespace Modules\Onboarding\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Onboarding\Models\Onboarding;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;


class OnboardingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        $onboardings = [
            [
                'title' => 'Watch on any device: Enjoy our content wherever you go!',
                'file_url' =>  '/dummy-images/onboarding/walk_image1.png',
                'description' => 'Stream across all devices without extra charges.',
                'status' => 1,

            ],
            [
                'title' => 'Download and Go: Access Your Content Anywhere, Anytime, on Any Device',
                'file_url' => '/dummy-images/onboarding/walk_image2.png',
                'description' => 'Download & enjoy content on the go, anywhere, anytime.',
                'status' => 1,
            ],

            [
                'title' => 'Enjoy Freedom Without Commitments or Hassles - Join Us Today!',
                'file_url' => '/dummy-images/onboarding/walk_image3.png',
                'description' => 'Join us hassle-free and no contracts required.',
                'status' => 1,
            ],



        ];

        if (env('IS_DUMMY_DATA')) {
            foreach ($onboardings as $onboardingsData) {
                $posterPath = $onboardingsData['file_url'] ?? null;

                $onboarding = Onboarding::create(Arr::except($onboardingsData, ['file_url']));
                if (isset($posterPath)) {
                    $onboardingUrl = $this->uploadToSpaces($posterPath);
                    if ($onboardingUrl) {
                        $onboarding->file_url = extractFileNameFromUrl($onboardingUrl,'onboarding');
                    }
                }

                $onboarding->save();
            }

            Schema::enableForeignKeyConstraints();
        }
    }

    private function uploadToSpaces($publicPath)
    {
        $localFilePath = public_path($publicPath);
        $remoteFilePath = 'onboarding/image/' . basename($publicPath);

        if (file_exists($localFilePath)) {
            // Get the active storage disk from the environment
            $disk = env('ACTIVE_STORAGE', 'local');

            if ($disk === 'local') {
                // Store in the public directory for local storage
                Storage::disk($disk)->put('public/' . $remoteFilePath, file_get_contents($localFilePath));
                return asset('storage/' . $remoteFilePath);
            } else {
                // Upload to the specified storage disk
                Storage::disk($disk)->put($remoteFilePath, file_get_contents($localFilePath));
                return Storage::disk($disk)->url($remoteFilePath);
            }
        }

        return false;
    }
}
