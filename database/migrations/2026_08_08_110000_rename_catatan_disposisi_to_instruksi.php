<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrasi pembuatan tabel menamai kolom ini 'catatan_disposisi', sedangkan
     * seluruh model dan controller memakai 'instruksi'. Pada database yang
     * sedang berjalan kolomnya sudah terlanjur di-rename secara manual, jadi
     * migrasi ini hanya berjalan bila penggantian nama memang belum terjadi -
     * tujuannya agar instalasi baru menghasilkan skema yang sama.
     */
    public function up(): void
    {
        if (Schema::hasColumn('disposisis', 'catatan_disposisi')
            && !Schema::hasColumn('disposisis', 'instruksi')) {
            Schema::table('disposisis', function (Blueprint $table) {
                $table->renameColumn('catatan_disposisi', 'instruksi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('disposisis', 'instruksi')
            && !Schema::hasColumn('disposisis', 'catatan_disposisi')) {
            Schema::table('disposisis', function (Blueprint $table) {
                $table->renameColumn('instruksi', 'catatan_disposisi');
            });
        }
    }
};
