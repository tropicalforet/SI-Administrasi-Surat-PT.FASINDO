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
     * Kolom status sebelumnya enum('baru','diproses','selesai') sementara
     * laporan menyaring memakai 'belum_diproses' dan 'disposisi' - kosakata
     * yang bahkan tidak dapat disimpan. Kolom diseragamkan menjadi string
     * seperti pada skpds, dengan tiga nilai yang benar-benar dipakai.
     */
    public function up(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->string('status')->default('baru')->change();
        });

        // Nilai lama 'diproses' disetarakan dengan kosakata baru.
        DB::table('surat_masuks')
            ->where('status', 'diproses')
            ->update(['status' => 'didisposisikan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('surat_masuks')
            ->where('status', 'didisposisikan')
            ->update(['status' => 'diproses']);

        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->enum('status', ['baru', 'diproses', 'selesai'])->default('baru')->change();
        });
    }
};
