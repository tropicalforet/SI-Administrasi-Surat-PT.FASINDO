<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skpds', function (Blueprint $table) {

            $table->id();

            $table->string('nomor_skpd')->unique();

            $table->string('nama_pegawai');

            $table->string('nip');

            $table->string('tujuan_dinas');

            $table->text('keperluan');

            $table->date('tanggal_berangkat');

            $table->date('tanggal_kembali');

            $table->integer('durasi_hari')->default(0);

            $table->decimal('biaya_transport', 15, 2)->default(0);

            $table->decimal('biaya_penginapan', 15, 2)->default(0);

            $table->decimal('biaya_konsumsi_per_hari', 15, 2)->default(0);

            $table->decimal('total_biaya', 15, 2)->default(0);

            $table->string('file')->nullable();

            $table->enum('status', [
                'draft',
                'disetujui',
                'selesai'
            ])->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skpds');
    }
};
