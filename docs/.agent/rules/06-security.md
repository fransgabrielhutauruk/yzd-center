# Rules 06 — Security & Permission

## Role-Based Authentication

Proyek ini menggunakan **role-based sederhana** via kolom `role` di tabel `users`:

```
role: 'administrator' | 'investor' | 'affiliator'
```

**Tidak menggunakan Spatie Laravel Permission** — cukup dengan middleware dan policy Laravel bawaan.

---

## Struktur Role

| Role | Hak Akses |
|------|-----------|
| `administrator` | Semua fitur admin: manage invitation, verifikasi dokumen, verifikasi copytrade, verifikasi komisi, konten edukasi |
| `investor` | Dashboard portofolio, stepper copytrade, kalkulator, panduan, kamus forex |
| `affiliator` | Semua hak investor + melihat kode referral + laporan komisi |

> **Catatan:** Affiliator adalah investor yang juga punya kode referral. Implementasi: kolom `referral_code` di tabel `users`, nullable untuk investor biasa.

---

## Middleware Authentication

### Setup di `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserRole::class,
    ]);
})
```

### EnsureUserRole Middleware

```php
// app/Http/Middleware/EnsureUserRole.php
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
```

### Penggunaan di Route

```php
// routes/web.php

// Admin only
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('invitations', InvitationController::class);
    // ...
});

// Investor & Affiliator
Route::middleware(['auth', 'role:investor,affiliator'])->prefix('investor')->name('investor.')->group(function () {
    Route::get('/dashboard', [InvestorController::class, 'dashboard'])->name('dashboard');
    // ...
});

// Affiliator only
Route::middleware(['auth', 'role:affiliator'])->prefix('affiliator')->name('affiliator.')->group(function () {
    Route::get('/komisi', [AffiliatorController::class, 'komisi'])->name('komisi');
    // ...
});
```

---

## Helper Role Check

Definisikan di `app/Helpers/helpers.php`:

```php
function is_admin(): bool
{
    return auth()->check() && auth()->user()->role === 'administrator';
}

function is_investor(): bool
{
    return auth()->check() && in_array(auth()->user()->role, ['investor', 'affiliator']);
}

function is_affiliator(): bool
{
    return auth()->check() && auth()->user()->role === 'affiliator';
}
```

---

## Invitation System (Pendaftaran Tertutup)

Berdasarkan SRS: pendaftaran hanya via **Invitation Link satu kali pakai**.

### Aturan Token:
- Token generate menggunakan `Str::uuid()` atau `bin2hex(random_bytes(32))`
- Token disimpan di tabel `invitations` dengan kolom `token`, `used_at` (nullable), `expired_at`
- Token expire setelah **24 jam** (configurable via `config('invitation.expire_hours')`)
- Token hanya bisa dipakai **sekali** — setelah dipakai, set `used_at`
- Jangan hardcode expire time — gunakan config

```php
// Cek token valid
$invitation = Invitation::where('token', $token)
    ->whereNull('used_at')
    ->where('expired_at', '>', now())
    ->firstOrFail();
```

---

## File Upload (Dokumen Legal)

Untuk upload dokumen bermaterai dan foto selfie KTP — **keputusan library diambil saat implementasi**. Aturan yang berlaku sekarang:

- **Jangan** simpan path file sebagai string di kolom DB
- Gunakan relasi morphable atau kolom `disk` + `path` yang terstandar
- Validasi tipe file: hanya PDF dan JPG/PNG
- Validasi ukuran: maksimal 5MB per file
- Simpan di disk `private` — jangan di disk `public` (dokumen sensitif)
- Generate URL via signed URL atau stream controller — jangan expose path langsung

---

## Security Headers

Tambahkan security headers via middleware `SecurityHeaders`:

```php
// Headers wajib
'X-Frame-Options'           => 'DENY',
'X-Content-Type-Options'    => 'nosniff',
'Referrer-Policy'           => 'strict-origin-when-cross-origin',
'X-XSS-Protection'          => '1; mode=block',
```

---

## Aturan Keamanan Umum

```php
// DILARANG: tampilkan .env di output apapun
// DILARANG: log credential atau token
// DILARANG: simpan password plain text

// WAJIB: validasi semua input via Form Request
// WAJIB: gunakan CSRF token untuk semua form POST
// WAJIB: sanitasi output — Inertia/Blade sudah auto-escape, tapi tetap hati-hati dengan {!! !!}

// WAJIB: mass assignment protection
protected $fillable = [...]; // definisikan eksplisit, jangan pakai $guarded = []
```

---

## Defensive Check di Service

Walaupun route sudah dilindungi middleware, service tetap perlu defensive check:

```php
public function verifyInvestor(int $investorId): void
{
    abort_unless(is_admin(), 403, 'Hanya administrator yang dapat memverifikasi investor.');

    $investor = Investor::findOrFail($investorId);
    // ...
}
```
