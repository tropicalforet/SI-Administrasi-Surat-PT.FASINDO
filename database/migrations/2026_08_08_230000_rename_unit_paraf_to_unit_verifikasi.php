<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Istilah "paraf" diganti "verifikasi" agar tidak rancu dengan tanda
     * tangan: dokumen tetap ditandatangani Direktur Utama seorang, sedangkan
     * direktur bidangnya hanya memeriksa isi surat sebelum diteruskan.
     */
    public function up(): void
    {
        if (Schema::hasColumn('surat_keluars', 'unit_paraf')
            && !Schema::hasColumn('surat_keluars', 'unit_verifikasi')) {
            Schema::table('surat_keluars', function (Blueprint $table) {
                $table->renameColumn('unit_paraf', 'unit_verifikasi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('surat_keluars', 'unit_verifikasi')
            && !Schema::hasColumn('surat_keluars', 'unit_paraf')) {
            Schema::table('surat_keluars', function (Blueprint $table) {
                $table->renameColumn('unit_verifikasi', 'unit_paraf');
            });
        }
    }
};
