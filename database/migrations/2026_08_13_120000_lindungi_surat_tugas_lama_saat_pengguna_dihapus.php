<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Surat Tugas sudah dipensiunkan, tetapi arsipnya masih dipakai.
 *
 * Tabelnya menyimpan dokumen lama yang masih ditunjuk oleh sejumlah SKPD lewat
 * kolom surat_tugas_id. Kunci asingnya karena itu ikut dilindungi seperti
 * dokumen lain: menghapus seorang pegawai tidak boleh melenyapkan surat tugas
 * yang pernah terbit atas namanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ubahAturan('set null');
    }

    public function down(): void
    {
        $this->ubahAturan('cascade');
    }

    private function ubahAturan(string $aturan): void
    {
        if (!Schema::hasTable('surat_tugas') || !Schema::hasColumn('surat_tugas', 'user_id')) {
            return;
        }

        Schema::table('surat_tugas', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('surat_tugas', function (Blueprint $table) use ($aturan) {
            $table->dropForeign('surat_tugas_user_id_foreign');

            $table->foreign('user_id', 'surat_tugas_user_id_foreign')
                ->references('id')
                ->on('users')
                ->onDelete($aturan);
        });
    }
};
