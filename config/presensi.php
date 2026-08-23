<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kata Sandi Akun Awal
    |--------------------------------------------------------------------------
    |
    | Nilai ini hanya digunakan ketika DatabaseSeeder membuat akun awal.
    | Pada hosting publik, simpan nilainya sebagai secret dan jangan pernah
    | menuliskannya langsung ke dalam repository.
    |
    */
    'initial_password' => env('PRESENSI_INITIAL_PASSWORD'),
];
