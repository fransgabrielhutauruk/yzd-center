# Rules 04 — Coding Conventions & Standardisasi Kode

## Konvensi PHP Modern

### 1. Deklarasi Tipe & Method Signature
- Seluruh method wajib memiliki **explicit return type**:
  ```php
  public function getDashboardStats(User $user): array { }
  public function verifyAccount(int $id, string $status): AkunCopytrade { }
  public function deleteProof(int $id): void { }
  ```
- Seluruh parameter method wajib memiliki **type hint eksplisit**:
  ```php
  public function submit(User $affiliator, array $payload, ?UploadedFile $file = null): KlaimKomisi { }
  ```
- Gunakan *Constructor Property Promotion* pada class:
  ```php
  class CommissionClaimService
  {
      public function __construct(
          private readonly ReferralService $referralService,
      ) {}
  }
  ```

### 2. Control Structures & Ekspresi
- Selalu gunakan kurung kurawal `{}` untuk semua blok kendali (`if`, `else`, `foreach`, `while`), dilarang syntax 1 baris tanpa kurung.
- Utamakan ekspresi `match` dibanding `switch` untuk pemetaan status/nilai:
  ```php
  $persentase = match ($tierLevel) {
      1 => 50,
      2 => 40,
      3 => 30,
      4 => 20,
      default => throw new InvalidArgumentException("Tier level tidak valid: {$tierLevel}"),
  };
  ```

---

## Konvensi Penamaan (Naming Conventions)

| Item | Konvensi | Contoh Baku |
|---|---|---|
| **Model** | PascalCase Singular | `User`, `AkunCopytrade`, `KlaimKomisi`, `PengaturanMaster` |
| **Controller** | PascalCase + Controller | `CopytradeController`, `CommissionClaimController` |
| **Service** | PascalCase + Service | `CopytradeService`, `CommissionClaimService` |
| **Form Request** | Verb + Entity + Request | `StoreCopytradeRequest`, `ProcessCommissionClaimRequest` |
| **Notification** | Event + Notification | `CommissionClaimSubmittedNotification` |
| **Tabel Database**| snake_case Plural / Kontekstual | `users`, `akun_copytrade`, `klaim_komisi`, `relasi_referral` |
| **Primary Key** | `id` (Standar Laravel) | `$table->id()` |
| **Foreign Key** | `{entity_singular}_id` | `user_id`, `affiliator_id`, `downline_id`, `processed_by` |
| **Kolom Tanggal**| `_at` suffix | `verified_at`, `copy_started_at`, `processed_at`, `created_at` |
| **Kolom Boolean**| `is_` prefix atau kondisi | `is_active`, `is_verified` |
| **Route Name** | dot.notation bertingkat | `investor.commissions.index`, `admin.copytrade.verify` |
| **Vue Component**| PascalCase | `AppDataTable.vue`, `SubmitClaimModal.vue` |
| **Vue Composable**| camelCase + `use` prefix | `useToast.js`, `useForm` |

---

## Format Tanggal & Mata Uang Baku (Helpers)

Aplikasi memiliki helper terstandarisasi untuk memastikan keseragaman format di seluruh tampilan:

```php
// Format Mata Uang Rupiah Baku
function rupiah(float|int $nominal): string
{
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Format Tanggal Lengkap dengan Waktu (Tabel Data & Audit)
// Output: "22 Sep 2026 15:11"
function tanggal_singkat_waktu(Carbon|string|null $date): string
{
    return $date ? Carbon::parse($date)->translatedFormat('d M Y H:i') : '-';
}

// Format Tanggal Ringkas (Tanpa Jam)
// Output: "22 Sep 2026"
function tanggal_singkat(Carbon|string|null $date): string
{
    return $date ? Carbon::parse($date)->translatedFormat('d M Y') : '-';
}
```

> **Aturan Tanggal di Frontend:** Dilarang keras menampilkan string ISO mentah (`2026-09-22T12:16:00+07:00`) pada antarmuka pengguna. Selalu format data tanggal di service sebelum dikirim ke Vue atau gunakan helper format di baris tabel.

---

## Pola Muatan Data Siap-Tampil (View Payload Pattern)

Data yang dikirim ke komponen Vue harus siap digunakan tanpa membebani frontend dengan parsing angka atau tanggal yang rumit:

```php
$claims->through(fn (KlaimKomisi $c) => [
    'id'                   => $c->id,
    'kode_klaim'           => $c->kode_klaim,
    'nominal_deposit_raw'  => (float) $c->nominal_deposit,
    'nominal_deposit_label'=> rupiah($c->nominal_deposit),
    'tier_label'           => "Tier {$c->tier_level} ({$c->persentase_tier}%)",
    'nominal_transfer_label'=> $c->nominal_transfer ? rupiah($c->nominal_transfer) : '-',
    'created_at'           => tanggal_singkat_waktu($c->created_at),
    'status'               => $c->status,
]);
```

---

## Standar Formatter Laravel Pint

Aturan formatting kode dikunci menggunakan konfigurasi kustom di [`src/pint.json`](file:///e:/5_projects/yzd-trading-management/src/pint.json):
- **Preset:** `laravel`
- **Array Arrow Alignment:** Perataan panah array operator (`binary_operator_spaces` minimal alignment).
- **String Concatenation:** Wajib menggunakan 1 spasi sebelum dan sesudah titik penggabungan (`concat_space: one`, contoh: `'Rp ' . number_format(...)`).
- **PHPDoc Alignment:** Rata kiri (`phpdoc_align: left`).

### Perintah Validasi & Format Pint:
```powershell
# Jalankan test formatting via PHP 8.3 Laragon:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint --test app database routes tests

# Terapkan perbaikan otomatis jika ada pelanggaran:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" vendor/bin/pint app database routes tests
```

---

## Larangan Kode (Banned Code & Anti-Patterns)

- **DILARANG** meninggalkan statement debugging di kode (`dd()`, `dump()`, `var_dump()`, `console.log`).
- **DILARANG** memanggil fungsi `env()` di luar file konfigurasi `config/`.
- **DILARANG** melakukan hardcode URL statis — selalu gunakan `route('nama.rute')`.
- **DILARANG** komentar-komentar basi/obvious hasil buatan AI (seperti `// Handle button click`, `// Return response`).
- **DILARANG** mengabaikan audit `git diff` untuk mencegah baris duplikat (*Zero Duplicate Policy*).
