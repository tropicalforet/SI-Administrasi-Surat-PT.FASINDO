<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tahap verifikasi direktur bidang dilepas dari alur surat keluar: konsep
     * dari penyusun langsung menuju sekretaris untuk dinomori, lalu ke Direktur
     * Utama.
     *
     * Kolom approved_direktur_by dan unit_verifikasi sengaja dipertahankan
     * karena menyimpan riwayat surat yang sudah terlanjur melewati tahap itu.
     */
    public function up(): void
    {
        // Surat yang sedang menggantung di tahap direktur dipindahkan agar
        // tidak terjebak pada status yang tidak lagi punya penindak.
        DB::table('surat_keluars')
            ->where('status', 'menunggu_direktur')
            ->update(['status' => 'menunggu_sekretaris']);
    }

    /**
     * Reverse the migrations.
     *
     * Tidak dapat dibalik dengan tepat: setelah dipindahkan, surat ini tidak
     * dapat dibedakan lagi dari yang memang sudah diverifikasi direktur.
     */
    public function down(): void
    {
        //
    }
};
