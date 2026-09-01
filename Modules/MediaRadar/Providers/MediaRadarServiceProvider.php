<?php

namespace Modules\MediaRadar\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\MediaRadar\Console\ExpireCandidatesCommand;
use Modules\MediaRadar\Console\RunDiscoveryCommand;
use Modules\MediaRadar\Console\WatchTrustedSourcesCommand;
use Modules\MediaRadar\Sources\ProviderManager;

class MediaRadarServiceProvider extends ServiceProvider
{
    protected $moduleName = 'MediaRadar';

    protected $moduleNameLower = 'mediaradar';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(base_path('Modules/MediaRadar/database/migrations'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                RunDiscoveryCommand::class,
                WatchTrustedSourcesCommand::class,
                ExpireCandidatesCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->registerConfig();
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(ProviderManager::class, fn () => new ProviderManager());
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            base_path('Modules/MediaRadar/Config/config.php'),
            $this->moduleNameLower
        );
    }

    public function registerViews(): void
    {
        $sourcePath = base_path('Modules/MediaRadar/Resources/views');

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(base_path('Modules/MediaRadar/lang'), $this->moduleNameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
