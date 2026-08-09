<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * surat_tugas dan skpds sudah menjamin nomornya unik di tingkat database,
     * sedangkan surat_keluars belum. Indeks ini menjadi jaring pengaman
     * terakhir bila ada jalur lain yang menerbitkan nomor.
     */
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->unique('nomor_surat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropUnique(['nomor_surat']);
        });
    }
};
