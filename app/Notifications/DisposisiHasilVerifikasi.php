<?php

namespace App\Notifications;

use App\Models\Disposisi;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Dikirim kepada penerima disposisi setelah pemberinya memutuskan: hasil
 * kerjanya diterima, atau dikembalikan untuk diperbaiki.
 */
class DisposisiHasilVerifikasi extends Notification
{
    /**
     * @param  string  $keputusan  'diterima' atau 'dikembalikan'
     */
    public function __construct(
        public Disposisi $disposisi,
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
        $perihal = $this->disposisi->suratMasuk?->perihal ?: 'surat masuk';
        $oleh = $this->olehNama ? ' oleh ' . $this->olehNama : '';

        [$judul, $pesan] = match ($this->keputusan) {
            'diterima' => [
                'Tindak lanjut Anda disetujui',
                'Tindak lanjut atas "' . $perihal . '" telah diverifikasi' . $oleh
                    . ' dan dinyatakan selesai.',
            ],
            'dikembalikan' => [
                'Tindak lanjut dikembalikan untuk diperbaiki',
                'Tindak lanjut atas "' . $perihal . '" dikembalikan' . $oleh
                    . '. Catatan: ' . ($this->disposisi->catatan_verifikasi ?: '-'),
            ],
            default => throw new InvalidArgumentException(
                'Keputusan verifikasi tidak dikenal: ' . $this->keputusan
            ),
        };

        return [
            'tipe'         => 'disposisi_' . $this->keputusan,
            'disposisi_id' => $this->disposisi->id,
            'judul'        => $judul,
            'pesan'        => $pesan,
            'url'          => route('disposisi.edit', $this->disposisi->id),
        ];
    }
}
