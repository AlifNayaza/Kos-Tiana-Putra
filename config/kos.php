<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Kos
    |--------------------------------------------------------------------------
    */
    'name' => env('KOS_NAME', 'Kos Tiana Putra 1'),

    'payment' => [
        'bank_name'    => env('KOS_BANK_NAME', 'Bank Central Asia (BCA)'),
        'account_no'   => env('KOS_BANK_ACCOUNT_NO', '7700-8899-21'),
        'account_name' => env('KOS_BANK_ACCOUNT_NAME', 'H. Tiana Sutisna'),
        'qris_name'    => env('KOS_QRIS_NAME', 'KOS TIANA PUTRA 1'),
        'whatsapp_cs'  => env('KOS_WHATSAPP_CS', '0812-3456-7890'),
        'owner_name'   => env('KOS_OWNER_NAME', 'Pak H. Tiana - Pengelola'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metode pembayaran pesanan yang diperbolehkan
    |--------------------------------------------------------------------------
    | Nilai pertama dipakai sebagai default. Rekap tunai/non-tunai petugas
    | membaca kunci 'cash' agar tidak bergantung pada pencocokan teks bebas.
    */
    'payment_methods' => [
        'Tunai / Bayar di Kamar' => ['cash' => true],
        'QRIS'                   => ['cash' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Akun demo (khusus development)
    |--------------------------------------------------------------------------
    | Rute /demo-login/{role} hanya aktif di environment "local" atau ketika
    | KOS_DEMO_LOGIN=true. Jangan aktifkan di production.
    */
    'demo_login' => env('KOS_DEMO_LOGIN', env('APP_ENV', 'production') === 'local'),

    /*
    |--------------------------------------------------------------------------
    | Tagihan sewa otomatis
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'due_day' => (int) env('KOS_BILL_DUE_DAY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prediksi stok (Python)
    |--------------------------------------------------------------------------
    | Isi PYTHON_BIN dengan path/perintah Python yang memiliki pandas,
    | scikit-learn dan pymysql. Kosongkan untuk deteksi otomatis.
    */
    'python_bin' => env('PYTHON_BIN'),

    /*
    |--------------------------------------------------------------------------
    | Padatkan HTML
    |--------------------------------------------------------------------------
    | Menghapus indentasi, baris kosong, dan komentar HTML dari keluaran halaman.
    */
    'minify_html' => (bool) env('KOS_MINIFY_HTML', true),

    'upload' => [
        // Disk privat (tidak dapat diakses langsung lewat URL publik).
        'proof_disk' => 'local',
        'proof_dir'  => 'payment_proofs',
    ],
];
