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
     * Alur surat keluar disesuaikan: konsep disusun staf atau manager yang
     * membutuhkannya, diperiksa direktur bidangnya, lalu singgah ke sekretaris
     * untuk penomoran dan pemeriksaan format, baru ditandatangani Dirut.
     *
     * Nomor kini terbit di tahap sekretaris, bukan saat draf dibuat, sehingga
     * draf yang dibatalkan tidak lagi meninggalkan lubang pada rangkaian nomor.
     */
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('id')
                  ->constrained('users')->nullOnDelete();
        });

        // Draf belum bernomor, jadi kolomnya tidak boleh lagi wajib diisi.
        // Indeks unik dipertahankan: MySQL mengizinkan banyak NULL pada
        // kolom unik, sehingga jaminan nomor tidak ganda tetap berlaku.
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('nomor_surat')->nullable()->change();
        });

        // Enum diganti string agar penambahan tahap tidak memerlukan
        // perubahan tipe kolom, seperti sudah dilakukan pada surat_masuks.
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });

        // Surat lama disusun sekretaris; dicatat agar riwayatnya tidak kosong.
        $sekretaris = DB::table('users')->where('role', 'sekretaris')->value('id');

        if ($sekretaris) {
            DB::table('surat_keluars')->whereNull('dibuat_oleh')->update([
                'dibuat_oleh' => $sekretaris,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropForeign(['dibuat_oleh']);
            $table->dropColumn('dibuat_oleh');
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->enum('status', ['draft', 'menunggu_direktur', 'menunggu_dirut', 'ditolak', 'terkirim'])
                  ->default('draft')->change();
        });
    }
};
