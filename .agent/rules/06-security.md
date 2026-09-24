# Rules 06 — Keamanan, Otorisasi & Penyimpanan Privat

## Role-Based Authentication

Aplikasi menggunakan role-based sederhana berbasis kolom `role` pada tabel `users`:
```php
role: 'administrator' | 'investor' | 'affiliator'
```

### 1. Hak Akses Berdasarkan Peran
- **`administrator`:** Hak akses penuh mencakup manajemen undangan pendaftaran, verifikasi KYC/identitas, verifikasi akun broker copytrade (fase 1 & 2), pengelolaan pengaturan master CMS, verifikasi klaim referral susulan (PB 4), serta pemrosesan dan pencairan komisi affiliator (PB 3).
- **`investor`:** Hak akses portal investor mencakup dasbor portofolio, stepper pendaftaran copytrade 3-tahap, panduan broker & perjanjian, kalkulator profit, dan materi edukasi.
- **`affiliator`:** Seluruh hak akses peran investor ditambah fitur kemitraan: kode referral unik 6-karakter (`ABI8K2`), dasbor jaringan kemitraan, pengajuan klaim susulan (PB 4), dan dasbor klaim pencairan komisi (PB 3).

### 2. Middleware Perlindungan Rute
Pendaftaran middleware alias di `bootstrap/app.php`:
```php
$middleware->alias([
    'role'          => \App\Http\Middleware\EnsureUserRole::class,
    'ensure.active' => \App\Http\Middleware\EnsureUserActive::class,
]);
```
Contoh penerapan pada `routes/web.php`:
```php
// Rute khusus Administrator
Route::middleware(['auth', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('commissions', AdminCommissionController::class);
});

// Rute Investor & Affiliator Aktif
Route::middleware(['auth', 'role:investor,affiliator', 'ensure.active'])->prefix('investor')->name('investor.')->group(function () {
    Route::get('/commissions', [InvestorCommissionController::class, 'index'])->name('commissions.index');
});
```

---

## Standar Penyimpanan Berkas Privat (Anti-Folder Duplikat)

> [!IMPORTANT]
> **Penyimpanan Berkas Privat Laravel 11/12:**
> - Root direktori disk `'local'` pada Laravel secara bawaan sudah berada di `storage/app/private`.
> - **DILARANG MENYERTAKAN PREFIKS `"private/"`** pada pemanggilan method `storeAs()` atau `Storage::disk('local')`.
> - **Contoh Benar:**
>   `$file->storeAs("referral_claims/{$claimId}", $filename, 'local');`
>   `$file->storeAs("commission_claims/proofs", $filename, 'local');`
> - **Contoh Salah:**
>   `$file->storeAs("private/referral_claims/{$claimId}", $filename, 'local');` *(Akan menghasilkan folder redundan `storage/app/private/private/...`)*.

---

## Streaming Dokumen Sensitif (`DocumentStreamController`)

Dokumen identitas (KTP, selfie), surat perjanjian investasi, bukti modal/deposit, dan struk transfer bank **tidak boleh dapat diakses publik**:
1. Seluruh berkas disimpan di disk `'local'`.
2. Akses hanya melalui endpoint streaming terotorisasi:
   ```php
   public function showCommissionProof(int $id): Response|BinaryFileResponse
   {
       $claim = KlaimKomisi::findOrFail($id);
       $user = auth()->user();

       // Otorisasi ketat: Administrator atau Affiliator pemilik klaim
       if ($user->role !== 'administrator' && $claim->affiliator_id !== $user->id) {
           abort(403, 'Anda tidak memiliki hak akses untuk melihat dokumen bukti modal ini.');
       }

       if (! Storage::disk('local')->exists($claim->bukti_modal_path)) {
           abort(404, 'Berkas bukti modal tidak ditemukan di penyimpanan server.');
       }

       return Storage::disk('local')->response($claim->bukti_modal_path);
   }
   ```

---

## Kapasitas Validasi Kolom Tipe TEXT

Untuk input formulir yang menampung deskripsi bebas, kronologi, alasan penolakan, catatan instruksi admin, atau catatan feedback yang disimpan ke kolom bertipe data `TEXT` pada database:
- **Gunakan batas validasi yang memadai (hingga `max:20000`).**
- **DILARANG** melakukan pembatasan sempit (seperti `max:1000` atau `max:500`) kecuali ada kebutuhan bisnis yang secara eksplisit membatasi panjang teks tersebut.

---

## Enkripsi Kredensial Sensitif

- Kredensial trading MT4 (master password, investor password) serta akses kredensial VPS (IP, root/admin password) wajib dilindungi menggunakan Eloquent attribute casting `'encrypted'`.
- Hindari menampilkan password dalam format plain text di layar frontend kecuali melalui tombol interaktif dengan konfirmasi sadar (*toggle show/hide password* yang terlindungi).

---

## Proteksi Mass Assignment

- Setiap model Eloquent wajib mendeklarasikan atribut `$fillable` secara eksplisit.
- **DILARANG** menggunakan `$guarded = []` pada model bisnis finansial untuk mencegah celah *mass-assignment vulnerability*.
