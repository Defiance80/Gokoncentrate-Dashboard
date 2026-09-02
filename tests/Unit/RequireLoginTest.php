<?php

namespace Tests\Unit;

use App\Http\Middleware\RequireLogin;
use PHPUnit\Framework\TestCase;

/**
 * The whole site is private, so the allowlist is the only thing standing
 * between a visitor and a redirect loop. These lock it down.
 */
class RequireLoginTest extends TestCase
{
    /**
     * @dataProvider publicPaths
     */
    public function test_paths_that_must_stay_reachable_when_signed_out(string $path): void
    {
        $this->assertTrue(
            RequireLogin::isPublicPath($path),
            $path . ' must be reachable without a session, or visitors cannot sign in.'
        );
    }

    public static function publicPaths(): array
    {
        return [
            'login' => ['login'],
            'login page' => ['login-page'],
            'register' => ['register'],
            'register submit' => ['store-user'],
            'forgot password' => ['forget-password'],
            'otp login' => ['otp-login'],
            'otp submit' => ['auth/otp-login-store'],
            'user exists check' => ['auth/check-user-exists'],
            'google redirect' => ['auth/google'],
            'google callback' => ['auth/google/callback'],
            'apple callback' => ['auth/apple/callback'],
            'admin shortcut' => ['admin'],
            'admin login' => ['admin/login'],
            'admin forgot password' => ['admin/forgot-password'],
            'admin reset password' => ['admin/reset-password/tok3n'],
            'socialite provider' => ['login/google'],
            'socialite callback' => ['login/google/callback'],
            'installer' => ['install'],
            'installer step' => ['install/environment'],
            'tv qr poll' => ['web-qr-status/abc123'],
            'language switch' => ['language/fr'],
            'magazine print redirect' => ['r/magazine/12/print'],
            'mobile api' => ['api/me'],
            'health check' => ['up'],
            'leading slash tolerated' => ['/login-page'],
        ];
    }

    /**
     * @dataProvider privatePaths
     */
    public function test_content_paths_are_gated(string $path): void
    {
        $this->assertFalse(
            RequireLogin::isPublicPath($path),
            $path . ' must require a login.'
        );
    }

    public static function privatePaths(): array
    {
        return [
            'home' => [''],
            'movies' => ['movies'],
            'movie detail' => ['movie-details/42'],
            'tv shows' => ['tv-shows'],
            'tvshow detail' => ['tvshow-details/7'],
            'videos' => ['videos'],
            'video detail' => ['video-details/9'],
            'live tv' => ['livetv'],
            'search' => ['search'],
            'cast list' => ['castcrew-list'],
            'faq' => ['faq'],
            'coming soon' => ['comingsoon'],
            'watchlist' => ['watch-list'],
            'subscription plan' => ['subscription-plan'],
            'account settings' => ['account-setting'],
            'admin dashboard' => ['app/dashboard'],
            'admin-ish content path' => ['administrators'],
            'admin sub path' => ['admin/users'],
            'media radar' => ['app/media-radar'],
            'stream' => ['video/stream/abc'],
        ];
    }

    public function test_the_allowlist_does_not_open_a_wildcard_over_everything(): void
    {
        foreach (['*', '**', ''] as $dangerous) {
            $this->assertNotContains(
                $dangerous,
                $this->allowlist(),
                'A catch-all pattern in the allowlist would leave the site public.'
            );
        }
    }

    public function test_auth_wildcard_does_not_leak_content_routes(): void
    {
        // "auth/*" must not accidentally match something like "authors/list".
        $this->assertFalse(RequireLogin::isPublicPath('authors/list'));
        $this->assertFalse(RequireLogin::isPublicPath('auth'));
    }

    /**
     * @dataProvider adminAreaPaths
     */
    public function test_admin_dashboard_paths_route_to_the_admin_sign_in(string $path): void
    {
        $this->assertTrue(
            RequireLogin::isAdminArea($path),
            $path . ' should send a signed-out visitor to the admin sign-in, not the subscriber one.'
        );
    }

    public static function adminAreaPaths(): array
    {
        return [
            'dashboard' => ['app/dashboard'],
            'media radar' => ['app/media-radar'],
            'settings' => ['app/settings'],
            'bare app' => ['app'],
        ];
    }

    /**
     * @dataProvider subscriberAreaPaths
     */
    public function test_front_end_paths_route_to_the_subscriber_sign_in(string $path): void
    {
        $this->assertFalse(
            RequireLogin::isAdminArea($path),
            $path . ' should send a signed-out visitor to the subscriber sign-in.'
        );
    }

    public static function subscriberAreaPaths(): array
    {
        return [
            'home' => [''],
            'movies' => ['movies'],
            'videos' => ['videos'],
            'watchlist' => ['watch-list'],
            'account' => ['account-setting'],
            // Must not be mistaken for the admin area.
            'apple auth' => ['auth/apple'],
            'application-ish path' => ['applications'],
        ];
    }

    /**
     * @return list<string>
     */
    private function allowlist(): array
    {
        $reflection = new \ReflectionClass(RequireLogin::class);

        return $reflection->getConstant('ALWAYS_PUBLIC');
    }
}
