<?php

namespace App\Providers;

use App\Enums\ConfigKey;
use App\Models\Config as ConfigModel;
use App\Models\Group;
use App\Utils;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        if (! file_exists(base_path('.env'))) {
            file_put_contents(base_path('.env'), file_get_contents(base_path('.env.example')));
            Artisan::call('key:generate');
        }

        if (! $this->isInstalled()) {
            return;
        }

        Config::set('app.name', Utils::config(ConfigKey::AppName));
        Config::set('mail', array_merge(config('mail'), Utils::config(ConfigKey::Mail)->toArray()));

        View::composer('*', function (\Illuminate\View\View $view) {
            /** @var Group|null $group */
            $group = Auth::check()
                ? Auth::user()?->group
                : Group::query()->where('is_guest', true)->first();

            if (is_null($group)) {
                $group = Group::query()->first();
            }

            $view->with([
                '_group' => $group,
                '_is_notice' => strip_tags(Utils::config(ConfigKey::SiteNotice)),
            ]);
        });
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
