# Workflow: Membuat Migrasi Basis Data (Database Migration)

Checklist ini wajib diikuti setiap kali membuat migrasi baru untuk pembuatan tabel atau perubahan struktur tabel yang ada.

---

## 1. Perencanaan Skema Migrasi
- [ ] Tentukan nama tabel: snake_case plural atau kontekstual (contoh: `users`, `akun_copytrade`, `klaim_komisi`, `relasi_referral`).
- [ ] Tentukan primary key standar: `$table->id()`.
- [ ] Tentukan foreign key dengan relasi dan *on-delete behavior* yang jelas (`cascadeOnDelete()`, `nullOnDelete()`).
- [ ] Tentukan tipe data nilai uang: wajib `decimal(15, 2)` (dilarang tipe `float`/`double`).
- [ ] Tentukan kolom timestamp bisnis dengan akhiran `_at` (contoh: `verified_at`, `copy_started_at`, `processed_at`).
- [ ] Identifikasi kebutuhan index untuk kolom yang menjadi kriteria filter/pencarian utama (`status`, `user_id`, `created_at`).

---

## 2. Pembuatan File Migrasi (CLI)

Gunakan perintah artisan melalui PHP 8.3 Laragon:

```powershell
# Untuk tabel baru:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan make:migration create_{nama_tabel}_table

# Untuk alter tabel yang sudah ada:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan make:migration add_{nama_kolom}_to_{nama_tabel}_table
```

---

## 3. Checklist Isi File Migrasi

### A. Untuk Tabel Baru:
- [ ] Primary key: `$table->id();`
- [ ] Relasi foreign key: `$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();`
- [ ] Presisi desimal finansial: `$table->decimal('nominal_deposit', 15, 2);`
- [ ] Kolom teks panjang: `$table->text('catatan_admin')->nullable();`
- [ ] Status dengan default: `$table->string('status', 30)->default('pending');`
- [ ] Timestamps: `$table->timestamps();`
- [ ] Index performa query: `$table->index('status');`, `$table->index('created_at');`
- [ ] Method `down()`: `$table->dropIfExists('{nama_tabel}');`

### B. Untuk Alter Tabel (Penambahan / Perubahan Kolom):
- [ ] Kolom baru pada tabel yang sudah berisi data wajib didefinisikan `nullable()` atau memiliki nilai `default()`.
- [ ] Method `down()` wajib mengembalikan perubahan secara bersih (contoh: `$table->dropColumn(['kolom_a', 'kolom_b']);`).

---

## 4. Eksekusi & Validasi Migrasi

```powershell
# Jalankan migrasi:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan migrate

# Periksa status migrasi:
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan migrate:status
```

---

## 5. Sinkronisasi Model Eloquent
- [ ] Daftarkan nama kolom baru pada properti `$fillable` Model terkait.
- [ ] Daftarkan tipe casting pada method `casts(): array` (misal `'encrypted'`, `'decimal:2'`, `'datetime'`).
- [ ] Definisikan relasi baru pada Model (`belongsTo`, `hasMany`).
- [ ] Perbarui Factory dan Seeder jika relevan untuk kebutuhan pengujian.
