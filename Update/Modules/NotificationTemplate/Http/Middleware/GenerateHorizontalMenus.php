<?php

namespace Modules\NotificationTemplate\Http\Middleware;

use App\Trait\HorizontalMenu;
use Closure;
use Illuminate\Http\Request;

class GenerateHorizontalMenus
{
    use HorizontalMenu;

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

    }
}
