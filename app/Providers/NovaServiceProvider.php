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
        Nova::userMenu(function (Request $request, Menu $menu) {
            $menu->prepend(
                MenuItem::make(
                    '中文 ⇋ English',
                    "/switch/language"
                )->withBadge('⚐', 'info')
            );
            // dd($request->getPreferredLanguage());// en_US
            return $menu;
        });
        $key = 'preferred_language';
        $preferredLanguage = Cookie::get($key);
        app()->setLocale($preferredLanguage);
        // dd($preferredLanguage);

        // navigator.language
            // en-US
            // zh-CN
            // zh-TW
            
        // $preferredLanguage = auth()->user()->getMeta('preferred_language', 'en');
        
        // dd(app()->getLocale());//en
        // Nova::userLocale(fn()=>'en');
        parent::boot();
        Nova::withBreadcrumbs();
        Nova::withoutNotificationCenter();
        Nova::footer(function ($request) {
            return Blade::render('<p class="text-center">© 2024 <a href="https://729ly.net">良友电台</a><span class="hidden">Created by www.yilindeli.com</span></p>');
        });
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
        return [];
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
