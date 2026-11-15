<?php

namespace Database\Seeders;

use App\Models\EdukasiGlosarium;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder data glosarium forex untuk modul Edukasi.
 */
class EdukasiGlosariumSeeder extends Seeder
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
    }
}
