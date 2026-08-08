<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class NomorDokumenHelper
{
    /**
     * Ambil nomor urut berikutnya untuk satu jenis dokumen pada satu tahun.
     *
     * Baris counter dikunci lewat lockForUpdate() di dalam transaksi, sehingga
     * dua permintaan yang datang bersamaan akan mengantre dan mustahil
     * memperoleh nomor yang sama.
     *
     * @param  string  $jenis  Contoh: 'skpd', 'surat_tugas', 'surat_keluar:Undangan'
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
}
