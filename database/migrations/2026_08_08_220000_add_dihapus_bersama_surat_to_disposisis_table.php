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
     * Menandai disposisi yang terhapus karena suratnya dihapus, bukan karena
     * dibatalkan tersendiri. Membedakan keduanya lewat kecocokan deleted_at
     * tidak dapat diandalkan: dua penghapusan pada detik yang sama akan
     * tampak identik, sehingga disposisi yang sengaja dibatalkan ikut hidup
     * kembali saat suratnya dipulihkan.
     */
    public function up(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->boolean('dihapus_bersama_surat')->default(false)->after('deleted_at');
        });

        // Disposisi yang dirapikan migrasi sebelumnya memang terhapus karena
        // suratnya, jadi ditandai agar ikut pulih bila suratnya dipulihkan.
        DB::table('disposisis')
            ->join('surat_masuks', 'disposisis.surat_masuk_id', '=', 'surat_masuks.id')
            ->whereNotNull('disposisis.deleted_at')
            ->whereColumn('disposisis.deleted_at', 'surat_masuks.deleted_at')
            ->update(['disposisis.dihapus_bersama_surat' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn('dihapus_bersama_surat');
        });
    }
};
