<?php

return [
    'glosarium' => [
        'model'    => \App\Models\EdukasiGlosarium::class,
        'label'    => 'Glosarium Istilah',
        'category' => 'edukasi_pengaturan',
        'display'  => 'istilah',
        'files'    => [],
        'children' => [],
    ],

    'faq' => [
        'model'    => \App\Models\EdukasiFaq::class,
        'label'    => 'FAQ',
        'category' => 'edukasi_pengaturan',
        'display'  => 'pertanyaan',
        'files'    => [],
        'children' => [],
        'parent'   => [
            'model' => \App\Models\EdukasiFaqTopik::class,
            'fk'    => 'topik_id',
            'label' => 'Topik FAQ',
        ],
    ],

    'faq-topik' => [
        'model'    => \App\Models\EdukasiFaqTopik::class,
        'label'    => 'Topik FAQ',
        'category' => 'edukasi_pengaturan',
        'display'  => 'nama',
        'files'    => [],
        'children' => ['faqs'],
    ],

    'panduan-kategori' => [
        'model'    => \App\Models\PanduanKategori::class,
        'label'    => 'Kategori Panduan',
        'category' => 'edukasi_pengaturan',
        'display'  => 'nama',
        'files'    => [],
        'children' => ['panduan'],
    ],

    'panduan-artikel' => [
        'model'    => \App\Models\PanduanBantuan::class,
        'label'    => 'Artikel Panduan',
        'category' => 'edukasi_pengaturan',
        'display'  => 'judul',
        'files'    => [],
        'children' => [],
        'parent'   => [
            'model' => \App\Models\PanduanKategori::class,
            'fk'    => 'kategori_id',
            'label' => 'Kategori Panduan',
        ],
    ],

    'profit-master' => [
        'model'    => \App\Models\ProfitMasterPost::class,
        'label'    => 'Post Profit Master',
        'category' => 'profit_master',
        'display'  => 'caption',
        'files'    => function ($post, $disk) {
            $dir = "profit_master/{$post->id}";
            if ($disk->exists($dir)) {
                $disk->deleteDirectory($dir);
            }
        },
        'children' => ['images'],
    ],

    'profit-master-foto' => [
        'model'    => \App\Models\ProfitMasterPostImage::class,
        'label'    => 'Foto Profit Master',
        'category' => 'profit_master',
        'display'  => 'file_name',
        'files'    => ['file_path'],
        'children' => [],
        'parent'   => [
            'model' => \App\Models\ProfitMasterPost::class,
            'fk'    => 'post_id',
            'label' => 'Post Profit Master',
        ],
    ],

    'laporan-profit' => [
        'model'    => \App\Models\LaporanProfitBulanan::class,
        'label'    => 'Laporan Profit Bulanan',
        'category' => 'master_fee',
        'display'  => 'nomor',
        'files'    => ['bukti_myfxbook_path', 'bukti_wd_path', 'bukti_transfer_path'],
        'children' => [],
    ],

    'undangan' => [
        'model'    => \App\Models\Invitation::class,
        'label'    => 'Tautan Undangan',
        'category' => 'undangan',
        'display'  => 'label',
        'files'    => [],
        'children' => [],
    ],
];
