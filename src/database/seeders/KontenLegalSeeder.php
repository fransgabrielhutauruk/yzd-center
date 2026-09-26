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
                'judul'  => 'Syarat & Ketentuan Layanan',
                'konten' => <<<'HTML'
                <h3>1. Kedudukan Layanan & Batasan Platform</h3>
                <p><strong>YZD Center</strong> adalah platform perangkat lunak manajemen relasi klien dan sistem pemantauan portofolio mandiri. YZD Center <strong>bukanlah pialang berjangka (broker), kustodian dana, penasihat keuangan terdaftar, maupun lembaga pengelola investasi kolektif</strong>. Seluruh modal, saldo transaksi, dan penempatan ekuitas trading berada sepenuhnya di akun trading masing-masing investor pada perusahaan pialang (broker) pihak ketiga yang dipilih secara independen.</p>

                <h3>2. Keanggotaan, Registrasi, dan KYC</h3>
                <ul>
                    <li>Pendaftaran akun bersifat privat dan eksklusif, hanya dapat diakses melalui tautan undangan resmi.</li>
                    <li>Pengguna wajib mengisi data identitas yang valid, mengunggah kartu identitas resmi (KTP), dan menyelesaikan proses verifikasi.</li>
                    <li>Satu individu hanya diperbolehkan memiliki satu akun terverifikasi. YZD Center berhak membekukan atau menutup akun yang terindikasi menggunakan identitas palsu atau milik pihak lain.</li>
                </ul>

                <h3>3. Mekanisme CopyTrade, Settlement, dan Komisi</h3>
                <ul>
                    <li><strong>Kinerja & Alokasi Proporsional:</strong> Akun investor menyalin transaksi Master Trader secara proporsional. Investor menyetujui bahwa perbedaan spread, jenis akun, atau latensi eksekusi antarserver broker dapat menyebabkan sedikit perbedaan hasil riil antara akun investor dan akun Master Trader.</li>
                    <li><strong>Siklus Settlement:</strong> Evaluasi pembagian keuntungan bersih (<em>Profit Sharing</em>) dan komisi afiliasi dihitung secara berkala pada akhir periode settlement yang telah ditentukan.</li>
                    <li><strong>Proteksi Penurunan Nilai (Drawdown Guard):</strong> Tidak ada pemotongan bagi hasil maupun komisi afiliasi jika portofolio berada dalam kondisi imbal hasil negatif (rugi/drawdown) pada akhir periode settlement.</li>
                </ul>

                <h3>4. Penunjukan dan Pengalihan Hak Ahli Waris</h3>
                <ul>
                    <li>Setiap investor wajib mendaftarkan satu nama ahli waris yang memiliki hubungan hukum sah (Pasangan, Anak, Orang Tua, atau Saudara Kandung).</li>
                    <li>Apabila investor meninggal dunia atau mengalami kondisi disabilitas permanen yang dibuktikan secara sah (<em>force majeure</em>), hak atas akun YZD Center dapat dialihkan kepada ahli waris terdaftar.</li>
                    <li>Klaim pengalihan hak wajib melampirkan: Surat Keterangan Kematian resmi, Kartu Keluarga, identitas ahli waris, serta dokumen penetapan waris sah sesuai hukum yang berlaku di Republik Indonesia.</li>
                </ul>

                <h3>5. Penafian Risiko & Batasan Tanggung Jawab</h3>
                <ul>
                    <li><strong>Risiko Modal Penuh:</strong> Perdagangan instrumen valuta asing (Forex) dan derivatif memiliki risiko tinggi dan dapat menyebabkan hilangnya sebagian atau seluruh modal investasi Anda. Jangan gunakan dana kebutuhan pokok atau dana darurat.</li>
                    <li><strong>Bukan Jaminan Hasil:</strong> Kinerja masa lalu, simulasi kalkulator masa depan, serta indikator estimasi BEP (<em>Break Even Point</em>) pada sistem bersifat proyeksi matematis informatif dan <strong>bukan merupakan jaminan imbal hasil pasti (fixed return)</strong>.</li>
                    <li>YZD Center dibebaskan dari segala bentuk tuntutan kerugian finansial akibat pergerakan pasar, kegagalan server broker, tindakan likuidasi pialang, atau kelalaian pengguna dalam menjaga kerahasiaan kredensial akun.</li>
                </ul>

                <h3>6. Pengakhiran Layanan</h3>
                <p>YZD Center berhak menonaktifkan akses pengguna secara sepihak apabila ditemukan upaya peretasan, manipulasi sistem rujukan (referral fraud), pencucian uang (<em>money laundering</em>), atau pelanggaran hukum yang merugikan ekosistem platform.</p>
                HTML,
            ]
        );

        KontenLegal::firstOrCreate(
            ['key_name' => 'privacy_policy'],
            [
                'judul'  => 'Kebijakan Privasi & Perlindungan Data',
                'konten' => <<<'HTML'
                <p>Kebijakan Privasi ini merupakan komitmen YZD Center dalam melindungi dan menghormati data pribadi pengguna sesuai ketentuan <strong>Undang-Undang Republik Indonesia No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)</strong>.</p>

                <h3>1. Data Pribadi yang Dikumpulkan</h3>
                <p>Kami mengumpulkan dan memproses informasi yang Anda serahkan secara sadar saat registrasi dan pemanfaatan sistem</p>

                <h3>2. Tujuan Pemrosesan Data</h3>
                <p>Informasi pribadi pengguna digunakan semata-mata untuk:</p>
                <ol>
                    <li>Memverifikasi identitas pengguna dan mencegah praktik identitas ganda.</li>
                    <li>Menghubungkan akun investor dengan kode afiliasi yang sah serta memvalidasi keabsahan pembagian komisi.</li>
                    <li>Memproses verifikasi pencairan hak komisi dan pengiriman dana ke rekening yang sah.</li>
                    <li>Memvalidasi legalitas pengalihan kepemilikan akun kepada ahli waris jika terjadi keadaan darurat.</li>
                    <li>Memenuhi audit administratif internal dan kewajiban penegakan hukum apabila diminta oleh otoritas berwenang.</li>
                </ol>

                <h3>3. Keamanan dan Penyimpanan Data</h3>
                <ul>
                    <li>Seluruh berkas identitas disimpan di dalam direktori privat terisolasi (<em>restricted private storage</em>) dengan proteksi otorisasi ganda dan tidak dapat diakses langsung melalui URL publik.</li>
                    <li>Transmisi data sensitif dilindungi menggunakan protokol enkripsi standar industri (Secure Sockets Layer / TLS).</li>
                    <li>Kata sandi akun disimpan dalam basis data menggunakan algoritma hash satu arah yang tidak dapat didekripsi oleh tim YZD Center sekalipun.</li>
                </ul>

                <h3>4. Pembagian Data kepada Pihak Ketiga</h3>
                <p>YZD Center <strong>tidak akan pernah menjual, menyewakan, atau memperdagangkan data pribadi pengguna kepada pihak ketiga untuk kepentingan komersial</strong>. Data hanya dapat diungkapkan kepada:</p>
                <ul>
                    <li>Penyedia gerbang perbankan/pembayaran resmi yang bekerja sama untuk memproses pencairan dana Anda.</li>
                    <li>Lembaga penegak hukum yang sah di wilayah Republik Indonesia atas dasar surat perintah pengadilan resmi.</li>
                </ul>

                <h3>5. Hak Pemilik Data Pribadi</h3>
                <p>Sesuai UU PDP, Anda berhak:</p>
                <ul>
                    <li>Mengakses dan memperbarui data profil pribadi yang tercatat di sistem.</li>
                    <li>Mengajukan permohonan pembetulan atas data identitas yang tidak akurat.</li>
                    <li>Mengajukan penutupan akun dan penghapusan data (<em>right to be forgotten</em>), dengan ketentuan data transaksi historis tetap diarsipkan selama jangka waktu yang diwajibkan oleh ketentuan perundang-undangan keuangan.</li>
                </ul>

                <h3>6. Kontak & Pusat Bantuan Data</h3>
                <p>Untuk pertanyaan, koreksi data, atau pelaksanaan hak privasi Anda, silakan hubungi tim Administrator YZD Center.</p>
                HTML,
            ]
        );
    }
}
