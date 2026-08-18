<?php

/*
|--------------------------------------------------------------------------
| Pengaturan dompdf
|--------------------------------------------------------------------------
|
| Bawaan paket dipakai apa adanya, hanya dua nilai yang disesuaikan dan
| itu pun hanya bila DOMPDF_PUBLIC_PATH diisi pada .env:
|
|   public_path   dompdf mencari folder publik lewat base_path('public').
|                 Pada susunan hosting yang memisahkan inti aplikasi dari
|                 document root, folder itu tidak ada, sehingga realpath()
|                 mengembalikan false dan dompdf melempar
|                 "Cannot resolve public path".
|
|   chroot        Membatasi berkas lokal yang boleh dibaca dompdf. Nilai
|                 bawaannya base_path(), yang tidak mencakup document root,
|                 sehingga logo dan gambar tanda tangan pada laporan PDF
|                 gagal dimuat tanpa pesan apa pun.
|
| Sengaja disetel ke folder publik saja, bukan ke direktori induknya, agar
| dompdf tidak dapat menjangkau berkas .env yang berada di luar sana.
|
| Bila DOMPDF_PUBLIC_PATH tidak diisi - seperti di lingkungan pengembangan -
| seluruh pengaturan bawaan paket berlaku tanpa perubahan.
|
*/

$berkasBawaan = base_path('vendor/barryvdh/laravel-dompdf/config/dompdf.php');

$pengaturan = file_exists($berkasBawaan) ? require $berkasBawaan : [];

$folderPublik = env('DOMPDF_PUBLIC_PATH');

if ($folderPublik) {
    $pengaturan['public_path'] = $folderPublik;
    $pengaturan['options']['chroot'] = $folderPublik;
}

return $pengaturan;
