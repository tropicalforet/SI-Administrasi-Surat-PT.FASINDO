<?php

namespace App\Notifications;

use App\Models\SuratKeluar;
use Illuminate\Notifications\Notification;

class SuratKeluarMenungguTindakan extends Notification
{
    /**
     * @param  string  $tindakan  'verifikasi' bagi direktur terkait, atau
     *                            'persetujuan' bagi Direktur Utama.
     */
    public function __construct(
        public SuratKeluar $suratKeluar,
        public string $tindakan = 'verifikasi'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $perluVerifikasi = $this->tindakan === 'verifikasi';

        return [
            'tipe'             => $perluVerifikasi ? 'surat_keluar_perlu_verifikasi' : 'surat_keluar_perlu_persetujuan',
            'surat_keluar_id'  => $this->suratKeluar->id,
            'judul'            => $perluVerifikasi
                ? 'Surat keluar menunggu verifikasi Anda'
                : 'Surat keluar menunggu persetujuan Anda',
            'pesan'            => 'Surat ' . $this->suratKeluar->nomor_surat
                . ' kepada ' . $this->suratKeluar->tujuan
                . ($perluVerifikasi
                    ? ' menunggu verifikasi sebelum diajukan ke Direktur Utama.'
                    : ' sudah diverifikasi direktur dan menunggu persetujuan Anda.'),
            'url'              => route('surat-keluar.show', $this->suratKeluar->id),
        ];
    }
}
