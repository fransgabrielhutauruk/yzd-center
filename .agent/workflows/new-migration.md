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

# Untuk penambahan kolom / modifikasi tabel yang sudah ada (fase produksi):
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan make:migration add_{kolom}_to_{tabel}_table --table={nama_tabel}
```

> [!IMPORTANT]
> **Kebijakan Migrasi Fase Produksi (File Migrasi Baru & Zero Data Loss):**
> Karena aplikasi telah berada di fase produksi, **DILARANG KERAS** memodifikasi file migrasi `create` yang sudah berjalan atau menjalankan `migrate:fresh`. Setiap penambahan atau perubahan skema tabel **wajib membuat file migrasi baru** (`add_..._to_..._table`) dengan implementasi fungsi `up()` dan `down()` yang simetris, lalu dijalankan menggunakan `php artisan migrate`.

---

## 3. Checklist Isi File Migrasi

### A. Jika Membuat Tabel Baru (`create`)
- [ ] Primary key: `$table->id();`
- [ ] Relasi foreign key: `$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();`
- [ ] Presisi desimal finansial: `$table->decimal('nominal_deposit', 15, 2);`
- [ ] Kolom teks panjang: `$table->text('catatan_admin')->nullable();`
- [ ] Status dengan default: `$table->string('status', 30)->default('pending');`
- [ ] Timestamps: `$table->timestamps();`
- [ ] Index performa query: `$table->index('status');`, `$table->index('created_at');`
- [ ] Method `down()`: `$table->dropIfExists('{nama_tabel}');`

### B. Jika Mengubah / Menambah Kolom Tabel yang Sudah Ada (`alter`)
- [ ] Kolom baru nullable atau memiliki nilai default (agar tidak memecah baris data yang sudah ada).
- [ ] Posisi penempatan kolom teratur (gunakan `->after('kolom_sebelumnya')`).
- [ ] Method `down()` simetris: wajib melakukan rollback kolom/index dengan benar (contoh: `$table->dropColumn('nama_kolom');`, `$table->dropIndex(['nama_kolom']);`).
- [ ] Uji rollback lokal sebelum push: `artisan migrate:rollback --step=1` lalu `artisan migrate`.

---

## 4. Eksekusi & Validasi Migrasi

```powershell
# Jalankan migrasi baru (Zero Data Loss):
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
