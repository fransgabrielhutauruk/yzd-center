# Workflow: Membuat Migration

Checklist untuk setiap migration baru.

## Sebelum Membuat Migration
- [ ] Tentukan nama tabel: snake_case plural (contoh: investors, invitations)
- [ ] Tentukan primary key: {entity}_id (contoh: investor_id, invitation_id)
- [ ] Tentukan semua kolom bisnis yang diperlukan
- [ ] Tentukan relasi (foreign key) jika ada
- [ ] Identifikasi index yang diperlukan untuk performa query

## Membuat Migration

Jalankan: php artisan make:migration create_{table}_table

Atau untuk tabel yang sudah ada: php artisan make:migration add_{column}_to_{table}_table

## Checklist Isi Migration

### Untuk Tabel Baru:
- [ ] Primary key: $table->id('{entity}_id');
- [ ] Kolom bisnis (sesuai kebutuhan)
- [ ] Foreign key (jika ada relasi)
- [ ] Kolom boolean pakai is_ prefix
- [ ] Kolom timestamp bisnis pakai _at suffix, nullable
- [ ] Kolom audit wajib:
  - $table->string('created_by')->nullable();
  - $table->string('updated_by')->nullable();
  - $table->string('deleted_by')->nullable();
- [ ] $table->timestamps();
- [ ] $table->softDeletes();
- [ ] Index performa: $table->index('foreign_key_col');

### Untuk Alter Tabel:
- [ ] Sertakan SEMUA atribut kolom yang sudah ada sebelumnya (jangan sampai atribut hilang)
- [ ] Tambahkan nullable() jika kolom baru boleh kosong
- [ ] Pertimbangkan data yang sudah ada sebelum membuat kolom NOT NULL

## Setelah Membuat Migration
- [ ] Review kembali file migration sebelum dijalankan
- [ ] Jalankan: php artisan migrate
- [ ] Cek tabel berhasil dibuat: php artisan db:table {table}
- [ ] Buat/update Model yang sesuai
- [ ] Buat/update Factory jika diperlukan untuk testing
