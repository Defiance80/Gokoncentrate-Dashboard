<?php

namespace Modules\PublisherStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatePublisher
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::guard('publisher')->check()) {
            return redirect()->route('studio.login');
        }

        // Keep the publisher guard as the active default for this request
        // so auth() helpers inside studio controllers resolve the publisher.
        Auth::shouldUse('publisher');

        return $next($request);
    }
}
