<?php

namespace App\Http\Middleware;

use App\Enums\ConfigKey;
use App\Http\Result;
use App\Utils;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIsEnableGuestUpload
{
    use Result;

    public function handle(Request $request, Closure $next)
    {
        // First-time install has no config records yet.
        if (! file_exists(base_path('installed.lock'))) {
            return $next($request);
        }

        if (! Utils::config(ConfigKey::IsAllowGuestUpload) && Auth::guest()) {
            return redirect('login');
        }

        return $next($request);
    }
}
