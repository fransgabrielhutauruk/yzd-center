# Rules 05 — Database

## Prinsip Umum

- Gunakan Eloquent ORM — hindari `DB::` raw query kecuali untuk kalkulasi agregat kompleks
- Selalu gunakan `Model::query()` daripada `Model::` facades langsung untuk clarity
- Selalu eager load relasi — cegah N+1 query
- Soft delete wajib untuk semua model bisnis
- Nilai uang/rupiah disimpan sebagai **integer (dalam rupiah penuh)** — jangan float

---

## Template Migration Wajib

Setiap migration tabel bisnis **wajib** memiliki kolom berikut:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investors', function (Blueprint $table): void {
            $table->id('investor_id');          // primary key custom: {entity}_id

            // === Kolom bisnis ===
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('modal')->default(0); // rupiah, bukan float!
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // === Kolom audit wajib — JANGAN DIHAPUS ===
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // === Index untuk performa ===
            $table->index('user_id');
            $table->index('is_verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
```

---

## Naming Convention Database

| Item | Convention | Contoh |
|------|-----------|--------|
| Tabel | snake_case plural | `users`, `investors`, `invitations` |
| Primary key | `{entity}_id` | `investor_id`, `invitation_id` |
| Foreign key | `{ref_entity}_id` | `user_id`, `referrer_id` |
| Kolom boolean | `is_` prefix | `is_active`, `is_verified` |
| Kolom timestamp bisnis | `_at` suffix | `verified_at`, `expired_at`, `followed_at` |
| Kolom audit | fixed names | `created_by`, `updated_by`, `deleted_by` |
| Kolom uang | tanpa suffix khusus, tipe integer | `modal`, `profit_gross`, `komisi` |

---

## Model — Aturan

```php
<?php

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Investor extends Model
{
    use SoftDeletes, Blameable;

    protected $primaryKey = 'investor_id'; // wajib jika bukan "id"

    protected $fillable = [
        'user_id',
        'modal',
        'is_verified',
        'verified_at',
        'referrer_id',
    ];

    // Gunakan casts() method (bukan $casts property) — Laravel 12
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'modal'       => 'integer',
        ];
    }

    // Relasi wajib punya return type
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}
```

---

## Aturan Nilai Uang (Rupiah)

**Simpan sebagai integer (rupiah penuh).** Jangan pakai float/decimal untuk uang.

```php
// BENAR: modal disimpan sebagai integer
$investor->modal = 5000000; // Rp 5.000.000

// SALAH: float menyebabkan pembulatan error kalkulasi keuangan
$investor->modal = 5000000.50;

// Kalkulasi: gunakan integer arithmetic
$investorShare = intdiv($profitGross * 20, 100); // 20% dari profit
$masterShare   = intdiv($profitGross * 25, 100); // 25% dari profit
```

---

## Seeder Convention

Setiap domain/area fitur punya **3 seeder standar**:

```
database/seeders/
├── DatabaseSeeder.php          ← entry point, panggil semua seeder di bawah
├── RoleSeeder.php              ← seed role: Administrator, Investor, Affiliator
├── BusinessConfigSeeder.php   ← seed config bisnis: persentase profit, dll.
└── DemoSeeder.php             ← data demo (jangan jalankan di production)
```

**Aturan seeder:**
- `RoleSeeder`: hanya data role dan permission dasar
- `BusinessConfigSeeder`: nilai-nilai konfigurasi bisnis (persentase pembagian profit, masa berlaku komisi, dll.)
- `DemoSeeder`: data demo, boleh truncate + insert, tapi **wajib ada pengecekan environment**

```php
// DemoSeeder — wajib ada guard
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('DemoSeeder dilewati di environment production.');
            return;
        }

        // ... insert demo data
    }
}
```

---

## Hal yang DILARANG

```php
// DILARANG: query langsung di controller
class InvestorController
{
    public function index()
    {
        $investors = Investor::where('is_verified', true)->get(); // SALAH
    }
}

// DILARANG: N+1 query — selalu eager load
$investors = Investor::all();
foreach ($investors as $inv) {
    echo $inv->user->name; // SALAH: N+1
}
// BENAR:
$investors = Investor::with('user')->where('is_verified', true)->get();

// DILARANG: float untuk uang
$modal = 1500000.75; // SALAH

// DILARANG: hardcode persentase
$share = $profit * 0.20; // SALAH — harus dari config
```
