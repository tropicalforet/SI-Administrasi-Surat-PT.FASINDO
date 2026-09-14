<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class NomorDokumenHelper
{
    /**
     * Bulan terbit ditulis dalam angka Romawi, mengikuti kelaziman tata
     * naskah dinas.
     */
    private const BULAN_ROMAWI = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
    ];

    /** Singkatan tetap nama instansi. */
    private const KODE_INSTANSI = 'FI';

    /**
     * Ambil nomor urut berikutnya untuk satu jenis dokumen pada satu tahun.
     *
     * Baris counter dikunci lewat lockForUpdate() di dalam transaksi, sehingga
     * dua permintaan yang datang bersamaan akan mengantre dan mustahil
     * memperoleh nomor yang sama.
     *
     * @param  string  $jenis  Contoh: 'skpd', 'surat_keluar:SU'
     */
    public static function next(string $jenis, int $tahun): int
    {
        // Pastikan baris counter tersedia. insertOrIgnore aman dijalankan
        // bersamaan karena bentrokan ditangkap oleh unique(jenis, tahun).
        DB::table('document_counters')->insertOrIgnore([
            'jenis'       => $jenis,
            'tahun'       => $tahun,
            'nomor_akhir' => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return DB::transaction(function () use ($jenis, $tahun) {
            $counter = DB::table('document_counters')
                ->where('jenis', $jenis)
                ->where('tahun', $tahun)
                ->lockForUpdate()
                ->first();

            $nomor = $counter->nomor_akhir + 1;

            DB::table('document_counters')
                ->where('id', $counter->id)
                ->update([
                    'nomor_akhir' => $nomor,
                    'updated_at'  => now(),
                ]);

            return $nomor;
        });
    }

    /**
     * Terbitkan nomor dokumen lengkap.
     *
     * Bentuknya seragam untuk seluruh jenis dokumen:
     *
     *     [nomor urut]/FI/[kode jenis]/[bulan Romawi]/[tahun]
     *     012/FI/SU/VIII/2026
     *
     * Nomor urut dihitung per jenis dan diatur ulang setiap tahun, sehingga
     * penomoran invoice tidak bercampur dengan surat dinas.
     *
     * Isi nomor sengaja hanya berupa data yang tidak pernah berubah. Nama
     * tujuan surat dulu ikut diselipkan di sini, tetapi itu membuat panjang
     * nomor berubah-ubah, terpotong bila tujuannya panjang, dan menyesatkan
     * bila perusahaan tujuan berganti nama.
     *
     * @param  string  $jenis  Kunci penghitung, contoh 'surat_keluar:SU'
     * @param  string  $kode   Kode jenis yang tampil pada nomor, contoh 'SU'
     */
    public static function terbitkan(string $jenis, string $kode): string
    {
        // now() dipakai alih-alih date() agar waktunya dapat dibekukan saat
        // pengujian, sehingga tes tidak ikut berubah tiap ganti bulan.
        $saat = now();
        $tahun = (int) $saat->year;

        $urut = self::next($jenis, $tahun);

        return self::susun($kode, $urut, (int) $saat->month, $tahun);
    }

    /**
     * Susun nomor dari bagian-bagiannya. Dipisahkan dari terbitkan() agar
     * bentuk nomor dapat diuji tanpa menyentuh penghitung di basis data.
     */
    public static function susun(string $kode, int $urut, int $bulan, int $tahun): string
    {
        return str_pad((string) $urut, 3, '0', STR_PAD_LEFT)
            . '/' . self::KODE_INSTANSI
            . '/' . self::kodeAman($kode)
            . '/' . (self::BULAN_ROMAWI[$bulan] ?? $bulan)
            . '/' . $tahun;
    }

    /**
     * Kategori "Lainnya" boleh diketik bebas penyusun surat. Isinya
     * dibersihkan agar spasi maupun garis miring tidak merusak bentuk nomor.
     */
    public static function kodeAman(string $kode): string
    {
        $bersih = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $kode));

        return substr($bersih, 0, 10) ?: 'LAIN';
    }
}
