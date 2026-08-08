<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Notifications\Notification;

class DisposisiTerlambat extends Notification
{
    /**
     * @param  bool  $sebagaiEskalasi  true bila notifikasi ditujukan kepada
     *                                 pemberi disposisi, bukan pelaksananya.
     */
    public function __construct(
        public Disposisi $disposisi,
        public bool $sebagaiEskalasi = false
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->disposisi->loadMissing(['suratMasuk', 'kepadaUser']);

        $nomor = $this->disposisi->suratMasuk->nomor_surat ?? '-';
        $tenggat = optional($this->disposisi->batas_waktu)->translatedFormat('d F Y');

        return [
            'tipe'         => $this->sebagaiEskalasi ? 'disposisi_eskalasi' : 'disposisi_terlambat',
            'disposisi_id' => $this->disposisi->id,
            'judul'        => $this->sebagaiEskalasi
                ? 'Disposisi Anda belum ditindaklanjuti'
                : 'Disposisi melewati batas waktu',
            'pesan'        => $this->sebagaiEskalasi
                ? 'Disposisi surat ' . $nomor . ' kepada '
                    . ($this->disposisi->kepadaUser->name ?? '-')
                    . ' melewati tenggat ' . $tenggat . ' dan belum selesai.'
                : 'Disposisi surat ' . $nomor . ' melewati tenggat ' . $tenggat
                    . ' dan belum diselesaikan.',
            'batas_waktu'  => optional($this->disposisi->batas_waktu)->toDateString(),
            'url'          => $this->sebagaiEskalasi
                ? route('disposisi.monitoring')
                : route('disposisi.edit', $this->disposisi->id),
        ];
    }
}
