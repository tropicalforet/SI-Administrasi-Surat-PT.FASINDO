<?php

namespace App\Notifications;

use App\Models\Skpd;
use Illuminate\Notifications\Notification;

class SkpdMenungguTindakan extends Notification
{
    /**
     * @param  string  $tindakan  'persetujuan_direktur' bagi direktur unit,
     *                            atau 'persetujuan_dirut' bagi Direktur Utama.
     */
    public function __construct(
        public Skpd $skpd,
        public string $tindakan = 'persetujuan_direktur'
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $keDirektur = $this->tindakan === 'persetujuan_direktur';
        $pegawai = $this->skpd->user?->name ?? '-';

        return [
            'tipe'            => $keDirektur ? 'skpd_perlu_persetujuan' : 'skpd_perlu_persetujuan_dirut',
            'skpd_id'  => $this->skpd->id,
            'judul'           => $keDirektur
                ? 'Usulan perjalanan dinas menunggu persetujuan'
                : 'SKPD menunggu persetujuan Anda',
            'pesan'           => $keDirektur
                ? $pegawai . ' mengusulkan penugasan ke ' . $this->skpd->tujuan
                    . '. Setujui bila memang diperlukan.'
                : 'SKPD ' . $this->skpd->nomor_skpd . ' untuk ' . $pegawai
                    . ' menunggu persetujuan Anda.',
            'url'             => route('skpd.show', $this->skpd->id),
        ];
    }
}
