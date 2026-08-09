<?php

namespace App\Notifications;

use App\Models\Skpd;
use Illuminate\Notifications\Notification;

class SkpdDiputuskan extends Notification
{
    /**
     * @param  string  $keputusan  'disetujui_direktur', 'diterbitkan', atau 'ditolak'.
     */
    public function __construct(
        public Skpd $skpd,
        public string $keputusan,
        public ?string $olehNama = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $nomor = $this->skpd->nomor_skpd;
        $oleh = $this->olehNama ? ' oleh ' . $this->olehNama : '';

        [$judul, $pesan] = match ($this->keputusan) {
            'disetujui_direktur' => [
                'Usulan perjalanan dinas disetujui',
                'Usulan Anda disetujui' . $oleh . ' dan diteruskan ke Direktur Utama untuk diterbitkan.',
            ],
            'disetujui' => [
                'SKPD disetujui',
                'SKPD ' . $nomor . ' telah disetujui' . $oleh . '. Dokumen sudah dapat diunduh.',
            ],
            default => [
                'SKPD ditolak',
                'SKPD ' . $nomor . ' ditolak' . $oleh . '. Catatan: '
                    . ($this->skpd->catatan_penolakan ?: '-'),
            ],
        };

        return [
            'tipe'           => 'skpd_' . $this->keputusan,
            'skpd_id' => $this->skpd->id,
            'judul'          => $judul,
            'pesan'          => $pesan,
            'url'            => route('skpd.show', $this->skpd->id),
        ];
    }
}
