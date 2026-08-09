<?php

namespace App\Notifications;

use App\Models\SuratKeluar;
use Illuminate\Notifications\Notification;

class SuratKeluarDiputuskan extends Notification
{
    /**
     * @param  string  $keputusan  'diverifikasi', 'disetujui', atau 'ditolak'.
     */
    public function __construct(
        public SuratKeluar $suratKeluar,
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
        $nomor = $this->suratKeluar->nomor_surat;
        $oleh = $this->olehNama ? ' oleh ' . $this->olehNama : '';

        [$judul, $pesan] = match ($this->keputusan) {
            'diverifikasi'   => [
                'Surat keluar sudah diverifikasi',
                'Surat ' . $nomor . ' telah diverifikasi' . $oleh . ' dan diteruskan ke Direktur Utama.',
            ],
            'disetujui' => [
                'Surat keluar disetujui',
                'Surat ' . $nomor . ' telah disetujui dan ditandatangani' . $oleh . '.',
            ],
            default     => [
                'Surat keluar dikembalikan untuk revisi',
                'Surat ' . $nomor . ' dikembalikan' . $oleh . '. Catatan: '
                    . ($this->suratKeluar->catatan_revisi ?: '-'),
            ],
        };

        return [
            'tipe'            => 'surat_keluar_' . $this->keputusan,
            'surat_keluar_id' => $this->suratKeluar->id,
            'judul'           => $judul,
            'pesan'           => $pesan,
            'url'             => route('surat-keluar.show', $this->suratKeluar->id),
        ];
    }
}
