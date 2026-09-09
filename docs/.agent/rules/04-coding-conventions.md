# Rules 04 — Coding Conventions

## PHP Conventions

### Wajib Diikuti

```php
// 1. Constructor property promotion
class InvestorService
{
    public function __construct(
        private readonly CommissionService $commissionService,
        private readonly ProfitService $profitService,
    ) {}
}

// 2. Explicit return type — semua method wajib punya return type
public function calculate(int $investorId): array { }
public function find(int $id): ?Investor { }
public function create(array $data): Investor { }
public function delete(int $id): void { }
public function exists(string $email): bool { }

// 3. Type hints untuk semua parameter
public function update(int $id, array $data, bool $notify = false): Investor { }

// 4. Nullable type untuk yang bisa null
public function findByEmail(string $email): ?User { }

// 5. Curly braces wajib untuk semua control structure
if ($condition) {
    // bahkan untuk 1 baris
}

// 6. match daripada switch untuk mapping sederhana
$tier = match(true) {
    $modal >= 1_000_000_000 => 'premium',
    $modal >= 100_000_000   => 'standard',
    default                 => 'basic',
};
```

### PHPDoc — Kapan Dipakai

```php
// Cukup gunakan PHPDoc untuk:
// - Method yang tidak jelas dari nama/signature-nya
// - Parameter array yang kompleks (gunakan array shape)
// - Return type yang kompleks

/**
 * Hitung pembagian profit sesuai aturan bisnis.
 *
 * @param  array{modal: int, profit_gross: int}  $data
 * @return array{investor: int, master_trader: int, operasional: int, compounding: int}
 */
public function splitProfit(array $data): array { }
```

### Import / `use` Statement

```php
// Selalu gunakan import, jangan inline FQCN
use App\Services\CommissionService;
use App\Models\Investor;

// Kecuali: ada konflik nama class
use App\Models\User as UserModel;
```

---

## Naming Convention

| Item | Convention | Contoh |
|------|-----------|--------|
| Model | PascalCase singular | `User`, `Investor`, `Affiliator`, `Invitation` |
| Controller | PascalCase + Controller | `InvestorController`, `InvitationController` |
| Service | PascalCase + Service | `InvestorService`, `CommissionService` |
| Form Request | PascalCase + Request | `StoreInvestorRequest`, `UpdateProfileRequest` |
| Middleware | PascalCase | `EnsureInvestorVerified`, `RedirectIfAuthenticated` |
| Trait | PascalCase | `Blameable`, `HasRole` |
| Route name | dot.case | `investor.dashboard`, `admin.investor.verify` |
| Permission (role check) | string enum | `'administrator'`, `'investor'`, `'affiliator'` |
| Tabel | snake_case plural | `users`, `investors`, `invitations`, `copytrade_steps` |
| Primary key | `{entity}_id` | `investor_id`, `invitation_id` |
| Kolom boolean | `is_` prefix | `is_active`, `is_verified`, `is_expired` |
| Kolom tanggal | `_at` suffix | `verified_at`, `expired_at`, `followed_at` |
| Migration | `{timestamp}_{action}_{table}` | `2026_09_08_000001_create_investors_table.php` |
| Vue component | PascalCase | `InvestorCard.vue`, `CommissionTable.vue` |
| Vue page | PascalCase | `Dashboard.vue`, `CopytradeStepper.vue` |
| Vue composable | camelCase + `use` prefix | `useToast.js`, `useConfirm.js` |
| CSS class (custom) | kebab-case | `copytrade-step`, `profit-badge` |
| JS variable | camelCase | `formData`, `selectedStep`, `isLoading` |
| Env variable | SCREAMING_SNAKE | `APP_KEY`, `INVITATION_EXPIRE_HOURS` |

---

## View Payload Pattern

Data yang dikirim dari service ke Vue harus **siap tampil**. Gunakan konvensi suffix berikut:

