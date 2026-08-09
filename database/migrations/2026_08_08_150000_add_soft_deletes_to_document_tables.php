<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel dokumen yang penghapusannya harus dapat dipulihkan.
     */
    private array $tabel = [
        'surat_masuks',
        'surat_keluars',
        'skpds',
        'surat_tugas',
        'disposisis',
    ];

    /**
     * Run the migrations.
     *
     * Dokumen persuratan tidak boleh hilang permanen sekali klik. Dengan
     * kolom deleted_at, penghapusan hanya menyembunyikan data sehingga
     * riwayat dan pertanggungjawaban tetap utuh.
     */
    public function up(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tabel as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
