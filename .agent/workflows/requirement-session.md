# Workflow: Sesi Diskusi Kebutuhan (Requirement Session)

Alur kerja saat mendiskusikan fitur baru, revisi alur bisnis, atau penyesuaian aturan sistem.

---

## 1. Persiapan Sebelum Diskusi
- [ ] **Pelajari Dokumen Acuan yang Ada:**
  - Spesifikasi per modul di [`docs/information-design/`](file:///e:/5_projects/yzd-trading-management/docs/information-design).
  - Panduan uji manual di [`docs/verifikasi-model/`](file:///e:/5_projects/yzd-trading-management/docs/verifikasi-model).
  - Rencana besar sistem di `SRS.md`.
- [ ] Identifikasi potensi benturan aturan dengan modul yang sudah selesai (Modul 01 sampai 05).

---

## 2. Prinsip Komunikasi Selama Diskusi (Socratic Gate)

### Yang Wajib Dilakukan:
- Berikan respons ringkas, lugas, dan kontekstual pada sistem trading finansial YZD Center.
- Jika ada persyaratan yang ambigu, **berhenti dan ajukan pertanyaan klarifikasi** dengan opsi pilihan konkret dan trade-off singkat.
- Sajikan draft ringkasan (*outline*) terlebih dahulu sebelum menyusun dokumen panjang.

### Yang Dilarang Keras:
- **DILARANG** menebak kebutuhan yang tidak jelas lalu mendokumentasikannya sebagai fakta mutlak.
- **DILARANG** langsung memulai implementasi penulisan kode saat sesi requirement masih berlangsung tanpa instruksi eksplisit.
- **DILARANG** menggunakan bahasa klise template AI yang bertele-tele.

---

## 3. Alur Pembaruan Dokumentasi Spesifikasi

Jika diskusi menghasilkan keputusan arsitektur atau aturan bisnis baru:
1. **Rangkum Keputusan di Chat:** Sampaikan poin kesepakatan dan batasannya.
2. **Perbarui Dokumen Desain Terkait:**
   - Update file terkait di [`docs/information-design/`](file:///e:/5_projects/yzd-trading-management/docs/information-design) (contoh: pembaruan skema tiering pada `05_klaim_komisi_afiliasi.md`).
3. **Perbarui Dokumen Panduan Verifikasi:**
   - Tambahkan pemetaan endpoint rute dan skenario uji manual di [`docs/verifikasi-model/`](file:///e:/5_projects/yzd-trading-management/docs/verifikasi-model).
4. **Beri Tahu Pengguna:** Konfirmasikan berkas mana saja yang telah diperbarui agar pengguna dapat meninjaunya secara transparan.
