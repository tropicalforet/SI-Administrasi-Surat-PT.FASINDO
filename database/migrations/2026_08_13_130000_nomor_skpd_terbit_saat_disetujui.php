<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor SKPD kini terbit saat Direktur Utama menyetujui, bukan saat draft
 * disimpan, sehingga pengajuan yang dibatalkan tidak lagi memakan nomor.
 *
 * Kolomnya karena itu harus boleh kosong. Indeks unik tetap dipertahankan;
 * MySQL mengizinkan banyak baris bernilai NULL pada kolom unik, sehingga
 * beberapa pengajuan dapat sama-sama menunggu tanpa saling bentrok.
 *
 * Nomor yang sudah terbit sebelumnya tidak diubah. Dokumen yang telanjur
 * beredar dengan format lama tetap sah dan harus tetap dapat ditelusuri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->string('nomor_skpd')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->string('nomor_skpd')->nullable(false)->change();
        });
    }
};
