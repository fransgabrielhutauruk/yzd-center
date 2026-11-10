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
                    'id'                       => $request->user()->id,
                    'name'                     => $request->user()->name,
                    'role'                     => $request->user()->role ?? null,
                    'status'                   => $request->user()->status ?? null,
                    'is_active'                => (bool) ($request->user()->status === 'active'),
                    'email'                    => $request->user()->email,
                    'username'                 => $request->user()->username,
                    'pending_master_fee_count' => $request->user()->role === 'member'
                        ? \App\Models\LaporanProfitBulanan::query()
                            ->where('user_id', $request->user()->id)
                            ->whereIn('status', [\App\Models\LaporanProfitBulanan::STATUS_BELUM_DIBAYAR, \App\Models\LaporanProfitBulanan::STATUS_DITOLAK])
                            ->count()
                        : 0,
                    'waiting_master_fee_count' => $request->user()->role === 'administrator'
                        ? \App\Models\LaporanProfitBulanan::query()
                            ->where('status', \App\Models\LaporanProfitBulanan::STATUS_MENUNGGU_VERIFIKASI)
                            ->count()
                        : 0,
                ] : null,
            ],
            'first_period_notice' => function () use ($request) {
                if (!$request->user() || $request->user()->role !== 'member') {
                    return null;
                }
                if ($request->session()->get('first_period_notice_dismissed')) {
                    return null;
                }

                $activeAccounts = \App\Models\AkunCopytrade::query()
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->whereNotNull('tanggal_mulai')
                    ->get();

                $inFirstPeriod = $activeAccounts->filter(fn ($a) => \App\Support\ProfitPeriod::isInFirstPeriod($a));
                if ($inFirstPeriod->isEmpty()) {
                    return null;
                }

                // Urutkan berdasarkan periode akhir paling awal
                $earliest = $inFirstPeriod->sortBy(function ($a) {
                    return \App\Support\ProfitPeriod::firstPeriodEnd(\Carbon\Carbon::parse($a->tanggal_mulai));
                })->first();

                $mulai = \Carbon\Carbon::parse($earliest->tanggal_mulai)->startOfDay();
                $p1End = \App\Support\ProfitPeriod::firstPeriodEnd($mulai);
                $openDate = \App\Support\ProfitPeriod::openReportDate($p1End);
                $otherCount = $inFirstPeriod->count() - 1;

                return [
                    'akun_id'       => $earliest->id,
                    'nomor_akun'    => $earliest->nomor_akun_trading,
                    'tanggal_mulai' => function_exists('tanggal_indo') ? tanggal_indo($mulai) : $mulai->toDateString(),
                    'periode_akhir' => function_exists('tanggal_indo') ? tanggal_indo($p1End) : $p1End->toDateString(),
                    'input_dibuka'  => function_exists('tanggal_indo') ? tanggal_indo($openDate) : $openDate->toDateString(),
                    'other_count'   => $otherCount,
                    'aturan'        => 'Mulai copy tgl 1–15 = s/d akhir bulan itu; tgl 16–31 = s/d akhir bulan berikutnya.',
                ];
            },
            'flash' => fn () => [
                'success'              => $request->session()->get('success'),
                'error'                => $request->session()->get('error'),
                'warning'              => $request->session()->get('warning'),
                'info'                 => $request->session()->get('info'),
                'registration_success' => $request->session()->get('registration_success'),
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
