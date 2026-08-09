<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Melengkapi tahap penyortiran: sifat surat menentukan prioritas
     * penanganan, jalur penerimaan mencatat lewat mana surat itu sampai.
     *
     * jalur_penerimaan dibuat nullable karena surat lama tidak punya
     * catatan ini dan tidak benar bila diisi tebakan.
     */
    public function up(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->string('sifat')->default('biasa')->after('kategori_surat');
            $table->string('jalur_penerimaan')->nullable()->after('pengirim');

            $table->index('sifat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropIndex(['sifat']);
            $table->dropColumn(['sifat', 'jalur_penerimaan']);
        });
    }
};
