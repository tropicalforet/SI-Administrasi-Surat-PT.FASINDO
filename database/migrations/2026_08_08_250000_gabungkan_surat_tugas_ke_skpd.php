<?php

use App\Helpers\NomorDokumenHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Surat Tugas dan SKPD sebelumnya dua dokumen dengan isi hampir identik,
     * masing-masing disetujui Direktur Utama - satu keputusan dinilai dua kali.
     * Keduanya digabung menjadi SKPD saja, yang kini mencakup perjalanan dinas
     * maupun penugasan internal.
     *
     * SKPD yang dipertahankan karena hanya ia yang punya dokumen cetak dan
     * verifikasi QR; mesin alur dari Surat Tugas dipindahkan ke sini.
     */
    public function up(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->string('jenis')->default('perjalanan_dinas')->after('nomor_skpd');
            $table->string('asal_usul')->default('penugasan')->after('jenis');
            $table->foreignId('ditugaskan_oleh')->nullable()->after('user_id')
                  ->constrained('users')->nullOnDelete();
            $table->foreignId('disetujui_direktur_by')->nullable()->after('ditugaskan_oleh')
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_direktur_at')->nullable()->after('disetujui_direktur_by');
            $table->foreignId('surat_masuk_id')->nullable()->after('disetujui_direktur_at')
                  ->constrained('surat_masuks')->nullOnDelete();

            $table->index('asal_usul');
            $table->index('jenis');
        });

        // Tujuan hanya wajib bagi penugasan yang benar-benar bepergian.
        Schema::table('skpds', function (Blueprint $table) {
            $table->string('tujuan_dinas')->nullable()->change();
        });

        $this->seragamkanStatus();
        $this->pindahkanDataSuratTugas();
    }

    /**
     * Samakan kosakata status dengan alur baru.
     */
    private function seragamkanStatus(): void
    {
        $peta = [
            'pengajuan'         => 'draft',
            'diperiksa'         => 'menunggu_dirut',
            'menunggu_keuangan' => 'menunggu_dirut',
        ];

        foreach ($peta as $lama => $baru) {
            DB::table('skpds')->where('status', $lama)->update(['status' => $baru]);
        }
    }

    private function pindahkanDataSuratTugas(): void
    {
        if (!Schema::hasTable('surat_tugas')) {
            return;
        }

        $statusSkpd = [
            'draft'             => 'draft',
            'menunggu_direktur' => 'menunggu_direktur',
            'menunggu_dirut'    => 'menunggu_dirut',
            'diterbitkan'       => 'disetujui',
            'ditolak'           => 'ditolak',
        ];

        foreach (DB::table('surat_tugas')->whereNull('deleted_at')->get() as $st) {
            $skpd = DB::table('skpds')->where('surat_tugas_id', $st->id)->first();

            $dataAlur = [
                'asal_usul'             => $st->asal_usul ?? 'penugasan',
                'ditugaskan_oleh'       => $st->ditugaskan_oleh,
                'disetujui_direktur_by' => $st->disetujui_direktur_by,
                'disetujui_direktur_at' => $st->disetujui_direktur_at,
                'surat_masuk_id'        => $st->surat_masuk_id,
            ];

            if ($skpd) {
                // SKPD-nya sudah ada: cukup lengkapi dengan data alur dari ST.
                DB::table('skpds')->where('id', $skpd->id)->update($dataAlur);
                continue;
            }

            // Belum punya SKPD: seluruh isi Surat Tugas dipindahkan agar
            // riwayatnya tidak hilang saat modulnya dipensiunkan.
            $mulai = Carbon::parse($st->tanggal_mulai);
            $selesai = Carbon::parse($st->tanggal_selesai);
            $tahun = (int) Carbon::parse($st->created_at)->format('Y');

            $nomor = 'SKPD-' . str_pad(NomorDokumenHelper::next('skpd', $tahun), 3, '0', STR_PAD_LEFT)
                . '/' . Carbon::parse($st->created_at)->format('m') . '/' . $tahun;

            DB::table('skpds')->insert(array_merge($dataAlur, [
                'user_id'           => $st->user_id,
                'surat_tugas_id'    => $st->id,
                'nomor_skpd'        => $nomor,
                'jenis'             => 'perjalanan_dinas',
                'nama_pegawai'      => DB::table('users')->where('id', $st->user_id)->value('name'),
                'tujuan_dinas'      => $st->tujuan,
                'keperluan'         => $st->perihal_tugas,
                'tanggal_berangkat' => $st->tanggal_mulai,
                'tanggal_kembali'   => $st->tanggal_selesai,
                'durasi_hari'       => $mulai->diffInDays($selesai) + 1,
                'file'              => $st->file,
                'catatan_revisi'    => $st->catatan_penolakan,
                'status'            => $statusSkpd[$st->status] ?? 'draft',
                'created_at'        => $st->created_at,
                'updated_at'        => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     *
     * Kolom dan perubahan skema dapat dikembalikan, tetapi SKPD hasil konversi
     * dari Surat Tugas sengaja tidak dihapus - menghapusnya berarti membuang
     * dokumen yang sejak penggabungan sudah menjadi satu-satunya riwayat.
     */
    public function down(): void
    {
        Schema::table('skpds', function (Blueprint $table) {
            $table->dropForeign(['ditugaskan_oleh']);
            $table->dropForeign(['disetujui_direktur_by']);
            $table->dropForeign(['surat_masuk_id']);
            $table->dropIndex(['asal_usul']);
            $table->dropIndex(['jenis']);
            $table->dropColumn([
                'jenis',
                'asal_usul',
                'ditugaskan_oleh',
                'disetujui_direktur_by',
                'disetujui_direktur_at',
                'surat_masuk_id',
            ]);
        });
    }
};
