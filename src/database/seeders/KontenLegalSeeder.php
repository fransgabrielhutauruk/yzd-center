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
                'judul'  => 'Syarat & Ketentuan',
                'konten' => <<<'HTML'
                    <h3>1. Ketentuan Umum dan Keanggotaan</h3><ul><li><p>Layanan platform <strong>YZD Center</strong> hanya diperuntukkan bagi anggota terdaftar melalui tautan undangan resmi.</p></li><li><p>Calon investor wajib memberikan informasi identitas yang benar, akurat, dan dapat dibuktikan secara hukum.</p></li></ul><h3>2. Perlindungan Ahli Waris</h3><ul><li><p>Setiap investor wajib menunjuk satu orang ahli waris sah (Anak, Istri, Suami, Orang Tua, Saudara Kandung, atau Lainnya).</p></li><li><p>Hak atas akun dan saldo investasi dapat dialihkan kepada ahli waris yang terdaftar apabila terjadi musibah atau keadaan kahar (<em>force majeure</em>) yang menimpa investor dengan melampirkan dokumen pembuktian yang sah.</p></li></ul><h3>3. Penafian Risiko (Risk Disclosure)</h3><ul><li><p>Perdagangan valuta asing (Forex) dan CFD memiliki tingkat risiko tinggi terhadap modal Anda.</p></li><li><p>Kinerja masa lalu tidak menjamin hasil di masa depan. Seluruh keputusan penempatan dana merupakan tanggung jawab penuh masing-masing investor.</p></li></ul><h3>4. Kerahasiaan Data &amp; Tata Kelola Berkas</h3><ul><li><p>Berkas identitas KTP dan surat perjanjian yang diunggah disimpan di direktori privat berkeamanan tinggi dan tidak akan dibagikan kepada pihak ketiga tanpa persetujuan investor.</p></li></ul>
                    HTML,
            ]
        );

        KontenLegal::firstOrCreate(
            ['key_name' => 'privacy_policy'],
            [
                'judul'  => 'Kebijakan Privasi & Keamanan Data',
                'konten' => <<<'HTML'
                    <h3>1. Pengumpulan Data Pribadi</h3><p>Kami mengumpulkan data berupa Nama Lengkap, NIK, Alamat, Nomor Kontak, Berkas Identitas KTP, dan Data Ahli Waris untuk keperluan verifikasi kepatuhan KYC (<em>Know Your Customer</em>) serta keabsahan hukum kemitraan investasi.</p><h3>2. Keamanan Penyimpanan Data</h3><p>Seluruh data dan berkas privat disimpan menggunakan enkripsi dan isolasi direktori privat yang hanya dapat diakses oleh Administrator berwenang dan pemilik akun yang sah.</p>
                    HTML,
            ]
        );
    }
}
