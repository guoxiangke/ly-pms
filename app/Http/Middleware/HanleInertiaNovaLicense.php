<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Nova\Http\Resources\UserResource;
use Laravel\Nova\Http\Middleware\HandleInertiaRequests;
use Laravel\Nova\Nova;

class HanleInertiaNovaLicense extends HandleInertiaRequests
{
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'validLicense' => function () {
                return true;
            },
        ]);
    }
}
