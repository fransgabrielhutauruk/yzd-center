<?php

namespace Database\Seeders;

use App\Models\EdukasiFaq;
use App\Models\EdukasiFaqTopik;
use App\Models\EdukasiGlosarium;
use App\Models\PanduanBantuan;
use App\Models\PanduanKategori;
use App\Models\User;
use Illuminate\Database\Seeder;

class EdukasiDanBantuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'administrator')->first();
        $adminId = $admin?->id;

        // ---------------------------------------------------------------------
        // 1. Data Glosarium Forex
        // ---------------------------------------------------------------------
        $glosariumData = [
            [
                'istilah'  => 'Copytrade (Penyalinan Transaksi)',
                'definisi' => 'Teknologi otomasi di mana setiap open order, modifikasi stop loss/take profit, dan close order pada akun Master Trader direplikasi secara instan dan proporsional ke akun investor.',
            ],
            [
                'istilah'  => 'Expert Advisor',
                'definisi' => 'Program-program ini digunakan untuk memantau dan mentradingkan pasar keuangan dengan bantuan algoritma. Mereka menemukan peluang sesuai dengan parameter yang ditetapkan oleh seorang trader. Untuk mengaktifkan Advisor di MTrading MT4 dan untuk mengonfigurasinya, trader harus memilih "Opsi" dari menu utama "Alat". Kemudian silakan buka tab "Expert Advisors" dan pastikan "Izinkan trading otomatis" dicentang.',
            ],
            [
                'istilah'  => 'Akun Demo',
                'definisi' => 'Ini adalah pelatih terbaik untuk trader baru. Ini membantu untuk mempelajari cara trading di pasar melalui terminal dengan menggunakan akun virtual.',
            ],
            [
                'istilah'  => 'Akun Investor',
                'definisi' => 'Ini adalah akun trader perusahaan forex, yang telah mempercayakan transaksi mereka untuk dipegang oleh manajer PAMM.',
            ],
            [
                'istilah'  => 'Arbitrase (Arbitrage)',
                'definisi' => 'Pembelian dan penjualan aset secara simultan untuk mengambil keuntungan dari spread, atau perbedaan antara harga beli dan jual, atau perbedaan harga antara broker. Itu dianggap ilegal di banyak tempat.',
            ],
            [
                'istilah'  => 'Ask Price (Harga Permintaan)',
                'definisi' => 'Juga dikenal sebagai tingkat penawaran. Harga di mana Anda setuju untuk membeli mata uang atau aset.',
            ],
            [
                'istilah'  => 'Alokasi Aset (Asset Allocation)',
                'definisi' => 'Penyebaran sekuritas Anda di berbagai sektor atau instrumen untuk mendorong pertumbuhan optimal dalam profil risiko Anda.',
            ],
            [
                'istilah'  => 'Averaging',
                'definisi' => 'Averaging adalah proses pembukaan posisi tambahan dengan standar atau volume yang meningkat setelah eksekusi trading pertama untuk mempertahankan perbedaan rata-rata yang dapat diterima antara harga pembukaan dan pesanan keuntungan. Teknik ini sering digunakan oleh trader berpengalaman.',
            ],
            [
                'istilah'  => 'Bar Chart (Grafik Batang)',
                'definisi' => 'Ini adalah salah satu opsi untuk menampilkan grafik harga di terminal. Sebenarnya itu adalah satu garis vertikal kecil. Dengan mengklik opsi "bar", trader dapat melihat dengan jelas apa yang diwakili grafik ini dalam periode waktu yang dipilih.',
            ],
            [
                'istilah'  => 'Mata Uang Dasar (Base Currency)',
                'definisi' => 'Dalam pasangan mata uang, mata uang pertama adalah mata uang dasar. Ini adalah mata uang yang dibeli atau dijual.',
            ],
            [
                'istilah'  => 'Bear Market (Pasar Bearish)',
                'definisi' => 'Tren penurunan harga instrumen trading. Ini ditandai dengan tertinggi yang lebih rendah dan posisi terendah yang lebih rendah.',
            ],
            [
                'istilah'  => 'Bid Price (Harga Penawaran)',
                'definisi' => 'Harga di mana Anda menjual mata uang atau aset.',
            ],
            [
                'istilah'  => 'Bid/Ask Spread',
                'definisi' => 'Selisih antara harga penawaran beli (Bid) dan harga permintaan jual (Ask) yang menjadi biaya transaksi trader.',
            ],
            [
                'istilah'  => 'Bollinger Bands',
                'definisi' => 'Grafik indikator tren yang menunjukkan trading aset dalam band support dan resistance.',
            ],
            [
                'istilah'  => 'Breakout',
                'definisi' => 'Ketika harga sekuritas naik atau turun melalui garis grafik trending.',
            ],
            [
                'istilah'  => 'Bull Market (Pasar Bullish)',
                'definisi' => 'Serangkaian tren naik harga saham. Anda melihat tertinggi yang lebih tinggi dan posisi terendah yang lebih tinggi.',
            ],
            [
                'istilah'  => 'Contract for Difference',
                'definisi' => 'Perjanjian antara broker dan klien untuk membayar selisih antara harga pembukaan dan penutupan sekuritas.',
            ],
            [
                'istilah'  => 'Price Channel (Saluran Harga)',
                'definisi' => 'Pola grafik yang menunjukkan trading yang terjadi dalam area sempit yang ditentukan.',
            ],
            [
                'istilah'  => 'Chasing the Trade (FOMO)',
                'definisi' => 'Membeli aset yang meningkat pada harga yang lebih tinggi karena takut kehilangan kemungkinan keuntungan. Seringkali mengakibatkan kerugian karena harga aset kembali turun.',
            ],
            [
                'istilah'  => 'Consumer Price Index',
                'definisi' => 'Ini adalah salah satu indikator inflasi terpenting yang diterbitkan sebulan sekali. Indeks ini mencerminkan setiap perubahan dalam biaya keranjang belanja rata-rata penduduk AS.',
            ],
            [
                'istilah'  => 'Mata Uang Kutipan (Counter Currency)',
                'definisi' => 'Dalam pasangan mata uang, mata uang kedua adalah mata uang lawan atau mata uang kutipan yang nilainya menjadi pembanding.',
            ],
            [
                'istilah'  => 'Debt Ratio (Rasio Hutang)',
                'definisi' => 'Total kewajiban dibagi dengan total hutang perusahaan.',
            ],
            [
                'istilah'  => 'Derivatif (Derivative)',
                'definisi' => 'Kontrak yang mendapatkan nilainya dari aset dasar seperti indeks, saham, komoditas, mata uang, atau suku bunga. Itu tidak memiliki aset.',
            ],
            [
                'istilah'  => 'Dividen (Dividend)',
                'definisi' => 'Keuntungan perusahaan saham dibayarkan kepada investor. Dividen biasanya dibayarkan setiap tiga bulan tetapi dapat dibayarkan setiap bulan, setiap tahun, atau sebagai pembayaran khusus',
            ],
            [
                'istilah'  => 'Doji Candlestick',
                'definisi' => 'Grafik candle dengan ujung tengah dan panjang yang sangat pendek.',
            ],
            [
                'istilah'  => 'Drawdown',
                'definisi' => 'Persentase atau nominal penurunan modal dari titik puncak ekuitas tertinggi (peak) ke titik terendah (trough) dalam akun trading.',
            ],
            [
                'istilah'  => 'Earnings Before Interest, Taxes, Depreciation, and Amortization',
                'definisi' => 'Laba Sebelum Bunga, Pajak, Penyusutan dan Amortisasi. Ini digunakan untuk membantu menentukan nilai perusahaan.',
            ],
            [
                'istilah'  => 'Ekuitas (Equity)',
                'definisi' => 'Nilai riil total dana di akun trading saat ini yang berfluktuasi secara langsung seiring pergerakan laba/rugi mengambang (floating profit/loss) dari transaksi terbuka.',
            ],
            [
                'istilah'  => 'Earnings Per Share',
                'definisi' => 'EPS membantu investor memahami kesehatan atau kekuatan perusahaan.',
            ],
            [
                'istilah'  => 'Eksekusi Instan (Instant Execution)',
                'definisi' => 'Ini adalah perubahan harga, yang masuk ke sistem trading tanpa diminta, mereka terus mengalir secara otomatis.',
            ],
            [
                'istilah'  => 'Euro',
                'definisi' => 'Mata uang resmi untuk negara-negara anggota Uni Eropa.',
            ],
            [
                'istilah'  => 'Enterprise Value',
                'definisi' => 'Total aset sebuah perusahaan.',
            ],
            [
                'istilah'  => 'Enterprise Value to EBITDA',
                'definisi' => 'Ukuran paling akurat dari nilai perusahaan. Ini adalah nilai perusahaan dibagi dengan EBITDA. Angka yang rendah berarti nilai yang lebih baik.',
            ],
            [
                'istilah'  => 'Floating Profit/Loss (Laba/Rugi Mengambang)',
                'definisi' => 'Ini adalah keuntungan dan kerugian yang belum dilaporkan dan nilainya berubah setiap detik, seiring dengan perubahan harga.',
            ],
            [
                'istilah'  => 'Floating Spread (Spread Mengambang)',
                'definisi' => 'Ini adalah perbedaan antara harga Bid dan Ask, yang berubah secara teratur, tergantung pada kondisi pasar: kenaikan harga permintaan atau harga penawaran.',
            ],
            [
                'istilah'  => 'Pasar Valuta Asing (Forex)',
                'definisi' => 'Pasar di mana mata uang pemerintah ditradingkan. Istilah umum lainnya untuk forex adalah pasar mata uang, valuta asing, dan FX.',
            ],
            [
                'istilah'  => 'Forex Trading (Perdagangan Valas)',
                'definisi' => 'Membeli atau menjual CFD atau kontrak berjangka dengan pertukaran mata uang pemerintah dan/atau mata uang kripto.',
            ],
            [
                'istilah'  => 'FTSE 100 Index',
                'definisi' => 'Financial Times Stock Exchange 100 Indeks. Ini adalah indeks yang terdiri dari 100 perusahaan terbesar yang terdaftar di bursa saham London.',
            ],
            [
                'istilah'  => 'Gap (Celah Harga)',
                'definisi' => 'Ini adalah tempat pada grafik di mana tidak ada kutipan. Paling sering, gap dapat diamati di antara sesi trading atau berita perubahan harga yang cepat.',
            ],
            [
                'istilah'  => 'Garis Tren (Trendline)',
                'definisi' => 'Garis pada grafik yang menunjukkan arah pergerakan harga instrumen. Dibentuk dengan menghubungkan tiga atau lebih puncak atau lembah.',
            ],
            [
                'istilah'  => 'British Pound Sterling',
                'definisi' => 'Mata uang resmi Inggris.',
            ],
            [
                'istilah'  => 'Hedging (Lindung Nilai)',
                'definisi' => 'Cara manajemen risiko dalam trading.',
            ],
            [
                'istilah'  => 'In the Money',
                'definisi' => 'Ketika sebuah opsi mencapai titik harga aset dasar.',
            ],
            [
                'istilah'  => 'Indeks Pasar (Index)',
                'definisi' => 'Ini adalah indikator bagaimana keadaan di pasar keuangan atau di industri tertentu. Dengan kata lain, ini adalah ukuran yang mencerminkan suasana pasar pada titik waktu tertentu.',
            ],
            [
                'istilah'  => 'Indikator Teknikal',
                'definisi' => 'Ini adalah grafik khusus yang dihitung secara matematis, yang membantu trader menemukan momen paling menguntungkan untuk membuka trading dan memprediksi pergerakan harga di masa depan.',
            ],
            [
                'istilah'  => 'Indikator Utama (Leading Indicator)',
                'definisi' => 'Ini adalah indeks, yang terdiri dari sepuluh indikator ekonomi makro terpenting. Ini memprediksi bagaimana ekonomi akan berkembang dalam beberapa bulan ke depan. Indikator utama dapat berguna untuk membantu memperkirakan waktu, besaran, dan durasi kondisi ekonomi dan bisnis di masa depan.',
            ],
            [
                'istilah'  => 'Instrumen Keuangan (Instrument)',
                'definisi' => 'Dalam istilah keuangan, ini adalah aset yang dapat dibeli, dijual, atau ditradingkan. Biasanya mengacu pada saham, pasangan mata uang, atau komoditas, tetapi dapat berupa apa saja yang bernilai yang dapat ditradingkan.',
            ],
            [
                'istilah'  => 'Investor',
                'definisi' => 'Seseorang yang berinvestasi dalam alat keuangan untuk mendapatkan keuntungan. Saham, obligasi, emas, minyak, pasangan mata uang dan PAMM sangat populer di kalangan investor.',
            ],
            [
                'istilah'  => 'Initial Public Offering',
                'definisi' => 'Pertama kali perusahaan menawarkan saham untuk dijual kepada publik.',
            ],
            [
                'istilah'  => 'Japanese Yen',
                'definisi' => 'Mata uang resmi Jepang.',
            ],
            [
                'istilah'  => 'Japanese Candlestick (Grafik Lilin)',
                'definisi' => 'Jenis grafik pada platform trading, yang memungkinkan trader untuk memeriksa fluktuasi harga pasar dari suatu instrumen. Setiap candle memiliki 2 bagian: body (sektor antara harga Open dan Close) dan shadow\\wick (selisih antara body dan harga High\\Low).',
            ],
            [
                'istilah'  => 'Kalender Ekonomi (Economic Calendar)',
                'definisi' => 'Jadwal laporan pemerintah dan ekonomi yang dapat mempengaruhi pergerakan pasar',
            ],
            [
                'istilah'  => 'Komoditas (Commodities)',
                'definisi' => 'Sumber daya alam yang berasal dari dalam tanah, seperti bahan galian dan pangan, yang memiliki ukuran mutu yang baku sehingga dapat dipertukarkan seperti Minyak Mentah, Gas Bumi, WTI, Perak, Emas, dll.',
            ],
            [
                'istilah'  => 'Kurs Valuta Asing (Exchange Rate)',
                'definisi' => 'Harga yang melekat pada pasangan mata uang. Disebut juga kurs FX atau kurs valuta asing.',
            ],
            [
                'istilah'  => 'Fibonacci Retracement',
                'definisi' => 'Pola matematika yang menunjukkan area resistensi pada grafik.',
            ],
            [
                'istilah'  => 'Kutipan Mata Uang (Currency Quotation)',
                'definisi' => 'Dalam pasangan mata uang, mata uang kedua adalah mata uang kutipan. Ini adalah mata uang yang dibeli. Hal ini juga dikenal sebagai mata uang counter.',
            ],
            [
                'istilah'  => 'Leverage (Daya Ungkit)',
                'definisi' => 'Daya ungkit modal yang dipinjamkan broker sehingga trader dapat mengontrol volume transaksi yang jauh lebih besar daripada modal riil yang didepositkan.',
            ],
            [
                'istilah'  => 'Limit Order (Order Batas)',
                'definisi' => 'Jumlah yang bersedia Anda bayarkan untuk membeli atau menjual sekuritas.',
            ],
            [
                'istilah'  => 'Likuiditas Pasar (Liquidity)',
                'definisi' => 'Ini menunjukkan betapa mudahnya melikuidasi (menjual) sekuritas.',
            ],
            [
                'istilah'  => 'Penyedia Likuiditas (Liquidity Provider)',
                'definisi' => 'Ini adalah perusahaan keuangan terbesar yang membantu perusahaan forex untuk menyediakan pelanggan dengan kutipan yang dapat diandalkan dan eksekusi instan dari pesanan mereka.',
            ],
            [
                'istilah'  => 'Long Position (Posisi Beli)',
                'definisi' => 'Untuk membeli aset mengharapkan pertumbuhan harganya.',
            ],
            [
                'istilah'  => 'Lot (Volume Kontrak)',
                'definisi' => 'Ukuran volume standar kontrak transaksi dalam perdagangan valuta asing. 1 standard lot bernilai 100.000 unit mata uang dasar, mini lot bernilai 0.10 (10.000 unit), dan micro lot bernilai 0.01 (1.000 unit).',
            ],
            [
                'istilah'  => 'Low Price (Harga Terendah)',
                'definisi' => 'Ini adalah titik terendah yang telah dicapai harga selama jangka waktu tertentu.',
            ],
            [
                'istilah'  => 'Moving Average',
                'definisi' => 'Statistik yang rata-rata kisaran harga untuk memberikan indikasi tren yang ditunjukkan sekuritas. Indikator grafik garis ini menghaluskan fluktuasi harga untuk menunjukkan tren, support dan resistance.',
            ],
            [
                'istilah'  => 'Moving Average Convergence Divergence',
                'definisi' => 'Moving Average/Convergence/Divergence. Rata-rata pergerakan eksponensial panjang dan pendek. Mereka membantu trader melihat perubahan harga dan tren naik atau turun.',
            ],
            [
                'istilah'  => 'Margin (Jaminan)',
                'definisi' => 'Tingkat dana tertentu yang dibutuhkan di akun Anda saat Anda menggunakan leverage untuk trading.',
            ],
            [
                'istilah'  => 'Margin Call',
                'definisi' => 'Peringatan resmi dari sistem broker bahwa ekuitas akun investor berada di bawah batas margin minimum yang diperlukan untuk mempertahankan posisi trading terbuka.',
            ],
            [
                'istilah'  => 'Kapitalisasi Pasar (Market Cap)',
                'definisi' => 'Nilai total semua saham yang beredar dari suatu perusahaan.',
            ],
            [
                'istilah'  => 'Market Maker (Pembuat Pasar)',
                'definisi' => 'Forex atau bursa efek yang siap membeli atau menjual sekuritas pada waktu tertentu.',
            ],
            [
                'istilah'  => 'Market Order (Order Pasar Instan)',
                'definisi' => 'Perintah untuk membeli atau menjual pada tingkat berapa pun yang diterima pasar pada saat itu.',
            ],
            [
                'istilah'  => 'Margin Trading (Perdagangan Margin)',
                'definisi' => 'Menggunakan dana pinjaman yang disediakan oleh broker untuk membuka posisi volume yang lebih besar untuk trading daripada yang dimungkinkan oleh modal sendiri trader.',
            ],
            [
                'istilah'  => 'MetaTrader MultiTerminal',
                'definisi' => 'Ini adalah terminal MT4, yang ditujukan untuk pengelolaan beberapa akun secara bersamaan. Ini sangat membantu bagi mereka yang mengelola akun investor dan untuk trader yang bekerja dengan banyak akun secara bersamaan.',
            ],
            [
                'istilah'  => 'Nasdaq 100 Index',
                'definisi' => 'Indeks yang mencakup 100 perusahaan AS terbesar, sebagian besar terkait teknologi dan internet, dan terdaftar di bursa saham Nasdaq.',
            ],
            [
                'istilah'  => 'Non-Farm Payrolls',
                'definisi' => 'Salah satu indikator ekonomi utama AS. Mewakili jumlah total pekerja yang dibayar di AS dikurangi pegawai pertanian, pegawai pemerintah, pegawai rumah tangga swasta, dan pegawai organisasi nirlaba. Diterbitkan pada hari Jumat pertama setiap bulan.',
            ],
            [
                'istilah'  => 'Non-Market Quote (Kuotasi Abnormal)',
                'definisi' => 'Ini adalah kutipan di dalam celah harga atau beberapa harga yang hampir mendekati, yang membentuk celah harga.',
            ],
            [
                'istilah'  => 'Open Market (Pembukaan Pasar)',
                'definisi' => 'Ini adalah pembukaan kembali pasar setelah penutupan sesi trading, pada hari libur/akhir pekan.',
            ],
            [
                'istilah'  => 'Open Position (Posisi Terbuka)',
                'definisi' => 'Ini adalah transaksi beli/jual yang sudah Anda buka dan belum sempat Anda tutup.',
            ],
            [
                'istilah'  => 'Oscillator (Osilator Teknikal)',
                'definisi' => 'Jenis indikator yang menyelidiki pasar.',
            ],
            [
                'istilah'  => 'Over The Counter',
                'definisi' => 'Over The Counter trades. Sebuah metode untuk membeli saham, orang-ke-orang ketika mereka tidak tersedia di bursa negara Anda.',
            ],
            [
                'istilah'  => 'Out of the Money',
                'definisi' => 'Ketika aset tidak berada dalam jangkauan opsi yang akan dieksekusi.',
            ],
            [
                'istilah'  => 'Pasangan Mata Uang (Currency Pair)',
                'definisi' => 'Ini adalah kutipan dari dua mata uang yang berbeda, dengan nilai satu mata uang yang dikutip terhadap yang lain. Dengan kata lain, ini adalah berapa banyak mata uang nasional suatu negara bersedia membayar untuk mata uang negara lain saat ini.',
            ],
            [
                'istilah'  => 'Pendapatan Pasif (Passive Income)',
                'definisi' => 'Uang yang datang kepada Anda dari investasi. Ini bukan uang yang secara aktif Anda kerjakan atau peroleh dalam suatu pekerjaan.',
            ],
            [
                'istilah'  => 'Penny Stock (Saham Murahan)',
                'definisi' => 'Saham perusahaan kecil dengan harga kurang dari  per lembar.',
            ],
            [
                'istilah'  => 'Price to Earnings Ratio',
                'definisi' => 'Price to Earnings Ratio. Ini membandingkan harga saham saat ini dengan pendapatan per saham perusahaan dengan membagi harga saham ekuitas dengan pendapatan tahunannya. Ini memberikan perkiraan kasar nilai aset dan berapa tahun Anda perlu memiliki aset untuk mendapatkan kembali biaya saham.',
            ],
            [
                'istilah'  => 'Pip (Percentage in Point)',
                'definisi' => 'Satuan ukuran standar terkecil untuk mengukur fluktuasi perubahan harga antara dua mata uang (biasanya desimal ke-4 pada pair EUR/USD, atau desimal ke-2 pada pair JPY).',
            ],
            [
                'istilah'  => 'Pola Grafik Candlestick',
                'definisi' => 'Bagan yang menampilkan harga tertinggi, terendah, pembukaan, dan penutupan sekuritas untuk jangka waktu tertentu. Pola grafik yang terlihat seperti lilin dengan bagian tengah yang gemuk dan garis \'sumbu\' memanjang dari atas dan bawah.',
            ],
            [
                'istilah'  => 'Portofolio Investasi (Portfolio)',
                'definisi' => 'Kumpulan total aset yang dimiliki atau dikuasai oleh seorang trader.',
            ],
            [
                'istilah'  => 'Profit (Keuntungan Bersih)',
                'definisi' => 'Ini adalah pendapatan trader dari sebuah transaksi.',
            ],
            [
                'istilah'  => 'Range (Rentang Fluktuasi)',
                'definisi' => 'Ini adalah perbedaan antara tingkat harga maksimum dan minimum pada periode tertentu.',
            ],
            [
                'istilah'  => 'Rebate (Pengembalian Komisi)',
                'definisi' => 'Cashback per lot yang ditradingkan.',
            ],
            [
                'istilah'  => 'Rollover (Perpanjangan Posisi Semalam)',
                'definisi' => 'Uang yang diperoleh atau hilang dari trading Anda saat disimpan semalaman untuk hari trading berikutnya.',
            ],
            [
                'istilah'  => 'Relative Strength Index',
                'definisi' => 'RSI adalah indikator momentum yang mengukur kecepatan dan perubahan pergerakan harga dan ditampilkan menggunakan angka mulai dari nol hingga 100. Ini menunjukkan kondisi oversold dan overbought yang menunjukkan kemungkinan tren dan perubahan tren.',
            ],
            [
                'istilah'  => 'Saham Blue Chip',
                'definisi' => 'Ini adalah saham yang mapan, dapat diandalkan, dan berkapitalisasi tinggi yang dianggap berisiko rendah.',
            ],
            [
                'istilah'  => 'Scalping',
                'definisi' => 'Metode trading hari banyak trading cepat lebih suka digunakan. Mereka mengambil keuntungan dari perubahan harga yang sangat kecil.',
            ],
            [
                'istilah'  => 'Short Position (Posisi Jual)',
                'definisi' => 'Untuk menjual aset mengharapkan penurunan harga.',
            ],
            [
                'istilah'  => 'Short Selling (Penjualan Kosong)',
                'definisi' => 'Menjual sekuritas yang tidak dimiliki oleh penjual, berdasarkan ekspektasi bahwa sekuritas tersebut akan menurun dan dibeli kembali dengan harga yang lebih rendah untuk mendapatkan keuntungan.',
            ],
            [
                'istilah'  => 'Slippage (Selisih Eksekusi Harga)',
                'definisi' => 'Selisih antara harga trading yang diharapkan dan harga trading sebenarnya. Ini adalah jumlah perubahan harga dari saat Anda mengeluarkan pesanan hingga dieksekusi.',
            ],
            [
                'istilah'  => 'Standard & Poor\'s 500 Index',
                'definisi' => 'Indeks yang mencakup 500 perusahaan publik AS terbesar. Dapat digunakan sebagai salah satu indikator perekonomian AS.',
            ],
            [
                'istilah'  => 'Spike (Lonjakan Harga Ekstrem)',
                'definisi' => 'Ini adalah perbedaan yang tidak terduga dari kutipan sebelumnya. Sebuah lonjakan selalu mencirikan pasar sebagai kegelisahan.',
            ],
            [
                'istilah'  => 'Spot Price (Harga Pasar Tunai)',
                'definisi' => 'Harga saat ini untuk pasangan mata uang.',
            ],
            [
                'istilah'  => 'Spread (Selisih Harga Jual-Beli)',
                'definisi' => 'Selisih harga antara harga beli (Ask) dan harga jual (Bid) yang ditawarkan oleh broker. Spread merupakan biaya langsung saat Anda membuka posisi trading.',
            ],
            [
                'istilah'  => 'Stop Loss Order (Pembatas Kerugian)',
                'definisi' => 'Titik yang telah ditentukan di mana Anda menginstruksikan broker Anda untuk menjual sekuritas jika nilainya turun. Ini bisa berupa persentase perubahan atau titik harga tertentu.',
            ],
            [
                'istilah'  => 'Stop Out Level',
                'definisi' => 'Ini adalah situasi ketika level margin turun di bawah titik tertentu dan broker mulai menutup posisi secara otomatis. Ini juga disebut Panggilan Margin.',
            ],
            [
                'istilah'  => 'Suku Bunga Acuan (Interest Rate)',
                'definisi' => 'Ini adalah tingkat bunga minimum, yang ditetapkan oleh bank sentral dari berbagai negara.',
            ],
            [
                'istilah'  => 'Swap (Bunga Inap Semalam)',
                'definisi' => 'Ini adalah biaya untuk transfer posisi terbuka pada malam hari.',
            ],
            [
                'istilah'  => 'Take Profit Order (Target Keuntungan)',
                'definisi' => 'Ini adalah perintah kepada broker untuk menutup transaksi ketika harga mencapai level yang ditentukan.',
            ],
            [
                'istilah'  => 'Tick (Perubahan Harga Minimum)',
                'definisi' => 'Ini adalah pergerakan ke atas atau ke bawah minimum dalam harga instrumen.',
            ],
            [
                'istilah'  => 'Trading Terminal (Platform Trading)',
                'definisi' => 'Platform perangkat lunak untuk trading, yang memungkinkan koneksi ke pasar keuangan internasional, trading dengan berbagai instrumen.',
            ],
            [
                'istilah'  => 'Trailing Stop Order',
                'definisi' => 'Ini adalah perintah tetap untuk menjual ekuitas Anda ketika jatuh persentase tertentu di bawah nilai tertinggi ekuitas saat Anda memilikinya.',
            ],
            [
                'istilah'  => 'United States Dollar',
                'definisi' => 'Mata uang resmi Amerika Serikat.',
            ],
            [
                'istilah'  => 'Volatilitas Pasar (Volatility)',
                'definisi' => 'Kecepatan perubahan harga dalam periode waktu tertentu. Volatilitas yang lebih tinggi membawa spread yang lebih tinggi.',
            ],
            [
                'istilah'  => 'Trading Volume (Volume Transaksi)',
                'definisi' => 'Berapa kali sekuritas dibeli atau dijual dalam jangka waktu tertentu, biasanya sehari.',
            ],
        ];

        foreach ($glosariumData as $item) {
            EdukasiGlosarium::updateOrCreate(
                ['istilah' => $item['istilah']],
                [
                    'definisi'   => $item['definisi'],
                    'created_by' => $adminId,
                ]
            );
        }

        // ---------------------------------------------------------------------
        // 2. Data Kategori Topik Panduan & Bantuan (Pusat Bantuan)
        // ---------------------------------------------------------------------
        // Bersihkan data panduan bantuan dan kategori lama agar terfokus pada alur investasi
        PanduanBantuan::query()->delete();
        PanduanKategori::query()->delete();

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

        // ---------------------------------------------------------------------
        // 4. Data Topik Tanya Jawab (FAQ) & Pertanyaan/Jawaban
        // ---------------------------------------------------------------------
        $faqTopikData = [
            [
                'nama'      => 'Umum & Legalitas',
                'deskripsi' => 'Informasi umum mengenai model bisnis, legalitas, kepemilikan dana, dan kontrak perjanjian.',
                'urutan'    => 1,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Apakah dana modal saya aman di akun broker pribadi?',
                        'jawaban'    => "Sistem copytrade YZD Center bekerja dengan prinsip non-custodial. Master Trader hanya memiliki hak mengeksekusi order beli dan jual secara otomatis pada akun trading Anda. Master Trader SAMA SEKALI TIDAK MEMILIKI AKSES ataupun wewenang untuk melakukan penarikan dana (withdrawal). Penarikan dana modal maupun keuntungan hanya dapat diproses langsung oleh Anda sendiri menuju rekening bank pribadi terdaftar.",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Mengapa diperlukan surat perjanjian bermaterai?',
                        'jawaban'    => "Surat perjanjian bermaterai memberikan kepastian hukum yang mengikat secara sah antara Anda sebagai Investor dan Master Trader. Dokumen ini menegaskan hak bagi hasil profit, pembagian komisi, serta batasan wewenang teknis trading.",
                        'urutan'     => 2,
                    ],
                ],
            ],
            [
                'nama'      => 'Sistem Copytrade & MT4',
                'deskripsi' => 'Seputar teknis penyalinan transaksi, platform MetaTrader 4, dan server VPS.',
                'urutan'    => 2,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Apakah saya harus menyalakan komputer/laptop selama 24 jam?',
                        'jawaban'    => "Tidak perlu. Seluruh proses eksekusi sinyal dan penyalinan transaksi dikelola secara terpusat melalui infrastruktur Virtual Private Server (VPS) berkecepatan tinggi dengan uptime 99.9%. Anda cukup memeriksa riwayat trading melalui aplikasi MT4 ponsel pintar Anda.",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Berapa ketahanan modal (pip buffer) yang diterapkan?',
                        'jawaban'    => "YZD Center mengutamakan manajemen risiko modal yang teruji. Rumus lot yang digunakan dirancang untuk menahan pergerakan pasar minimal 50.000 hingga 75.000 pip, sehingga potensi Margin Call dapat dihindari secara disiplin.",
                        'urutan'     => 2,
                    ],
                ],
            ],
            [
                'nama'      => 'Deposit, Pembagian Hasil & Penarikan',
                'deskripsi' => 'Tata cara penyetoran dana, skema profit sharing 30:30:40, dan penarikan keuntungan.',
                'urutan'    => 3,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Bagaimana skema pembagian keuntungan (profit share)?',
                        'jawaban'    => "Dari total keuntungan yang diraih pada periode evaluasi, pembagian dialokasikan sebagai berikut: 30% Master Fee (jasa trading), 30% Investor Payout (penarikan laba oleh investor), dan 40% dialokasikan untuk compounding modal agar pertumbuhan dana semakin optimal.",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Kapan saya dapat menarik keuntungan atau modal awal?',
                        'jawaban'    => "Modal awal dan profit berada 100% di akun broker Anda. Anda memiliki hak penuh untuk menarik dana Anda sewaktu-waktu sesuai ketentuan penarikan broker mitra.",
                        'urutan'     => 2,
                    ],
                ],
            ],
            [
                'nama'      => 'Kemitraan & Afiliasi',
                'deskripsi' => 'Program referral, skema 4-Tier komisi, dan evaluasi 90 hari.',
                'urutan'    => 4,
                'faqs'      => [
                    [
                        'pertanyaan' => 'Kapan komisi affiliator dapat diklaim?',
                        'jawaban'    => "Komisi affiliator dapat diajukan setelah akun copytrade downline binaan Anda berstatus aktif menyalin selama minimal 90 hari kalender, dan akun berada dalam kondisi laba bersih (positif profit).",
                        'urutan'     => 1,
                    ],
                    [
                        'pertanyaan' => 'Berapa besaran persentase komisi affiliator?',
                        'jawaban'    => "Besaran komisi dihitung dari bagian Master Fee berdasarkan tingkatan modal downline: Tier 1 (50%), Tier 2 (40%), Tier 3 (30%), dan Tier 4 (20%).",
                        'urutan'     => 2,
                    ],
                ],
            ],
        ];

        foreach ($faqTopikData as $topikItem) {
            $topik = EdukasiFaqTopik::updateOrCreate(
                ['nama' => $topikItem['nama']],
                [
                    'deskripsi' => $topikItem['deskripsi'],
                    'urutan'    => $topikItem['urutan'],
                    'is_active' => true,
                ]
            );

            foreach ($topikItem['faqs'] as $faqItem) {
                EdukasiFaq::updateOrCreate(
                    [
                        'topik_id'   => $topik->id,
                        'pertanyaan' => $faqItem['pertanyaan'],
                    ],
                    [
                        'jawaban'    => $faqItem['jawaban'],
                        'urutan'     => $faqItem['urutan'],
                        'is_active'  => true,
                        'created_by' => $adminId,
                    ]
                );
            }
        }
    }
}
