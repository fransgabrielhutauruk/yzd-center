# Rules 03 — Arsitektur Kode & Pola Komponen

## Prinsip Inti: Thin Controller + Fat Service + Dedicated Form Request

Untuk menjaga maintainability, keterujian, dan pemisahan tanggung jawab (*separation of concerns*):

1. **Form Request (`app/Http/Requests/`):**
   - Bertanggung jawab memvalidasi seluruh input pengguna dengan aturan yang ketat dan pesan validasi berbahasa Indonesia.
   - Mengatur otorisasi dasar pemanggilan request (`authorize(): bool`).

2. **Controller (`app/Http/Controllers/`):**
   - **Hanya bertindak sebagai pengarah lalu lintas (traffic router):**
     - Menerima request yang sudah divalidasi oleh Form Request.
     - Memanggil service terkait untuk mengeksekusi logika bisnis.
     - Mengembalikan respons Inertia (`Inertia::render()`), JSON, download file, atau redirect dengan flash message (`with('success', ...)`).
   - **Larangan di Controller:** Dilarang meletakkan kalkulasi bisnis rumit, manipulasi file mentah, atau query Eloquent berantai langsung di controller.

3. **Service Layer (`app/Services/`):**
   - Merupakan pusat logika bisnis (*business logic*) dan aturan domain.
   - Menjalankan transaksi database berantai (`DB::transaction(...)`).
   - Melakukan kalkulasi keuangan (pembagian profit, hak komisi 4-tier, penentuan durasi aktif 90 hari).
   - Mengelola penyimpanan berkas privat ke disk `'local'`.
   - Mengirim notifikasi in-app database (`Notification::send()`).
   - Mempersiapkan payload data siap-tampil (*view-ready payload*) untuk dikirim ke Vue.

---

## Struktur Direktori Nyata Proyek

```text
src/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                       ← AuthenticatedSession, RegisteredUserController, dsb.
│   │   │   ├── Admin/                      ← Dashboard, Copytrade, CommissionClaim, Settings, dsb.
│   │   │   ├── Investor/                   ← Dashboard, Copytrade, Affiliate, CommissionClaim, dsb.
│   │   │   └── DocumentStreamController.php← Streaming privat berkas sensitif terotorisasi
│   │   ├── Middleware/                     ← EnsureUserRole, EnsureUserActive, HandleInertiaRequests
│   │   └── Requests/                       ← Form Request terisolasi per peran (Auth, Admin, Investor)
│   ├── Models/                             ← User, AkunCopytrade, KlaimKomisi, RelasiReferral, dsb.
│   ├── Notifications/                      ← In-App Database Notifications
│   ├── Services/                           ← Business logic per domain
│   │   ├── AuthService.php                 ← Pendaftaran & login
│   │   ├── InvitationService.php           ← Token pendaftaran tertutup
│   │   ├── KycService.php                  ← Dokumen identitas & ahli waris
│   │   ├── ReferralService.php             ← Relasi referral, kode unik, klaim susulan PB 4
│   │   ├── CopytradeService.php            ← Pendaftaran akun broker, stepper, verifikasi MT4
│   │   └── CommissionClaimService.php      ← Eligibilitas 90 hari, 4-tier komisi, pencairan PB 3
│   └── Helpers/
│       └── helpers.php                     ← Helper format rupiah, tanggal Indonesia, dsb.
├── database/
│   ├── migrations/                         ← File migrasi skema tabel
│   └── seeders/                            ← DatabaseSeeder, DemoSeeder, PengaturanMasterSeeder
├── resources/
│   └── js/
│       ├── Components/
│       │   ├── Common/                     ← AppDataTable, AppModal, AppConfirmDialog, AppToaster, DevRegisterAutoFill
│       │   └── ui/                         ← Shadcn primitives (button, dsb.)
│       ├── Layouts/                        ← AdminLayout.vue, InvestorLayout.vue, AuthLayout.vue
│       ├── lib/                            ← Helper utility (imageCompression.js, utils.js)
│       └── Pages/                          ← Halaman Inertia (Auth, Admin, Investor)
└── routes/
    ├── web.php                             ← Seluruh rute aplikasi web
    └── console.php                         ← Task scheduling & perintah artisan
```

---

## Pola Layanan Khusus (Special Architecture Patterns)

### 1. Pola Streaming Dokumen Privat (`DocumentStreamController`)
Dokumen sensitif (KTP, selfie, surat perjanjian bermaterai, bukti modal MT4, bukti transfer bank) **tidak pernah disimpan di folder public**.
- Disimpan di disk `'local'` (`storage/app/private/`).
- Diakses secara eksklusif via rute streaming:
  ```php
  Route::get('/documents/commission-proof/{id}', [DocumentStreamController::class, 'showCommissionProof'])
      ->middleware(['auth'])
      ->name('documents.commission_proof');
  ```
- Controller memverifikasi otorisasi: pengguna hanya dapat mengalirkan dokumen jika merupakan pemilik berkas atau memiliki peran `administrator`.
- Mengembalikan response `Storage::disk('local')->response($path)`.

### 2. Pola In-App Database Notifications
Aplikasi memanfaatkan notifikasi database internal Laravel (`notifications` table):
- Saat ada pengajuan penting (pendaftaran referral susulan, klaim komisi baru, akun copytrade baru), service men-dispatch notifikasi ke seluruh Administrator:
  ```php
  $admins = User::where('role', 'administrator')->get();
  Notification::send($admins, new CommissionClaimSubmittedNotification($claim));
  ```
- Saat admin memverifikasi/mencairkan/menolak, notifikasi dikirimkan langsung ke user terkait:
  ```php
  $affiliator->notify(new CommissionClaimProcessedNotification($claim));
  ```

### 3. Pola Transaksi Data & Atomic Execution
Setiap operasi yang melibatkan penulisan lebih dari satu tabel wajib dibungkus dalam blok `DB::transaction()`:
```php
public function submitClaim(User $affiliator, array $data, UploadedFile $buktiModal): KlaimKomisi
{
    return DB::transaction(function () use ($affiliator, $data, $buktiModal): KlaimKomisi {
        // 1. Simpan berkas ke storage privat
        // 2. Kunci persentase tier & generate kode klaim
        // 3. Simpan record KlaimKomisi
        // 4. Kirim notifikasi database ke admin
        return $claim;
    });
}
```

---

## Pola Respons Controller & Flash Messages

### 1. Render Halaman Inertia (GET Request)
```php
return Inertia::render('Investor/Commissions/Index', [
    'claims'            => $claims,
    'stats'             => $stats,
    'eligibleDownlines' => $eligibleDownlines,
    'filters'           => $request->only(['search', 'status', 'sort_by', 'sort_direction']),
]);
```

### 2. Aksi Mutasi (POST/PUT/DELETE Request)
Setelah berhasil memproses formulir, kembalikan redirect dengan flash message:
```php
return redirect()->back()->with('success', 'Pengajuan klaim komisi berhasil dikirim dan menunggu verifikasi admin.');
```
Pesan `success` atau `error` dari session secara otomatis di-share oleh middleware `HandleInertiaRequests` dan ditangkap oleh `AppToaster.vue`.
