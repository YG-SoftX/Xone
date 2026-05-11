<?php

namespace App\Http\Middleware;

use App\Services\ThemeService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],
            'csrf_token' => csrf_token(),
            'theme' => ThemeService::getTheme('account'),
            'theme_css' => ThemeService::getCssVariables('account'),
        ]);
    }
}
