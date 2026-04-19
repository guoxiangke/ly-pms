<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

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
        Nova::initialPath('/resources/lts-items');
        Nova::withoutGlobalSearch();
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

        // nova-sortable 路由需要触发 ServingNova 事件来注册资源
        Route::matched(function (\Illuminate\Routing\Events\RouteMatched $event) {
            if (str_starts_with($event->route->uri(), 'nova-vendor/nova-sortable')) {
                Nova::serving(function () {});
                event(new \Laravel\Nova\Events\ServingNova($event->request));
            }
        });
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
            return $user->id == 1 || in_array($user->email, [
                'admin@admin.com',
            ]) || Str::of($user->email)->endsWith([
                '@febchk.org',
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
            // new \App\Nova\Dashboards\Main,
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
        Nova::sortResourcesBy(function ($resource) {
            return $resource::$priority ?? 99999;
        });
    }
}
