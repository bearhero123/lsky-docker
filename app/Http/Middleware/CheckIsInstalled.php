<?php

namespace App\Http\Middleware;

use App\Http\Result;
use App\Models\Config as ConfigModel;
use App\Models\Group;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CheckIsInstalled
{
    use Result;

    public function handle(Request $request, Closure $next)
    {
        if (! $this->isInstalled()) {
            if (! $request->expectsJson()) {
                return redirect('install');
            }

            return $this->fail('Application is not installed yet.');
        }

        return $next($request);
    }

    protected function isInstalled(): bool
    {
        if (file_exists(base_path('installed.lock'))) {
            return true;
        }

        try {
            if (! Schema::hasTable('configs') || ! Schema::hasTable('groups')) {
                return false;
            }

            if (! ConfigModel::query()->exists() || ! Group::query()->exists()) {
                return false;
            }

            @file_put_contents(base_path('installed.lock'), '');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
