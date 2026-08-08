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
     * Menyimpan nomor urut terakhir per jenis dokumen per tahun. Sebelumnya
     * nomor dihitung dengan membaca seluruh dokumen lalu mencari nilai
     * terbesar, sehingga dua pengguna yang menyimpan bersamaan bisa mendapat
     * nomor yang sama. Dengan tabel ini nomor diambil lewat penguncian baris.
     */
    public function up(): void
    {
        Schema::create('document_counters', function (Blueprint $table) {
            $table->id();
            $table->string('jenis');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_akhir')->default(0);
            $table->timestamps();

            $table->unique(['jenis', 'tahun']);
        });

        $this->backfill();
    }

    /**
     * Isi counter dari nomor yang sudah terlanjur dipakai, agar nomor lama
     * tidak diterbitkan ulang.
     */
    private function backfill(): void
    {
        $counters = [];

        $catat = function (string $jenis, int $tahun, int $nomor) use (&$counters) {
            $kunci = $jenis . '|' . $tahun;
            if (!isset($counters[$kunci]) || $counters[$kunci] < $nomor) {
                $counters[$kunci] = $nomor;
            }
        };

        // Surat keluar: nomor urut berada di depan, dihitung per kategori per tahun.
        // Contoh: 001/FI/Undangan PT-CONTOH/VIII/2026
        foreach (DB::table('surat_keluars')->get(['nomor_surat', 'kategori_surat', 'created_at']) as $row) {
            if (preg_match('/^(\d+)\//', (string) $row->nomor_surat, $m)) {
                $catat(
                    'surat_keluar:' . $row->kategori_surat,
                    (int) date('Y', strtotime((string) $row->created_at)),
                    (int) $m[1]
                );
            }
        }

        // Surat tugas: ST-001/08/2026
        foreach (DB::table('surat_tugas')->get(['nomor_surat_tugas']) as $row) {
            if (preg_match('#^ST-(\d+)/\d+/(\d{4})$#', (string) $row->nomor_surat_tugas, $m)) {
                $catat('surat_tugas', (int) $m[2], (int) $m[1]);
            }
        }

        // SKPD: SKPD-001/08/2026
        foreach (DB::table('skpds')->get(['nomor_skpd']) as $row) {
            if (preg_match('#^SKPD-(\d+)/\d+/(\d{4})$#', (string) $row->nomor_skpd, $m)) {
                $catat('skpd', (int) $m[2], (int) $m[1]);
            }
        }

        foreach ($counters as $kunci => $nomor) {
            [$jenis, $tahun] = explode('|', $kunci);

            DB::table('document_counters')->insert([
                'jenis'       => $jenis,
                'tahun'       => (int) $tahun,
                'nomor_akhir' => $nomor,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_counters');
    }
};
