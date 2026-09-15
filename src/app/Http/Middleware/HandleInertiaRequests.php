<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            //
            'auth' => [
                'user' => $request->user() ? [
                    'id'    => $request->user()->id,
                    'name'  => $request->user()->name,
                    'role'  => $request->user()->role ?? null,
                    'email' => $request->user()->email,
                ] : null,
            ],
            'flash' => [
                'success'              => session('success'),
                'error'                => session('error'),
                'registration_success' => session('registration_success'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'locale' => function () {
                $locale = app()->getLocale();
                // If translations exist in lang directory, load them. Fallback to basic if not.
                $path = base_path("lang/{$locale}.json");

                return [
                    'current'      => $locale,
                    'translations' => file_exists($path) ? json_decode(file_get_contents($path), true) : [],
                ];
            },
        ];
    }
}
