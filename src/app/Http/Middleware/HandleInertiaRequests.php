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
                    'id'        => $request->user()->id,
                    'name'      => $request->user()->name,
                    'role'      => $request->user()->role ?? null,
                    'status'    => $request->user()->status ?? null,
                    'is_active' => (bool) ($request->user()->status === 'active'),
                    'email'     => $request->user()->email,
                    'username'  => $request->user()->username,
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
            'notifications' => function () use ($request) {
                if (!$request->user()) {
                    return null;
                }

                $user = $request->user();
                $unreadCount = $user->unreadNotifications()->count();
                $recent = $user->notifications()->latest()->take(10)->get()->map(function ($notif) {
                    return [
                        'id'         => $notif->id,
                        'type'       => $notif->data['type'] ?? 'general',
                        'title'      => $notif->data['title'] ?? 'Notifikasi',
                        'message'    => $notif->data['message'] ?? '',
                        'icon'       => $notif->data['icon'] ?? 'bell',
                        'url'        => $notif->data['url'] ?? null,
                        'is_read'    => $notif->read_at !== null,
                        'created_at' => $notif->created_at->diffForHumans(),
                    ];
                });

                return [
                    'unread_count' => $unreadCount,
                    'items'        => $recent,
                ];
            },
        ];
    }
}
