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
                'nama'      => 'Pendaftaran & Verifikasi KYC',
                'slug'      => 'pendaftaran-kyc',
                'deskripsi' => 'Panduan pengisian profil investor, pengunggahan foto KTP yang sah, dan penunjukan data ahli waris.',
                'icon'      => 'UserCheck',
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
                'nama'      => 'Kemitraan & Komisi Afiliasi',
                'slug'      => 'kemitraan-komisi',
                'deskripsi' => 'Informasi pembagian kode referral, skema 4-Tier komisi affiliator, dan pengajuan klaim bagi hasil.',
                'icon'      => 'Users',
                'urutan'    => 4,
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
        // 3. Data Artikel Panduan & Video Bantuan
        // ---------------------------------------------------------------------
        $artikelData = [
            [
                'kategori_slug' => 'broker-deposit',
                'judul'         => 'Cara Membuka Akun MT4 & Melakukan Deposit di Broker HFM (Tier 1)',
                'slug'          => 'cara-buka-akun-deposit-hfm',
                'ringkasan'     => 'Panduan bertahap registrasi akun MT4 di broker HFM melalui link referral resmi Master Trader dan verifikasi deposit minimum.',
                'konten'        => "### Langkah-langkah Membuka Akun di Broker HFM:\n\n1. **Gunakan Link Referral Resmi:** Buka link referral yang tertera pada dasbor copytrade YZD Center untuk memastikan akun terdaftar di bawah jaringan Master Trader Pak Abi.\n2. **Registrasi Akun:** Isi formulir identitas sesuai data KTP Anda.\n3. **Pilih Tipe Akun:** Pilih akun berjenis MT4 Pro / Premium sesuai rekomendasi tier Anda.\n4. **Verifikasi Identitas:** Unggah dokumen KTP dan bukti domisili pada portal HFM.\n5. **Lakukan Deposit Modal:** Lakukan deposit minimum sesuai tier yang dipilih (Tier 1 min. $100). Simpan bukti transfer atau screenshot saldo MT4 Anda.",
                'video_url'     => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            [
                'kategori_slug' => 'pendaftaran-kyc',
                'judul'         => 'Tips Agar Verifikasi Foto KTP dan Dokumen Cepat Disetujui',
                'slug'          => 'tips-verifikasi-ktp-cepat-disetujui',
                'ringkasan'     => 'Kriteria foto KTP yang tajam, pencahayaan cukup, dan data teks terbaca jelas oleh tim verifikasi kepatuhan hukum.',
                'konten'        => "### Ketentuan Foto KTP yang Sah:\n\n- **Foto Asli Fisik KTP:** Jangan menggunakan foto fotokopi atau hasil scan buram.\n- **Pencahayaan Terang:** Pastikan tidak ada pantulan kilau lampu (glare) yang menutupi NIK atau nama lengkap.\n- **Semua Sudut Masuk Frame:** 4 sudut fisik KTP harus berada utuh di dalam bingkai foto.\n- **Kesesuaian Data:** Nama lengkap dan NIK yang diinput saat pendaftaran wajib persis sama dengan yang tertera di fisik KTP.",
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'copytrade-mt4',
                'judul'         => 'Panduan Mengunduh Template & Mengunggah Surat Perjanjian Copytrade',
                'slug'          => 'panduan-surat-perjanjian-copytrade',
                'ringkasan'     => 'Cara memanfaatkan autofill otomatis template surat perjanjian bermaterai dan verifikasinya sebelum copytrade aktif.',
                'konten'        => "### Alur Legalitas Surat Perjanjian:\n\n1. **Buka Menu Copytrade:** Klik tombol 'Tambah Akun Copytrade' di portal investor.\n2. **Unduh Template:** Klik tombol 'Unduh Template Surat Perjanjian'. Data diri Anda dan nomor akun MT4 akan terisi secara otomatis (*autofill*).\n3. **Cetak & Tanda Tangan:** Cetak dokumen, tempelkan meterai Rp 10.000 pada kolom tanda tangan pihak kedua (investor), lalu bubuhkan tanda tangan fisik Anda.\n4. **Unggah Berkas:** Foto dokumen yang sudah bertandatangan secara jelas (format PDF/JPG/PNG) dan unggah ke sistem. Sistem kami akan melakukan kompresi otomatis secara senyap.",
                'video_url'     => null,
            ],
            [
                'kategori_slug' => 'kemitraan-komisi',
                'judul'         => 'Penjelasan Skema 4-Tier Komisi Afiliasi dari Profit Master Trader',
                'slug'          => 'skema-4-tier-komisi-afiliasi',
                'ringkasan'     => 'Pelajari cara kerja perhitungan komisi 20% hingga 50% dari bagian profit Master Trader berdasarkan modal downline binaan Anda.',
                'konten'        => "### Pembagian Tier Komisi Afiliator:\n\n- **Tier 1 (Modal > $10.000 / > Rp 10 M):** Hak komisi **50%** dari bagian profit Master.\n- **Tier 2 (Modal $1.000 - $10.000 / Rp 1 M - 10 M):** Hak komisi **40%** dari bagian profit Master.\n- **Tier 3 (Modal $100 - $1.000 / Rp 100 Jt - 1 M):** Hak komisi **30%** dari bagian profit Master.\n- **Tier 4 (Modal < $100 / < Rp 100 Jt):** Hak komisi **20%** dari bagian profit Master.\n\n### Syarat Pengajuan Klaim:\nAkun copytrade downline harus berstatus aktif selama minimal **90 hari (3 bulan)** sejak mulai menyalin (*copy_started_at*). Jika akun mengalami kerugian (drawdown), perlindungan Drawdown Guard memastikan tidak ada potongan komisi.",
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
