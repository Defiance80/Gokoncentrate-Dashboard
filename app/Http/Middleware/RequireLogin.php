<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the whole website behind a login.
 *
 * A logged-out visitor cannot reach any page: they are sent to the sign-in
 * screen, which acts as the site's splash page. Applied to the "web" middleware
 * group so a newly added route is private by default and cannot be forgotten.
 *
 * Only the paths in ALWAYS_PUBLIC stay open, and each is there because the site
 * breaks without it, not as a content exception.
 */
class RequireLogin
{
    /**
     * Paths a signed-out visitor must still be able to reach.
     *
     * @var list<string>
     */
    private const ALWAYS_PUBLIC = [
        // --- Subscriber sign-in and sign-up -----------------------------
        'login',
        'login-page',
        'register',
        'store-user',
        'forget-password',
        'otp-login',
        // Social sign-in, OTP submission and the "does this user exist" check.
        'auth/*',
        // Socialite entry points: /login/google, /login/google/callback.
        'login/*',

        // --- Admin sign-in, kept on its own original URL ----------------
        // /admin itself only redirects to /admin/login.
        'admin',
        'admin/login',
        'admin/forgot-password',
        'admin/reset-password/*',

        // First-run installer. Also short-circuited below when the app has not
        // been installed yet.
        'install',
        'install/*',

        // TV sign-in polls this while the viewer authorises the screen.
        'web-qr-status/*',

        // Language switcher, so the sign-in screen can change language.
        'language/*',

        // Mobile app entry points that are opened in a web view.
        'r/magazine/*',

        // The mobile API authenticates with Sanctum, not the web session.
        'api/*',

        // Laravel health check.
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        if ($this->isPublic($request) || ! $this->isInstalled()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => __('frontend.sign_in_required'),
            ], 401);
        }

        // Remember where they were headed so signing in returns them there.
        if ($request->isMethod('GET')) {
            session()->put('url.intended', $request->fullUrl());
        }

        return redirect()->to($this->signInUrlFor($request));
    }

    /**
     * Subscribers and administrators have separate sign-in screens.
     *
     * Anyone heading for the admin dashboard is sent to the admin sign-in on
     * its original URL; everyone else gets the subscriber sign-in, which is the
     * site's front door.
     *
     * routes/auth.php is loaded conditionally, so the named routes may not be
     * registered. The URLs are resolved defensively rather than throwing.
     */
    private function signInUrlFor(Request $request): string
    {
        if (self::isAdminArea($request->path())) {
            return Route::has('admin-login') ? route('admin-login') : url('/admin/login');
        }

        return Route::has('login') ? route('login') : url('/login');
    }

    /**
     * True for the admin dashboard, which keeps its own sign-in screen.
     */
    public static function isAdminArea(string $path): bool
    {
        return Str::is(['app', 'app/*'], ltrim($path, '/'));
    }

    private function isPublic(Request $request): bool
    {
        return self::isPublicPath($request->path());
    }

    /**
     * Exposed so the allowlist can be unit tested: locking out the sign-in
     * screen or the TV sign-in poll would take the whole site down.
     */
    public static function isPublicPath(string $path): bool
    {
        return Str::is(self::ALWAYS_PUBLIC, ltrim($path, '/'));
    }

    /**
     * Mirrors CheckInstallation so a fresh deployment can still reach the
     * installer instead of being bounced to a sign-in page that cannot work.
     */
    private function isInstalled(): bool
    {
        return file_exists(storage_path('installed'));
    }
}
