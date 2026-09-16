<?php

namespace Modules\PublisherStudio\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\PublisherStudio\Models\Publisher;

class PublisherStudioServiceProvider extends ServiceProvider
{
    protected $moduleName = 'PublisherStudio';

    protected $moduleNameLower = 'publisherstudio';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(base_path('Modules/PublisherStudio/database/migrations'));

        // Register the separate "publisher" auth guard at runtime so the
        // Publisher Studio login is fully isolated from the admin/user guard.
        // Non-destructive: no edit to config/auth.php required.
        Config::set('auth.guards.publisher', [
            'driver'   => 'session',
            'provider' => 'publishers',
        ]);
        Config::set('auth.providers.publishers', [
            'driver' => 'eloquent',
            'model'  => Publisher::class,
        ]);
        Config::set('auth.passwords.publishers', [
            'provider' => 'publishers',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ]);
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        // Route-scoped middleware aliases used by the studio routes.
        $router = $this->app['router'];
        $router->aliasMiddleware('publisher.auth', \Modules\PublisherStudio\Http\Middleware\AuthenticatePublisher::class);
        $router->aliasMiddleware('publisher.guest', \Modules\PublisherStudio\Http\Middleware\RedirectIfPublisher::class);
    }

    public function registerViews(): void
    {
        $sourcePath = base_path('Modules/PublisherStudio/Resources/views');
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    public function registerTranslations(): void
    {
        $this->loadTranslationsFrom(base_path('Modules/PublisherStudio/lang'), $this->moduleNameLower);
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }
        return $paths;
    }
}
