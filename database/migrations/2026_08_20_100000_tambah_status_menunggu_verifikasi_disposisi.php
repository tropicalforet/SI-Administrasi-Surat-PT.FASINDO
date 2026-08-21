<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penutupan disposisi kini melewati verifikasi pemberi disposisi, sehingga
 * dibutuhkan satu status antara: 'menunggu_verifikasi'.
 *
 * Kolomnya diubah dari enum menjadi string, mengikuti cara yang sama yang
 * dipakai tabel skpds. Alasannya, daftar status yang dikunci di tingkat basis
 * data harus diubah lewat migrasi setiap kali alur bertambah, padahal daftar
 * itu sudah dinyatakan pada model. Satu sumber kebenaran lebih baik daripada
 * dua yang bisa berselisih.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->string('status', 32)->default('menunggu')->change();
        });

        Schema::table('disposisis', function (Blueprint $table) {
            if (!Schema::hasColumn('disposisis', 'catatan_verifikasi')) {
                // Alasan pemberi disposisi mengembalikan pekerjaan, supaya
                // penerimanya tahu apa yang perlu diperbaiki.
                $table->text('catatan_verifikasi')->nullable()->after('file_tindak_lanjut');
            }

            if (!Schema::hasColumn('disposisis', 'diverifikasi_pada')) {
                $table->timestamp('diverifikasi_pada')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Disposisi yang sedang menunggu verifikasi dikembalikan menjadi
        // 'diproses' lebih dahulu, sebab nilai itu tidak ada pada enum lama
        // dan akan ditolak saat kolomnya dipersempit kembali.
        DB::table('disposisis')
            ->where('status', 'menunggu_verifikasi')
            ->update(['status' => 'diproses']);

        Schema::table('disposisis', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'diproses', 'selesai'])
                  ->default('menunggu')
                  ->change();
        });

        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn(['catatan_verifikasi', 'diverifikasi_pada']);
        });
    }
};
