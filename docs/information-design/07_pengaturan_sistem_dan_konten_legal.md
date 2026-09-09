# Modul 07: Pengaturan Sistem, Master Trader & Konten Legal Dinamis

Dokumen ini memuat spesifikasi teknis dan aturan bisnis untuk modul konfigurasi tautan master trader, pengelolaan pasal-pasal hukum/disclaimer risiko, serta dukungan lokalisasi multi-bahasa.

---

## 1. Ringkasan Fitur & Peran Terlibat

- **Pengaturan Master Trader Terpusat:** Administrator mengelola tautan-tautan eksternal kritis dari satu pintu di panel admin tanpa perlu mengubah kode sumber (*hardcoded*):
  - URL Widget Myfxbook Master Pak Abi.
  - Link referral broker resmi HFM.
  - Link referral broker resmi XM.
  - Tautan rahasia penyalinan sinyal (*Master CopyTrade Secret URL*).
- **Pengelolaan Syarat & Kebijakan Privasi Dinamis:** Admin dapat memperbarui pasal-pasal perlindungan hukum dan penafian risiko trading (*risk disclosure*), lengkap dengan nomor versi dokumen.
- **Dukungan Multi-Bahasa (i18n):** Opsi pergantian bahasa (Indonesia dan Inggris) yang di-handle native di level framework (file kamus Laravel & Vue i18n), menjamin layout HTML tidak rusak.

---

## 2. Entitas Database Terkait

- **`pengaturan_master`**: Tabel konfigurasi baris tunggal (*single-row table*) (`master_myfxbook_url`, `broker_hfm_referral_url`, `broker_xm_referral_url`, `master_copytrade_secret_url`).
- **`konten_web`**: Menyimpan pasal-pasal dinamis berdasarkan kunci unik (`key_name`, `judul`, `konten`, `versi`). Kunci standar:
  - `'terms_conditions'`: Syarat & Ketentuan Layanan.
  - `'privacy_policy'`: Kebijakan Privasi & Keamanan Data.
  - `'risk_disclaimer'`: Pernyataan Penafian Risiko Trading Forex.

---

## 3. Aturan Bisnis & Logika Validasi

### 3.1 Manajemen Baris Tunggal `pengaturan_master`
1. Tabel ini hanya memiliki record dengan `id = 1`.
2. Dibuat pertama kali melalui Database Seeder saat instalasi awal sistem.
3. Form di panel admin tidak menyediakan fitur "Tambah Data", melainkan hanya antarmuka "Ubah Pengaturan" (*Update*).
4. Setiap URL divalidasi dengan format URL valid (`url` rule).

### 3.2 Pembaruan Konten Legal & Versioning
1. Ketika Administrator memperbarui teks Syarat & Ketentuan dan menaikkan nomor versi (misal: dari `'v1.0'` ke `'v1.1'`), riwayat pembaruan tercatat di timestamps.
2. Form registrasi calon investor selalu menarik versi dokumen hukum paling mutakhir yang tersimpan di database.
3. Pendaftar baru akan menyetujui versi terkini tersebut, dan string versi disimpan ke kolom `users.terms_version`.

### 3.3 Penanganan Multi-Bahasa (i18n)
1. Bahasa utama bawaan sistem: **Bahasa Indonesia (`id`)**.
2. Bahasa sekunder: **Bahasa Inggris (`en`)**.
3. Kamus bahasa disimpan di file JSON/PHP:
   - Backend: `lang/id.json` dan `lang/en.json` (untuk pesan validasi error).
   - Frontend Vue: `resources/js/lang/id.json` dan `resources/js/lang/en.json`.
4. Preferensi bahasa pengguna disimpan di `localStorage` peramban atau session pengguna.

---

## 4. Catatan Teknis & Optimasi Kinerja

- **Application Caching:** Karena data pengaturan master dan konten legal jarang berubah, query database dibungkus dengan mekanisme caching Laravel:
  ```php
  $masterSettings = Cache::rememberForever('pengaturan_master', function () {
      return PengaturanMaster::first();
  });
  ```
- **Cache Invalidation:** Saat Admin menekan tombol simpan perubahan, sistem otomatis menghapus cache terkait (`Cache::forget('pengaturan_master')`) agar perubahan langsung tayang seketika.

---

## 5. Ruang Catatan Tambahan (Area Kolaboratif Pengembang & AI)

*Tambahkan catatan atau perubahan kebutuhan terkait pengaturan sistem di bawah ini:*

- **[2026-09-08]**: Disepakati i18n native tanpa bergantung pada ekstensi translate browser pihak ketiga agar integritas UI tetap konsisten.
- *(Tulis catatan baru di sini jika ada perubahan flow)*

