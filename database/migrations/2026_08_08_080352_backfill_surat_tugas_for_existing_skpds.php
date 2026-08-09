<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ambil semua SKPD yang belum punya surat_tugas_id
        $skpds = DB::table('skpds')->whereNull('surat_tugas_id')->get();

        foreach ($skpds as $skpd) {
            // 2. Buat record Surat Tugas baru
            $suratTugasId = DB::table('surat_tugas')->insertGetId([
                'nomor_surat_tugas' => 'ST-MIGRASI-' . $skpd->id . '-' . time(),
                'user_id' => $skpd->user_id,
                'ditugaskan_oleh' => null, // Bottom-up (self-assigned untuk yang lama)
                'perihal_tugas' => 'Migrasi data lama — ' . $skpd->tujuan_dinas,
                'tujuan' => $skpd->tujuan_dinas,
                'tanggal_mulai' => $skpd->tanggal_berangkat,
                'tanggal_selesai' => $skpd->tanggal_kembali,
                'status' => 'diterbitkan', // Langsung diterbitkan karena SKPD sudah ada
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Update SKPD dengan ID Surat Tugas yang baru dibuat
            DB::table('skpds')
                ->where('id', $skpd->id)
                ->update(['surat_tugas_id' => $suratTugasId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Untuk rollback, set surat_tugas_id di skpds jadi null (opsional)
        // dan hapus semua surat_tugas yang nomor_surat_tugas-nya berawalan ST-MIGRASI-
        DB::table('skpds')->whereNotNull('surat_tugas_id')->update(['surat_tugas_id' => null]);
        DB::table('surat_tugas')->where('nomor_surat_tugas', 'like', 'ST-MIGRASI-%')->delete();
    }
};
