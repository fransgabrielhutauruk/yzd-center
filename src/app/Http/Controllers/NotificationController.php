<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Tampilkan seluruh notifikasi pengguna dengan paginasi.
     */
    public function index(Request $request): Response
    {
        $filter = $request->query('filter', 'all');

        $query = $request->user()->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->paginate(15)
            ->withQueryString()
            ->through(function ($notif) {
                return [
                    'id'         => $notif->id,
                    'type'       => $notif->data['type'] ?? 'general',
                    'title'      => $notif->data['title'] ?? 'Notifikasi',
                    'message'    => $notif->data['message'] ?? '',
                    'icon'       => $notif->data['icon'] ?? 'bell',
                    'url'        => $notif->data['url'] ?? null,
                    'is_read'    => $notif->read_at !== null,
                    'created_at' => $notif->created_at->diffForHumans(),
                    'full_date'  => $notif->created_at->translatedFormat('d M Y, H:i'),
                ];
            });

        return Inertia::render('Notifications/Index', [
            'notificationsData' => $notifications,
            'currentFilter'     => $filter,
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if ($notification && $notification->unread()) {
            $notification->markAsRead();
        }

        return back();
    }

    /**
     * Tandai seluruh notifikasi pengguna sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
