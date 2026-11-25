<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Koneksi baca DB tenant
    |--------------------------------------------------------------------------
    |
    | Sidulur publik membaca langsung DB tenant. Kredensial dibaca dari env
    | bale-front dan TIDAK boleh memakai koneksi tulis admin.
    |
    */

    'connection' => [
        'name' => env('SIDULUR_DB_CONNECTION', 'sidulur'),
        'host' => env('SIDULUR_DB_HOST', '127.0.0.1'),
        'port' => env('SIDULUR_DB_PORT', '3306'),
        'database' => env('SIDULUR_DB_DATABASE'),
        'username' => env('SIDULUR_DB_USERNAME'),
        'password' => env('SIDULUR_DB_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blind index
    |--------------------------------------------------------------------------
    |
    | HARUS sama dengan SIDULUR_INDEX_KEY di aplikasi admin (bale-dev).
    | Nilai berbeda membuat pencarian/verifikasi NIK selalu gagal tertutup.
    |
    */

    'index_key' => env('SIDULUR_INDEX_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Dokumen
    |--------------------------------------------------------------------------
    */

    'document_link_days' => (int) env('SIDULUR_DOCUMENT_LINK_DAYS', 30),

    'document_disk' => env('SIDULUR_DOCUMENT_DISK', 'sidulur'),

    'whatsapp_center' => env('SIDULUR_WHATSAPP'),

    'instansi' => [
        'nama' => env('SIDULUR_INSTANSI', 'Dinas Sosial Kabupaten Ponorogo'),
        'alamat' => env('SIDULUR_ALAMAT', 'Jawa Timur'),
    ],

    'jam_layanan' => env('SIDULUR_JAM_LAYANAN', 'Senin–Kamis, 07.30–15.45 WIB; Jumat, 07.00–11.00 WIB'),

    /*
    |--------------------------------------------------------------------------
    | Landing page
    |--------------------------------------------------------------------------
    */

    'landing_slug' => 'sidulur',

];
