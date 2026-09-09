# Rules 03 — Arsitektur Kode

## Prinsip Utama: Thin Controller + Fat Service

**Controller** hanya boleh:
1. Menerima request dan memvalidasi input (via Form Request class)
2. Memanggil satu atau beberapa method service
3. Mengembalikan response (Inertia render atau JSON)

**Service** berisi semua business logic:
- Query Eloquent
- Kalkulasi (profit, komisi, proyeksi)
- Transformasi data siap-tampil untuk Vue
- `DB::transaction()`
- Activity log

**Larangan di Controller:**
- Jangan query Eloquent langsung di controller
- Jangan kalkulasi bisnis di controller
- Jangan panggil model langsung selain untuk `findOrFail()` setelah service gagal

---

## Struktur Folder

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/               ← controller autentikasi
│   │   ├── Admin/              ← controller fitur admin
│   │   ├── Investor/           ← controller fitur investor
│   │   └── Affiliator/         ← controller fitur affiliator
│   ├── Middleware/             ← semua middleware custom
│   └── Requests/
│       ├── Auth/               ← form request untuk auth
│       ├── Admin/
│       ├── Investor/
│       └── Affiliator/
├── Models/                     ← semua Eloquent model
├── Services/                   ← business logic
│   ├── AuthService.php
│   ├── InvitationService.php   ← manage invitation link
│   ├── InvestorService.php
│   ├── AffiliatorService.php
│   ├── CommissionService.php   ← kalkulasi komisi (KRITIKAL)
│   ├── ProfitService.php       ← kalkulasi profit (KRITIKAL)
│   └── DocumentService.php     ← upload & manage dokumen legal
├── Helpers/
│   └── helpers.php             ← global helper functions (di-autoload via composer.json)
└── Traits/
    ├── Blameable.php           ← auto-set created_by, updated_by, deleted_by
    └── HasRole.php             ← role check helpers

resources/
├── js/
│   ├── Components/             ← komponen Vue reusable
│   │   ├── Common/             ← komponen umum (Button, Input, Modal, Alert, dll.)
│   │   ├── Admin/              ← komponen khusus admin
│   │   └── Investor/           ← komponen khusus investor
│   ├── Layouts/                ← layout utama
│   │   ├── AdminLayout.vue
│   │   ├── InvestorLayout.vue
│   │   └── AuthLayout.vue
│   ├── Pages/                  ← halaman Inertia (1 file = 1 route)
│   │   ├── Auth/               ← Login.vue, Register.vue
│   │   ├── Admin/
│   │   ├── Investor/
│   │   └── Affiliator/
│   └── composables/            ← Vue composables reusable
│       ├── useToast.js
│       └── useConfirm.js
├── css/
│   └── app.css
└── views/
    └── app.blade.php           ← root template Inertia

routes/
├── web.php                     ← semua web routes
└── console.php                 ← artisan schedule & commands

database/
├── migrations/
├── seeders/
│   ├── DatabaseSeeder.php      ← entry point, panggil semua seeder
│   ├── RoleSeeder.php          ← seed role: Administrator, Investor, Affiliator
│   └── DemoSeeder.php          ← data demo (opsional, jangan di production)
└── factories/
```

---

## Service Layer — Template & Aturan

```php
<?php

namespace App\Services;

use App\Models\Investor;
use Illuminate\Support\Facades\DB;

class InvestorService
{
    /**
     * Buat investor baru dari data registrasi.
     */
    public function create(array $data): Investor
    {
        return DB::transaction(function () use ($data): Investor {
            return Investor::create([
                'user_id' => $data['user_id'],
                'modal'   => $data['modal'],
            ]);
        });
    }

    /**
     * Hitung data tampilan untuk dashboard investor.
     * Return array siap-tampil — kalkulasi dilakukan di sini, bukan di Vue.
     */
    public function getDashboardData(int $userId): array
    {
        $investor = Investor::where('user_id', $userId)->firstOrFail();

        return [
            'modal_raw'            => $investor->modal,
            'modal_label'          => 'Rp ' . number_format($investor->modal, 0, ',', '.'),
            'profit_pct_label'     => '20%',
            'performance_fee_pct'  => '25%',
            'operasional_pct'      => '5%',
            'compounding_pct'      => '50%',
        ];
    }
}
```

### Aturan Service:
- Satu service per domain (InvestorService, CommissionService, dst.)
- Method name: verb + noun (`getDashboardData`, `createInvitation`, `calculateCommission`)
- Selalu gunakan `DB::transaction()` untuk operasi yang menyentuh > 1 tabel
- Lemparkan exception jika ada error — biarkan controller yang handle HTTP response
- Constructor injection untuk dependency (jangan `new Service()` di dalam method)

---

## Cross-Domain Access

Jangan query model dari domain lain secara langsung. Inject service-nya.

```php
// BENAR: inject service domain lain
class CommissionService
{
    public function __construct(
        private readonly InvestorService $investorService
    ) {}

    public function calculate(int $investorId): array
    {
        $data = $this->investorService->getModalData($investorId);
        // hitung komisi berdasarkan tiering modal...
    }
}

// SALAH: langsung ke model domain lain
class CommissionService
{
    public function calculate(int $investorId): array
    {
        $investor = Investor::find($investorId); // coupling langsung
    }
}
```

---

## Response Pattern

### Inertia Response (web route):

```php
// Render halaman Vue
return inertia('Investor/Dashboard', [
    'dashboard' => $this->investorService->getDashboardData($user->investor_id),
]);

// Redirect setelah form submit
return redirect()->route('investor.dashboard')
    ->with('success', 'Data berhasil disimpan.');
```

### JSON Response (API endpoint):

```php
// Success
return response()->json([
    'success' => true,
    'message' => 'Data berhasil disimpan.',
    'data'    => $resource,   // optional
]);

// Error validation
return response()->json([
    'success' => false,
    'message' => 'Gagal menyimpan data.',
    'errors'  => $validator->errors(),
], 422);
```

---

## View / Vue — Batasan

- **DILARANG** memanggil API atau melakukan fetch dari dalam Vue untuk data awal halaman
  (gunakan Inertia props — data dikirim saat render pertama)
- **DILARANG** melakukan kalkulasi bisnis di Vue (profit, komisi, tiering modal)
- **DILARANG** melakukan format tanggal/angka di Vue jika bisa dilakukan di service (gunakan `*_label` pattern)
- **BOLEH** di Vue: format string sederhana, state UI (loading, open/close modal), animasi

---

## Shared Data (HandleInertiaRequests)

Data yang dibutuhkan di semua halaman dikirim via `HandleInertiaRequests` middleware:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'role'  => $request->user()->role,  // 'administrator'|'investor'|'affiliator'
                'email' => $request->user()->email,
            ] : null,
        ],
        'flash' => [
            'success' => session('success'),
            'error'   => session('error'),
        ],
    ];
}
```
