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
     * Perjalanan dinas dapat lahir dari dua arah: penugasan dari atasan, atau
     * usulan pegawai yang melihat kebutuhan di lapangan. Keduanya sah, tetapi
     * harus dibedakan - sebelumnya pegawai membuat Surat Tugas untuk dirinya
     * sendiri dengan kolom ditugaskan_oleh kosong, sehingga dokumen penugasan
     * lahir dari pihak yang ditugaskan.
     */
    public function up(): void
    {
        Schema::table('surat_tugas', function (Blueprint $table) {
            $table->string('asal_usul')->default('penugasan')->after('ditugaskan_oleh');
            $table->foreignId('disetujui_direktur_by')->nullable()->after('asal_usul')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_direktur_at')->nullable()->after('disetujui_direktur_by');
            $table->text('catatan_penolakan')->nullable()->after('disetujui_direktur_at');

            $table->index('asal_usul');
        });

        // Surat tugas lama tanpa penugas berarti dibuat sendiri oleh pegawainya.
        DB::table('surat_tugas')
            ->whereNull('ditugaskan_oleh')
            ->update(['asal_usul' => 'usulan']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surat_tugas', function (Blueprint $table) {
            $table->dropForeign(['disetujui_direktur_by']);
            $table->dropIndex(['asal_usul']);
            $table->dropColumn([
                'asal_usul',
                'disetujui_direktur_by',
                'disetujui_direktur_at',
                'catatan_penolakan',
            ]);
        });
    }
};
