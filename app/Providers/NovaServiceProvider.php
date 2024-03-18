<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuItem;
use Illuminate\Support\Facades\Blade;
use Cookie;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
        // Nova::withBreadcrumbs();
        Nova::withoutNotificationCenter();
        Nova::withoutThemeSwitcher();
        Nova::footer(function ($request) {
            return Blade::render('<p class="text-center">&copy; {!! $year !!} <a href="https://729ly.net">良友电台</a> · v{!! $version !!}<span class="hidden">Created by dale404200@gmail.com</span></p>', [
            'version' => Nova::version(),
            'year' => date('Y')]);
        });
        Nova::initialPath('/pulse');
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                // ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return in_array($user->email, [
                'admin@admin.com',
            ]) || Str::of($user->email)->endsWith([
                '@febchk.org'
            ]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            new \Badinansoft\LanguageSwitch\LanguageSwitch(),
            new \Xiangkeguo\Pulse\Pulse(),
            // new \Anaseqal\NovaSidebarIcons\NovaSidebarIcons,
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
