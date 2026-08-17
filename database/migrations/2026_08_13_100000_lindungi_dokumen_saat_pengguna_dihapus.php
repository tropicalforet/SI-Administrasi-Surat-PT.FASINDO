<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menghapus seorang pegawai tidak boleh ikut menghapus dokumen perusahaan.
 *
 * Sebelumnya kunci asing ke tabel users memakai ON DELETE CASCADE, sehingga
 * menghapus satu pengguna ikut melenyapkan seluruh SKPD miliknya - termasuk
 * yang sudah disetujui dan ber-QR - beserta disposisi dan jejak aktivitasnya.
 * Penghapusan itu menembus SoftDeletes, jadi dokumennya tidak singgah di arsip
 * terhapus dan tidak dapat dipulihkan.
 *
 * Aturannya diubah menjadi SET NULL: dokumen tetap tersimpan sebagai arsip,
 * hanya kehilangan penunjuk ke pemiliknya.
 */
return new class extends Migration
{
    /**
     * Kolom penunjuk ke users beserta nama constraint-nya.
     */
    private array $acuan = [
        ['tabel' => 'skpds',         'kolom' => 'user_id',        'constraint' => 'skpds_user_id_foreign'],
        ['tabel' => 'activity_logs', 'kolom' => 'user_id',        'constraint' => 'activity_logs_user_id_foreign'],
        ['tabel' => 'disposisis',    'kolom' => 'dari_user_id',   'constraint' => 'disposisis_dari_user_id_foreign'],
        ['tabel' => 'disposisis',    'kolom' => 'kepada_user_id', 'constraint' => 'disposisis_kepada_user_id_foreign'],
    ];

    public function up(): void
    {
        foreach ($this->acuan as $a) {
            if (!Schema::hasTable($a['tabel']) || !Schema::hasColumn($a['tabel'], $a['kolom'])) {
                continue;
            }

            // SET NULL mensyaratkan kolomnya boleh kosong.
            Schema::table($a['tabel'], function (Blueprint $table) use ($a) {
                $table->unsignedBigInteger($a['kolom'])->nullable()->change();
            });

            $this->pasangUlangKunci($a, 'set null');
        }
    }

    public function down(): void
    {
        foreach ($this->acuan as $a) {
            if (!Schema::hasTable($a['tabel']) || !Schema::hasColumn($a['tabel'], $a['kolom'])) {
                continue;
            }

            $this->pasangUlangKunci($a, 'cascade');
        }
    }

    /**
     * Pasang ulang kunci asing dengan aturan hapus yang baru.
     *
     * SQLite tidak mengenal penghapusan kunci asing lewat ALTER TABLE, jadi di
     * sana pengubahan dilewati saja - penjagaan yang sesungguhnya ada pada
     * event 'deleting' di model User, yang berlaku di semua driver.
     */
    private function pasangUlangKunci(array $a, string $aturan): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        Schema::table($a['tabel'], function (Blueprint $table) use ($a, $aturan) {
            $table->dropForeign($a['constraint']);

            $table->foreign($a['kolom'], $a['constraint'])
                ->references('id')
                ->on('users')
                ->onDelete($aturan);
        });
    }
};