| Suffix | Tujuan | Contoh |
|--------|--------|--------|
| `*_raw` | Nilai asli untuk perhitungan di JS (jika diperlukan) | `modal_raw: 5000000` |
| `*_label` | String siap tampil di UI | `modal_label: "Rp 5.000.000"` |
| `*_count` | Integer untuk badge/plural | `investor_count: 12` |
| `*_url` | URL siap pakai | `document_url: "https://..."` |
| `*_at` | Timestamp raw (ISO string) | `created_at: "2026-09-08T10:00:00Z"` |
| `*_at_label` | Tanggal/waktu sudah diformat | `created_at_label: "8 Sep 2026"` |

```php
// Contoh payload kalkulasi profit
return [
    'modal_raw'                   => $investor->modal,
    'modal_label'                 => 'Rp ' . number_format($investor->modal, 0, ',', '.'),
    'profit_gross_raw'            => $profitGross,
    'profit_gross_label'          => 'Rp ' . number_format($profitGross, 0, ',', '.'),
    'investor_share_raw'          => $investorShare,
    'investor_share_label'        => 'Rp ' . number_format($investorShare, 0, ',', '.'),
    'investor_share_pct_label'    => '20%',
    'master_trader_share_label'   => 'Rp ' . number_format($masterShare, 0, ',', '.'),
    'master_trader_share_pct_label' => '25%',
];
```

---

## Helper Functions

Helper global disimpan di `app/Helpers/helpers.php` dan di-autoload via `composer.json`:

```json
// composer.json
"autoload": {
    "files": ["app/Helpers/helpers.php"]
}
```

Contoh helper yang direkomendasikan untuk proyek ini:

```php
// Cek role user aktif
function is_admin(): bool
function is_investor(): bool
function is_affiliator(): bool

// Format rupiah
function rupiah(int $amount): string  // return "Rp 5.000.000"

// Format tanggal Indonesia
function tanggal_indo(string|\Carbon\Carbon $date): string  // return "8 September 2026"
function tanggal_singkat(string|\Carbon\Carbon $date): string  // return "8 Sep 2026"
```

---

## Code Formatter (Laravel Pint)

Proyek ini menggunakan Laravel Pint dengan konfigurasi kustom di [`src/pint.json`](file:///e:/5_projects/yzd-trading-management/src/pint.json).

### Konvensi Berdasarkan `pint.json`:
- **Preset:** `laravel`
- **Perataan Operator Array (`=>`):** Selalu sejajarkan panah array (`binary_operator_spaces` dengan `align_single_space_minimal`).
- **Spasi Konkat String (`.`):** Wajib menggunakan 1 spasi sebelum dan sesudah titik (`concat_space: one`, contoh: `'Rp ' . number_format(...)`).
- **PHPDoc Alignment:** Rata kiri (`phpdoc_align: left`).
- **Fleksibilitas String Quote:** Tanda kutip ganda/tunggal tidak dipaksa (`single_quote: false`).
- **Statement & Operator Spacing:** `blank_line_before_statement: false`, `not_operator_with_successor_space: false`.

### Perintah Format Kode:
```sh
# Saat Git aktif (format file yang berubah):
vendor/bin/pint --dirty

# Format direktori spesifik (atau saat Git belum aktif):
vendor/bin/pint app database
```

---

## Hal yang DILARANG di Kode

```php
// DILARANG: env() di luar config/
$key = env('APP_KEY'); // ← SALAH di controller/service
$key = config('app.key'); // ← BENAR

// DILARANG: dd(), dump(), var_dump() di kode yang di-commit
dd($user);
dump($commission);

// DILARANG: hardcode URL
$url = 'https://yzd-trading.com/investor/dashboard'; // SALAH
$url = route('investor.dashboard'); // BENAR

// DILARANG: hardcode nilai bisnis di kode
$investorShare = $profit * 0.20; // SALAH — angka 0.20 harus dari config/konstanta
$investorShare = $profit * config('business.profit_share.investor'); // BENAR
```
