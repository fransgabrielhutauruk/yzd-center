<?php

namespace Database\Seeders;

use App\Models\KontenLegal;
use Illuminate\Database\Seeder;

class KontenLegalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KontenLegal::firstOrCreate(
            ['key_name' => 'terms_conditions'],
            [
                'judul'  => 'Syarat & Ketentuan Layanan YZD Trading',
                'konten' => "### 1. Ketentuan Umum dan Keanggotaan\n1. Layanan platform YZD Trading Management hanya diperuntukkan bagi anggota terdaftar melalui tautan undangan resmi.\n2. Calon investor wajib memberikan informasi identitas yang benar, akurat, dan dapat dibuktikan secara hukum.\n\n### 2. Perlindungan Ahli Waris\n1. Setiap investor wajib menunjuk satu orang ahli waris sah yang memiliki hubungan keluarga langsung (Anak, Istri, Suami, Orang Tua, atau Saudara Kandung).\n2. Hak atas akun dan saldo investasi dapat dialihkan kepada ahli waris yang terdaftar apabila terjadi musibah atau keadaan kahar (force majeure) yang menimpa investor dengan melampirkan dokumen pembuktian yang sah.\n\n### 3. Penafian Risiko (Risk Disclosure)\n1. Perdagangan valuta asing (Forex) dan CFD memiliki tingkat risiko tinggi terhadap modal Anda.\n2. Kinerja masa lalu tidak menjamin hasil di masa depan. Seluruh keputusan penempatan dana merupakan tanggung jawab penuh masing-masing investor.\n\n### 4. Kerahasiaan Data & Tata Kelola Berkas\n1. Berkas identitas KTP dan surat perjanjian yang diunggah disimpan di direktori privat berkeamanan tinggi dan tidak akan dibagikan kepada pihak ketiga tanpa persetujuan investor.",
                'versi'  => 'v1.0',
            ]
        );

        KontenLegal::firstOrCreate(
            ['key_name' => 'privacy_policy'],
            [
                'judul'  => 'Kebijakan Privasi & Keamanan Data',
                'konten' => "### 1. Pengumpulan Data Pribadi\nKami mengumpulkan data berupa Nama Lengkap, NIK, Alamat, Nomor Kontak, Berkas Identitas KTP, dan Data Ahli Waris untuk keperluan verifikasi kepatuhan KYC (Know Your Customer) serta keabsahan hukum kemitraan investasi.\n\n### 2. Keamanan Penyimpanan Data\nSeluruh data dan berkas privat disimpan menggunakan enkripsi dan isolasi direktori privat yang hanya dapat diakses oleh Administrator berwenang dan pemilik akun yang sah.",
                'versi'  => 'v1.0',
            ]
        );
    }
}
