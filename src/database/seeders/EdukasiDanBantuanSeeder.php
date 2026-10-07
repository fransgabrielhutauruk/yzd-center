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
        $kategoriData = [
            [
                'nama'      => 'Panduan Broker & Deposit',
                'slug'      => 'broker-deposit',
                'deskripsi' => 'Langkah pembukaan akun di broker mitra resmi (HFM & XM), verifikasi area klien, dan tata cara deposit modal.',
                'icon'      => 'Wallet',
                'urutan'    => 1,
            ],
            [
                'nama'      => 'Panduan Investasi Tier 1, 2, & 3',
                'slug'      => 'investasi-tiering',
                'deskripsi' => 'Karakteristik alokasi modal, spesifikasi jenis akun broker, leverage, dan alur aktivasi portofolio copytrade untuk Tier 1, 2, dan 3.',
                'icon'      => 'TrendingUp',
                'urutan'    => 2,
            ],
            [
                'nama'      => 'Manajemen Copytrade & MT4',
                'slug'      => 'copytrade-mt4',
                'deskripsi' => 'Tutorial pendaftaran akun copytrade, penandatanganan surat perjanjian bermaterai, dan pemantauan portofolio.',
                'icon'      => 'TrendingUp',
                'urutan'    => 3,
            ],
            [
                'nama'      => 'Pendaftaran & Verifikasi KYC',
                'slug'      => 'pendaftaran-kyc',
                'deskripsi' => 'Panduan pengisian profil investor, pengunggahan foto KTP yang sah, dan penunjukan data ahli waris.',
                'icon'      => 'UserCheck',
                'urutan'    => 4,
            ],
            [
                'nama'      => 'Kemitraan & Komisi Afiliasi',
                'slug'      => 'kemitraan-komisi',
                'deskripsi' => 'Informasi pembagian kode referral, skema 4-Tier komisi affiliator, dan pengajuan klaim bagi hasil.',
                'icon'      => 'Users',
                'urutan'    => 5,
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
        // 3. Data Artikel Panduan & Video Bantuan (Format TipTap Rich HTML)
        // ---------------------------------------------------------------------
        $artikelData = [
            [
                'kategori_slug' => 'broker-deposit',
                'judul'         => 'Cara Membuka Akun MT4 & Melakukan Deposit di Broker HFM (Tier 1)',
                'slug'          => 'cara-buka-akun-deposit-hfm',
                'ringkasan'     => 'Panduan bertahap registrasi akun MT4 di broker HFM melalui link referral resmi Master Trader dan verifikasi deposit minimum.',
                'konten'        => '<h2>Langkah-langkah Membuka Akun di Broker HFM</h2><p>Berikut adalah petunjuk resmi pembukaan akun trading MT4 di broker mitra HFM yang terhubung langsung dengan sistem copytrade YZD Center:</p><ol><li><p><strong>Gunakan Link Referral Resmi:</strong> Buka tautan referral resmi Master Trader yang tertera pada dasbor portofolio YZD Center untuk memastikan akun berada dalam jaringan resmi Pak Abi.</p></li><li><p><strong>Registrasi Akun myHF:</strong> Isi formulir identitas diri pada portal HFM sesuai dengan data KTP Anda untuk mempermudah verifikasi.</p></li><li><p><strong>Pilih Tipe Akun MT4:</strong> Untuk portofolio Tier 1, pilih tipe akun <strong>Cent</strong> dengan mata uang USD dan leverage rekomendasi 1:500 hingga 1:1000.</p></li><li><p><strong>Verifikasi Identitas:</strong> Unggah foto KTP dan bukti alamat tempat tinggal Anda di portal area klien HFM.</p></li><li><p><strong>Lakukan Deposit Modal:</strong> Lakukan deposit dana riil sesuai alokasi modal yang Anda rencanakan. Simpan bukti transfer atau tangkapan layar saldo akun MT4 Anda.</p></li></ol><blockquote><p><strong>Catatan Penting:</strong> Pastikan Anda mencatat Nomor Akun MT4 dan Server Broker dengan cermat untuk didaftarkan pada formulir portofolio copytrade YZD Center.</p></blockquote><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950" data-video-embed="" data-video-src="https://www.youtube.com/embed/dQw4w9WgXcQ" data-provider="YouTube"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" class="absolute inset-0 w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="true"></iframe></div>',
                'video_url'     => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            [
                'kategori_slug' => 'investasi-tiering',
                'judul'         => 'Panduan Investasi Tier 1: Alokasi Modal Bebas & Akun Cent HFM',
                'slug'          => 'panduan-investasi-tier-1',
                'ringkasan'     => 'Karakteristik Tier 1 dengan alokasi modal di bawah $5.000 tanpa batas minimal nominal, penggunaan akun Cent HFM, dan rekomendasi leverage.',
                'konten'        => '<h2>Mengenal Portofolio Investasi Tier 1</h2><p>Portofolio <strong>Tier 1</strong> dirancang khusus bagi investor pemula maupun investor yang ingin memulai dengan alokasi modal fleksibel di bawah <strong>$5.000</strong> (sekitar Rp 80.000.000). Tier ini memberikan kemudahan karena bebas batas minimal deposit.</p><h3>Spesifikasi Teknis Akun Tier 1:</h3><ul><li><p><strong>Batas Modal:</strong> Di bawah $5.000 USD (bebas nominal modal).</p></li><li><p><strong>Broker Mitra Resmi:</strong> HFM (HotForex).</p></li><li><p><strong>Tipe Akun Trading:</strong> Akun Cent MT4 (memungkinkan transaksi lot mikro presisi tinggi).</p></li><li><p><strong>Leverage Rekomendasi:</strong> 1:500 hingga 1:1000.</p></li><li><p><strong>Karakteristik Strategi:</strong> Manajemen risiko konservatif dengan ketahanan margin maksimal terhadap fluktuasi pasar.</p></li></ul><h3>Tahapan Aktivasi Akun Tier 1:</h3><ol><li><p><strong>Buka Akun Cent di HFM:</strong> Pastikan mendaftar melalui tautan referral resmi YZD Center.</p></li><li><p><strong>Deposit Modal:</strong> Lakukan deposit ke akun Cent Anda sesuai kesiapan modal.</p></li><li><p><strong>Daftarkan ke Sistem YZD Center:</strong> Buka menu <em>Portofolio Investasi</em> &gt; <em>Tambah Akun Investasi</em>, lalu pilih <strong>Tier 1</strong>.</p></li><li><p><strong>Unduh &amp; Tanda Tangani Perjanjian:</strong> Unduh template surat perjanjian bermaterai Rp 10.000, bubuhkan tanda tangan fisik, dan unggah kembali ke sistem.</p></li></ol><blockquote><p><strong>Keuntungan Akun Cent:</strong> Modal $100 akan terbaca sebagai 10.000 USC (United States Cent) pada platform MT4, memberikan ruang ketahanan drawdown yang jauh lebih aman saat eksekusi copytrade berjalan.</p></blockquote>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'investasi-tiering',
                'judul'         => 'Panduan Investasi Tier 2: Akun Standard ($5.000 - $300.000) & Likuiditas Langsung',
                'slug'          => 'panduan-investasi-tier-2',
                'ringkasan'     => 'Karakteristik Tier 2 untuk modal menengah $5.000 s/d $300.000, penggunaan broker mitra resmi XM/HFM akun Standard, dan eksekusi order likuiditas.',
                'konten'        => '<h2>Mengenal Portofolio Investasi Tier 2</h2><p>Portofolio <strong>Tier 2</strong> ditujukan bagi investor modal menengah dengan rentang alokasi <strong>$5.000 s/d $300.000 USD</strong>. Pada tingkatan ini, transaksi dieksekusi menggunakan akun Standard yang terhubung langsung ke penyedia likuiditas internasional (*liquidity provider*).</p><h3>Spesifikasi Teknis Akun Tier 2:</h3><ul><li><p><strong>Batas Alokasi Modal:</strong> $5.000 USD s/d $300.000 USD.</p></li><li><p><strong>Broker Mitra Resmi:</strong> XM atau HFM.</p></li><li><p><strong>Tipe Akun Trading:</strong> Akun MT4 Standard / Pro.</p></li><li><p><strong>Kecepatan Eksekusi:</strong> Direct Market Execution dengan spread kompetitif.</p></li><li><p><strong>Leverage Rekomendasi:</strong> 1:200 hingga 1:500.</p></li></ul><h3>Tahapan Aktivasi Akun Tier 2:</h3><ol><li><p><strong>Pilih Broker Mitra:</strong> Anda dapat memilih broker XM atau HFM sesuai preferensi metode perbankan lokal yang Anda gunakan.</p></li><li><p><strong>Buka Akun Standard MT4:</strong> Daftarkan akun tipe Standard dengan mata uang USD.</p></li><li><p><strong>Lakukan Verifikasi KYC Broker &amp; Deposit:</strong> Pastikan akun broker sudah terverifikasi penuh sebelum mentransfer modal.</p></li><li><p><strong>Registrasi Akun di YZD Center:</strong> Masukkan nomor akun MT4, pilih <strong>Tier 2</strong>, dan lengkapi data rekening bank untuk verifikasi penarikan hasil bagi keuntungan.</p></li><li><p><strong>Finalisasi Surat Kontrak:</strong> Unggah lembar surat perjanjian bermaterai yang telah ditandatangani.</p></li></ol><blockquote><p><strong>Manajemen Portofolio Khusus:</strong> Akun Tier 2 mendapatkan pemantauan berkala dari tim teknis YZD Center guna memastikan keselarasan lot trading dan eksekusi sinyal real-time.</p></blockquote>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'investasi-tiering',
                'judul'         => 'Panduan Investasi Tier 3: Akun VIP Dedicated VPS ($300.000+) & Server Privat',
                'slug'          => 'panduan-investasi-tier-3',
                'ringkasan'     => 'Karakteristik Tier 3 untuk portofolio modal besar minimal $300.000 dengan penyiapan dedicated server VPS berkecepatan tinggi dan enkripsi penuh.',
                'konten'        => '<h2>Mengenal Portofolio Investasi Tier 3 (VIP High Net-Worth)</h2><p>Portofolio <strong>Tier 3</strong> adalah program investasi premium bagi institusi atau investor individual dengan alokasi modal besar minimal <strong>$300.000 USD</strong> ke atas. Portofolio Tier 3 mendapatkan fasilitas infrastruktur server dedicated mandiri (*Dedicated VPS*) dengan latensi sangat rendah (*ultra-low latency*).</p><h3>Keistimewaan &amp; Fasilitas Tier 3:</h3><ul><li><p><strong>Dedicated Virtual Private Server (VPS):</strong> Server privat berkecepatan tinggi yang disiapkan khusus dan dikelola langsung oleh tim teknis YZD Center.</p></li><li><p><strong>Enkripsi Kredensial Penuh:</strong> Master Password dan Investor Password MT4 dienkripsi secara aman dengan standar keamanan tingkat tinggi di database.</p></li><li><p><strong>Akses Pemantauan Privat:</strong> Dapatkan akses pemantauan portofolio privat eksklusif dan sinyal analitik mendalam.</p></li><li><p><strong>Prioritas Eksekusi &amp; Support Khusus:</strong> Jalur konsultasi langsung dengan Master Trader Pak Abi dan pemeliharaan teknis 24/7.</p></li></ul><h3>Tahapan Aktivasi Akun Tier 3:</h3><ol><li><p><strong>Konsultasi Awal:</strong> Tim YZD Center akan mendampingi proses penyiapan rekening institusional/pribadi pada broker mitra resmi.</p></li><li><p><strong>Alokasi Modal Riil:</strong> Lakukan penyetoran modal minimal $300.000 USD.</p></li><li><p><strong>Pendaftaran Tier 3 di Portal:</strong> Pilih <strong>Tier 3</strong> pada formulir penambahan akun dan lengkapi data kredensial MT4 yang akan dienkripsi oleh sistem.</p></li><li><p><strong>Setup Dedicated VPS:</strong> Tim teknis YZD Center akan mengonfigurasi dedicated VPS dalam waktu 1x24 jam kerja.</p></li><li><p><strong>Penandatanganan Perjanjian Eksklusif:</strong> Legalitas kontrak kerja sama eksklusif bermaterai disahkan.</p></li></ol><blockquote><p><strong>Keamanan Dana Mutlak:</strong> Seluruh modal tetap berada di akun broker atas nama pribadi/institusi investor sendiri tanpa hak penarikan oleh pihak mana pun (Non-Custodial).</p></blockquote>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'pendaftaran-kyc',
                'judul'         => 'Tips Agar Verifikasi Foto KTP dan Dokumen Cepat Disetujui',
                'slug'          => 'tips-verifikasi-ktp-cepat-disetujui',
                'ringkasan'     => 'Kriteria foto KTP yang tajam, pencahayaan cukup, dan data teks terbaca jelas oleh tim verifikasi kepatuhan hukum.',
                'konten'        => '<h2>Ketentuan Foto KTP dan Dokumen yang Sah</h2><p>Untuk memastikan proses verifikasi pendaftaran akun investor dan affiliator berjalan lancar tanpa penolakan, mohon perhatikan ketentuan berikut:</p><ul><li><p><strong>Foto Asli Fisik KTP:</strong> Gunakan foto langsung dari fisik KTP asli Anda. Jangan menggunakan foto fotokopi, tangkapan layar (screenshot), atau hasil scan buram.</p></li><li><p><strong>Pencahayaan Terang &amp; Bebas Pantulan:</strong> Pastikan ruangan cukup terang dan tidak ada pantulan kilau lampu (glare) yang menutupi NIK, nama lengkap, atau pasfoto.</p></li><li><p><strong>Semua Sudut Masuk Bingkai:</strong> Pastikan keempat sudut fisik kartu KTP berada utuh di dalam bingkai foto tanpa terpotong.</p></li><li><p><strong>Kesesuaian Data:</strong> NIK, nama lengkap, tempat tanggal lahir, dan alamat pada form pendaftaran wajib persis sama dengan yang tertera di fisik KTP.</p></li></ul><blockquote><p><strong>Kompresi Otomatis:</strong> Sistem YZD Center secara otomatis mengompresi ukuran foto dokumen di latar belakang sehingga pengunggahan tetap cepat tanpa mengurangi ketajaman teks.</p></blockquote>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'copytrade-mt4',
                'judul'         => 'Panduan Mengunduh Template & Mengunggah Surat Perjanjian Copytrade',
                'slug'          => 'panduan-surat-perjanjian-copytrade',
                'ringkasan'     => 'Cara memanfaatkan autofill otomatis template surat perjanjian bermaterai dan verifikasinya sebelum copytrade aktif.',
                'konten'        => '<h2>Alur Legalitas Surat Perjanjian Copytrade</h2><p>Sistem copytrade YZD Center mewajibkan surat perjanjian bertandatangan fisik bermaterai demi perlindungan hak dan kewajiban hukum antara Investor dan Master Trader:</p><ol><li><p><strong>Buka Menu Portofolio Investasi:</strong> Klik tombol <em>Tambah Akun Investasi</em> di portal investor.</p></li><li><p><strong>Unduh Template Surat Perjanjian:</strong> Klik tombol <em>Unduh Template Perjanjian</em> sesuai tier Anda. Data diri Anda dan nomor akun MT4 akan terisi secara otomatis (*autofill*).</p></li><li><p><strong>Cetak Dokumen &amp; Tempel Materai:</strong> Cetak lembar perjanjian pada kertas A4, tempelkan meterai Rp 10.000 pada kolom tanda tangan pihak kedua (investor), lalu bubuhkan tanda tangan fisik Anda mengenai meterai.</p></li><li><p><strong>Unggah Berkas ke Sistem:</strong> Foto lembar surat perjanjian yang telah ditandatangani secara jelas dan unggah kembali ke sistem untuk diverifikasi oleh admin.</p></li></ol><blockquote><p><strong>Verifikasi Cepat:</strong> Setelah diunggah, tim admin akan memverifikasi keabsahan dokumen dalam waktu 1x24 jam kerja sebelum proses penyalinan transaksi diaktifkan.</p></blockquote>',
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'kemitraan-komisi',
                'judul'         => 'Penjelasan Skema 4-Tier Komisi Afiliasi dari Profit Master Trader',
                'slug'          => 'skema-4-tier-komisi-afiliasi',
                'ringkasan'     => 'Pelajari cara kerja perhitungan komisi 20% hingga 50% dari bagian profit Master Trader berdasarkan modal downline binaan Anda.',
                'konten'        => '<h2>Pembagian Tier Komisi Afiliator</h2><p>Setiap mitra affiliator berhak atas komisi bagi hasil yang dihitung secara transparan dari bagian keuntungan (*profit share*) Master Trader berdasarkan modal downline:</p><ul><li><p><strong>Tier 1 (Modal &gt; $10.000 / &gt; Rp 160 Juta):</strong> Hak komisi <strong>50%</strong> dari bagian profit Master Trader.</p></li><li><p><strong>Tier 2 (Modal $1.000 - $10.000 / Rp 16 Juta - Rp 160 Juta):</strong> Hak komisi <strong>40%</strong> dari bagian profit Master Trader.</p></li><li><p><strong>Tier 3 (Modal $100 - $1.000 / Rp 1,6 Juta - Rp 16 Juta):</strong> Hak komisi <strong>30%</strong> dari bagian profit Master Trader.</p></li><li><p><strong>Tier 4 (Modal &lt; $100 / &lt; Rp 1,6 Juta):</strong> Hak komisi <strong>20%</strong> dari bagian profit Master Trader.</p></li></ul><h3>Syarat &amp; Ketentuan Pengajuan Klaim:</h3><ol><li><p><strong>Masa Aktif Akun:</strong> Akun copytrade downline harus berstatus aktif selama minimal <strong>90 hari (3 bulan)</strong> sejak mulai menyalin (<em>tanggal_mulai</em>).</p></li><li><p><strong>Drawdown Guard:</strong> Jika akun downline sedang mengalami penurunan ekuitas sementara (drawdown), fitur Drawdown Guard melindungi hak komisi affiliator agar tidak terjadi pemotongan saldo.</p></li></ol><div class="relative w-full aspect-video rounded-xl overflow-hidden my-4 border border-border shadow-xs bg-slate-950" data-video-embed="" data-video-src="https://www.youtube.com/embed/dBG3JQYxqzs" data-provider="YouTube"><iframe src="https://www.youtube.com/embed/dBG3JQYxqzs" class="absolute inset-0 w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen="true"></iframe></div>',
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
