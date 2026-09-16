<?php

namespace Modules\PublisherStudio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfPublisher
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('publisher')->check()) {
            return redirect()->route('studio.dashboard');
        }

        return $next($request);
    }
}
