<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Surat keluar kini diparaf direktur terkait sebelum naik ke Direktur
     * Utama. Kolom ini menentukan direktorat mana yang berwenang memaraf,
     * karena kategori surat (SK/SU/SP) tidak menyiratkan bidangnya.
     */
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('unit_paraf')->nullable()->after('kategori_surat');
            $table->index('unit_paraf');
        });

        // Surat yang sudah disetujui atau sedang menunggu Dirut dibiarkan apa
        // adanya: alur lamanya memang tanpa paraf, dan menyisipkan tahap baru
        // pada dokumen yang sudah berjalan justru memundurkannya.
        DB::table('surat_keluars')->whereNull('unit_paraf')->update([
            'unit_paraf' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropIndex(['unit_paraf']);
            $table->dropColumn('unit_paraf');
        });
    }
};
