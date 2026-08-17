<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SKPD kembali menjadi satu macam dokumen: perjalanan dinas.
     *
     * Kolom "jenis" lahir saat modul Surat Tugas digabung ke SKPD, untuk
     * membedakan perjalanan dinas dari tugas internal. Pilihan tugas internal
     * kemudian dilepas karena SKPD adalah Surat Keterangan Perjalanan Dinas -
     * dokumen yang selalu menerangkan sebuah perjalanan. Tidak ada satu pun
     * dokumen berjenis internal yang pernah terbit, sehingga kolomnya tidak
     * menyimpan riwayat apa pun dan aman dilepas.
     */
    public function up(): void
    {
        // Pengaman bila ada baris internal yang lolos di lingkungan lain:
        // tandai di keperluannya supaya keterangannya tidak hilang diam-diam.
        // Dirangkai di PHP karena CONCAT tidak ada di SQLite yang dipakai tes.
        foreach (DB::table('skpds')->where('jenis', 'internal')->get(['id', 'keperluan']) as $baris) {
            DB::table('skpds')->where('id', $baris->id)->update([
                'keperluan' => '[Tugas internal] ' . $baris->keperluan,
            ]);
        }

        Schema::table('skpds', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropColumn('jenis');
        });

        // Setiap SKPD kini pasti punya tujuan perjalanan.
        DB::table('skpds')
            ->whereNull('tujuan_dinas')
            ->update(['tujuan_dinas' => '-']);

        Schema::table('skpds', function (Blueprint $table) {
            $table->string('tujuan_dinas')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->string('tujuan_dinas')->nullable()->change();
        });

        Schema::table('skpds', function (Blueprint $table) {
            $table->string('jenis')->default('perjalanan_dinas')->after('nomor_skpd');
            $table->index('jenis');
        });
    }
};
