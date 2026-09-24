# Rules 05 — Database & Skema Eloquent

## Prinsip Dasar Skema Database

- Seluruh tabel menggunakan primary key standar Laravel: `$table->id()`.
- Gunakan Eloquent ORM secara konsisten. Hindari query SQL mentah (`DB::raw`) kecuali untuk kalkulasi agregasi kompleks yang sangat mendesak.
- **Pencegahan N+1 Query:** Selalu lakukan *eager loading* (`with(['affiliator', 'downline'])`) saat mengambil data koleksi untuk tabel atau daftar.
- **Presisi Nilai Finansial:** Nilai uang (deposit, komisi, profit, estimasi modal) disimpan menggunakan tipe data `DECIMAL(15, 2)`. **DILARANG** menggunakan tipe data `FLOAT` atau `DOUBLE` karena rentan terhadap galat pembulatan biner.

---

## Template Migration Standar

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klaim_komisi', function (Blueprint $table): void {
            $table->id();

            // Kolom Unik / Identifikasi
            $table->string('kode_klaim', 30)->unique();

            // Relasi Foreign Key
            $table->foreignId('affiliator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('downline_id')->constrained('users')->cascadeOnDelete();

            // Data Finansial & Kalkulasi
            $table->decimal('nominal_deposit', 15, 2);
            $table->unsignedTinyInteger('tier_level');      // 1, 2, 3, 4
            $table->unsignedTinyInteger('persentase_tier');  // 50, 40, 30, 20
            $table->string('bukti_modal_path', 255);

            // Kolom Pemrosesan Admin
            $table->decimal('profit_master', 15, 2)->nullable();
            $table->decimal('nominal_transfer', 15, 2)->nullable();
            $table->string('bukti_transfer_path', 255)->nullable();

            // Data Rekening Bank Affiliator
            $table->string('nama_bank', 100);
            $table->string('nomor_rekening', 50);
            $table->string('atas_nama_rekening', 100);

            // Siklus Status & Audit
            $table->string('status', 30)->default('pending'); // pending, approved_transferred, rejected
            $table->text('catatan_admin')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // Indexing untuk Performa Query
            $table->index('status');
            $table->index('affiliator_id');
            $table->index('downline_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klaim_komisi');
    }
};
```

---

## Model Eloquent & Casting Modern (Laravel 11/12)

Gunakan method `casts(): array` (bukan property `$casts` model lama) untuk mendefinisikan tipe data:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkunCopytrade extends Model
{
    protected $table = 'akun_copytrade';

    protected $fillable = [
        'user_id',
        'tier',
        'broker',
        'tipe_akun',
        'nomor_akun_trading',
        'server_broker',
        'master_password',
        'investor_password',
        'vps_ip',
        'vps_password',
        'estimasi_modal',
        'modal_riil',
        'surat_perjanjian_path',
        'step1_deposit_status',
        'step1_deposit_verified_at',
        'step2_copy_status',
        'step2_copy_verified_at',
        'step3_mql5_url',
        'copy_started_at',
        'status',
        'catatan_admin',
    ];

    /**
     * Konfigurasi casting modern Laravel.
     */
    protected function casts(): array
    {
        return [
            'estimasi_modal'            => 'decimal:2',
            'modal_riil'                => 'decimal:2',
            'master_password'           => 'encrypted', // Enkripsi native otomatis
            'investor_password'         => 'encrypted',
            'vps_password'              => 'encrypted',
            'step1_deposit_verified_at' => 'datetime',
            'step2_copy_verified_at'    => 'datetime',
            'copy_started_at'           => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## Enkripsi Kredensial Sensitif

- Kredensial akun broker MT4/MT5 (master password, investor password) serta akses server VPS wajib dideklarasikan dengan tipe casting `'encrypted'`.
- Framework secara transparan melakukan enkripsi saat menyimpan ke database dan dekripsi saat membaca di memori aplikasi tanpa memerlukan pemanggilan manual `Crypt::encryptString()`.

---

## Standar Seeder

Aplikasi memiliki susunan seeder terstruktur di `database/seeders/`:
1. `DatabaseSeeder.php`: Entry point utama pemanggilan seeder.
2. `PengaturanMasterSeeder.php`: Seeder konfigurasi master dinamis (tiering minimum modal copytrade, URL referral broker, link Myfxbook).
3. `DemoSeeder.php`: Data dummy untuk pengujian lokal, wajib dilindungi guard environment agar tidak berjalan di production:
   ```php
   if (app()->isProduction()) {
       $this->command->warn('DemoSeeder dilewati pada environment production.');
       return;
   }
   ```
