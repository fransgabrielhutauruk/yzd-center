# Panduan Verifikasi Implementasi: Modul 01 - Autentikasi, Manajemen Akun & Multi-Role

Dokumen ini digunakan sebagai panduan pengujian dan verifikasi fungsionalitas Modul 01 sesuai spesifikasi pada [`docs/information-design/01_autentikasi_dan_manajemen_akun.md`](../information-design/01_autentikasi_dan_manajemen_akun.md).

---

## 1. Pemetaan Rute Web (`routes/web.php`)

| No | HTTP Method | URI / Endpoint | Nama Rute (`route()`) | Middleware | Controller & Method | Komponen Tampilan (Vue) |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 ok ok| `GET` | `/` | `home` | `web` | *Closure* di `routes/web.php:21` | Redirect dinamis |
| 2 ok ok| `GET` | `/login` | `login` | `guest` | `Auth\AuthenticatedSessionController@create` | `resources/js/Pages/Auth/Login.vue` |
| 3 ok ok| `POST` | `/login` | - | `guest` | `Auth\AuthenticatedSessionController@store` | - |
| 4 ok ok | `GET` | `/register` | `register` | `guest` | `Auth\RegisteredUserController@create` | `resources/js/Pages/Auth/Register.vue` |
| 5 ok ok | `POST` | `/register` | - | `guest` | `Auth\RegisteredUserController@store` | - |
| 6 ok ok | `POST` | `/logout` | `logout` | `auth` | `Auth\AuthenticatedSessionController@destroy` | - |
| 7 later later| `GET` | `/admin/dashboard` | `admin.dashboard` | `auth`, `role:administrator` | `Admin\AdminDashboardController@index` | `resources/js/Pages/Admin/Dashboard.vue` |
| 8 later later | `GET` | `/investor/dashboard` | `investor.dashboard` | `auth`, `role:investor,affiliator` | `Investor\InvestorDashboardController@index` | `resources/js/Pages/Investor/Dashboard.vue` |
| 9 ok ok| `GET` | `/admin/invitations` | `admin.invitations.index` | `auth`, `role:administrator` | `Admin\InvitationController@index` | `resources/js/Pages/Admin/Invitations/Index.vue` |
| 10 ok ok | `POST` | `/admin/invitations` | `admin.invitations.store` | `auth`, `role:administrator` | `Admin\InvitationController@store` | - |
| 11 ok ok | `DELETE` | `/admin/invitations/{id}` | `admin.invitations.destroy` | `auth`, `role:administrator` | `Admin\InvitationControlle
r@destroy` | - |

---

## 2. Kredensial Akun Pengujian Default

Kredensial bawaan dari database seeder (`AdminUserSeeder.php`):

| Peran (*Role*) | Email | Kata Sandi (*Password*) | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@yzdcenter.com` | `password` | Akun pengelola penuh sistem YZD Center |
| **Investor** | *(Dibuat via `/register`)* | Sesuai pendaftaran (atau `asdf1234` jika menggunakan Dev Tool) | Status awal akun: `pending` |
| **Affiliator** | *(Dibuat via `/register` dengan token affiliator)* | Sesuai pendaftaran | Memiliki peran ganda: `investor` + `affiliator` |

---

## 3. Skenario Pengujian & Checklist Verifikasi Manual

### Skenario A: Pendaftaran Tertutup (*Closed Registration*)
- [ ] **Akselerasi Uji Coba Tanpa Token:**
  - Buka URL `http://localhost:8000/register` tanpa parameter query token.
  - **Ekspektasi:** Menampilkan pesan *"Tautan Undangan Tidak Valid"* atau diarahkan untuk menghubungi Administrator.
- [ ] **Akses dengan Token Valid:**
  - Login sebagai Admin, buat token baru di `http://localhost:8000/admin/invitations` (misal: kuota 5, target: `investor`).
  - Salin URL undangan yang terbit (`/register?token=INV-XXXXX`).
  - Buka pada *incognito window* / browser tamu.
  - **Ekspektasi:** Form pendaftaran wizard 4-langkah terbuka normal dan label token terdeteksi aktif.
