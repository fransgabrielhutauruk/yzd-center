<?php

namespace Database\Seeders;

use App\Models\PanduanBantuan;
use App\Models\PanduanKategori;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder kategori dan artikel panduan & bantuan.
 */
class PanduanBantuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $adminId = $admin?->id;

        // ---------------------------------------------------------------------
        // 2. Data Kategori Topik Panduan & Bantuan (Pusat Bantuan)
        // ---------------------------------------------------------------------
        // Bersihkan data panduan bantuan dan kategori lama agar terfokus pada alur investasi
        PanduanBantuan::query()->forceDelete();
        PanduanKategori::query()->forceDelete();

        $kategoriData = [
            [
                'nama'      => 'Panduan Mulai Investasi',
                'slug'      => 'panduan-mulai-investasi',
                'deskripsi' => 'Panduan langkah demi langkah untuk memulai investasi copytrade dan direct trading di YZD Center, mulai dari pembukaan akun broker hingga integrasi Myfxbook.',
                'icon'      => 'Compass',
                'urutan'    => 1,
            ],
        ];

        $kategoriMap = [];
        foreach ($kategoriData as $kat) {
            $catRecord = PanduanKategori::updateOrCreate(
                ['slug' => $kat['slug']],
                array_merge($kat, ['is_active' => true])
            );
            $kategoriMap[$kat['slug']] = $catRecord->id;
        }

        // ---------------------------------------------------------------------
        // 3. Data Artikel Panduan & Video Bantuan (Alur Memulai Investasi)
        // ---------------------------------------------------------------------
        $artikelData = [
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Registrasi & Verifikasi Akun di Broker HFM',
                'slug'          => 'panduan-registrasi-broker-hfm',
                'ringkasan'     => 'Panduan langkah demi langkah membuat akun myHF baru dan menyelesaikan proses verifikasi KYC di broker HFM untuk persiapan copytrade Tier 1 & 2.',
                'konten'        => '<h2>Panduan Registrasi &amp; Verifikasi Akun di Broker HFM</h2><p>Ikuti panduan berikut untuk membuka akun profil di broker mitra resmi HFM (HotForex) yang terhubung langsung dengan kemitraan YZD Center:</p><ol><li><p><strong>Buka Tautan Resmi HFM:</strong> Akses situs pendaftaran resmi HFM melalui tautan resmi yang telah disediakan pada alur investasi YZD Center.</p></li><li><p><strong>Isi Formulir myHF:</strong> Lengkapi data pendaftaran (nama lengkap, negara asal, email aktif, dan nomor telepon) sesuai dengan identitas resmi pada KTP Anda.</p></li><li><p><strong>Aktivasi Email &amp; Login:</strong> Periksa email konfirmasi dari broker HFM untuk mengaktifkan akun, kemudian login ke portal klien myHF.</p></li><li><p><strong>Lengkapi Profil &amp; Verifikasi KYC:</strong> Unggah foto KTP asli (Proof of Identity) dan dokumen bukti tempat tinggal (Proof of Address) yang masih berlaku untuk verifikasi kepatuhan hukum.</p></li></ol><blockquote><p><strong>Catatan Penting:</strong> Pastikan nama lengkap pada akun HFM sama persis dengan nama rekening bank penarikan Anda di YZD Center agar proses pencairan profit berjalan lancar.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Registrasi Broker HFM ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Deposit Dana ke MyWallet Broker HFM',
                'slug'          => 'panduan-deposit-wallet-hfm',
                'ringkasan'     => 'Tata cara menyetorkan saldo modal investasi ke MyWallet akun HFM melalui metode pembayaran resmi transfer bank lokal Indonesia.',
                'konten'        => '<h2>Panduan Deposit Dana ke MyWallet HFM</h2><p>Sebelum mengaktifkan copytrade, Anda perlu mengisi saldo dana modal ke dompet utama (MyWallet) akun HFM Anda:</p><ol><li><p><strong>Masuk ke Portal myHF:</strong> Login ke area klien myHF menggunakan ID akun dan password Anda.</p></li><li><p><strong>Akses Menu Deposit:</strong> Pilih menu <em>Manage Funds</em> &gt; <em>Deposit</em> pada bilah navigasi utama.</p></li><li><p><strong>Pilih Metode Bank Lokal:</strong> Pilih opsi Instant Bank Transfer atau Local Bank Transfer Indonesia (tersedia BCA, Mandiri, BNI, BRI, QRIS, dsb).</p></li><li><p><strong>Tentukan Jumlah Modal:</strong> Masukkan nominal deposit sesuai estimasi modal yang Anda rencanakan (minimal $1 untuk Tier 1, atau minimal $5.000 untuk Tier 2).</p></li><li><p><strong>Selesaikan Transfer &amp; Simpan Bukti:</strong> Lakukan transfer ke rekening virtual account resmi HFM dan simpan bukti transfer Anda hingga saldo tampil di MyWallet.</p></li></ol><blockquote><p><strong>Perhatian:</strong> Saldo di MyWallet bersifat aman dan belum mengalami risiko pasar sampai Anda mengalokasikannya ke akun Follower Master Copytrade.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Deposit MyWallet HFM ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Mengikuti (Follow) Akun Master Copytrade di HFM',
                'slug'          => 'panduan-follow-master-hfm',
                'ringkasan'     => 'Petunjuk mencari, mengalokasikan saldo MyWallet, dan mengikuti strategi Master Copytrade YZD Center di platform HFM Copy.',
                'konten'        => '<h2>Panduan Follow Akun Master Copytrade HFM</h2><p>Langkah krusial untuk menghubungkan alokasi dana Anda ke strategi eksekusi transaksi otomatis Master Trader YZD Center:</p><ol><li><p><strong>Akses HFM Copy:</strong> Pada menu dasbor myHF, pilih menu <em>HFMcopy</em> &gt; <em>Strategy List</em>.</p></li><li><p><strong>Temukan Akun Master YZD:</strong> Masukkan nama akun strategi Master YZD Center pada bilah pencarian atau klik tautan strategi yang tercantum di panduan.</p></li><li><p><strong>Klik Tombol Follow:</strong> Buka profil strategi Master lalu tekan tombol <em>Follow</em>.</p></li><li><p><strong>Alokasikan Dana dari MyWallet:</strong> Masukkan jumlah dana yang ingin dialokasikan dari saldo MyWallet ke akun copytrade Anda.</p></li><li><p><strong>Konfirmasi Ketentuan Broker:</strong> Setujui syarat &amp; ketentuan fitur copytrade HFM, kemudian konfirmasi aktivasi.</p></li><li><p><strong>Dapatkan Nomor Akun Follower:</strong> Sistem HFM akan menerbitkan nomor akun MT4 Follower baru khusus penyalinan strategi ini.</p></li></ol><blockquote><p><strong>Penting:</strong> Salin Nomor Akun MT4 Follower yang baru terbit tersebut untuk dicatatkan pada Langkah 4 formulir alur investasi YZD Center.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Follow Master HFM Copy ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Registrasi & Verifikasi Profil di Broker XM',
                'slug'          => 'panduan-registrasi-broker-xm',
                'ringkasan'     => 'Langkah registrasi akun profil member baru dan validasi identitas resmi di broker mitra XM (Khusus Portofolio Tier 3).',
                'konten'        => '<h2>Panduan Registrasi Profil di Broker XM</h2><p>Bagi investor Tier 3 (Direct Managed Trading), langkah pertama adalah mendaftarkan akun profil di broker mitra resmi XM:</p><ol><li><p><strong>Akses Tautan Kemitraan XM:</strong> Buka situs pendaftaran resmi broker XM melalui tautan yang disediakan pada alur investasi Tier 3.</p></li><li><p><strong>Isi Formulir Profil Anggota:</strong> Lengkapi data identitas pribadi (nama lengkap, tanggal lahir, alamat tinggal, email, dan telepon) sesuai KTP/Paspor.</p></li><li><p><strong>Konfirmasi Alamat Email:</strong> Buka kotak masuk email Anda dan klik tombol verifikasi untuk mengonfirmasi pendaftaran akun XM.</p></li><li><p><strong>Unggah Dokumen Verifikasi (KYC):</strong> Masuk ke Member Area XM, lalu unggah foto KTP asli dan bukti alamat (rekening koran/tagihan utilitas) untuk validasi status akun.</p></li></ol><blockquote><p><strong>Syarat Validasi:</strong> Akun harus berstatus tervalidasi penuh (*Validated*) sebelum Anda dapat membuka akun trading MT4 dan menyetorkan modal.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Registrasi Broker XM ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Membuka Akun Trading MT4 di Broker XM',
                'slug'          => 'panduan-buka-akun-mt4-xm',
                'ringkasan'     => 'Petunjuk pembukaan akun trading tipe Standard MT4 untuk portofolio Direct Managed Trading Tier 3 di broker XM.',
                'konten'        => '<h2>Panduan Membuka Akun Trading MT4 XM</h2><p>Setelah akun member XM berstatus valid, ikuti langkah berikut untuk membuat akun trading MT4 yang akan dikelola di Private VPS Dedicated:</p><ol><li><p><strong>Masuk ke Member Area XM:</strong> Login ke portal area anggota XM Anda.</p></li><li><p><strong>Buka Akun Tambahan:</strong> Pilih opsi <em>Buka Akun Tambahan</em> (Open Additional Account).</p></li><li><p><strong>Pilih Platform MT4:</strong> Pada pilihan platform trading, pilih <strong>MetaTrader 4 (MT4)</strong>.</p></li><li><p><strong>Pilih Tipe Akun Standard:</strong> Pilih tipe akun <strong>Standard</strong>, mata uang dasar <strong>USD</strong>, dan leverage rekomendasi <strong>1:500</strong>.</p></li><li><p><strong>Atur Kata Sandi Trading:</strong> Tentukan Master Password dan Investor Password (read-only) yang kuat.</p></li><li><p><strong>Catat Data Akun:</strong> Simpan Nomor Akun MT4, Server Broker (contoh: XMGlobal-Real 50), Master Password, dan Investor Password Anda.</p></li></ol><blockquote><p><strong>Penting:</strong> Keempat data kredensial tersebut wajib dimasukkan ke formulir Langkah 3 di sistem YZD Center agar tertera otomatis pada Surat Perjanjian Kemitraan Tier 3.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Buka Akun MT4 XM ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Deposit Dana ke Akun Trading MT4 XM',
                'slug'          => 'panduan-deposit-akun-mt4-xm',
                'ringkasan'     => 'Tata cara menyetorkan modal investasi langsung ke akun trading MT4 XM yang telah disiapkan untuk Private VPS Tier 3.',
                'konten'        => '<h2>Panduan Deposit Dana ke Akun MT4 XM</h2><p>Panduan pengisian modal langsung ke saldo akun trading MT4 XM Anda sebelum aktivasi robot trading di Dedicated Private VPS:</p><ol><li><p><strong>Pilih Akun Trading MT4:</strong> Pada Member Area XM, tentukan nomor akun MT4 yang telah Anda buat khusus untuk program Tier 3.</p></li><li><p><strong>Klik Tombol Deposit:</strong> Tekan tombol <em>Deposit</em> yang berada tepat di samping nomor akun trading Anda.</p></li><li><p><strong>Pilih Jalur Pembayaran:</strong> Gunakan metode pembayaran yang diinginkan (Online Bank Transfer Indonesia, Virtual Account, Wire Transfer, dll).</p></li><li><p><strong>Setorkan Modal Sesuai Alokasi:</strong> Masukkan jumlah dana modal investasi minimal $300.000 USD (atau sesuai rencana portofolio Tier 3 Anda).</p></li><li><p><strong>Simpan Konfirmasi Setoran:</strong> Pastikan saldo berhasil masuk ke kolom balance/equity akun MT4 XM Anda.</p></li></ol><blockquote><p><strong>Keamanan Dana Mutlak:</strong> Seluruh modal berada aman di bawah rekening akun broker atas nama investor pribadi. Master Trader sama sekali tidak memiliki hak penarikan dana.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Deposit Akun XM ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Login & Pemantauan di Aplikasi MetaTrader 4 (MT4)',
                'slug'          => 'panduan-login-aplikasi-mt4',
                'ringkasan'     => 'Cara mengunduh, login, dan memantau transaksi trading secara real-time di aplikasi MetaTrader 4 smartphone maupun komputer.',
                'konten'        => '<h2>Panduan Login &amp; Pemantauan di Aplikasi MT4</h2><p>Pantau seluruh posisi order dan performa pertumbuhan profit trading secara transparan langsung dari genggaman ponsel Anda:</p><ol><li><p><strong>Unduh Aplikasi MT4:</strong> Unduh aplikasi resmi <strong>MetaTrader 4</strong> melalui Google Play Store (Android), App Store (iOS), atau PC Desktop.</p></li><li><p><strong>Pilih Menu Kelola Akun:</strong> Buka aplikasi MT4, masuk ke menu <em>Settings</em> (Pengaturan) &gt; <em>Manage Accounts</em> (Kelola Akun) &gt; tekan ikon <strong>+</strong>.</p></li><li><p><strong>Pilih Masuk ke Akun yang Ada:</strong> Pilih opsi <em>Login to an existing account</em>.</p></li><li><p><strong>Cari Server Broker:</strong> Ketikkan nama server broker Anda pada kolom pencarian (sesuai informasi dari broker Anda, misal: HFMarkets-Live atau XMGlobal-Real).</p></li><li><p><strong>Masukkan Kredensial Akun:</strong> Masukkan Nomor Akun MT4 dan Password Anda (Anda dapat menggunakan Investor Password untuk akses pemantauan aman).</p></li><li><p><strong>Mulai Pantau Transaksi:</strong> Buka tab <em>Trade</em> untuk melihat posisi transaksi terbuka dan tab <em>History</em> untuk melihat rekap riwayat keuntungan yang dicapai.</p></li></ol><blockquote><p><strong>Transparansi 100%:</strong> Anda memiliki kendali penuh untuk memantau aktivitas transaksi yang dieksekusi Master Trader kapan saja secara real-time.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Login Aplikasi MT4 ]</div>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'panduan-mulai-investasi',
                'judul'         => 'Panduan Menghubungkan Akun Trading ke Myfxbook',
                'slug'          => 'panduan-menghubungkan-myfxbook',
                'ringkasan'     => 'Tutorial mengaitkan akun MT4 dengan platform analitik Myfxbook dan menyalin tautan portofolio publik untuk verifikasi pendaftaran YZD Center.',
                'konten'        => '<h2>Panduan Menghubungkan Akun ke Myfxbook</h2><p>Myfxbook adalah platform pelacak performa trading global yang digunakan untuk transparansi pencatatan statistik dan verifikasi profit portofolio Anda:</p><ol><li><p><strong>Buat Akun Myfxbook:</strong> Buka situs <em>myfxbook.com</em> dan lakukan pendaftaran akun gratis jika Anda belum memilikinya.</p></li><li><p><strong>Tambahkan Akun Portofolio:</strong> Buka menu <em>Portfolio</em> &gt; <em>Add Account</em>.</p></li><li><p><strong>Pilih MT4 Auto Update:</strong> Pilih opsi platform <strong>MetaTrader 4 (Auto Update)</strong> agar data tersinkronisasi otomatis dari server broker.</p></li><li><p><strong>Isi Parameter Akun:</strong> Masukkan Nama Portofolio, pilih nama Broker mitra (HFM atau XM), Server Broker yang sesuai, Nomor Akun MT4, dan <strong>Investor Password</strong> (kata sandi read-only).</p></li><li><p><strong>Sinkronisasi Portofolio:</strong> Sistem Myfxbook akan memproses verifikasi dan sinkronisasi data akun trading Anda dalam beberapa saat.</p></li><li><p><strong>Salin Tautan Portofolio Publik:</strong> Atur status visibilitas portofolio menjadi Public, lalu salin tautan URL Myfxbook Anda (contoh: <code>https://www.myfxbook.com/members/username/portofolio/123456</code>).</p></li><li><p><strong>Tempelkan pada Formulir YZD Center:</strong> Masukkan URL Myfxbook tersebut pada kolom Tautan Myfxbook di Langkah 6 formulir alur investasi YZD Center.</p></li></ol><blockquote><p><strong>Keamanan Investor Password:</strong> Myfxbook HANYA memerlukan Investor Password (read-only) untuk membaca grafik. Jangan pernah memberikan Master Password Anda.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950 flex items-center justify-center text-muted-foreground text-sm font-mono">[ Video Tutorial Menghubungkan Myfxbook ]</div>',
                'video_url'     => null,
            ],
        ];

        foreach ($artikelData as $art) {
            $catId = $kategoriMap[$art['kategori_slug']] ?? null;
            if (!$catId) {
                continue;
            }

            PanduanBantuan::updateOrCreate(
                ['slug' => $art['slug']],
                [
                    'kategori_id'  => $catId,
                    'judul'        => $art['judul'],
                    'ringkasan'    => $art['ringkasan'],
                    'konten'       => $art['konten'],
                    'video_url'    => $art['video_url'],
                    'is_published' => true,
                    'views_count'  => 0,
                    'created_by'   => $adminId,
                ]
            );
        }
    }
}