- [ ] **Uji Kuota Habis / Kadaluarsa:**
  - Gunakan token yang telah mencapai batas `kuota_maksimal`.
  - **Ekspektasi:** Halaman menolak pendaftaran dan kuota tidak melebihi batas.

### Skenario B: Formulir Registrasi & Pengisian Cepat (*Dev Tool*)
- [ ] **Tombol Isi Form Acak (*Dev Tool*):**
  - Pada halaman registrasi aktif, klik tombol **"Isi Form Acak"** di banner atas atau tombol melayang di kanan bawah.
  - **Ekspektasi:**
    - Step 1 (Akun) terisi lengkap (Nama, Username, Email, No HP, NIK, Alamat, Password: `asdf1234`).
    - Step 2 (Ahli Waris) terisi lengkap.
    - Step 3 (Berkas) otomatis menghasilkan dummy berkas KTP & Perjanjian resolusi tinggi tanpa peringatan kualitas.
    - Step 4 (Persetujuan) otomatis mencentang Syarat & Ketentuan.
- [ ] **Kirim Pendaftaran (*Submit*):**
  - Klik tombol "Kirim Pendaftaran".
  - **Ekspektasi:** Diarahkan ke halaman `/login` disertai modal/pesan sukses pendaftaran dan instruksi menunggu verifikasi admin.

### Skenario C: Autentikasi Login & Multi-Role
- [ ] **Login dengan Email:**
  - Masuk ke `http://localhost:8000/login`, input email terdaftar dan password.
  - **Ekspektasi:** Berhasil masuk ke dashboard sesuai role (`/admin/dashboard` atau `/investor/dashboard`).
- [ ] **Login dengan Username:**
  - Masuk dengan input `username` dan password.
  - **Ekspektasi:** Berhasil login tanpa error.
- [ ] **Proteksi Status Akun (*Pending KYC*):**
  - Login menggunakan akun investor yang baru terdaftar (status `pending`).
  - Akses `http://localhost:8000/investor/dashboard`.
  - **Ekspektasi:** Menampilkan banner pengingat status verifikasi KYC atau redirect ke `/investor/kyc`.
- [ ] **Rate Limiting Percobaan Login Gagal:**
  - Masukkan password salah sebanyak 5 kali berturut-turut.
  - **Ekspektasi:** Sistem mengunci login sementara dengan pesan throttle rate-limiting (*"Terlalu banyak percobaan masuk..."*).
- [ ] **Logout:**
  - Tekan tombol keluar / logout dari antarmuka.
  - **Ekspektasi:** Sesi terhapus dan pengguna diarahkan kembali ke `/login`.

---

## 4. Pengujian Otomatis (*Automated Feature Tests*)

Jalankan perintah pengujian fitur autentikasi via PHP CLI Laragon:

```powershell
& "C:\laragon\bin\php\php-8.3.12-nts-Win32-vs16-x64\php.exe" artisan test tests/Feature/AuthTest.php --compact
```

**Cakupan Uji Otomatis:**
1. `test_login_screen_can_be_rendered`
2. `test_users_can_authenticate_using_login_screen`
3. `test_users_can_authenticate_using_username`
4. `test_users_cannot_authenticate_with_invalid_password`
5. `test_users_are_rate_limited_after_too_many_failed_attempts`
6. `test_users_can_logout`
7. `test_guest_cannot_register_without_valid_invitation_token`
8. `test_registration_screen_can_be_rendered_with_valid_token`
9. `test_new_investor_can_register_and_receives_investor_role`
10. `test_new_affiliator_can_register_and_receives_both_roles`
11. `test_invitation_usage_count_increments_after_successful_registration`
12. `test_admin_can_view_and_create_invitations`
13. `test_non_admin_cannot_access_invitations_management`
